<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Repair Tracking</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="mx-auto flex min-h-screen max-w-3xl flex-col justify-center px-6 py-10">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="text-center">
                <h1 class="text-2xl font-black">Repair Tracking</h1>
                <p class="mt-2 text-sm text-slate-500">Enter your job number or phone number to check the latest repair status.</p>
            </div>

            <form method="GET" action="{{ route('repairs.public.track') }}" class="mt-6 flex flex-col gap-3 sm:flex-row">
                <input name="job" value="{{ $query }}" placeholder="REP-20260710-0001 or phone number" class="flex-1 rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                <button class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-black text-white hover:bg-blue-700">Track</button>
            </form>

            @if($query && !$job)
                <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800">No repair job found for that search.</div>
            @endif

            @if($job)
                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="text-lg font-black">{{ $job->job_no }}</div>
                            <div class="mt-1 text-sm font-semibold text-slate-500">{{ $job->brand }} {{ $job->model }}</div>
                        </div>
                        @include('repairs.partials.status-badge', ['status' => $job->status])
                    </div>
                    <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="rounded-xl bg-white p-4">
                            <div class="text-xs font-black uppercase text-slate-400">Customer</div>
                            <div class="mt-1 font-bold">{{ $job->customer_name }}</div>
                        </div>
                        <div class="rounded-xl bg-white p-4">
                            <div class="text-xs font-black uppercase text-slate-400">Expected Date</div>
                            <div class="mt-1 font-bold">{{ $job->expected_completion_date ?: 'To be confirmed' }}</div>
                        </div>
                    </div>
                </div>
            @endif
        </section>
    </main>
</body>
</html>
