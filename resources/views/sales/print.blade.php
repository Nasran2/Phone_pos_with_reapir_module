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
            color: var(--ink);
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
            background: linear-gradient(90deg, var(--brand-dark), var(--brand) 62%, #38bdf8);
        }
        .invoice-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 28px;
            padding: 4px 0 21px;
            border-bottom: 1px solid var(--line);
        }
        .brand { display: flex; align-items: flex-start; gap: 13px; min-width: 0; }
        .brand-logo, .brand-mark {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            flex: 0 0 auto;
        }
        .brand-logo { object-fit: contain; border: 1px solid var(--line); padding: 4px; }
        .brand-mark {
            display: grid;
            place-items: center;
            color: #fff;
            background: linear-gradient(145deg, var(--brand-dark), var(--brand));
            box-shadow: 0 7px 17px rgba(21, 94, 239, .24);
        }
        .brand-mark svg { width: 28px; height: 28px; }
        .brand-copy h1 { margin: 0 0 3px; font-size: 22px; line-height: 1.15; letter-spacing: -.45px; }
        .brand-copy .tagline { color: var(--brand); font-size: 9px; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase; }
        .contact { margin-top: 8px; color: var(--muted); font-size: 10px; line-height: 1.55; max-width: 390px; }
        .invoice-heading { text-align: right; flex: 0 0 245px; }
        .invoice-heading .eyebrow { color: var(--muted); font-size: 10px; font-weight: 800; letter-spacing: 1.6px; text-transform: uppercase; }
        .invoice-heading h2 { margin: 4px 0 6px; color: var(--brand-dark); font-size: 24px; line-height: 1; letter-spacing: -.4px; }
        .invoice-number { font-size: 11px; font-weight: 800; overflow-wrap: anywhere; }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 10px;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: .8px;
        }
        .status-pill::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
        .status-paid { color: var(--success); background: #ecfdf3; border: 1px solid #abefc6; }
        .status-due { color: var(--danger); background: #fef3f2; border: 1px solid #fecdca; }
        .status-pending { color: var(--warning); background: #fffaeb; border: 1px solid #fedf89; }
        .status-cancelled { color: #475467; background: #f2f4f7; border: 1px solid #d0d5dd; }

        .details-grid {
            display: grid;
            grid-template-columns: 1.2fr .8fr;
            gap: 12px;
            margin: 16px 0 18px;
        }
        .detail-card { min-height: 90px; padding: 12px 14px; border: 1px solid var(--line); border-radius: 10px; background: var(--soft); }
        .detail-title { margin-bottom: 7px; color: var(--muted); font-size: 8px; font-weight: 900; letter-spacing: 1.1px; text-transform: uppercase; }
        .customer-name { font-size: 14px; font-weight: 800; }
        .detail-line { margin-top: 3px; color: #475569; font-size: 10px; }
        .meta-grid { display: grid; grid-template-columns: auto 1fr; gap: 4px 13px; }
        .meta-grid span:nth-child(odd) { color: var(--muted); }
        .meta-grid span:nth-child(even) { text-align: right; font-weight: 700; }

        .items-table, .payments-table { width: 100%; border-collapse: collapse; }
        .items-table { margin-bottom: 17px; }
        th {
            padding: 9px 8px;
            color: #fff;
            background: var(--brand-dark);
            font-size: 8px;
            font-weight: 800;
            letter-spacing: .7px;
            text-align: left;
            text-transform: uppercase;
        }
        th:first-child { border-radius: 7px 0 0 7px; }
        th:last-child { border-radius: 0 7px 7px 0; }
        td { padding: 10px 8px; border-bottom: 1px solid var(--line); vertical-align: top; }
        tbody tr { break-inside: avoid; page-break-inside: avoid; }
        .item-index { width: 28px; color: var(--muted); }
        .item-name { font-weight: 800; font-size: 11px; }
        .item-meta { margin-top: 3px; color: var(--muted); font-size: 8.5px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; white-space: nowrap; }

        .summary-row { display: grid; grid-template-columns: 1fr 292px; gap: 24px; align-items: start; break-inside: avoid; page-break-inside: avoid; }
        .payment-summary h3, .terms h3 { margin: 0 0 7px; color: var(--brand-dark); font-size: 9px; letter-spacing: .8px; text-transform: uppercase; }
        .payment-summary p { margin: 0 0 5px; color: var(--muted); font-size: 9px; }
        .payment-summary strong { color: var(--ink); }
        .totals { border: 1px solid var(--line); border-radius: 10px; overflow: hidden; }
        .total-row { display: flex; justify-content: space-between; gap: 14px; padding: 8px 12px; border-bottom: 1px solid var(--line); }
        .total-row:last-child { border-bottom: 0; }
        .total-row span:first-child { color: var(--muted); }
        .total-row span:last-child { font-weight: 800; white-space: nowrap; }
        .grand-total { padding: 11px 12px; color: #fff; background: var(--brand-dark); font-size: 14px; }
        .grand-total span:first-child { color: #dbeafe; }
        .due-total { color: var(--danger); background: #fef3f2; font-size: 13px; font-weight: 900; }
        .paid-total { color: var(--success); background: #ecfdf3; }

        .payments-section { margin-top: 20px; break-inside: avoid; page-break-inside: avoid; }
        .section-heading { display: flex; align-items: center; gap: 9px; margin-bottom: 8px; color: var(--brand-dark); font-size: 9px; font-weight: 900; letter-spacing: .9px; text-transform: uppercase; }
        .section-heading::after { content: ''; height: 1px; flex: 1; background: var(--line); }
        .payments-table th { padding: 7px 8px; background: #eaf1fb; color: #344054; }
        .payments-table td { padding: 8px; font-size: 9px; }
        .mini-status { padding: 3px 7px; border-radius: 999px; background: #eef2f6; color: #475467; font-size: 7.5px; font-weight: 900; text-transform: uppercase; }

        .invoice-footer { margin-top: auto; padding-top: 22px; break-inside: avoid; page-break-inside: avoid; }
        .terms { padding: 10px 12px; border-radius: 8px; background: var(--soft); color: var(--muted); font-size: 8.5px; white-space: pre-line; }
        .signatures { display: grid; grid-template-columns: repeat(2, 150px); justify-content: space-between; margin: 30px 18px 21px; text-align: center; }
        .signature-line { padding-top: 6px; border-top: 1px solid #94a3b8; color: var(--muted); font-size: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; }
        .footer-bar { display: flex; justify-content: space-between; gap: 20px; padding-top: 10px; border-top: 1px solid var(--line); color: var(--muted); font-size: 8px; }
        .footer-message { color: var(--brand-dark); font-weight: 800; }

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
            background: var(--brand-dark);
            border: 0;
            border-radius: 9px;
            box-shadow: 0 8px 25px rgba(15, 23, 42, .24);
            cursor: pointer;
            font-weight: 800;
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
            .details-grid { grid-template-columns: 1fr; }
            .items-table { min-width: 620px; }
            .table-scroll { overflow-x: auto; }
            .print-btn { right: 12px; top: 12px; }
        }
    </style>
</head>
<body>
    <button class="no-print print-btn" onclick="window.print()" aria-label="Print A4 invoice">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2m-2-5H8v8h8v-8Z"/></svg>
        Print A4 Invoice
    </button>

    <main class="invoice-sheet">
        <div class="brand-stripe"></div>

        <header class="invoice-header">
            <div class="brand">
                @if($showLogo && $businessLogo)
                    <img class="brand-logo" src="{{ asset('storage/'.$businessLogo) }}" alt="{{ $businessName }} logo">
                @else
                    <div class="brand-mark" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="7" y="2" width="10" height="20" rx="2" stroke-width="1.8"/><path d="M10 5h4M11 19h2" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </div>
                @endif
                <div class="brand-copy">
                    <h1>{{ $businessName }}</h1>
                    <div class="tagline">Mobile Phones • Accessories • Wholesale & Retail</div>
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
                </div>
            </div>
            <div class="invoice-heading">
                <div class="eyebrow">{{ $sale->is_tax_invoice ? 'Tax Invoice' : 'Sales Invoice' }}</div>
                <h2>{{ strtoupper($sale->pos_type) }}</h2>
                <div class="invoice-number"># {{ $sale->invoice_no }}</div>
                @if($sale->tax_invoice_number)<div class="detail-line">Tax invoice: {{ $sale->tax_invoice_number }}</div>@endif
                <div class="status-pill {{ $statusClass }}">{{ $statusLabel }}</div>
            </div>
        </header>

        <section class="details-grid">
            <div class="detail-card">
                <div class="detail-title">Bill To</div>
                <div class="customer-name">{{ $sale->customer->name ?? 'Walk-in Customer' }}</div>
                @if($sale->customer?->phone)<div class="detail-line">Phone: {{ $sale->customer->phone }}</div>@endif
                @if($sale->customer?->email)<div class="detail-line">Email: {{ $sale->customer->email }}</div>@endif
                @if($sale->customer?->address)<div class="detail-line">{{ $sale->customer->address }}</div>@endif
            </div>
            <div class="detail-card">
                <div class="detail-title">Invoice Details</div>
                <div class="meta-grid">
                    <span>Date</span><span>{{ $sale->created_at->format($dateFormat) }}</span>
                    <span>Time</span><span>{{ $sale->created_at->format($timeFormat) }}</span>
                    <span>Cashier</span><span>{{ $sale->cashier->name ?? '-' }}</span>
                    <span>Sale type</span><span>{{ ucfirst($sale->pos_type) }}</span>
                    @if($sale->source_type)<span>Source</span><span>{{ ucfirst($sale->source_type) }}</span>@endif
                </div>
            </div>
        </section>

        <div class="table-scroll">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Phone / Product Description</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->items as $item)
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
                            <td class="text-center">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                            <td class="text-right">{{ SystemSettings::money((float) ((float) $item->unit_price > 0 ? $item->unit_price : $item->selling_price)) }}</td>
                            <td class="text-right"><strong>{{ SystemSettings::money((float) ((float) $item->line_total > 0 ? $item->line_total : $item->subtotal)) }}</strong></td>
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
            <div class="totals">
                <div class="total-row"><span>Subtotal</span><span>{{ SystemSettings::money((float) $sale->total_amount) }}</span></div>
                @if((float) $sale->discount_amount > 0)
                    <div class="total-row"><span>Discount</span><span>- {{ SystemSettings::money((float) $sale->discount_amount) }}</span></div>
                @endif
                @if((float) $sale->tax_amount > 0)
                    <div class="total-row"><span>Tax</span><span>{{ SystemSettings::money((float) $sale->tax_amount) }}</span></div>
                @endif
                <div class="total-row grand-total"><span>Grand Total</span><span>{{ SystemSettings::money((float) $sale->payable_amount) }}</span></div>
                <div class="total-row paid-total"><span>Amount Paid</span><span>{{ SystemSettings::money((float) $sale->paid_amount) }}</span></div>
                @if($hasChequeHold)
                    <div class="total-row"><span>Cheque Pending</span><span>{{ SystemSettings::money((float) $sale->cheque_hold_amount) }}</span></div>
                @endif
                @if($hasDue)
                    <div class="total-row due-total"><span>Balance Due</span><span>{{ SystemSettings::money((float) $sale->due_amount) }}</span></div>
                @endif
            </div>
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
                <div class="signature-line">Customer Signature</div>
                <div class="signature-line">Authorized Signature</div>
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
                const posUrl = @json(route($sale->pos_type === 'wholesale' ? 'pos.wholesale' : 'pos.retail'));
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
