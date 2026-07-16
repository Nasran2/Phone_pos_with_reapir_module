<x-layouts::app :title="__('Device Check-In')">
    <div class="border-b border-slate-200 bg-white px-6 py-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <nav class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">Dashboard / Repair Management / Device Check-In</nav>
                <h1 class="mt-2 text-2xl font-black text-slate-900">Device Check-In</h1>
                <p class="mt-1 text-sm text-slate-500">Create a repair job card with customer, device, checklist, estimate, and technician details.</p>
            </div>
            <a href="{{ route('repairs.jobs.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-extrabold text-slate-700 hover:bg-slate-50">Job Cards</a>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @include('repairs.partials.flash')

            <form method="POST" action="{{ route('repairs.jobs.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-5">
                        <h2 class="text-lg font-black text-slate-900">Customer Details</h2>
                        <p class="mt-1 text-sm text-slate-500">Select an existing customer or fill the new customer fields below.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-5 p-6 lg:grid-cols-2">
                        <div class="lg:col-span-2">
                            <label class="text-sm font-extrabold text-slate-800">Existing Customer</label>
                            <select name="customer_id" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                <option value="">Create a new customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }} / {{ $customer->phone }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-extrabold text-slate-800">Customer Name</label>
                            <input name="customer_name" value="{{ old('customer_name') }}" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="text-sm font-extrabold text-slate-800">Phone</label>
                            <input name="customer_phone" value="{{ old('customer_phone') }}" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="text-sm font-extrabold text-slate-800">WhatsApp</label>
                            <input name="customer_whatsapp" value="{{ old('customer_whatsapp') }}" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="text-sm font-extrabold text-slate-800">Email</label>
                            <input type="email" name="customer_email" value="{{ old('customer_email') }}" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="text-sm font-extrabold text-slate-800">NIC / ID</label>
                            <input name="customer_nic" value="{{ old('customer_nic') }}" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="text-sm font-extrabold text-slate-800">Address</label>
                            <input name="customer_address" value="{{ old('customer_address') }}" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-5">
                        <h2 class="text-lg font-black text-slate-900">Device Details</h2>
                        <p class="mt-1 text-sm text-slate-500">Record identity, locks, battery, accessories, and device condition at handover.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-5 p-6 lg:grid-cols-3">
                        <div>
                            <label class="text-sm font-extrabold text-slate-800">Device Type</label>
                            <select name="device_type" required class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                                @foreach($deviceTypes as $type)
                                    <option value="{{ $type }}" @selected(old('device_type') === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-extrabold text-slate-800">Brand</label>
                            <select name="brand" required class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                                @foreach($brands as $brand)
                                    <option value="{{ $brand }}" @selected(old('brand') === $brand)>{{ $brand }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-extrabold text-slate-800">Model</label>
                            <input name="model" value="{{ old('model') }}" required class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                        </div>
                        <div>
                            <label class="text-sm font-extrabold text-slate-800">IMEI 1</label>
                            <input name="imei_1" value="{{ old('imei_1') }}" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                        </div>
                        <div>
                            <label class="text-sm font-extrabold text-slate-800">IMEI 2</label>
                            <input name="imei_2" value="{{ old('imei_2') }}" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                        </div>
                        <div>
                            <label class="text-sm font-extrabold text-slate-800">Serial Number</label>
                            <input name="serial_number" value="{{ old('serial_number') }}" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                        </div>
                        @foreach(['color' => 'Color', 'storage' => 'Storage', 'ram' => 'RAM', 'carrier' => 'Carrier / Network', 'pattern_lock' => 'Pattern Lock', 'password' => 'Password', 'pin' => 'PIN'] as $field => $label)
                            <div>
                                <label class="text-sm font-extrabold text-slate-800">{{ $label }}</label>
                                <input name="{{ $field }}" value="{{ old($field) }}" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                            </div>
                        @endforeach
                        <div>
                            <label class="text-sm font-extrabold text-slate-800">Battery %</label>
                            <input type="number" min="0" max="100" name="battery_percent" value="{{ old('battery_percent') }}" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                        </div>
                        <label class="flex min-h-[54px] items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <span class="text-sm font-extrabold text-slate-800">Google Lock</span>
                            <input type="checkbox" name="google_lock" value="1" @checked(old('google_lock')) class="h-5 w-5 rounded border-slate-300 text-blue-600">
                        </label>
                        <label class="flex min-h-[54px] items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <span class="text-sm font-extrabold text-slate-800">Apple ID Lock</span>
                            <input type="checkbox" name="apple_id_lock" value="1" @checked(old('apple_id_lock')) class="h-5 w-5 rounded border-slate-300 text-blue-600">
                        </label>
                        <div class="lg:col-span-3">
                            <label class="text-sm font-extrabold text-slate-800">Accessories Received</label>
                            <textarea name="accessories_received" rows="3" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">{{ old('accessories_received') }}</textarea>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-5">
                        <h2 class="text-lg font-black text-slate-900">Checklist & Condition</h2>
                        <p class="mt-1 text-sm text-slate-500">Tap all received accessories and visible fault conditions before accepting the device.</p>
                    </div>
                    <div class="space-y-6 p-6">
                        <div>
                            <h3 class="text-sm font-black text-slate-800">Received / Tested Items</h3>
                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                @foreach($checklistItems as $item)
                                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-bold text-slate-700">
                                        <input type="checkbox" name="condition_checklist[]" value="{{ $item }}" @checked(in_array($item, old('condition_checklist', []), true)) class="h-4 w-4 rounded border-slate-300 text-blue-600">
                                        <span>{{ $item }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-800">Physical Condition</h3>
                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                @foreach($physicalConditions as $condition)
                                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-bold text-slate-700">
                                        <input type="checkbox" name="physical_condition[]" value="{{ $condition }}" @checked(in_array($condition, old('physical_condition', []), true)) class="h-4 w-4 rounded border-slate-300 text-blue-600">
                                        <span>{{ $condition }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-extrabold text-slate-800">Custom Checklist Notes</label>
                            <textarea name="custom_checklist" rows="3" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">{{ old('custom_checklist') }}</textarea>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-5">
                        <h2 class="text-lg font-black text-slate-900">Repair Intake</h2>
                        <p class="mt-1 text-sm text-slate-500">Set complaint, target date, estimate, assignment, warranty flag, and photos.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-5 p-6 lg:grid-cols-2">
                        <div class="lg:col-span-2">
                            <label class="text-sm font-extrabold text-slate-800">Problem Description</label>
                            <textarea name="problem_description" required rows="4" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">{{ old('problem_description') }}</textarea>
                        </div>
                        <div>
                            <label class="text-sm font-extrabold text-slate-800">Customer Request</label>
                            <textarea name="customer_request" rows="3" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">{{ old('customer_request') }}</textarea>
                        </div>
                        <div>
                            <label class="text-sm font-extrabold text-slate-800">Internal Repair Notes</label>
                            <textarea name="repair_notes" rows="3" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">{{ old('repair_notes') }}</textarea>
                        </div>
                        <div>
                            <label class="text-sm font-extrabold text-slate-800">Store</label>
                            <select name="store_id" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                                <option value="">Use default store</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}" @selected(old('store_id') == $store->id)>{{ $store->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-extrabold text-slate-800">Technician</label>
                            <select name="technician_id" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                                <option value="">Assign later</option>
                                @foreach($technicians as $technician)
                                    <option value="{{ $technician->id }}" @selected(old('technician_id') == $technician->id)>{{ $technician->name }} / {{ $technician->specialization }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-extrabold text-slate-800">Estimated Cost</label>
                            <input type="number" step="any" min="0" name="estimated_cost" value="{{ old('estimated_cost') }}" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                        </div>
                        <div>
                            <label class="text-sm font-extrabold text-slate-800">Expected Completion Date</label>
                            <input type="date" name="expected_completion_date" value="{{ old('expected_completion_date') }}" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                        </div>
                        <label class="flex min-h-[54px] items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <span class="text-sm font-extrabold text-slate-800">Urgent Repair</span>
                            <input type="checkbox" name="urgent" value="1" @checked(old('urgent')) class="h-5 w-5 rounded border-slate-300 text-blue-600">
                        </label>
                        <label class="flex min-h-[54px] items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <span class="text-sm font-extrabold text-slate-800">Warranty Repair</span>
                            <input type="checkbox" name="is_warranty" value="1" @checked(old('is_warranty')) class="h-5 w-5 rounded border-slate-300 text-blue-600">
                        </label>
                        <div class="lg:col-span-2">
                            <h3 class="text-sm font-black text-slate-800">Device Photos</h3>
                            <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-4">
                                @foreach(['front' => 'Front', 'back' => 'Back', 'issue' => 'Issue Area', 'accessories' => 'Accessories'] as $key => $label)
                                    <label class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
                                        <span class="text-xs font-black uppercase tracking-wide text-slate-500">{{ $label }}</span>
                                        <input type="file" name="photos[{{ $key }}]" accept="image/*" class="mt-3 block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:text-xs file:font-bold file:text-white">
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>

                <div class="sticky bottom-0 z-10 rounded-2xl border border-slate-200 bg-white/95 px-5 py-4 shadow-lg backdrop-blur">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-sm font-semibold text-slate-500">Saving creates a job number, barcode, public tracking link, and first status log.</div>
                        <button class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-extrabold text-white shadow-lg shadow-blue-600/20 hover:bg-blue-700">Create Repair Job</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
