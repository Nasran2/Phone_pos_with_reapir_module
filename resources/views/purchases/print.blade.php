<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $purchase->displayNo() }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #0f172a; margin: 32px; }
        .top { display: flex; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 16px; margin-bottom: 20px; }
        h1 { margin: 0; font-size: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 10px; font-size: 12px; text-align: left; }
        th { background: #f8fafc; text-transform: uppercase; font-size: 11px; }
        .right { text-align: right; }
        .totals { width: 320px; margin-left: auto; margin-top: 20px; font-size: 13px; }
        .totals div { display: flex; justify-content: space-between; padding: 6px 0; }
        .grand { font-weight: bold; border-top: 1px solid #cbd5e1; }
        @media print { button { display: none; } body { margin: 18px; } }
    </style>
</head>
<body>
    <button onclick="window.print()" style="float:right;padding:8px 14px;background:#2563eb;color:white;border:0;border-radius:6px;">Print</button>
    <div class="top">
        <div>
            <h1>Purchase Invoice</h1>
            <strong>{{ $purchase->displayNo() }}</strong><br>
            Date: {{ optional($purchase->purchase_date)->format('Y-m-d') }}<br>
            Status: {{ $purchase->status }}
        </div>
        <div class="right">
            <strong>Phone Shop POS</strong><br>
            Supplier: {{ $purchase->supplier->name ?? '-' }}<br>
            {{ $purchase->supplier->phone ?? '' }}<br>
            {{ $purchase->supplier->address ?? '' }}
        </div>
    </div>
    <table>
        <thead>
            <tr><th>Product</th><th class="right">Qty</th><th class="right">Unit Cost</th><th class="right">Discount</th><th class="right">Tax</th><th class="right">Line Total</th></tr>
        </thead>
        <tbody>
            @foreach($purchase->items as $item)
            <tr>
                <td>{{ $item->product->name ?? '-' }}</td>
                <td class="right">{{ number_format((float) $item->quantity, 2) }}</td>
                <td class="right">Rs. {{ number_format((float) ($item->unit_cost ?: $item->cost_price), 2) }}</td>
                <td class="right">Rs. {{ number_format((float) $item->discount_amount, 2) }}</td>
                <td class="right">Rs. {{ number_format((float) $item->tax_amount, 2) }}</td>
                <td class="right">Rs. {{ number_format((float) $item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="totals">
        <div><span>Subtotal</span><span>Rs. {{ number_format((float) $purchase->subtotal, 2) }}</span></div>
        <div><span>Discount</span><span>Rs. {{ number_format((float) $purchase->discount_amount, 2) }}</span></div>
        <div><span>Tax</span><span>Rs. {{ number_format((float) $purchase->tax_amount, 2) }}</span></div>
        <div><span>Shipping</span><span>Rs. {{ number_format((float) $purchase->shipping_cost, 2) }}</span></div>
        <div class="grand"><span>Grand Total</span><span>Rs. {{ number_format((float) $purchase->grand_total, 2) }}</span></div>
        <div><span>Paid</span><span>Rs. {{ number_format((float) $purchase->paid_amount, 2) }}</span></div>
        <div><span>Due</span><span>Rs. {{ number_format((float) $purchase->due_amount, 2) }}</span></div>
    </div>
</body>
</html>
