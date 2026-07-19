<x-layouts::app :title="$cheque->cheque_reference">
    <div class="flex h-16 items-center justify-between border-b border-slate-200 bg-white/70 backdrop-blur-xl px-6 sticky top-0 z-10">
        <div>
            <h1 class="text-xl font-black text-slate-900 tracking-tight">{{ $cheque->cheque_reference }}</h1>
            <p class="text-xs font-semibold text-slate-500 flex items-center gap-2 mt-0.5">
                <span>Cheque #{{ $cheque->cheque_number }}</span>
                <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                <span>{{ ucfirst($cheque->cheque_type) }}</span>
            </p>
        </div>
        <span class="rounded-full bg-amber-50 border border-amber-200 px-4 py-1.5 text-xs font-black tracking-wide text-amber-700 shadow-sm">{{ ucwords(str_replace('_',' ',$cheque->display_status)) }}</span>
    </div>

    <div class="flex-1 space-y-6 overflow-y-auto bg-slate-50/50 p-6 md:p-8">
        @if(session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-700 shadow-sm flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                {{ session('success') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
            <section class="space-y-6">
                
                <!-- Cheque Information Card -->
                <div class="rounded-3xl border border-slate-200/60 bg-white p-6 md:p-8 shadow-sm">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <h2 class="text-lg font-extrabold text-slate-800">Cheque Details</h2>
                    </div>

                    <dl class="grid gap-x-6 gap-y-8 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach([
                            ['Amount', \App\Support\SystemSettings::money($cheque->amount)],
                            ['Hold Amount', \App\Support\SystemSettings::money($cheque->hold_amount)],
                            ['Cheque Date', $cheque->cheque_date?->format('Y-m-d') ?? 'N/A'],
                            ['Party', $cheque->customer?->name ?? $cheque->supplier?->name ?? $cheque->payee_name ?? '-'],
                            ['Bank', $cheque->bank_name ?: '-'],
                            ['Branch', $cheque->branch_name ?: '-'],
                            ['Account Holder', $cheque->account_holder ?: '-'],
                            ['Account Number', auth()->user()->hasPermission('cheques.view_bank_details') ? ($cheque->account_number ?: '-') : 'Protected'],
                            ['Related Document', $cheque->sale?->invoice_no ?? ($cheque->purchase?->purchase_no ?? '-')]
                        ] as $d)
                            <div class="bg-slate-50/50 rounded-2xl p-4 border border-slate-100/50">
                                <dt class="text-[10px] font-black tracking-wider uppercase text-slate-400">{{ $d[0] }}</dt>
                                <dd class="mt-1.5 text-sm font-extrabold text-slate-800">{{ $d[1] }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    @if($cheque->attachment_path)
                        <div class="mt-8 pt-6 border-t border-slate-100">
                            <a target="_blank" href="{{ asset('storage/'.$cheque->attachment_path) }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-sm font-bold text-slate-700 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                View Attachment
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Activity Timeline -->
                <div class="rounded-3xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                    <div class="bg-slate-50/80 border-b border-slate-200/60 p-5 px-6">
                        <h2 class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Activity Timeline
                        </h2>
                    </div>
                    <div class="divide-y divide-slate-100 p-2">
                        @forelse($cheque->statusLogs as $log)
                            <div class="flex gap-4 p-4 hover:bg-slate-50/50 rounded-2xl transition group">
                                <div class="mt-1 relative flex items-center justify-center w-8 h-8 rounded-full {{ $log->new_status==='passed' ? 'bg-emerald-100 text-emerald-600' : ($log->new_status==='returned' ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600') }} shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($log->new_status==='passed')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        @elseif($log->new_status==='returned')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        @endif
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-800">{{ ucwords(str_replace('_',' ',$log->action_type)) }}</p>
                                    <p class="text-sm text-slate-500 mt-0.5">{{ $log->note }}</p>
                                    <p class="mt-2 flex items-center gap-2 text-[10px] font-semibold text-slate-400 tracking-wide uppercase">
                                        <span>{{ $log->created_at?->format('Y-m-d h:i A') }}</span>
                                        <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                                        <span>{{ $log->user?->name ?? 'System' }}</span>
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-sm font-medium text-slate-400">
                                No activity recorded yet.
                            </div>
                        @endforelse
                    </div>
                </div>

            </section>

            <aside class="space-y-6">
                @if(in_array($cheque->status, ['pending','upcoming','due_today','overdue','deposited']))
                    <form method="POST" action="{{ route('cheques.pass', $cheque) }}" class="rounded-3xl border border-emerald-200/60 bg-gradient-to-b from-white to-emerald-50/30 p-6 md:p-8 shadow-sm relative overflow-hidden">
                        @csrf
                        <div class="absolute top-0 right-0 p-4 opacity-10">
                            <svg class="w-24 h-24 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                        </div>
                        <div class="relative z-10">
                            <h3 class="text-base font-black text-emerald-800 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Mark as Passed
                            </h3>
                            <p class="mt-2 text-xs font-medium text-emerald-600/80 mb-5">Confirm this cheque cleared successfully into your account.</p>
                            
                            <label class="block text-[10px] font-bold text-emerald-700 uppercase tracking-wide mb-1.5 ml-1">Cleared Date</label>
                            <input type="date" name="cleared_date" value="{{ today()->toDateString() }}" class="mb-4 w-full rounded-xl border-emerald-200 bg-white/60 focus:bg-white focus:ring-emerald-500 focus:border-emerald-500 text-sm font-medium shadow-sm">
                            
                            <label class="block text-[10px] font-bold text-emerald-700 uppercase tracking-wide mb-1.5 ml-1">Deposit Reference (Optional)</label>
                            <input name="deposit_reference" placeholder="e.g., TXN-8392" class="mb-5 w-full rounded-xl border-emerald-200 bg-white/60 focus:bg-white focus:ring-emerald-500 focus:border-emerald-500 text-sm font-medium shadow-sm">
                            
                            <button class="w-full rounded-xl bg-emerald-600 hover:bg-emerald-700 py-3 text-sm font-black text-white shadow-lg shadow-emerald-500/30 transition transform hover:-translate-y-0.5">
                                Process Cleared Cheque
                            </button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('cheques.return', $cheque) }}" class="rounded-3xl border border-rose-200/60 bg-gradient-to-b from-white to-rose-50/30 p-6 md:p-8 shadow-sm relative overflow-hidden">
                        @csrf
                        <div class="absolute top-0 right-0 p-4 opacity-10">
                            <svg class="w-24 h-24 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                        </div>
                        <div class="relative z-10">
                            <h3 class="text-base font-black text-rose-800 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Mark as Returned
                            </h3>
                            <p class="mt-2 text-xs font-medium text-rose-600/80 mb-5">Record a bounced or rejected cheque.</p>
                            
                            <label class="block text-[10px] font-bold text-rose-700 uppercase tracking-wide mb-1.5 ml-1">Return Date</label>
                            <input type="date" name="return_date" value="{{ today()->toDateString() }}" class="mb-4 w-full rounded-xl border-rose-200 bg-white/60 focus:bg-white focus:ring-rose-500 focus:border-rose-500 text-sm font-medium shadow-sm">
                            
                            <label class="block text-[10px] font-bold text-rose-700 uppercase tracking-wide mb-1.5 ml-1">Reason</label>
                            <select name="return_reason" required class="mb-4 w-full rounded-xl border-rose-200 bg-white/60 focus:bg-white focus:ring-rose-500 focus:border-rose-500 text-sm font-medium shadow-sm">
                                @foreach(['Insufficient Funds', 'Signature Mismatch', 'Account Closed', 'Payment Stopped', 'Date Error', 'Technical Error', 'Other'] as $r)
                                    <option>{{ $r }}</option>
                                @endforeach
                            </select>
                            
                            <label class="block text-[10px] font-bold text-rose-700 uppercase tracking-wide mb-1.5 ml-1">Return Charge (Optional)</label>
                            <input type="number" step="0.01" name="return_charge" placeholder="0.00" class="mb-5 w-full rounded-xl border-rose-200 bg-white/60 focus:bg-white focus:ring-rose-500 focus:border-rose-500 text-sm font-medium shadow-sm">
                            
                            <button class="w-full rounded-xl bg-rose-600 hover:bg-rose-700 py-3 text-sm font-black text-white shadow-lg shadow-rose-500/30 transition transform hover:-translate-y-0.5">
                                Process Return
                            </button>
                        </div>
                    </form>
                @endif

                @if($cheque->status === 'returned')
                    <form method="POST" enctype="multipart/form-data" action="{{ route('cheques.replace', $cheque) }}" class="rounded-3xl border border-blue-200/60 bg-gradient-to-b from-white to-blue-50/30 p-6 md:p-8 shadow-sm">
                        @csrf
                        <h3 class="text-base font-black text-blue-800 flex items-center gap-2 mb-5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            Replace Cheque
                        </h3>
                        
                        <label class="block text-[10px] font-bold text-blue-700 uppercase tracking-wide mb-1.5 ml-1">New Cheque Number</label>
                        <input name="cheque_number" required placeholder="e.g. 123456" class="mb-4 w-full rounded-xl border-blue-200 bg-white/60 focus:bg-white focus:ring-blue-500 focus:border-blue-500 text-sm shadow-sm">
                        
                        <label class="block text-[10px] font-bold text-blue-700 uppercase tracking-wide mb-1.5 ml-1">New Date</label>
                        <input type="date" name="cheque_date" required class="mb-4 w-full rounded-xl border-blue-200 bg-white/60 focus:bg-white focus:ring-blue-500 focus:border-blue-500 text-sm shadow-sm">
                        
                        <label class="block text-[10px] font-bold text-blue-700 uppercase tracking-wide mb-1.5 ml-1">Amount</label>
                        <input type="number" step=".01" name="amount" value="{{ $cheque->amount }}" required class="mb-4 w-full rounded-xl border-blue-200 bg-white/60 focus:bg-white focus:ring-blue-500 focus:border-blue-500 text-sm shadow-sm">
                        
                        <label class="block text-[10px] font-bold text-blue-700 uppercase tracking-wide mb-1.5 ml-1">Bank Name</label>
                        <input name="bank_name" value="{{ $cheque->bank_name }}" required class="mb-5 w-full rounded-xl border-blue-200 bg-white/60 focus:bg-white focus:ring-blue-500 focus:border-blue-500 text-sm shadow-sm">
                        
                        <button class="w-full rounded-xl bg-blue-600 hover:bg-blue-700 py-3 text-sm font-black text-white shadow-lg shadow-blue-500/30 transition transform hover:-translate-y-0.5">
                            Create Replacement
                        </button>
                    </form>
                @endif
            </aside>
        </div>
    </div>
</x-layouts::app>
