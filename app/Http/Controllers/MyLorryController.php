<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\LorryAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class MyLorryController extends Controller
{
    public function __construct(private readonly LorryAccessService $access) {}

    public function dashboard(Request $request)
    {
        $assignment = $this->access->currentAssignment($request->user(), $request->integer('lorry_id') ?: null);
        $lorryId = (int) $assignment->lorry_id;
        $from = \Illuminate\Support\Carbon::parse($request->input('from', today()->toDateString()))->startOfDay();
        $to = \Illuminate\Support\Carbon::parse($request->input('to', today()->toDateString()))->endOfDay();
        $trip = DB::table('distribution_trips')->where('lorry_id', $lorryId)->whereIn('status', ['planned', 'active'])->latest('id')->first();
        $stock = DB::table('lorry_stocks')->where('lorry_id', $lorryId);
        $sales = DB::table('sales')->where('lorry_id', $lorryId)->whereBetween('sale_date', [$from, $to]);
        $collections = DB::table('lorry_collections')->where('lorry_id', $lorryId)->whereBetween('collection_date', [$from, $to]);
        $expenses = DB::table('expenses')->where('lorry_id', $lorryId)->whereBetween('expense_date', [$from, $to]);
        $canProfit = (bool) $assignment->can_view_profit;
        $cards = [
            ['label'=>'Current Lorry Stock','value'=>number_format((float) (clone $stock)->sum('quantity'), 2)],
            ['label'=>'Today Orders','value'=>DB::table('distribution_orders')->where('lorry_id',$lorryId)->whereBetween('order_date',[$from,$to])->count()],
            ['label'=>'Today Sales','value'=>\App\Support\SystemSettings::money((float) (clone $sales)->sum('payable_amount'))],
            ['label'=>'Today Collections','value'=>\App\Support\SystemSettings::money((float) $collections->sum('amount'))],
            ['label'=>'Today Expenses','value'=>\App\Support\SystemSettings::money((float) $expenses->sum('amount'))],
            ['label'=>'Active Trip','value'=>$trip->trip_no ?? 'No active trip'],
            ['label'=>'Pending Reconciliation','value'=>DB::table('lorry_reconciliations')->where('lorry_id',$lorryId)->whereIn('status',['draft','submitted'])->count()],
        ];
        if ($assignment->can_view_cost) {
            $cards[] = ['label'=>'Stock Value','value'=>\App\Support\SystemSettings::money((float) DB::table('lorry_stocks')->join('products','products.id','=','lorry_stocks.product_id')->where('lorry_id',$lorryId)->sum(DB::raw('lorry_stocks.quantity * products.cost_price')))];
        }
        $recentSales = DB::table('sales as s')->leftJoin('customers as c','c.id','=','s.customer_id')->where('s.lorry_id',$lorryId)->whereBetween('s.sale_date',[$from,$to])->select('s.invoice_no','s.sale_date','s.payable_amount','s.payment_status','c.name as customer_name')->latest('s.id')->limit(8)->get();
        $recentExpenses = DB::table('expenses')->where('lorry_id',$lorryId)->whereBetween('expense_date',[$from,$to])->latest('id')->limit(5)->get();
        ActivityLog::log('View', 'My Lorry', "Viewed dashboard for lorry {$assignment->lorry_no}");
        return view('distribution.my-lorry', compact('assignment','trip','cards','canProfit','recentSales','recentExpenses'));
    }

    public function stock(Request $request)
    {
        $assignment = $this->access->currentAssignment($request->user(), $request->integer('lorry_id') ?: null);
        $stocks = DB::table('lorry_stocks')->join('products','products.id','=','lorry_stocks.product_id')
            ->leftJoin('categories','categories.id','=','products.category_id')->where('lorry_stocks.lorry_id',$assignment->lorry_id)
            ->when($request->filled('q'), fn($q) => $q->where(fn($x) => $x->where('products.name','like','%'.$request->q.'%')->orWhere('products.sku','like','%'.$request->q.'%')->orWhere('products.barcode','like','%'.$request->q.'%')))
            ->select('lorry_stocks.*','products.name as product_name','products.sku','products.barcode','products.retail_price','products.wholesale_price','categories.name as category_name')->orderBy('products.name')->paginate(30)->withQueryString();
        ActivityLog::log('View', 'My Lorry', "Viewed stock for lorry {$assignment->lorry_no}");
        return view('distribution.my-stock', compact('assignment','stocks'));
    }

    public function createOrder(Request $request)
    {
        $assignment = $this->access->currentAssignment($request->user());
        $tripId = DB::table('distribution_trips')->where('lorry_id', $assignment->lorry_id)->where('status', 'active')->value('id');

        return redirect()->route('pos.wholesale', array_filter([
            'source_type' => 'lorry',
            'lorry_id' => $assignment->lorry_id,
            'trip_id' => $tripId,
        ]));
    }

    public function storeOrder(Request $request)
    {
        $assignment = $this->access->currentAssignment($request->user());
        $data = $request->validate(['customer_id'=>['required','exists:customers,id'],'status'=>['required','in:draft,confirmed'],'notes'=>['nullable','string','max:1000'],'discount_type'=>['nullable','in:fixed,percentage'],'discount_amount'=>['nullable','numeric','min:0'],'payment_method'=>['nullable','string','max:50'],'items'=>['required','array','min:1'],'items.*.product_id'=>['required','integer','exists:products,id'],'items.*.quantity'=>['required','numeric','min:0.01']]);
        DB::transaction(function () use ($data, $assignment, $request) {
            $lines = []; $subtotal = 0;
            foreach ($data['items'] as $item) {
                $stock = DB::table('lorry_stocks')->where('lorry_id',$assignment->lorry_id)->where('product_id',$item['product_id'])->whereNull('variant_id')->lockForUpdate()->first();
                $reserved = DB::table('distribution_order_items as oi')->join('distribution_orders as o','o.id','=','oi.order_id')->where('o.lorry_id',$assignment->lorry_id)->where('oi.product_id',$item['product_id'])->whereIn('o.status',['confirmed','packed','out_for_delivery'])->sum('oi.reserved_quantity');
                $available = max(0, (float)($stock->quantity ?? 0) - (float)$reserved);
                if ((float)$item['quantity'] > $available) throw ValidationException::withMessages(['items'=>"Only {$available} units are available for one of the selected products."]);
                $product = DB::table('products')->where('id',$item['product_id'])->firstOrFail();
                $lineTotal = (float)$product->wholesale_price * (float)$item['quantity']; $subtotal += $lineTotal;
                $lines[] = ['product_id'=>$product->id,'quantity'=>$item['quantity'],'reserved_quantity'=>$data['status']==='confirmed'?$item['quantity']:0,'unit_price'=>$product->wholesale_price,'discount_amount'=>0,'tax_amount'=>0,'line_total'=>$lineTotal,'created_at'=>now(),'updated_at'=>now()];
            }
            $discountType = $data['discount_type'] ?? 'fixed';
            $discountInput = (float)($data['discount_amount'] ?? 0);
            if ($discountType === 'percentage' && $discountInput > 100) {
                throw ValidationException::withMessages(['discount_amount' => 'Percentage discount cannot be greater than 100%.']);
            }
            $calculatedDiscount = $discountType === 'percentage' ? ($subtotal * ($discountInput / 100)) : $discountInput;
            $calculatedDiscount = min($subtotal, max(0, $calculatedDiscount));
            $totalAmount = max(0, $subtotal - $calculatedDiscount);
            $tripId = DB::table('distribution_trips')->where('lorry_id',$assignment->lorry_id)->where('status','active')->value('id');
            $orderId = DB::table('distribution_orders')->insertGetId(['order_no'=>'ORD-'.now()->format('ymdHis').'-'.random_int(10,99),'lorry_id'=>$assignment->lorry_id,'trip_id'=>$tripId,'customer_id'=>$data['customer_id'],'salesperson_id'=>auth()->id(),'order_date'=>now(),'status'=>$data['status'],'subtotal'=>$subtotal,'discount_type'=>$discountType,'discount_amount'=>$calculatedDiscount,'tax_amount'=>0,'total_amount'=>$totalAmount,'payment_method'=>$data['payment_method'] ?? null,'reserved_stock'=>$data['status']==='confirmed','notes'=>$data['notes']??null,'created_by'=>auth()->id(),'created_at'=>now(),'updated_at'=>now()]);
            foreach ($lines as &$line) $line['order_id'] = $orderId;
            DB::table('distribution_order_items')->insert($lines);
            ActivityLog::log('Create','My Lorry',"Created {$data['status']} order for lorry {$assignment->lorry_no}");
        });
        return redirect()->route('distribution.my-lorry.order.create')->with('success','Order saved successfully.');
    }

    public function sales(Request $request)
    {
        $assignment = $this->access->currentAssignment($request->user());
        $range = $this->dateRange($request);
        $query = DB::table('sales as s')->leftJoin('customers as c','c.id','=','s.customer_id')->where('s.lorry_id',$assignment->lorry_id)
            ->whereBetween('s.sale_date', $range)->select('s.*','c.name as customer_name')
            ->when($request->filled('q'), fn($q)=>$q->where(fn($x)=>$x->where('s.invoice_no','like','%'.$request->q.'%')->orWhere('c.name','like','%'.$request->q.'%')))
            ->when($request->filled('payment_status'), fn($q)=>$q->where('s.payment_status',$request->payment_status));
        $summary = ['count'=>(clone $query)->count(),'total'=>(float)(clone $query)->sum('s.payable_amount'),'paid'=>(float)(clone $query)->sum('s.paid_amount'),'due'=>(float)(clone $query)->sum('s.due_amount')];
        $sales = $query->latest('s.sale_date')->latest('s.id')->paginate(20)->withQueryString();
        return view('distribution.my-sales', compact('assignment','sales','summary','range'));
    }

    public function expenses(Request $request)
    {
        $assignment = $this->access->currentAssignment($request->user());
        if ($request->isMethod('post')) {
            $data = $request->validate(['expense_date'=>['required','date'],'expense_category_id'=>['required','exists:expense_categories,id'],'title'=>['required','string','max:255'],'amount'=>['required','numeric','min:0.01'],'payment_method'=>['required','in:Cash,Bank Transfer,Card,Other'],'paid_from_account_id'=>['required','exists:accounts,id'],'reference_no'=>['nullable','string','max:100'],'description'=>['nullable','string','max:1000']]);
            $tripId = DB::table('distribution_trips')->where('lorry_id',$assignment->lorry_id)->where('status','active')->value('id');
            DB::table('expenses')->insert(array_merge($data,['expense_no'=>'LEXP-'.now()->format('ymdHis').random_int(10,99),'lorry_id'=>$assignment->lorry_id,'trip_id'=>$tripId,'created_by'=>auth()->id(),'created_at'=>now(),'updated_at'=>now()]));
            ActivityLog::log('Create','My Lorry',"Added lorry expense {$data['title']} ({$data['amount']}) for {$assignment->lorry_no}");
            return redirect()->route('distribution.my-lorry.expenses')->with('success','Lorry expense recorded successfully.');
        }
        $range = $this->dateRange($request);
        $query = DB::table('expenses as e')->leftJoin('expense_categories as ec','ec.id','=','e.expense_category_id')->where('e.lorry_id',$assignment->lorry_id)->whereBetween('e.expense_date',$range)->select('e.*','ec.name as category_name');
        $total = (float)(clone $query)->sum('e.amount'); $expenses = $query->latest('e.expense_date')->latest('e.id')->paginate(20)->withQueryString();
        $categories = DB::table('expense_categories')->orderBy('name')->get(); $accounts = DB::table('accounts')->where('status',true)->whereIn('account_type',['Asset','Liability'])->orderBy('account_name')->get();
        $trip = DB::table('distribution_trips')->where('lorry_id',$assignment->lorry_id)->where('status','active')->first();
        return view('distribution.my-expenses', compact('assignment','expenses','total','categories','accounts','trip','range'));
    }

    private function dateRange(Request $request): array
    {
        $filter = $request->input('filter','today');
        return match($filter) {
            'yesterday' => [today()->subDay()->toDateString(), today()->subDay()->toDateString()],
            'this_week' => [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()],
            'last_week' => [now()->subWeek()->startOfWeek()->toDateString(), now()->subWeek()->endOfWeek()->toDateString()],
            'this_month' => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->endOfMonth()->toDateString()],
            'custom' => [\Illuminate\Support\Carbon::parse($request->input('from',today()->toDateString()))->toDateString(), \Illuminate\Support\Carbon::parse($request->input('to',today()->toDateString()))->toDateString()],
            default => [today()->toDateString(), today()->toDateString()],
        };
    }

    public function customers(Request $request)
    {
        $assignment = $this->access->currentAssignment($request->user());
        
        $customers = DB::table('customers')
            ->where('status', 1)
            ->where('created_by', $request->user()->id)
            ->when($request->filled('q'), function($q) use ($request) {
                $q->where(function($query) use ($request) {
                    $query->where('name', 'like', '%'.$request->q.'%')
                          ->orWhere('phone', 'like', '%'.$request->q.'%');
                });
            })
            ->select('id', 'name', 'phone', 'address', 'balance')
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();
            
        ActivityLog::log('View', 'My Lorry', "Viewed customers for lorry {$assignment->lorry_no}");
        return view('distribution.my-customers', compact('assignment', 'customers'));
    }
}
