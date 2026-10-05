@php
    use App\Support\SystemSettings;

    $businessName = SystemSettings::get('business_name', 'Phone Shop POS', 'business_settings');
    $businessAddress = SystemSettings::get('business_address', '', 'business_settings');
    $businessPhone = SystemSettings::get('business_phone', '', 'business_settings');
    $businessEmail = SystemSettings::get('business_email', '', 'business_settings');
    $businessWebsite = SystemSettings::get('business_website', '', 'business_settings');
    $businessLogo = SystemSettings::get('business_logo', null, 'business_settings');
    $showLogo = SystemSettings::bool('show_logo', true, 'invoice_settings');
    $registrationNo = SystemSettings::get('business_registration_number', '', 'business_settings');
    $tin = SystemSettings::get('company_tin', '', 'tax_settings');
    $vat = SystemSettings::get('vat_registration_number', '', 'tax_settings');
    $footerMessage = SystemSettings::get(
        'footer_message',
        SystemSettings::get('invoice_footer_note', 'Thank you for choosing us.', 'business_settings'),
        'invoice_settings'
    );
    $terms = SystemSettings::get(
        'terms_text',
        SystemSettings::get('terms_and_conditions', '', 'business_settings'),
        'invoice_settings'
    );
    $dateFormat = SystemSettings::get('date_format', 'd M Y', 'business_settings');
    $timeFormat = SystemSettings::get('time_format', 'h:i A', 'business_settings');
    $isCancelled = $sale->status === 'cancelled';
    $hasDue = (float) $sale->due_amount > 0.009;
    $hasChequeHold = (float) $sale->cheque_hold_amount > 0.009;
    $statusLabel = $isCancelled ? 'CANCELLED' : ($hasDue ? 'PAYMENT DUE' : ($hasChequeHold ? 'CHEQUE PENDING' : 'PAID'));
    $statusClass = $isCancelled ? 'status-cancelled' : ($hasDue ? 'status-due' : ($hasChequeHold ? 'status-pending' : 'status-paid'));
    $isPdf = $isPdf ?? false;
    $logoSource = $businessLogo ? asset('storage/'.$businessLogo) : null;
    if ($isPdf && $businessLogo) {
        $logoSource = public_path('storage/'.$businessLogo);
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $sale->invoice_no }}</title>
    <style>
        :root {
            --ink: #0f172a;
            --muted: #64748b;
            --line: #dbe3ee;
            --soft: #f5f8fc;
            --brand: #155eef;
            --brand-dark: #103a8c;
            --success: #067647;
            --warning: #b54708;
            --danger: #b42318;
        }

        * { box-sizing: border-box; }
        html { background: #e9eef5; }
        body {
            margin: 0;
            color: #0f172a;
            font-family: Inter, ui-sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
            font-size: 12px;
            line-height: 1.45;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .invoice-sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 18px auto;
            padding: 13mm 14mm 11mm;
            background: #fff;
            box-shadow: 0 18px 55px rgba(15, 23, 42, .16);
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }
        .brand-stripe {
            position: absolute;
            inset: 0 0 auto;
            height: 6px;
            background: linear-gradient(90deg, #103a8c, #155eef 62%, #38bdf8);
        }
        .invoice-header {
            padding: 4px 0 21px;
            border-bottom: 1px solid #dbe3ee;
        }
        .header-layout { width: 100%; border-collapse: collapse; }
        .header-layout td { padding: 0; border: 0; }
        .brand { min-width: 0; padding-right: 28px !important; }
        .brand-identity { display: table; width: 100%; border-collapse: collapse; }
        .brand-icon { width: 67px; }
        .brand-copy { vertical-align: top; }
        .brand-logo, .brand-mark {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            flex: 0 0 auto;
        }
        .brand-logo { object-fit: contain; border: 1px solid #dbe3ee; padding: 4px; }
        .brand-mark {
            display: grid;
            place-items: center;
            color: #fff;
            background: linear-gradient(145deg, #103a8c, #155eef);
            box-shadow: 0 7px 17px rgba(21, 94, 239, .24);
        }
        .brand-mark svg { width: 28px; height: 28px; }
        .brand-copy h1 { margin: 0 0 3px; font-size: 22px; line-height: 1.15; letter-spacing: -.45px; }
        .brand-copy .tagline { color: #155eef; font-size: 9px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; }
        .contact { margin-top: 8px; color: #64748b; font-size: 10px; line-height: 1.55; max-width: 390px; }
        .invoice-heading { text-align: right; width: 245px; }
        .invoice-heading .eyebrow { color: #64748b; font-size: 10px; font-weight: 700; letter-spacing: 1.6px; text-transform: uppercase; }
        .invoice-heading h2 { margin: 4px 0 6px; color: #103a8c; font-size: 24px; line-height: 1; letter-spacing: -.4px; }
        .invoice-number { font-size: 11px; font-weight: 700; overflow-wrap: anywhere; }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 10px;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .8px;
        }
        .status-pill::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
        .status-paid { color: #067647; background: #ecfdf3; border: 1px solid #abefc6; }
        .status-due { color: #b42318; background: #fef3f2; border: 1px solid #fecdca; }
        .status-pending { color: #b54708; background: #fffaeb; border: 1px solid #fedf89; }
        .status-cancelled { color: #475467; background: #f2f4f7; border: 1px solid #d0d5dd; }

        .details-grid {
            display: grid;
            grid-template-columns: 1.2fr .8fr;
            gap: 12px;
            margin: 16px 0 18px;
        }
        .detail-card { height: 100%; min-height: 90px; padding: 12px 14px; border: 1px solid #dbe3ee; border-radius: 10px; background: #f5f8fc; }
        .detail-title { margin-bottom: 7px; color: #64748b; font-size: 8px; font-weight: 700; letter-spacing: 1.1px; text-transform: uppercase; }
        .customer-name { font-size: 14px; font-weight: 700; }
        .detail-line { margin-top: 3px; color: #475569; font-size: 10px; }
        .meta-grid { display: table; width: 100%; border-collapse: collapse; }
        .meta-row { display: table-row; }
        .meta-row span { display: table-cell; padding-bottom: 4px; }
        .meta-row:last-child span { padding-bottom: 0; }
        .meta-row span:last-child { padding-left: 13px; }
        .meta-grid span:nth-child(odd) { color: #64748b; }
        .meta-grid span:nth-child(even) { text-align: right; font-weight: 700; }

        .items-table, .payments-table { width: 100%; border-collapse: collapse; }
        .items-table { margin-bottom: 17px; }
        th {
            padding: 9px 8px;
            color: #fff;
            background: #103a8c;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: .7px;
            text-align: left;
            text-transform: uppercase;
        }
        th:first-child { border-radius: 7px 0 0 7px; }
        th:last-child { border-radius: 0 7px 7px 0; }
        td { padding: 10px 8px; border-bottom: 1px solid #dbe3ee; vertical-align: top; }
        tbody tr { break-inside: avoid; page-break-inside: avoid; }
        .item-index { width: 28px; color: #64748b; }
        .item-name { font-weight: 700; font-size: 11px; }
        .item-meta { margin-top: 3px; color: #64748b; font-size: 8.5px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; white-space: nowrap; }

        .summary-row { display: grid; grid-template-columns: 1fr 292px; gap: 24px; align-items: start; break-inside: avoid; page-break-inside: avoid; }
        .payment-summary h3, .terms h3 { margin: 0 0 7px; color: #103a8c; font-size: 9px; letter-spacing: .8px; text-transform: uppercase; }
        .payment-summary p { margin: 0 0 5px; color: #64748b; font-size: 9px; }
        .payment-summary strong { color: #0f172a; }
        .totals { border: 1px solid #dbe3ee; border-radius: 10px; overflow: hidden; }
        .total-row { display: flex; justify-content: space-between; gap: 14px; padding: 8px 12px; border-bottom: 1px solid #dbe3ee; }
        .total-row:last-child { border-bottom: 0; }
        .total-row span:first-child { color: #64748b; }
        .total-row span:last-child { font-weight: 700; white-space: nowrap; }
        .grand-total { padding: 11px 12px; color: #fff; background: #103a8c; font-size: 14px; }
        .grand-total span:first-child { color: #dbeafe; }
        .due-total { color: #b42318; background: #fef3f2; font-size: 13px; font-weight: 700; }
        .paid-total { color: #067647; background: #ecfdf3; }

        .payments-section { margin-top: 20px; break-inside: avoid; page-break-inside: avoid; }
        .section-heading { display: flex; align-items: center; gap: 9px; margin-bottom: 8px; color: #103a8c; font-size: 9px; font-weight: 700; letter-spacing: .9px; text-transform: uppercase; }
        .section-heading::after { content: ''; height: 1px; flex: 1; background: #dbe3ee; }
        .payments-table th { padding: 7px 8px; background: #eaf1fb; color: #344054; }
        .payments-table td { padding: 8px; font-size: 9px; }
        .mini-status { padding: 3px 7px; border-radius: 999px; background: #eef2f6; color: #475467; font-size: 7.5px; font-weight: 700; text-transform: uppercase; }

        .invoice-footer { margin-top: auto; padding-top: 22px; break-inside: avoid; page-break-inside: avoid; }
        .terms { padding: 10px 12px; border-radius: 8px; background: #f5f8fc; color: #64748b; font-size: 8.5px; white-space: pre-line; }
        .signatures { display: grid; grid-template-columns: repeat(2, 150px); justify-content: space-between; margin: 30px 18px 21px; text-align: center; }
        .signature-line { padding-top: 6px; border-top: 1px solid #94a3b8; color: #64748b; font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
        .footer-bar { display: flex; justify-content: space-between; gap: 20px; padding-top: 10px; border-top: 1px solid #dbe3ee; color: #64748b; font-size: 8px; }
        .footer-message { color: #103a8c; font-weight: 700; }

        .print-btn {
            position: fixed;
            z-index: 10;
            right: 22px;
            top: 22px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 17px;
            color: #fff;
            background: #103a8c;
            border: 0;
            border-radius: 9px;
            box-shadow: 0 8px 25px rgba(15, 23, 42, .24);
            cursor: pointer;
            font-weight: 700;
        }
        .print-btn svg { width: 16px; height: 16px; }

        @page { size: A4 portrait; margin: 10mm; }
        @media print {
            html, body { width: 100%; background: #fff; }
            .no-print { display: none !important; }
            .invoice-sheet {
                width: 100%;
                min-height: 277mm;
                margin: 0;
                padding: 4mm 4mm 2mm;
                box-shadow: none;
                overflow: visible;
            }
            .brand-stripe { left: -6mm; right: -6mm; top: -4mm; }
        }
        @media screen and (max-width: 760px) {
            html { background: #fff; }
            .invoice-sheet { width: 100%; min-height: 100vh; margin: 0; padding: 24px 18px; box-shadow: none; }
            .invoice-header, .summary-row { grid-template-columns: 1fr; display: grid; }
            .invoice-heading { text-align: left; }
            .header-layout, .header-layout > tbody, .header-layout > tbody > tr, .header-layout > tbody > tr > td { display: block; width: 100%; }
            .brand { padding-right: 0 !important; }
            .invoice-heading { margin-top: 16px; }
            .details-grid { grid-template-columns: 1fr; }
            .items-table { min-width: 620px; }
            .table-scroll { overflow-x: auto; }
            .print-btn { right: 12px; top: 12px; }
        }
        @if($isPdf)
        /* Dompdf supports table layout, but not the browser's flex/grid layout. */
        html, body { background: #fff; }
        * { font-family: Helvetica, sans-serif; }
        h1, h2, h3, strong, th, .tagline, .eyebrow, .invoice-number, .status-pill, .detail-title, .customer-name, .item-name, .section-heading, .mini-status, .signature-line, .footer-message { font-weight: bold; }
        .invoice-sheet { display: block; width: auto; min-height: 0; margin: 0; padding: 4mm 4mm 2mm; overflow: visible; box-shadow: none; }
        .brand-stripe { top: -4mm; left: -6mm; right: -6mm; background: #155eef; }
        .header-layout { table-layout: fixed; }
        .brand, .invoice-heading { display: table-cell; vertical-align: top; }
        .brand { width: 72%; padding-right: 0 !important; }
        .invoice-heading { width: 28%; }
        .invoice-heading > div, .invoice-heading h2 { text-align: right; }
        .brand-identity { table-layout: auto; }
        .brand-icon { display: table-cell; width: 67px; vertical-align: top; }
        .brand-copy { display: table-cell; vertical-align: top; }
        .brand-copy h1 { font-size: 22px; }
        .brand-copy .tagline { font-size: 8px; letter-spacing: .6px; }
        .brand-mark { display: block; background: #155eef; text-align: center; }
        .brand-mark img { width: 28px; height: 28px; margin-top: 13px; }
        .status-pill { display: inline-block; }
        .status-pill::before { display: none; }
        .details-grid { display: table; width: 100%; table-layout: fixed; border-collapse: collapse; }
        .detail-column { display: table-cell; width: 40%; vertical-align: top; }
        .detail-column:first-child { width: 60%; padding-right: 12px; }
        .detail-card { height: 120px; min-height: 0; }
        .summary-row { display: table; width: 100%; table-layout: fixed; }
        .payment-summary, .totals-column { display: table-cell; vertical-align: top; }
        .payment-summary { padding-right: 24px; }
        .totals-column { width: 42.5%; }
        .total-row { display: table; width: 100%; table-layout: fixed; }
        .total-row span { display: table-cell; vertical-align: middle; }
        .total-row span:first-child { width: 70%; }
        .total-row span:last-child { text-align: right; font-weight: bold; }
        .section-heading { display: block; }
        .section-heading::after { display: none; }
        .invoice-footer { margin-top: 22px; }
        .signatures { display: table; width: 100%; margin: 30px 0 21px; table-layout: fixed; }
        .signature-line { display: table-cell; width: 50%; border-top: 0; padding: 6px 18px 0; }
        .signature-line span { display: block; width: 150px; border-top: 1px solid #94a3b8; padding-top: 6px; }
        .signature-line:last-child span { margin-left: auto; }
        .footer-bar { display: table; width: 100%; }
        .footer-bar > div { display: table-cell; }
        .footer-bar > div:last-child { text-align: right; }
        thead { display: table-header-group; }
        @endif
    </style>
</head>
<body>
    @if(!isset($isPdf) || !$isPdf)
    <button class="no-print print-btn" onclick="window.print()" aria-label="Print A4 invoice">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2m-2-5H8v8h8v-8Z"/></svg>
        Print A4 Invoice
    </button>
    @endif

    <main class="invoice-sheet">
        <div class="brand-stripe"></div>

        <header class="invoice-header">
            <table class="header-layout"><tbody><tr>
            <td class="brand">
                <table class="brand-identity"><colgroup><col style="width: 67px;"><col></colgroup><tbody><tr>
                <td class="brand-icon">
                @if($showLogo && $businessLogo)
                    <img class="brand-logo" src="{{ $logoSource }}" alt="{{ $businessName }} logo">
                @else
                    <div class="brand-mark" aria-hidden="true">
                        @if($isPdf)
                        <img src="data:image/svg+xml;base64,{{ base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff"><rect x="7" y="2" width="10" height="20" rx="2" stroke-width="1.8"/><path d="M10 5h4M11 19h2" stroke-width="1.8" stroke-linecap="round"/></svg>') }}" alt="">
                        @else
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="7" y="2" width="10" height="20" rx="2" stroke-width="1.8"/><path d="M10 5h4M11 19h2" stroke-width="1.8" stroke-linecap="round"/></svg>
                        @endif
                    </div>
                @endif
                </td>
                <td class="brand-copy">
                    <h1>{{ $businessName }}</h1>
                    <div class="tagline">Mobile Phones • Accessories • {{ \App\Support\Features::wholesaleEnabled() ? 'Wholesale & Retail' : 'Retail & Repair' }}</div>
                    <div class="contact">
                        @if($businessAddress)<div>{{ $businessAddress }}</div>@endif
                        @if($businessPhone || $businessEmail)<div>{{ $businessPhone }}{{ $businessPhone && $businessEmail ? '  •  ' : '' }}{{ $businessEmail }}</div>@endif
                        @if($businessWebsite)<div>{{ $businessWebsite }}</div>@endif
                        @if($registrationNo || $tin || $vat)
                            <div>
                                @if($registrationNo) Reg: {{ $registrationNo }} @endif
                                @if($tin) {{ $registrationNo ? ' • ' : '' }}TIN: {{ $tin }} @endif
                                @if($vat) {{ ($registrationNo || $tin) ? ' • ' : '' }}VAT: {{ $vat }} @endif
                            </div>
                        @endif
                    </div>
                </td>
                </tr></tbody></table>
            </td>
            <td class="invoice-heading">
                <div class="eyebrow">{{ $sale->is_tax_invoice ? 'Tax Invoice' : 'Sales Invoice' }}</div>
                <h2>{{ \App\Support\Features::wholesaleEnabled() ? strtoupper($sale->pos_type) : 'SALE' }}</h2>
                <div class="invoice-number"># {{ $sale->invoice_no }}</div>
                @if($sale->tax_invoice_number)<div class="detail-line">Tax invoice: {{ $sale->tax_invoice_number }}</div>@endif
                <div class="status-pill {{ $statusClass }}">{{ $statusLabel }}</div>
            </td>
            </tr></tbody></table>
        </header>

        <section class="details-grid">
            <div class="detail-column"><div class="detail-card">
                <div class="detail-title">Bill To</div>
                <div class="customer-name">{{ $sale->customer->name ?? 'Walk-in Customer' }}</div>
                @if($sale->customer?->phone)<div class="detail-line">Phone: {{ $sale->customer->phone }}</div>@endif
                @if($sale->customer?->email)<div class="detail-line">Email: {{ $sale->customer->email }}</div>@endif
                @if($sale->customer?->address)<div class="detail-line">{{ $sale->customer->address }}</div>@endif
            </div></div>
            <div class="detail-column"><div class="detail-card">
                <div class="detail-title">Invoice Details</div>
                <div class="meta-grid">
                    <div class="meta-row"><span>Date</span><span>{{ $sale->created_at->format($dateFormat) }}</span></div>
                    <div class="meta-row"><span>Time</span><span>{{ $sale->created_at->format($timeFormat) }}</span></div>
                    <div class="meta-row"><span>Cashier</span><span>{{ $sale->cashier->name ?? '-' }}</span></div>
                    <div class="meta-row"><span>Sale type</span><span>{{ \App\Support\Features::wholesaleEnabled() ? ucfirst($sale->pos_type) : 'Sale' }}</span></div>
                    @if($sale->source_type)<div class="meta-row"><span>Source</span><span>{{ ucfirst($sale->source_type) }}</span></div>@endif
                </div>
            </div></div>
        </section>

        <div class="table-scroll">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Phone / Product Description</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Unit Discount</th>
                        <th class="text-right">Final Unit Price</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Final Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->items as $item)
                        @php
                            $itemGross = (float) $item->quantity * (float) $item->selling_price;
                            $itemDiscount = $itemGross - (float) $item->subtotal;
                            $unitDiscount = $item->quantity > 0 ? $itemDiscount / (float) $item->quantity : 0;
                            $finalUnitPrice = (float) $item->selling_price - $unitDiscount;
                        @endphp
                        <tr>
                            <td class="item-index">{{ $loop->iteration }}</td>
                            <td>
                                <div class="item-name">{{ $item->product->name ?? 'Product' }}</div>
                                <div class="item-meta">
                                    @if($item->product?->sku)SKU: {{ $item->product->sku }}@endif
                                    @if($item->variantLabel()){{ $item->product?->sku ? ' • ' : '' }}{{ $item->variantLabel() }}@endif
                                    @if($item->productSerial){{ ($item->product?->sku || $item->variantLabel()) ? ' • ' : '' }}IMEI / Serial: {{ $item->productSerial->serial_number }}@endif
                                </div>
                            </td>
                            <td class="text-right">{{ SystemSettings::money((float) $item->selling_price) }}</td>
                            <td class="text-right">{{ $unitDiscount > 0 ? SystemSettings::money($unitDiscount) : '-' }}</td>
                            <td class="text-right">{{ SystemSettings::money($finalUnitPrice) }}</td>
                            <td class="text-center">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                            <td class="text-right"><strong>{{ SystemSettings::money((float) $item->subtotal) }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <section class="summary-row">
            <div class="payment-summary">
                <h3>Payment Summary</h3>
                @if($hasDue)
                    <p>This invoice is issued on credit to <strong>{{ $sale->customer->name ?? 'the customer' }}</strong>.</p>
                    <p>Payment status: <strong>{{ $sale->payment_status }}</strong></p>
                @elseif($hasChequeHold)
                    <p>Cheque payment is awaiting clearance. Stock remains pending until it clears.</p>
                @else
                    <p>Payment received. Thank you for your purchase.</p>
                @endif
                @if($sale->payments->isNotEmpty())
                    <p>Last payment: <strong>{{ optional($sale->payments->sortByDesc('payment_date')->first())->payment_date }}</strong></p>
                @endif
            </div>
            <div class="totals-column"><div class="totals">
                @php
                    $totalDiscount = (float) $sale->total_amount - ((float) $sale->payable_amount - (float) $sale->tax_amount);
                @endphp
                <div class="total-row"><span>Total Amount (Before Discount)</span><span>{{ SystemSettings::money((float) $sale->total_amount) }}</span></div>
                @if($totalDiscount > 0)
                    <div class="total-row"><span>Discount Total</span><span>- {{ SystemSettings::money($totalDiscount) }}</span></div>
                @endif
                @if((float) $sale->tax_amount > 0)
                    <div class="total-row"><span>Tax</span><span>{{ SystemSettings::money((float) $sale->tax_amount) }}</span></div>
                @endif
                <div class="total-row grand-total"><span>Final Total Price</span><span>{{ SystemSettings::money((float) $sale->payable_amount) }}</span></div>
                <div class="total-row paid-total"><span>Amount Paid</span><span>{{ SystemSettings::money((float) $sale->paid_amount) }}</span></div>
                @if($hasChequeHold)
                    <div class="total-row"><span>Cheque Pending</span><span>{{ SystemSettings::money((float) $sale->cheque_hold_amount) }}</span></div>
                @endif
                @if($hasDue)
                    <div class="total-row due-total"><span>Balance Due</span><span>{{ SystemSettings::money((float) $sale->due_amount) }}</span></div>
                @endif
            </div></div>
        </section>

        @if($sale->invoicePayments->isNotEmpty())
            <section class="payments-section">
                <div class="section-heading">Payment Allocation & Tracking</div>
                <table class="payments-table">
                    <thead><tr><th>Method</th><th>Reference / Cheque Details</th><th>Status</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                        @foreach($sale->invoicePayments as $payment)
                            <tr>
                                <td>{{ $payment->payment_method === 'Customer Credit / Due' ? 'Customer Credit / Due' : $payment->payment_method }}</td>
                                <td>{{ $payment->reference_no ?: ($payment->cheque ? $payment->cheque->cheque_number.' • '.$payment->cheque->bank_name.' • '.$payment->cheque->cheque_date : '-') }}</td>
                                <td><span class="mini-status">{{ strtoupper($payment->status) }}</span></td>
                                <td class="text-right"><strong>{{ SystemSettings::money((float) $payment->amount) }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif

        <footer class="invoice-footer">
            @if($terms)
                <div class="terms"><h3>Terms & Conditions</h3>{{ $terms }}</div>
            @endif
            <div class="signatures">
                <div class="signature-line"><span>Customer Signature</span></div>
                <div class="signature-line"><span>Authorized Signature</span></div>
            </div>
            <div class="footer-bar">
                <div class="footer-message">{{ $footerMessage }}</div>
                <div>Computer-generated invoice • {{ $sale->invoice_no }}</div>
            </div>
        </footer>
    </main>

    @if(request()->boolean('print'))
        <script>
            (() => {
                const shouldReturnToPos = @json(request()->boolean('return_to_pos'));
                const posUrl = @json(route(\App\Support\Features::wholesaleEnabled() && $sale->pos_type === 'wholesale' ? 'pos.wholesale' : 'pos.retail'));
                let printFinished = false;

                const returnToPos = () => {
                    if (!shouldReturnToPos || printFinished) return;
                    printFinished = true;

                    if (window.opener && !window.opener.closed) {
                        window.opener.location.replace(posUrl);
                        window.opener.focus();
                        window.close();
                        window.setTimeout(() => {
                            if (!window.closed) window.location.replace(posUrl);
                        }, 150);
                        return;
                    }

                    window.location.replace(posUrl);
                };

                window.addEventListener('afterprint', returnToPos);
                window.addEventListener('load', () => window.setTimeout(() => window.print(), 250));
            })();
        </script>
    @endif
</body>
</html>
