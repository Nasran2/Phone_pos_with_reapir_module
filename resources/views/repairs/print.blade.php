<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Repair Job Card - {{ $job->job_no }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { size: A4; margin: 10mm; }
            .no-print { display: none !important; }
        }
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    </style>
    <!-- Include Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen p-8 print:p-0 print:bg-white flex justify-center">
    
    <main class="w-[210mm] min-h-[297mm] bg-white p-[15mm] shadow-lg print:shadow-none mx-auto relative">
        
        <!-- Print Button (Hidden in print) -->
        <div class="absolute top-4 right-4 no-print">
            <button onclick="window.print()" class="px-4 py-2 bg-slate-800 text-white rounded-lg text-sm font-bold shadow-sm hover:bg-slate-900 flex items-center gap-2">
                <i data-lucide="printer" class="w-4 h-4"></i> Print Job Card
            </button>
        </div>

        <!-- Header -->
        <div class="flex items-start justify-between border-b-2 border-slate-900 pb-6 mb-6">
            <div>
                <h1 class="text-3xl font-black text-slate-900 uppercase tracking-tighter">Repair Job Card</h1>
                <div class="mt-2 text-sm font-bold text-slate-500 uppercase tracking-widest">
                    {{ \App\Support\SystemSettings::get('system_name', 'Phone Shop POS', 'business_settings') }}
                </div>
                <div class="text-xs text-slate-500 mt-1">
                    {{ \App\Support\SystemSettings::get('business_address', '123 Main St, City', 'business_settings') }}<br>
                    Tel: {{ \App\Support\SystemSettings::get('business_phone', '011-2345678', 'business_settings') }}
                </div>
            </div>
            <div class="text-right">
                <img src="https://barcode.tec-it.com/barcode.ashx?data={{ $job->job_no }}&code=Code128&translate-esc=on" alt="Barcode" class="h-12 mb-2 object-contain ml-auto">
                <div class="text-xl font-black tracking-tight">{{ $job->job_no }}</div>
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mt-1">{{ \Carbon\Carbon::parse($job->created_at)->format('Y-m-d h:i A') }}</div>
            </div>
        </div>

        <!-- Customer & Device Info -->
        <div class="grid grid-cols-2 gap-6 mb-6">
            <div class="border border-slate-200 rounded-lg p-4">
                <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3 flex items-center gap-2">
                    <i data-lucide="user" class="w-3 h-3"></i> Customer Details
                </h3>
                <div class="font-black text-base">{{ $job->customer_name }}</div>
                <div class="text-sm font-bold text-slate-600 mt-1">{{ $job->customer_phone }}</div>
                <div class="text-xs text-slate-500 mt-1">{{ $job->customer_email ?? '' }}</div>
            </div>

            <div class="border border-slate-200 rounded-lg p-4 bg-slate-50">
                <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3 flex items-center gap-2">
                    <i data-lucide="smartphone" class="w-3 h-3"></i> Device Details
                </h3>
                <div class="font-black text-base">{{ $job->device->brand ?? '' }} {{ $job->device->model ?? '' }}</div>
                <div class="grid grid-cols-2 gap-2 mt-2 text-xs">
                    <div><span class="font-bold text-slate-500">IMEI:</span> <span class="font-bold">{{ $job->device->imei_1 ?? '-' }}</span></div>
                    <div><span class="font-bold text-slate-500">Serial:</span> <span class="font-bold">{{ $job->device->serial_number ?? '-' }}</span></div>
                    <div><span class="font-bold text-slate-500">Color:</span> <span class="font-bold">{{ $job->device->color ?? '-' }}</span></div>
                    <div><span class="font-bold text-slate-500">Pass:</span> <span class="font-bold">{{ $job->device->password ?? $job->device->pin ?? '-' }}</span></div>
                </div>
            </div>
        </div>

        <!-- Problem & Request -->
        <div class="mb-6 border-l-4 border-slate-900 pl-4 py-1">
            <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Customer Complaint</h3>
            <p class="text-sm font-bold text-slate-900">{{ $job->problem_description }}</p>
            
            @if($job->customer_request)
            <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1 mt-3">Customer Request</h3>
            <p class="text-sm font-medium text-slate-700">{{ $job->customer_request }}</p>
            @endif
        </div>

        <!-- Intake Checklist & Physical Condition -->
        @php
            $checklistItems = $job->checklist && $job->checklist->items ? json_decode($job->checklist->items, true) : [];
            $physicalCondition = $job->device && $job->device->physical_condition ? json_decode($job->device->physical_condition, true) : [];
        @endphp
        
        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 border-b border-slate-200 pb-2">Checklist & Accessories</h3>
                <div class="flex flex-wrap gap-1.5 mt-3">
                    @forelse($checklistItems as $item)
                        <span class="px-2 py-0.5 bg-slate-100 text-[10px] font-bold text-slate-600 rounded">{{ $item }}</span>
                    @empty
                        <span class="text-xs text-slate-400 italic">None checked</span>
                    @endforelse
                </div>
                <div class="mt-3 text-xs font-bold text-slate-600">
                    <span class="text-slate-400 uppercase tracking-wider text-[10px]">Included:</span> {{ $job->device->accessories_received ?? 'Device Only' }}
                </div>
            </div>
            <div>
                <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 border-b border-slate-200 pb-2">Physical Condition</h3>
                <div class="flex flex-wrap gap-1.5 mt-3">
                    @forelse($physicalCondition as $cond)
                        <span class="px-2 py-0.5 bg-slate-100 text-[10px] font-bold text-slate-600 rounded">{{ $cond }}</span>
                    @empty
                        <span class="text-xs text-slate-400 italic">No damage reported</span>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Job Details Table -->
        <table class="w-full text-sm mb-6 border border-slate-200">
            <tr class="bg-slate-50 border-b border-slate-200">
                <th class="px-4 py-2 text-left text-[10px] font-black uppercase tracking-widest text-slate-500">Technician</th>
                <th class="px-4 py-2 text-left text-[10px] font-black uppercase tracking-widest text-slate-500">Expected Date</th>
                <th class="px-4 py-2 text-left text-[10px] font-black uppercase tracking-widest text-slate-500">Priority</th>
                <th class="px-4 py-2 text-left text-[10px] font-black uppercase tracking-widest text-slate-500">Warranty</th>
            </tr>
            <tr>
                <td class="px-4 py-3 font-bold">{{ $job->technician_name ?? 'Unassigned' }}</td>
                <td class="px-4 py-3 font-bold">{{ $job->expected_completion_date ? \Carbon\Carbon::parse($job->expected_completion_date)->format('Y-m-d') : 'TBD' }}</td>
                <td class="px-4 py-3 font-bold">{{ $job->urgent ? 'Urgent' : 'Normal' }}</td>
                <td class="px-4 py-3 font-bold">{{ $job->is_warranty ? 'Yes' : 'No' }}</td>
            </tr>
        </table>

        <!-- Financial Summary -->
        <div class="flex justify-end mb-8">
            <div class="w-1/2">
                <table class="w-full text-sm">
                    <tr>
                        <td class="py-1.5 text-slate-500 font-bold uppercase tracking-wider text-[10px]">Estimated Total:</td>
                        <td class="py-1.5 text-right font-black text-lg">{{ \App\Support\SystemSettings::money((float) $job->total_amount ?: $job->estimated_cost) }}</td>
                    </tr>
                    <tr class="border-b border-slate-200">
                        <td class="py-1.5 text-slate-500 font-bold uppercase tracking-wider text-[10px]">Advance Paid:</td>
                        <td class="py-1.5 text-right font-black text-emerald-600">{{ \App\Support\SystemSettings::money((float) $job->paid_amount) }}</td>
                    </tr>
                    <tr>
                        <td class="py-3 text-slate-900 font-black uppercase tracking-widest text-xs">Balance Due:</td>
                        <td class="py-3 text-right font-black text-xl text-red-600">{{ \App\Support\SystemSettings::money((float) max(0, ($job->total_amount ?: $job->estimated_cost) - $job->paid_amount)) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Terms and Conditions -->
        <div class="text-[10px] text-slate-500 leading-relaxed mb-12 border-t border-slate-200 pt-6">
            <p class="font-bold uppercase tracking-widest mb-2 text-slate-900">Terms & Conditions</p>
            <ol class="list-decimal pl-4 space-y-1">
                <li>This receipt must be presented when collecting the device.</li>
                <li>Devices left for over 30 days after repair completion will be disposed of.</li>
                <li>We are not responsible for any data loss during the repair process. Please backup your data.</li>
                <li>Warranty covers only the repaired part and labor for the specified period. Physical/Water damage voids warranty.</li>
                <li>The estimated cost is subject to change if additional hidden faults are discovered during repair.</li>
            </ol>
        </div>

        <!-- Signatures -->
        <div class="grid grid-cols-2 gap-12 mt-auto">
            <div>
                <div class="border-t border-slate-400 pt-2 text-center text-xs font-bold uppercase tracking-wider text-slate-500">Customer Signature</div>
            </div>
            <div>
                <div class="border-t border-slate-400 pt-2 text-center text-xs font-bold uppercase tracking-wider text-slate-500">Authorized Signature</div>
            </div>
        </div>

    </main>

    <script>
        lucide.createIcons();
        // window.print();
    </script>
</body>
</html>
