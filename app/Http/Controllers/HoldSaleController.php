<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\HoldSale;
use App\Models\User;
use Illuminate\Http\Request;

class HoldSaleController extends Controller
{
    public function index(Request $request)
    {
        $query = HoldSale::with(['customer', 'cashier'])->withCount('items');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
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

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $holds = $query->latest('created_at')->paginate(12)->withQueryString();
        $cashiers = User::whereIn('id', HoldSale::query()->whereNotNull('created_by')->select('created_by'))->orderBy('name')->get();

        return view('sales.holds.index', compact('holds', 'cashiers'));
    }

    public function show(HoldSale $holdSale)
    {
        $holdSale->load(['customer', 'cashier', 'items.product', 'items.variant']);

        return view('sales.holds.show', compact('holdSale'));
    }

    public function resume(HoldSale $holdSale)
    {
        $route = $holdSale->pos_type === 'wholesale' ? 'pos.wholesale' : 'pos.retail';

        return redirect()->route($route, ['hold_id' => $holdSale->id])
            ->with('success', "Resume hold {$holdSale->reference_no} from the hold queue.");
    }

    public function destroy(HoldSale $holdSale)
    {
        $referenceNo = $holdSale->reference_no;
        $holdSale->items()->delete();
        $holdSale->delete();

        ActivityLog::log('Delete', 'Hold Sales Module', "Deleted hold sale {$referenceNo}");

        return redirect()->route('hold-sales.index')->with('success', 'Hold sale deleted successfully.');
    }
}
