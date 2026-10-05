<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\HoldSale;
use App\Models\Sale;
use App\Models\User;
use App\Services\LorryAccessService;
use App\Support\Features;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class HoldSaleController extends Controller
{
    public function index(Request $request, LorryAccessService $access)
    {
        $query = HoldSale::query();
        $query->when(! Features::wholesaleEnabled(), fn ($q) => $q->where('pos_type', 'retail'));
        $user = $request->user();
        $isLorryUser = $access->isLorryUser($user);
        $allocatedQuery = null;

        if ($user->hasPermission('sales.invoice_view') || $isLorryUser) {
            $allocatedQuery = Sale::whereNull('stock_deducted_at')->whereIn('status', ['final', 'completed']);
            $allocatedQuery->when(! Features::wholesaleEnabled(), fn ($q) => $q->where('pos_type', 'retail'));
            if ($isLorryUser && ! $access->isUnrestricted($user)) {
                $allocatedQuery->where('source_type', 'lorry')->whereIn('lorry_id', $access->allowedIds($user));
            }
        }

        $cashiers = User::where(function ($q) use ($allocatedQuery) {
            $q->whereIn('id', HoldSale::whereNotNull('created_by')->select('created_by'));
            if ($allocatedQuery) {
                $q->orWhereIn('id', (clone $allocatedQuery)->whereNotNull('created_by')->select('created_by'));
            }
        })->orderBy('name')->get();

        $this->applyFilters($query, $request, 'reference_no');
        $holds = $query->with(['customer', 'cashier'])->withCount('items')->latest('created_at')->paginate(12)->withQueryString();
        $allocatedSales = null;
        if ($allocatedQuery) {
            $this->applyFilters($allocatedQuery, $request, 'invoice_no');
            $allocatedSales = $allocatedQuery->with(['customer', 'cashier'])->withCount('items')
                ->latest('created_at')->latest('id')->paginate(12, ['*'], 'allocated_page')->withQueryString();
        }

        return view('sales.holds.index', compact('holds', 'cashiers', 'allocatedSales'));
    }

    private function applyFilters(Builder $query, Request $request, string $billColumn): void
    {
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search, $billColumn) {
                $q->where($billColumn, 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('cashier', fn ($cashierQuery) => $cashierQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('sale_type')) {
            $query->where('pos_type', $request->input('sale_type'));
        }

        if ($request->filled('cashier_id')) {
            $query->where('created_by', $request->input('cashier_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }
    }

    public function show(HoldSale $holdSale)
    {
        abort_if(! Features::wholesaleEnabled() && $holdSale->pos_type === 'wholesale', 404);
        $holdSale->load(['customer', 'cashier', 'items.product', 'items.variant']);

        return view('sales.holds.show', compact('holdSale'));
    }

    public function resume(HoldSale $holdSale)
    {
        abort_if(! Features::wholesaleEnabled() && $holdSale->pos_type === 'wholesale', 404);
        $route = $holdSale->pos_type === 'wholesale' ? 'pos.wholesale' : 'pos.retail';

        return redirect()->route($route, ['hold_id' => $holdSale->id])
            ->with('success', "Resume hold {$holdSale->reference_no} from the hold queue.");
    }

    public function destroy(HoldSale $holdSale)
    {
        abort_if(! Features::wholesaleEnabled() && $holdSale->pos_type === 'wholesale', 404);
        $referenceNo = $holdSale->reference_no;
        $holdSale->items()->delete();
        $holdSale->delete();

        ActivityLog::log('Delete', 'Hold Sales Module', "Deleted hold sale {$referenceNo}");

        return redirect()->route('hold-sales.index')->with('success', 'Hold sale deleted successfully.');
    }
}
