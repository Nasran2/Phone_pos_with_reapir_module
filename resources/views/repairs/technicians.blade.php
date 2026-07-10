<x-layouts::app :title="__('Repair Technicians')">
    <div class="border-b border-slate-200 bg-white px-6 py-5">
        <h1 class="text-2xl font-black text-slate-900">Repair Technicians</h1>
        <p class="mt-1 text-sm text-slate-500">Manage bench technicians, specialization, status, commission, and workload.</p>
    </div>

    <div class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">
        <div class="mx-auto grid max-w-7xl grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="space-y-6 xl:col-span-2">
                @include('repairs.partials.flash')
                <section class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    @forelse($technicians as $technician)
                        <details class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <summary class="cursor-pointer list-none">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="text-lg font-black text-slate-900">{{ $technician->name }}</div>
                                        <div class="mt-1 text-sm font-semibold text-slate-500">{{ $technician->specialization ?: 'General repairs' }}</div>
                                    </div>
                                    <span class="rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-black text-blue-700">{{ $technician->status }}</span>
                                </div>
                                <div class="mt-4 grid grid-cols-2 gap-3">
                                    <div class="rounded-xl bg-slate-50 p-3">
                                        <div class="text-xs font-black text-slate-400">Current Jobs</div>
                                        <div class="mt-1 text-xl font-black text-slate-900">{{ $technician->current_jobs }}</div>
                                    </div>
                                    <div class="rounded-xl bg-emerald-50 p-3">
                                        <div class="text-xs font-black text-emerald-600">Completed</div>
                                        <div class="mt-1 text-xl font-black text-emerald-800">{{ $technician->completed_jobs }}</div>
                                    </div>
                                </div>
                            </summary>
                            <form method="POST" action="{{ route('repairs.technicians.update', $technician->id) }}" class="mt-5 space-y-3 border-t border-slate-100 pt-5">
                                @csrf
                                @method('PUT')
                                <input name="name" value="{{ $technician->name }}" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                                <div class="grid grid-cols-2 gap-3">
                                    <input name="phone" value="{{ $technician->phone }}" placeholder="Phone" class="rounded-xl border border-slate-200 px-4 py-3 text-sm">
                                    <input type="email" name="email" value="{{ $technician->email }}" placeholder="Email" class="rounded-xl border border-slate-200 px-4 py-3 text-sm">
                                </div>
                                <input name="specialization" value="{{ $technician->specialization }}" placeholder="Specialization" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                                <div class="grid grid-cols-2 gap-3">
                                    <select name="status" class="rounded-xl border border-slate-200 px-4 py-3 text-sm">
                                        @foreach(['Active', 'Inactive', 'On Leave'] as $status)
                                            <option value="{{ $status }}" @selected($technician->status === $status)>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" step="0.01" min="0" max="100" name="commission_rate" value="{{ $technician->commission_rate }}" placeholder="Commission %" class="rounded-xl border border-slate-200 px-4 py-3 text-sm">
                                </div>
                                <textarea name="notes" rows="2" placeholder="Notes" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">{{ $technician->notes }}</textarea>
                                <button class="rounded-xl bg-blue-600 px-4 py-3 text-sm font-black text-white hover:bg-blue-700">Update Technician</button>
                            </form>
                        </details>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-sm font-bold text-slate-400 md:col-span-2">No repair technicians yet.</div>
                    @endforelse
                </section>
            </div>

            <aside>
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-black text-slate-900">Add Technician</h2>
                    <form method="POST" action="{{ route('repairs.technicians.store') }}" class="mt-4 space-y-4">
                        @csrf
                        <input name="name" placeholder="Technician name" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                        <input name="phone" placeholder="Phone" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                        <input type="email" name="email" placeholder="Email" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                        <input name="specialization" placeholder="Specialization" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                        <select name="status" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                            <option>Active</option>
                            <option>Inactive</option>
                            <option>On Leave</option>
                        </select>
                        <input type="number" step="0.01" min="0" max="100" name="commission_rate" placeholder="Commission %" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                        <textarea name="notes" rows="3" placeholder="Notes" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"></textarea>
                        <button class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-black text-white hover:bg-blue-700">Save Technician</button>
                    </form>
                </section>
            </aside>
        </div>
    </div>
</x-layouts::app>
