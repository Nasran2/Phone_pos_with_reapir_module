<x-layouts::app :title="__('Repair Settings')">
    <div class="border-b border-slate-200 bg-white px-6 py-5">
        <h1 class="text-2xl font-black text-slate-900">Repair Settings</h1>
        <p class="mt-1 text-sm text-slate-500">Default warranty, labor, notification, and repair terms settings.</p>
    </div>

    <div class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">
        <div class="mx-auto max-w-4xl space-y-6">
            @include('repairs.partials.flash')
            <form method="POST" action="{{ route('repairs.settings') }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                @csrf
                <div class="grid grid-cols-1 gap-5 p-6 lg:grid-cols-2">
                    <div>
                        <label class="text-sm font-extrabold text-slate-800">Default Warranty Days</label>
                        <p class="mt-1 text-xs font-semibold text-slate-500">Used when issuing repair warranties after delivery.</p>
                        <input type="number" min="0" name="default_warranty_days" value="{{ old('default_warranty_days', $settings['repair_default_warranty_days'] ?? 30) }}" class="mt-3 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                    </div>
                    <div>
                        <label class="text-sm font-extrabold text-slate-800">Default Labor Charge</label>
                        <p class="mt-1 text-xs font-semibold text-slate-500">Suggested base charge for jobs that do not use parts.</p>
                        <input type="number" step="any" min="0" name="default_labor_charge" value="{{ old('default_labor_charge', $settings['repair_default_labor_charge'] ?? 0) }}" class="mt-3 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                    </div>
                    @foreach([
                        'notify_checkin' => 'Notify on Check-In',
                        'notify_ready' => 'Notify When Ready',
                        'notify_delivered' => 'Notify on Delivered',
                    ] as $key => $label)
                        <label class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <span>
                                <span class="block text-sm font-extrabold text-slate-800">{{ $label }}</span>
                                <span class="mt-1 block text-xs font-semibold text-slate-500">Queue customer notification records for this event.</span>
                            </span>
                            <input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, ($settings['repair_' . $key] ?? '1')) == '1') class="h-5 w-5 rounded border-slate-300 text-blue-600">
                        </label>
                    @endforeach
                    <div class="lg:col-span-2">
                        <label class="text-sm font-extrabold text-slate-800">Repair Terms</label>
                        <p class="mt-1 text-xs font-semibold text-slate-500">Shown on printouts and customer handover notes.</p>
                        <textarea name="repair_terms" rows="5" class="mt-3 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">{{ old('repair_terms', $settings['repair_repair_terms'] ?? 'Customer must collect repaired device with job card and valid ID. Warranty applies only to the repaired fault unless separately stated.') }}</textarea>
                    </div>
                </div>
                <div class="border-t border-slate-100 bg-slate-50 px-6 py-4 text-right">
                    <button class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-black text-white hover:bg-blue-700">Save Repair Settings</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
