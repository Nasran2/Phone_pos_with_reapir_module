<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $sale->invoice_no }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; margin: 32px; }
        .top { display: flex; justify-content: space-between; border-bottom: 1px solid #e5e7eb; padding-bottom: 18px; margin-bottom: 18px; }
        h1 { margin: 0; font-size: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { padding: 10px; border-bottom: 1px solid #e5e7eb; font-size: 12px; text-align: left; }
        th { background: #f8fafc; text-transform: uppercase; font-size: 10px; color: #64748b; }
        .right { text-align: right; }
        .totals { margin-left: auto; width: 300px; margin-top: 20px; }
        .row { display: flex; justify-content: space-between; padding: 7px 0; border-bottom: 1px solid #eef2f7; }
        @media print { .no-print { display: none; } body { margin: 12px; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Print</button>
    @if(!$sale->stock_deducted_at && $sale->status !== 'cancelled')
    <div style="margin: 12px 0; padding: 10px 12px; background: #fffbeb; border: 1px solid #f59e0b; color: #92400e; font-size: 12px;">
        PAYMENT PENDING — This invoice has not reduced inventory. Stock will be deducted automatically after full payment clears.
    </div>
    @endif
    <div class="top">
        <div>
            <h1>{{ \App\Support\SystemSettings::get('business_name', 'Phone Shop POS', 'business_settings') }}</h1>
            <p>Retail & Wholesale</p>
            @php
                $tin = \App\Support\SystemSettings::get('company_tin', '', 'tax_settings');
                $vat = \App\Support\SystemSettings::get('vat_registration_number', '', 'tax_settings');
            @endphp
            @if($tin) <p style="font-size: 12px; margin: 4px 0;"><strong>TIN:</strong> {{ $tin }}</p> @endif
            @if($vat) <p style="font-size: 12px; margin: 4px 0;"><strong>VAT Reg:</strong> {{ $vat }}</p> @endif
        </div>
        <div class="right">
            <h2>@if($sale->is_tax_invoice) Tax Invoice @else Invoice @endif {{ $sale->invoice_no }}</h2>
            @if($sale->tax_invoice_number) <p><strong>Tax Inv #:</strong> {{ $sale->tax_invoice_number }}</p> @endif
            <p>{{ $sale->created_at->format('Y-m-d h:i A') }}</p>
            <p>{{ ucfirst($sale->pos_type) }} &bull; {{ $sale->displayStatus() }}</p>
        </div>
    </div>
    <p><strong>Customer:</strong> {{ $sale->customer->name ?? 'Walk-in Customer' }} {{ $sale->customer?->phone ? ' - '.$sale->customer->phone : '' }}</p>
    <p><strong>Cashier:</strong> {{ $sale->cashier->name ?? '-' }}</p>
    <table>
        <thead><tr><th>Product</th><th class="right">Qty</th><th class="right">Unit Price</th><th class="right">Line Total</th></tr></thead>
        <tbody>
            @foreach($sale->items as $item)
            <tr>
                <td>{{ $item->product->name ?? 'Product' }} @if($item->variantLabel()) ({{ $item->variantLabel() }}) @endif</td>
                <td class="right">{{ (float) $item->quantity }}</td>
                <td class="right">Rs. {{ number_format((float) ($item->unit_price ?: $item->selling_price), 2) }}</td>
                <td class="right">Rs. {{ number_format((float) ($item->line_total ?: $item->subtotal), 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="totals">
        <div class="row"><span>Subtotal</span><strong>Rs. {{ number_format((float) $sale->total_amount, 2) }}</strong></div>
        <div class="row"><span>Discount</span><strong>Rs. {{ number_format((float) $sale->discount_amount, 2) }}</strong></div>
        @if($sale->tax_amount > 0)
            @if($sale->taxTransactions && $sale->taxTransactions->isNotEmpty())
                @foreach($sale->taxTransactions as $tx)
                <div class="row" style="color:#475569;">
                    <span>{{ $tx->tax_code ?? 'VAT' }} ({{ (float)$tx->tax_rate }}%)</span>
                    <strong>Rs. {{ number_format((float) $tx->tax_amount, 2) }}</strong>
                </div>
                @endforeach
            @endif
            <div class="row">
                <span>Total Tax</span>
                <strong>Rs. {{ number_format((float) $sale->tax_amount, 2) }}</strong>
            </div>
        @endif
        <div class="row"><span>Grand Total</span><strong>Rs. {{ number_format((float) $sale->payable_amount, 2) }}</strong></div>
        <div class="row"><span>Paid</span><strong>Rs. {{ number_format((float) $sale->paid_amount, 2) }}</strong></div>
        <div class="row"><span>Cheque Hold</span><strong>Rs. {{ number_format((float) $sale->cheque_hold_amount, 2) }}</strong></div>
        <div class="row"><span>Due</span><strong>Rs. {{ number_format((float) $sale->due_amount, 2) }}</strong></div>
    </div>
    @if($sale->invoicePayments->isNotEmpty())
    <h3 style="margin-top:32px">Payment Allocation</h3>
    <table>
        <thead><tr><th>Method</th><th>Reference / Cheque</th><th>Status</th><th class="right">Amount</th></tr></thead>
        <tbody>@foreach($sale->invoicePayments as $payment)<tr><td>{{ $payment->payment_method }}</td><td>{{ $payment->reference_no ?: ($payment->cheque ? $payment->cheque->cheque_number . ' (' . $payment->cheque->bank_name . ' - ' . $payment->cheque->cheque_date . ')' : '-') }}</td><td>{{ strtoupper($payment->status) }}</td><td class="right">Rs. {{ number_format((float)$payment->amount, 2) }}</td></tr>@endforeach</tbody>
    </table>
    @endif
</body>
</html>
