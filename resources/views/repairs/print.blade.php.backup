<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $job->job_no }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white p-8 text-slate-900">
    <main class="mx-auto max-w-3xl">
        <div class="flex items-start justify-between border-b border-slate-200 pb-5">
            <div>
                <h1 class="text-2xl font-black">Repair Job Card</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $job->job_no }}</p>
            </div>
            <div class="text-right text-sm">
                <div class="font-black">{{ \App\Support\SystemSettings::get('system_name', 'Phone Shop POS', 'business_settings') }}</div>
                <div class="text-slate-500">{{ now()->format('Y-m-d h:i A') }}</div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-4 text-sm">
            <div class="rounded-xl border border-slate-200 p-4">
                <div class="text-xs font-black uppercase text-slate-400">Customer</div>
                <div class="mt-2 font-black">{{ $job->customer_name }}</div>
                <div>{{ $job->customer_phone }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 p-4">
                <div class="text-xs font-black uppercase text-slate-400">Device</div>
                <div class="mt-2 font-black">{{ $job->brand }} {{ $job->model }}</div>
                <div>{{ $job->imei_1 ?: $job->serial_number }}</div>
            </div>
            <div class="col-span-2 rounded-xl border border-slate-200 p-4">
                <div class="text-xs font-black uppercase text-slate-400">Problem</div>
                <div class="mt-2">{{ $job->problem_description }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 p-4">
                <div class="text-xs font-black uppercase text-slate-400">Status</div>
                <div class="mt-2 font-black">{{ $job->status }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 p-4">
                <div class="text-xs font-black uppercase text-slate-400">Estimate</div>
                <div class="mt-2 font-black">{{ \App\Support\SystemSettings::money((float) $job->estimated_cost) }}</div>
            </div>
        </div>

        <div class="mt-10 grid grid-cols-2 gap-8 text-sm">
            <div class="border-t border-slate-300 pt-3">Customer Signature</div>
            <div class="border-t border-slate-300 pt-3">Received By</div>
        </div>
    </main>
    <script>window.print();</script>
</body>
</html>
