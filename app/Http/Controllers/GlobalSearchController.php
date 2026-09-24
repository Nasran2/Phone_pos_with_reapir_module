<?php

namespace App\Http\Controllers;

use App\Models\Cheque;
use App\Services\ChequeAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate(['q' => 'required|string|min:2|max:100']);
        $term = '%'.$request->string('q')->trim().'%';
        $results = collect();
        $sources = [
            ['products.view', 'products', ['name', 'sku', 'barcode'], 'name', 'products.index', 'Product'],
            ['sales.view', 'sales', ['invoice_no'], 'invoice_no', 'sales.show', 'Sale'],
            ['purchase.view', 'purchases', ['invoice_no'], 'invoice_no', 'purchases.show', 'Purchase'],
            ['customers.view', 'customers', ['name', 'phone'], 'name', 'customers.show', 'Customer'],
            ['suppliers.view', 'suppliers', ['name', 'phone'], 'name', 'suppliers.show', 'Supplier'],
        ];
        // Lorry users have their own scoped workspace; do not expose company-wide search.
        if ($request->user()->user_type === 'lorry') {
            return response()->json(['results' => []]);
        }
        foreach ($sources as [$permission, $table, $columns, $label, $route, $type]) {
            if (! $request->user()->hasPermission($permission)) {
                continue;
            }
            $query = DB::table($table)->where(function ($query) use ($columns, $term, $table) {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'like', $term);
                }
                if ($table === 'products') {
                    $query->orWhereIn('id', DB::table('product_serials')->select('product_id')->where('serial_number', 'like', $term));
                }
            });
            foreach ($query->limit(5)->get(['id', $label]) as $row) {
                $results->push(['label' => $row->$label, 'type' => $type, 'url' => route($route, $table === 'products' ? ['search' => $row->$label] : $row->id)]);
            }
        }
        if ($request->user()->hasPermission('cheques.view')) {
            foreach (app(ChequeAccessService::class)->scope(Cheque::query(), $request->user())->where('cheque_number', 'like', $term)->limit(5)->get() as $cheque) {
                $results->push(['label' => $cheque->cheque_number, 'type' => 'Cheque', 'url' => route('cheques.show', $cheque)]);
            }
        }

        return response()->json(['results' => $results]);
    }
}
