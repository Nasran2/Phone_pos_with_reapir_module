<x-report-layout 
    title="Stock Report" 
    :summary="$summary ?? []" 
    :exportPdf="true" 
    :exportExcel="true" 
    :exportRoute="route('reports.stock')" 
    :resetRoute="route('reports.stock')"
>
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-slate-100 text-slate-600 text-xs uppercase tracking-wider border-b border-slate-200">
                <th class="p-4 font-bold">Product</th>
                <th class="p-4 font-bold">Category</th>
                <th class="p-4 font-bold text-center">Stock</th>
                <th class="p-4 font-bold text-right">Cost Price</th>
                <th class="p-4 font-bold text-right">Retail Price</th>
                <th class="p-4 font-bold text-right">Wholesale Price</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
            @forelse($products as $product)
                <tr class="hover:bg-slate-50 transition">
                    <td class="p-4 font-medium text-slate-900">{{ $product->name }}</td>
                    <td class="p-4">{{ $product->category->name ?? '-' }}</td>
                    <td class="p-4 text-center font-bold {{ ($product->stock->quantity ?? 0) <= $product->stock_alert_quantity ? 'text-rose-600' : 'text-emerald-600' }}">
                        {{ $product->stock->quantity ?? 0 }}
                    </td>
                    <td class="p-4 text-right">Rs. {{ number_format($product->cost_price, 2) }}</td>
                    <td class="p-4 text-right">Rs. {{ number_format($product->retail_price, 2) }}</td>
                    <td class="p-4 text-right">Rs. {{ number_format($product->wholesale_price, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="p-8 text-center text-slate-500">No products found.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($products->hasPages())
        <div class="p-4 border-t border-slate-200">{{ $products->links() }}</div>
    @endif
</x-report-layout>
