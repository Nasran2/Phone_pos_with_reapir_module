<?php

namespace App\Http\Controllers\Repairs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Repairs\RepairCustomerRequest;
use App\Http\Requests\Repairs\RepairIntakeRequest;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Product;
use App\Models\RepairChargeTemplate;
use App\Models\RepairJob;
use App\Models\RepairProblem;
use App\Models\RepairStatus;
use App\Models\RepairTechnician;
use App\Models\Store;
use App\Services\MixedPaymentService;
use App\Services\Repairs\RepairInventoryService;
use App\Services\Repairs\RepairJobService;
use App\Support\RepairSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepairJobController extends Controller
{
    public function index(Request $request): View
    {
        $jobs = $this->query($request)->with(['customer', 'device', 'technician', 'repairStatus'])->latest()->paginate(20)->withQueryString();

        return view('repairs.index', array_merge($this->options(), compact('jobs')));
    }

    /** @return Builder<RepairJob> */
    public function query(Request $request): Builder
    {
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from'], 'search' => ['nullable', 'string', 'max:100'], 'customer_id' => ['nullable', 'exists:customers,id'], 'store_id' => ['nullable', 'exists:stores,id'], 'technician_id' => ['nullable', 'exists:repair_technicians,id'], 'status_id' => ['nullable', 'exists:repair_statuses,id']]);
        $q = RepairJob::query();
        if ($request->filled('search')) {
            $q->search($request->string('search')->trim()->toString());
        }
        foreach (['store_id', 'customer_id', 'primary_technician_id' => 'technician_id', 'repair_status_id' => 'status_id'] as $column => $key) {
            if (is_int($column)) {
                $column = $key;
            }
            if ($request->filled($key)) {
                $q->where($column, $request->input($key));
            }
        }
        foreach (['urgent', 'is_warranty'] as $field) {
            if ($request->filled($field)) {
                $q->where($field, $request->boolean($field));
            }
        }
        if ($request->filled('from')) {
            $q->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('created_at', '<=', $request->input('to'));
        }
        if ($request->input('payment') === 'paid') {
            $q->where('balance_amount', '<=', 0);
        }
        if ($request->input('payment') === 'due') {
            $q->where('balance_amount', '>', 0);
        }
        if ($request->input('payment') === 'hold') {
            $q->where('cheque_hold_amount', '>', 0);
        }
        if ($request->boolean('overdue')) {
            $q->whereDate('expected_completion_date', '<', today())->whereNull('completed_at')->whereNull('delivered_at')->whereHas('repairStatus', fn ($s) => $s->where('is_cancelled', false));
        }

        return $q;
    }

    public function create(): View
    {
        return view('repairs.intake', array_merge($this->options(), ['job' => null]));
    }

    public function edit(RepairJob $repairJob): View
    {
        app(RepairInventoryService::class)->editable($repairJob);

        return view('repairs.intake', array_merge($this->options(), ['job' => $repairJob->load(['device', 'checklist'])]));
    }

    public function store(RepairIntakeRequest $request, RepairJobService $service): RedirectResponse
    {
        $job = $service->save($request->validated());

        return redirect()->route(RepairSettings::bool('auto_print') ? 'repairs.job-card' : 'repairs.show', $job)->with('success', 'Device checked in.');
    }

    public function update(RepairIntakeRequest $request, RepairJob $repairJob, RepairJobService $service): RedirectResponse
    {
        $service->save($request->validated(), $repairJob);

        return redirect()->route('repairs.show', $repairJob)->with('success', 'Intake updated.');
    }

    public function show(RepairJob $repairJob): View
    {
        $job = $repairJob->load(['customer', 'store', 'repairStatus', 'device', 'checklist', 'warranty', 'technician', 'parts.product', 'parts.variant', 'charges', 'payments.cheque', 'notes.user', 'images', 'assignments.technician', 'assignments.user', 'statusLogs.user', 'activities.user', 'notifications']);

        return view('repairs.show', array_merge($this->options(), compact('job'), ['accounts' => Account::available()->where('account_type', 'Asset')->where('status', true)->get(), 'paymentMethods' => collect(MixedPaymentService::paymentMethods())]));
    }

    public function customers(Request $request): JsonResponse
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $term = '%'.$request->input('q').'%';

        return response()->json(Customer::where('status', true)->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('phone', 'like', $term)->orWhere('id', $request->input('q')))->limit(20)->get(['id', 'name', 'phone']));
    }

    public function quickCustomer(RepairCustomerRequest $request): JsonResponse
    {
        $data = $request->validated();
        $phone = $data['phone'];

        return DB::transaction(function () use ($data, $phone) {
            // Lock the contact namespace so simultaneous intake submissions cannot create duplicates.
            DB::table('stores')->orderBy('id')->lockForUpdate()->first();
            $existing = Customer::whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') = ?", [$phone])->first();
            if (! $existing) {
                $existing = Customer::create(array_merge($data, ['phone' => $phone, 'status' => true, 'balance' => 0, 'opening_balance' => 0, 'created_by' => auth()->id()]));
            }

            return response()->json($existing->only(['id', 'name', 'phone']));
        }, 3);
    }

    public function products(Request $request): JsonResponse
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100'], 'store_id' => ['required', 'exists:stores,id']]);
        $term = '%'.$request->input('q').'%';
        $products = Product::where(fn ($q) => $q->where('name', 'like', $term)->orWhere('sku', 'like', $term)->orWhere('barcode', 'like', $term)->orWhereHas('variants', fn ($v) => $v->where('sku', 'like', $term)))->with(['variants' => fn ($q) => $q->where('is_active', true)])->limit(20)->get();
        $stocks = DB::table('product_store_stock')->where('store_id', $request->input('store_id'))->whereIn('product_id', $products->pluck('id'))->pluck('quantity', 'product_id');

        return response()->json($products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'cost_price' => $p->cost_price, 'retail_price' => $p->retail_price, 'stock' => $stocks[$p->id] ?? 0, 'variants' => $p->variants->map(fn ($v) => ['id' => $v->id, 'name' => trim($v->color.' '.$v->storage.' '.$v->ram), 'stock' => $v->stock_quantity, 'retail_price' => $v->retail_price, 'cost_price' => $v->cost_price])->all()]));
    }

    /** @return array{statuses: Collection<int, RepairStatus>, technicians: Collection<int, RepairTechnician>, problems: Collection<int, RepairProblem>, templates: Collection<int, RepairChargeTemplate>, stores: Collection<int, Store>} */
    public function options(): array
    {
        return ['statuses' => RepairStatus::orderBy('sort_order')->get(), 'technicians' => RepairTechnician::orderBy('name')->get(), 'problems' => RepairProblem::where('is_active', true)->orderBy('sort_order')->get(), 'templates' => RepairChargeTemplate::where('is_active', true)->orderBy('sort_order')->get(), 'stores' => Store::orderBy('name')->get()];
    }
}
