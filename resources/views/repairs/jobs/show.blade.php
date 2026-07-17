@php
    $currencySymbol = \App\Support\SystemSettings::currencySymbol();
    $decimalPlaces = (int) \App\Support\SystemSettings::number('decimal_places', 2, 'business_settings');
    $defaultStoreId = \App\Support\SystemSettings::number('default_store_id', 1, 'business_settings');
@endphp

<x-layouts::app :title="'Repair Job ' . $job->job_no">
    <!-- Alpine Data wrapper -->
    <div x-data="repairWorkspace()" x-init="initData()" class="relative pb-28 min-h-[calc(100vh-4rem)] bg-slate-50">

        <!-- Top Header & Breadcrumb -->
        <div class="bg-white border-b border-slate-200 px-6 py-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <div class="text-xs text-slate-500 font-medium mb-1 flex items-center gap-2">
                    <a href="{{ route('dashboard') }}" class="hover:text-blue-600 transition-colors">Dashboard</a> &rsaquo;
                    <span class="text-slate-400">Repair Management</span> &rsaquo;
                    <a href="{{ route('repairs.jobs.index') }}" class="hover:text-blue-600 transition-colors">Repair Job Cards</a> &rsaquo;
                    <span class="text-slate-900 font-bold">{{ $job->job_no }}</span>
                </div>
                <h1 class="text-2xl font-black text-slate-900 flex items-center gap-3">
                    Repair Job
                    <span class="px-2.5 py-1 bg-slate-100 border border-slate-200 rounded-md text-sm font-bold text-slate-700">
                        {{ $job->job_no }}
                    </span>
                    <span x-text="currentStatus" class="px-3 py-1.5 text-xs font-black uppercase tracking-wider rounded-md"
                          :class="{
                              'bg-blue-100 text-blue-700 border border-blue-200': ['Under Repair', 'Repairing', 'Quality Check'].includes(currentStatus),
                              'bg-green-100 text-green-700 border border-green-200': ['Completed', 'Ready for Pickup', 'Delivered'].includes(currentStatus),
                              'bg-orange-100 text-orange-700 border border-orange-200': ['Waiting Parts', 'Waiting Approval'].includes(currentStatus),
                              'bg-slate-100 text-slate-700 border border-slate-200': !['Under Repair', 'Repairing', 'Quality Check', 'Completed', 'Ready for Pickup', 'Delivered', 'Waiting Parts', 'Waiting Approval'].includes(currentStatus)
                          }">
                    </span>
                </h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('repairs.jobs.print', $job->id) }}" target="_blank" class="px-5 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-50 flex items-center justify-center transition-colors shadow-sm">
                    Print Job Card
                </a>
                <a x-show="['Completed', 'Ready for Pickup', 'Delivered'].includes(currentStatus)" style="display: none;" href="{{ route('repairs.jobs.invoice', $job->id) }}" target="_blank" class="px-5 py-2.5 bg-white border border-emerald-200 rounded-xl text-sm font-bold text-emerald-700 hover:bg-emerald-50 flex items-center justify-center transition-colors shadow-sm">
                    Print Bill
                </a>
                <a href="{{ route('repairs.jobs.index') }}" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white border border-slate-800 rounded-xl text-sm font-bold transition-colors shadow-sm">
                    All Jobs
                </a>
            </div>
        </div>

        <!-- Progress Status Bar -->
        <div class="bg-white border-b border-slate-200 px-6 py-6 overflow-x-auto scrollbar-hide shadow-sm">
            <div class="flex items-center min-w-[900px] justify-between relative px-4">
                <!-- Status steps -->
                <template x-for="(step, index) in steps" :key="step">
                    <div class="flex flex-col items-center relative z-10 cursor-pointer group" @click="promptStatusChange(step)">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300 outline outline-4 outline-white"
                             :class="{
                                 'bg-blue-600 text-white shadow-lg scale-110': currentStatus === step,
                                 'bg-green-500 text-white shadow-md': steps.indexOf(currentStatus) > index,
                                 'bg-slate-100 text-slate-400 border border-slate-300': steps.indexOf(currentStatus) < index
                             }">
                            <span x-show="steps.indexOf(currentStatus) > index"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M20 6 9 17l-5-5"/></svg></span>
                            <span x-show="steps.indexOf(currentStatus) <= index" x-text="index + 1"></span>
                        </div>
                        <span class="mt-3 text-[11px] uppercase tracking-wider font-bold text-center w-24"
                              :class="{
                                  'text-blue-700': currentStatus === step,
                                  'text-green-600': steps.indexOf(currentStatus) > index,
                                  'text-slate-400': steps.indexOf(currentStatus) < index
                              }"
                              x-text="step"></span>
                    </div>
                </template>
                <!-- Connecting lines rendered via absolute div behind -->
                <div class="absolute top-5 left-8 right-8 h-1 bg-slate-100 rounded-full -z-0"></div>
                <div class="absolute top-5 left-8 h-1 bg-green-500 rounded-full transition-all duration-500 -z-0"
                     :style="'width: ' + (steps.indexOf(currentStatus) / (steps.length - 1) * 100) + '%'"></div>
            </div>
        </div>

        <!-- Live Financial Summary -->
        <div class="bg-slate-50 border-b border-slate-200 px-6 py-4">
            <div class="max-w-[1600px] mx-auto grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3">
                <div class="rounded-xl border border-blue-100 bg-blue-50 p-4">
                    <div class="text-[10px] font-black uppercase tracking-widest text-blue-600">Expected Amount</div>
                    <div class="mt-1 text-lg font-black text-blue-900">{{ \App\Support\SystemSettings::money((float) $job->estimated_cost) }}</div>
                </div>
                <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-4">
                    <div class="text-[10px] font-black uppercase tracking-widest text-emerald-600">Actual Repair Amount</div>
                    <div class="mt-1 text-lg font-black text-emerald-900" x-text="formatMoney(grandTotal)"></div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <div class="text-[10px] font-black uppercase tracking-widest text-slate-500">Paid Amount</div>
                    <div class="mt-1 text-lg font-black text-slate-900" x-text="formatMoney(paidAmount)"></div>
                </div>
                <div class="rounded-xl border border-red-100 bg-red-50 p-4">
                    <div class="text-[10px] font-black uppercase tracking-widest text-red-600">Balance Amount</div>
                    <div class="mt-1 text-lg font-black text-red-900" x-text="formatMoney(Math.max(0, grandTotal - paidAmount))"></div>
                </div>
                <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4">
                    <div class="text-[10px] font-black uppercase tracking-widest text-indigo-600">Parts Total</div>
                    <div class="mt-1 text-lg font-black text-indigo-900" x-text="formatMoney(partsTotal)"></div>
                </div>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="p-6 max-w-[1600px] mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                <!-- Left Column (col-span-3) -->
                <div class="lg:col-span-3 space-y-6">

                    <!-- Customer Information -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-sm font-black text-slate-800 flex items-center gap-2">
                                <i data-lucide="user" class="w-4 h-4 text-blue-600"></i> Customer
                            </h3>
                            <button class="text-blue-600 hover:bg-blue-50 p-1.5 rounded-lg transition-colors">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <div class="text-base font-black text-slate-900">{{ $job->customer_name }}</div>
                                <div class="text-sm font-bold text-slate-600 mt-0.5 flex items-center gap-1.5">
                                    <i data-lucide="phone" class="w-3.5 h-3.5 text-slate-400"></i> {{ $job->customer_phone }}
                                </div>
                            </div>

                            <!-- Helpers -->
                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-xs text-slate-500 font-medium leading-relaxed">
                                Use customer history to check previous repairs and warranties.
                            </div>
                        </div>

                        <div class="mt-5 pt-4 border-t border-slate-100 flex gap-2">
                            <a href="tel:{{ $job->customer_phone }}" class="flex-1 flex justify-center items-center gap-2 py-2.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 transition-colors">
                                <i data-lucide="phone-call" class="w-4 h-4"></i> Call
                            </a>
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $job->customer_phone) }}" target="_blank" class="flex-1 flex justify-center items-center gap-2 py-2.5 bg-[#25D366]/10 hover:bg-[#25D366]/20 text-[#25D366] rounded-xl text-sm font-bold transition-colors">
                                <i data-lucide="message-circle" class="w-4 h-4"></i> WhatsApp
                            </a>
                        </div>
                    </div>

                    <!-- Device Information -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-sm font-black text-slate-800 flex items-center gap-2">
                                <i data-lucide="smartphone" class="w-4 h-4 text-indigo-600"></i> Device Details
                            </h3>
                            <button class="text-indigo-600 hover:bg-indigo-50 p-1.5 rounded-lg transition-colors">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                        </div>

                        <div class="mb-4">
                            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">{{ $job->device->device_type ?? 'Phone' }}</div>
                            <div class="text-base font-black text-slate-900">{{ $job->device->brand ?? '' }} {{ $job->device->model ?? '' }}</div>
                        </div>

                        <div class="space-y-3 bg-slate-50 rounded-xl p-4 border border-slate-100">
                            <div class="flex justify-between items-center border-b border-slate-200/60 pb-2">
                                <span class="text-xs font-bold text-slate-500">IMEI 1</span>
                                <span class="text-xs font-black text-slate-900">{{ $job->device->imei_1 ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-slate-200/60 pb-2">
                                <span class="text-xs font-bold text-slate-500">Serial</span>
                                <span class="text-xs font-black text-slate-900">{{ $job->device->serial_number ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-slate-200/60 pb-2">
                                <span class="text-xs font-bold text-slate-500">Battery</span>
                                <span class="text-xs font-black text-slate-900 flex items-center gap-1.5">
                                    <i data-lucide="battery-charging" class="w-3.5 h-3.5 text-emerald-500"></i>
                                    {{ $job->device->battery_percent ? $job->device->battery_percent.'%' : '-' }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center border-b border-slate-200/60 pb-2">
                                <span class="text-xs font-bold text-slate-500">Storage/RAM</span>
                                <span class="text-xs font-black text-slate-900">{{ $job->device->storage ?? '-' }} / {{ $job->device->ram ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-500">Color</span>
                                <span class="text-xs font-black text-slate-900">{{ $job->device->color ?? '-' }}</span>
                            </div>
                        </div>

                        <div class="mt-4">
                            <span class="block text-xs font-bold text-slate-500 mb-1.5">Accessories Received</span>
                            <div class="text-sm font-medium text-slate-700 bg-orange-50/50 p-3 rounded-xl border border-orange-100">
                                {{ $job->device->accessories_received ?? 'None' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Middle Column (col-span-4) -->
                <div class="lg:col-span-4 space-y-6">
                    <!-- Problem & Request -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-slate-100">
                            <h3 class="text-sm font-black text-slate-800 flex items-center gap-2 mb-3">
                                <i data-lucide="alert-circle" class="w-4 h-4 text-red-500"></i> Customer Complaint
                            </h3>
                            <p class="text-sm font-medium text-slate-700 bg-red-50 p-4 rounded-xl border border-red-100 leading-relaxed">
                                {{ $job->problem_description ?? 'No description provided.' }}
                            </p>
                        </div>
                        <div class="p-5 bg-slate-50/50">
                            <h3 class="text-sm font-black text-slate-800 flex items-center gap-2 mb-3">
                                <i data-lucide="message-square" class="w-4 h-4 text-emerald-500"></i> Customer Request
                            </h3>
                            <p class="text-sm font-medium text-slate-700 bg-white p-4 rounded-xl border border-slate-200 leading-relaxed">
                                {{ $job->customer_request ?? 'No special request.' }}
                            </p>
                            <div class="mt-3 text-[11px] uppercase tracking-wider font-bold text-slate-400 flex items-center gap-1.5">
                                <i data-lucide="info" class="w-3.5 h-3.5"></i> Do not replace parts without approval
                            </div>
                        </div>
                    </div>

                    <!-- Intake Checklist -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                        <h3 class="text-sm font-black text-slate-800 mb-5 flex items-center gap-2">
                            <i data-lucide="clipboard-check" class="w-4 h-4 text-blue-600"></i> Intake Checklist
                        </h3>
                        @php
                            $checklistItems = $job->checklist && $job->checklist->items ? json_decode($job->checklist->items, true) : [];
                            $physicalCondition = $job->device && $job->device->physical_condition ? json_decode($job->device->physical_condition, true) : [];
                        @endphp

                        <div class="space-y-6">
                            <div>
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-3">Checked Functions & Items</span>
                                <div class="flex flex-wrap gap-2">
                                    @forelse($checklistItems as $item)
                                        <span class="px-3 py-1.5 bg-blue-50 text-blue-700 border border-blue-200 rounded-lg text-xs font-bold flex items-center gap-1.5 shadow-sm">
                                            <i data-lucide="check" class="w-3.5 h-3.5"></i> {{ $item }}
                                        </span>
                                    @empty
                                        <span class="text-sm font-medium text-slate-400 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100">None checked</span>
                                    @endforelse
                                </div>
                            </div>

                            <div class="pt-5 border-t border-slate-100">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-3">Physical Condition</span>
                                <div class="flex flex-wrap gap-2">
                                    @forelse($physicalCondition as $cond)
                                        @php
                                            $isBad = in_array(strtolower($cond), ['cracked screen', 'frame damage', 'water damage', 'dead device', 'broken back glass']);
                                            $colorClass = $isBad ? 'bg-red-50 text-red-700 border-red-200' : 'bg-orange-50 text-orange-700 border-orange-200';
                                            $icon = $isBad ? 'alert-triangle' : 'info';
                                        @endphp
                                        <span class="px-3 py-1.5 {{ $colorClass }} border rounded-lg text-xs font-bold flex items-center gap-1.5 shadow-sm">
                                            <i data-lucide="{{ $icon }}" class="w-3.5 h-3.5"></i> {{ $cond }}
                                        </span>
                                    @empty
                                        <span class="text-sm font-medium text-slate-400 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100">None reported</span>
                                    @endforelse
                                </div>
                                <div class="mt-4 text-[11px] uppercase tracking-wider font-bold text-slate-400 flex items-center gap-1.5">
                                    <i data-lucide="camera" class="w-3.5 h-3.5"></i> Recorded during customer check-in
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column (col-span-5) -->
                <div class="lg:col-span-5 space-y-6">

                    <!-- Job Info -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                                <label class="text-[10px] uppercase tracking-wider font-bold text-slate-500">Check In Date</label>
                                <div class="text-sm font-black text-slate-900 mt-1">{{ \Carbon\Carbon::parse($job->created_at)->format('Y-m-d') }}</div>
                                <div class="text-xs font-bold text-slate-500">{{ \Carbon\Carbon::parse($job->created_at)->format('h:i A') }}</div>
                            </div>
                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                                <label class="text-[10px] uppercase tracking-wider font-bold text-slate-500">Expected</label>
                                <div class="text-sm font-black text-slate-900 mt-1">{{ $job->expected_completion_date ? \Carbon\Carbon::parse($job->expected_completion_date)->format('Y-m-d') : '-' }}</div>
                                <div class="text-xs font-bold text-slate-500">{{ $job->expected_completion_date ? \Carbon\Carbon::parse($job->expected_completion_date)->format('h:i A') : '' }}</div>
                            </div>
                        </div>

                        <div class="mt-5">
                            <label class="text-xs font-bold text-slate-700 mb-2 block flex items-center gap-2">
                                <i data-lucide="wrench" class="w-4 h-4 text-blue-600"></i> Assigned Technician
                            </label>
                            <div class="relative">
                                <select x-model="technicianId" class="w-full appearance-none bg-white rounded-xl border border-slate-200 px-4 py-3.5 text-sm font-bold text-slate-700 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all cursor-pointer">
                                    <option value="">Unassigned</option>
                                    @foreach($technicians as $tech)
                                        <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                                    @endforeach
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-400">
                                    <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Parts & Services Cart -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                        <h3 class="text-sm font-black text-slate-800 mb-4 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i data-lucide="package-plus" class="w-4 h-4 text-blue-600"></i> Parts & Services
                            </div>
                            <span class="text-xs font-bold bg-blue-50 text-blue-600 px-2.5 py-1 rounded-md" x-text="parts.length + ' Items'"></span>
                        </h3>

                        <!-- Search Bar -->
                        <div class="relative mb-4">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i data-lucide="search" class="h-4 w-4 text-slate-400"></i>
                            </div>
                            <input type="text" x-model="searchQuery" @input="searchParts()" placeholder="Search part by name or scan barcode" class="w-full pl-10 pr-10 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:bg-white focus:ring-2 focus:ring-blue-500 transition-all shadow-sm">
                            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none">
                                <i data-lucide="barcode" class="h-4 w-4 text-slate-400"></i>
                            </div>

                            <!-- Dropdown Results -->
                            <div x-show="searchResults.length > 0" @click.away="searchResults = []" x-transition class="absolute z-50 w-full mt-2 bg-white border border-slate-200 rounded-xl shadow-xl max-h-[340px] overflow-y-auto">
                                <template x-for="product in searchResults" :key="product.id">
                                    <div @click="addPart(product)" class="p-3.5 border-b border-slate-100 hover:bg-blue-50 cursor-pointer flex justify-between items-center gap-4 last:border-0 transition-colors group">
                                        <div class="min-w-0">
                                            <div class="text-sm font-bold text-slate-900 group-hover:text-blue-700 transition-colors" x-text="product.name"></div>
                                            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mt-1 flex flex-wrap items-center gap-x-2 gap-y-1">
                                                <span x-show="product.sku">SKU: <span x-text="product.sku"></span></span>
                                                <span x-show="product.barcode">BC: <span x-text="product.barcode"></span></span>
                                                <span>Available: <span :class="{'text-green-600': availableQtyForProduct(product.id, defaultStoreId) > 0, 'text-red-500': availableQtyForProduct(product.id, defaultStoreId) <= 0}" x-text="availableQtyForProduct(product.id, defaultStoreId)"></span></span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3 shrink-0">
                                            <div class="text-sm font-black text-slate-900" x-text="formatMoney(product.retail_price)"></div>
                                            <button class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Added Parts Cart -->
                        <div class="mb-4 overflow-hidden rounded-xl border border-slate-200 bg-white">
                            <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-4 py-2">
                                <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Added Parts</div>
                                <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Store / Qty / Amount</div>
                            </div>
                            <template x-for="(part, index) in parts" :key="index">
                                <div class="border-b border-slate-100 px-4 py-4 last:border-0" :class="{'bg-slate-50 opacity-70': part.status === 'Returned'}">
                                    <div class="mb-3 flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="truncate text-base font-black text-slate-900" x-text="part.product_name"></div>
                                            <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                                <span class="rounded-md border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-black text-slate-600">
                                                    Avail <span :class="{'text-emerald-600': availableQty(part) > 0, 'text-red-600': availableQty(part) <= 0}" x-text="availableQty(part)"></span>
                                                </span>
                                                <span class="rounded-md border border-blue-100 bg-blue-50 px-2 py-0.5 text-[11px] font-black text-blue-700" x-text="formatMoney(part.quantity * part.selling_price)"></span>
                                            </div>
                                        </div>
                                        <button @click="removePart(index)" type="button" class="inline-flex h-9 shrink-0 items-center justify-center gap-1.5 rounded-lg border border-red-100 bg-red-50 px-3 text-xs font-black text-red-600 hover:bg-red-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                            Remove
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <label class="block">
                                            <span class="mb-1 block text-[10px] font-black uppercase tracking-widest text-slate-400">Store</span>
                                            <select x-model.number="part.store_id" @change="recalculateTotals()" class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm font-black text-slate-700 focus:ring-2 focus:ring-blue-500">
                                                @foreach($stores as $store)
                                                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <label class="block">
                                            <span class="mb-1 block text-[10px] font-black uppercase tracking-widest text-slate-400">Qty</span>
                                            <div class="flex h-11 items-center overflow-hidden rounded-lg border border-slate-200 bg-white">
                                                <button @click="decreasePart(part)" type="button" class="flex h-full w-10 items-center justify-center bg-slate-50 font-black text-slate-500 hover:bg-slate-100">-</button>
                                                <input type="number" min="0.01" step="any" x-model.number="part.quantity" @input="recalculateTotals()" class="h-full min-w-0 flex-1 border-x border-slate-200 p-0 text-center text-base font-black text-slate-900 focus:ring-0">
                                                <button @click="increasePart(part)" type="button" class="flex h-full w-10 items-center justify-center bg-slate-50 font-black text-slate-500 hover:bg-slate-100">+</button>
                                            </div>
                                        </label>
                                        <label class="block">
                                            <span class="mb-1 block text-[10px] font-black uppercase tracking-widest text-slate-400">Amount</span>
                                            <div class="relative">
                                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-black text-slate-400">{{ $currencySymbol }}</span>
                                                <input type="number" x-model.number="part.selling_price" @input="recalculateTotals()" class="h-11 w-full rounded-lg border border-slate-200 bg-white pl-11 pr-3 text-right text-base font-black text-slate-900 focus:ring-2 focus:ring-blue-500">
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </template>

                            <!-- Empty State -->
                            <div x-show="parts.length === 0" class="h-full flex flex-col items-center justify-center text-center p-6 text-slate-400">
                                <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mb-3">
                                    <i data-lucide="package-search" class="w-6 h-6 text-slate-300"></i>
                                </div>
                                <div class="text-sm font-bold text-slate-500 mb-1">No parts added</div>
                                <div class="text-xs font-medium text-slate-400 max-w-[200px]">Search for a part or scan a barcode to add it to this repair job.</div>
                            </div>
                        </div>

                        <!-- Services / Non-stock Charges -->
                        <div class="mb-4 rounded-xl border border-blue-100 bg-blue-50/40 p-4">
                            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <h4 class="text-sm font-black text-slate-900">Services & Repair Costs</h4>
                                    <div class="text-xs font-semibold text-slate-500">Add work that is not taken from store stock.</div>
                                </div>
                                <div class="text-sm font-black text-blue-700" x-text="formatMoney(serviceItemsTotal + repairChargesTotal)"></div>
                            </div>
                            <div class="mb-3 grid grid-cols-1 md:grid-cols-3 gap-3">
                                <template x-for="charge in chargeFields" :key="charge.key">
                                    <label class="block">
                                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1" x-text="charge.label"></span>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-sm font-bold text-slate-400">{{ $currencySymbol }}</span>
                                            <input type="number" min="0" step="any" x-model.number="charges[charge.key]" @input="recalculateTotals()" class="w-full h-11 rounded-lg border border-slate-200 bg-white pl-11 pr-3 text-base font-black text-slate-800 focus:ring-2 focus:ring-blue-500">
                                        </div>
                                    </label>
                                </template>
                            </div>
                            <div class="mb-3 flex flex-wrap gap-2">
                                <button type="button" @click="addServiceItem('Service Charge', 0)" class="rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs font-black text-blue-700 hover:bg-blue-50">+ Service</button>
                                <button type="button" @click="addServiceItem('Cleaning', 0)" class="rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs font-black text-blue-700 hover:bg-blue-50">+ Cleaning</button>
                                <button type="button" @click="addServiceItem('Software Work', 0)" class="rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs font-black text-blue-700 hover:bg-blue-50">+ Software</button>
                                <button type="button" @click="addServiceItem('Custom Charge', 0)" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 hover:bg-slate-50">+ Custom</button>
                            </div>
                            <div class="space-y-2">
                                <template x-for="(item, index) in serviceItems" :key="index">
                                    <div class="grid grid-cols-1 md:grid-cols-12 gap-2 rounded-xl border border-blue-100 bg-white p-2">
                                        <input type="text" x-model="item.name" @input="recalculateTotals()" class="md:col-span-6 h-11 rounded-lg border border-slate-200 px-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-500" placeholder="Service name">
                                        <div class="relative md:col-span-4">
                                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-black text-slate-400">{{ $currencySymbol }}</span>
                                            <input type="number" min="0" step="any" x-model.number="item.amount" @input="recalculateTotals()" class="h-11 w-full rounded-lg border border-slate-200 pl-11 pr-3 text-right text-base font-black text-slate-800 focus:ring-2 focus:ring-blue-500">
                                        </div>
                                        <button type="button" @click="removeServiceItem(index)" class="md:col-span-2 inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-red-100 bg-red-50 px-3 text-xs font-black text-red-600 hover:bg-red-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                            Delete
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Discount -->
                        <div class="mb-4 grid grid-cols-1 sm:grid-cols-12 gap-3 rounded-xl border border-slate-200 bg-white p-4">
                            <label class="sm:col-span-4 block">
                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Discount Type</span>
                                <select x-model="discountType" @change="recalculateTotals()" class="w-full h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-500">
                                    <option value="fixed">Fixed</option>
                                    <option value="percentage">Percentage</option>
                                </select>
                            </label>
                            <label class="sm:col-span-4 block">
                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Discount</span>
                                <input type="number" min="0" step="any" x-model.number="discountValue" @input="recalculateTotals()" class="w-full h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-500">
                            </label>
                            <div class="sm:col-span-4 rounded-lg border border-red-100 bg-red-50 px-3 py-2">
                                <div class="text-[10px] font-black uppercase tracking-widest text-red-500">Discount Amount</div>
                                <div class="text-sm font-black text-red-700" x-text="formatMoney(discountAmount)"></div>
                            </div>
                        </div>

                        <!-- Totals Summary -->
                        <div class="bg-white border border-slate-200 p-4 rounded-xl shadow-sm mt-auto">
                            <div class="flex justify-between text-sm mb-2 text-slate-600">
                                <span class="font-medium">Parts Subtotal</span>
                                <span class="font-black tracking-wide text-slate-900" x-text="formatMoney(partsTotal)"></span>
                            </div>
                            <div class="flex justify-between text-sm mb-3 text-slate-600">
                                <span class="font-medium">Services & Charges</span>
                                <span class="font-black tracking-wide text-slate-900" x-text="formatMoney(repairChargesTotal + serviceItemsTotal)"></span>
                            </div>
                            <div class="flex justify-between text-sm mb-3 text-slate-600">
                                <span class="font-medium">Discount</span>
                                <span class="font-black tracking-wide text-red-600" x-text="'-' + formatMoney(discountAmount)"></span>
                            </div>
                            <div class="flex justify-between items-center pt-3 border-t border-slate-200">
                                <span class="text-xs font-bold uppercase tracking-widest text-slate-500">Grand Total</span>
                                <span class="text-xl font-black text-emerald-600 tracking-tight" x-text="formatMoney(grandTotal)"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Record Payment -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                        <h3 class="text-sm font-black text-slate-800 mb-4 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i data-lucide="credit-card" class="w-4 h-4 text-emerald-600"></i> Record Payment
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] uppercase tracking-wider font-bold bg-emerald-50 text-emerald-700 px-2 py-1 rounded-md">
                                    Paid: <span x-text="formatMoney(paidAmount)"></span>
                                </span>
                                <span class="text-[11px] uppercase tracking-wider font-bold bg-red-50 text-red-700 px-2 py-1 rounded-md">
                                    Bal: <span x-text="formatMoney(Math.max(0, grandTotal - paidAmount))"></span>
                                </span>
                            </div>
                        </h3>
                        <form action="{{ route('repairs.jobs.payments', $job->id) }}" method="POST" class="space-y-4">
                            @csrf
                            <div class="flex items-center gap-3">
                                <div class="relative flex-1">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-sm font-bold text-slate-400">{{ $currencySymbol }}</span>
                                    </div>
                                    <input type="number" step="any" name="amount" x-bind:max="Math.max(0, grandTotal - paidAmount)" placeholder="Amount" required class="w-full pl-9 pr-3 rounded-xl border border-slate-200 py-3 text-sm font-bold focus:ring-2 focus:ring-emerald-500 shadow-sm transition-shadow">
                                </div>
                                <select name="payment_method" class="flex-1 rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-700 shadow-sm focus:ring-2 focus:ring-emerald-500 transition-shadow appearance-none cursor-pointer">
                                    <option value="Cash">Cash</option>
                                    <option value="Card">Card</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                </select>
                            </div>
                            <input type="text" name="reference_no" placeholder="Reference Number (Optional)" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium focus:ring-2 focus:ring-emerald-500 shadow-sm transition-shadow">
                            <button type="submit" class="w-full py-3 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 rounded-xl text-sm font-black transition-colors flex items-center justify-center gap-2 shadow-sm">
                                <i data-lucide="plus-circle" class="w-4 h-4"></i> Record Payment
                            </button>
                        </form>

                        <!-- Payment History -->
                        @if($job->payments->count() > 0)
                        <div class="mt-5 pt-4 border-t border-slate-100 space-y-2.5">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Recent Payments</span>
                            @foreach($job->payments as $payment)
                            <div class="flex justify-between items-center bg-slate-50 p-3 rounded-xl border border-slate-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">
                                        <i data-lucide="check" class="w-4 h-4 font-bold"></i>
                                    </div>
                                    <div>
                                        <div class="text-xs font-black text-slate-700 uppercase tracking-wider">{{ $payment->payment_method }}</div>
                                        <div class="text-[10px] font-bold text-slate-400 mt-0.5">{{ \Carbon\Carbon::parse($payment->payment_date)->format('M d, Y') }}</div>
                                    </div>
                                </div>
                                <div class="text-sm font-black text-slate-900">{{ \App\Support\SystemSettings::money($payment->amount) }}</div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>

        <!-- Sticky Bottom Action Bar -->
        <div class="fixed bottom-0 left-0 lg:left-64 right-0 bg-white border-t border-slate-200 shadow-[0_-10px_20px_-10px_rgba(0,0,0,0.1)] p-4 z-40 transition-all">
            <div class="max-w-[1600px] mx-auto flex justify-between items-center px-2">
                <a href="{{ route('repairs.jobs.index') }}" class="px-6 py-3 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-sm font-bold text-slate-600 transition-colors">
                    Back to Jobs
                </a>

                <div class="flex items-center gap-8">
                    <!-- Dynamic Balance Display -->
                    <div class="text-right hidden sm:block">
                        <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-0.5">Balance Due</div>
                        <div class="text-2xl font-black tracking-tight" :class="{'text-red-600': (grandTotal - paidAmount) > 0, 'text-emerald-600': (grandTotal - paidAmount) <= 0}" x-text="formatMoney(Math.max(0, grandTotal - paidAmount))"></div>
                    </div>

                    <button @click="saveChanges()" :disabled="isSaving" class="px-10 py-3.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-black shadow-lg shadow-blue-500/30 transition-all flex items-center gap-2 transform hover:-translate-y-0.5 active:translate-y-0 active:shadow-sm">
                        <span x-show="!isSaving" class="flex items-center gap-2"><i data-lucide="save" class="w-4 h-4"></i> Save Workspace</span>
                        <span x-show="isSaving" class="flex items-center gap-2"><i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Saving changes...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-layouts::app>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('repairWorkspace', () => ({
            jobId: {{ $job->id }},
            workspaceUrl: @json(route('repairs.jobs.workspace', $job->id)),
            currentStatus: '{!! $job->status !!}',
            technicianId: '{!! $job->primary_technician_id ?? '' !!}',
            currencySymbol: @json($currencySymbol),
            decimalPlaces: {{ $decimalPlaces }},
            defaultStoreId: {{ (int) ($job->store_id ?? $defaultStoreId) }},

            // Static Data
            steps: [
                'Checked In', 'Diagnosis', 'Waiting Approval', 'Waiting Parts',
                'Assigned', 'Under Repair', 'Quality Check', 'Ready for Pickup', 'Completed', 'Delivered'
            ],
            availableProducts: @json($products),
            availableStocks: @json($productStocks),
            stockMap: {},

            // Search
            searchQuery: '',
            searchResults: [],

            // State
            @php
                $mappedParts = $job->parts->map(function($p) {
                    return [
                        'id' => $p->id,
                        'product_id' => $p->product_id,
                        'product_name' => $p->product_name,
                        'quantity' => (float)$p->quantity,
                        'original_quantity' => (float)$p->quantity,
                        'selling_price' => (float)$p->selling_price,
                        'status' => $p->status,
                        'original_status' => $p->status,
                        'store_id' => $p->store_id ?: $defaultStoreId,
                        'original_store_id' => $p->store_id ?: $defaultStoreId,
                    ];
                })->values();
            @endphp
            parts: @json($mappedParts),
            @php
                $mappedServiceItems = ($job->charge_items ?? collect())->map(function($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'amount' => (float) $item->amount,
                    ];
                })->values();
            @endphp
            serviceItems: @json($mappedServiceItems),

            // Financials
            chargeFields: [
                { key: 'labor', label: 'Labour' },
                { key: 'diagnosis', label: 'Diagnosis' },
                { key: 'service', label: 'Service' },
                { key: 'software', label: 'Software' },
                { key: 'other', label: 'Other' },
            ],
            charges: {
                labor: {{ (float)($job->labor_charge ?: $job->estimated_cost ?: 0) }},
                diagnosis: {{ (float)($job->diagnosis_fee ?? 0) }},
                service: {{ (float)($job->service_charge ?? 0) }},
                software: {{ (float)($job->software_charge ?? 0) }},
                other: {{ (float)($job->other_charge ?? 0) }},
            },
            discountType: @json($job->discount_type ?? 'fixed'),
            discountValue: {{ (float)($job->discount_value ?? $job->discount_amount ?? 0) }},
            paidAmount: {{ (float)$job->paid_amount }},
            partsTotal: 0,
            repairChargesTotal: 0,
            serviceItemsTotal: 0,
            discountAmount: 0,
            grandTotal: 0,

            isSaving: false,

            initData() {
                this.availableStocks.forEach((row) => {
                    this.stockMap[this.stockKey(row.product_id, row.store_id)] = Number(row.quantity || 0);
                });
                this.recalculateTotals();
                if (window.lucide) window.lucide.createIcons();
            },

            promptStatusChange(newStatus) {
                if (this.currentStatus === newStatus) return;

                let msg = 'Change status from ' + this.currentStatus + ' to ' + newStatus + '?';
                if (['Delivered', 'Cancelled', 'Rejected'].includes(newStatus)) {
                    msg = 'Warning: You are about to mark this job as ' + newStatus + '. Are you sure?';
                }

                Swal.fire({
                    title: 'Confirm Status Change',
                    text: msg,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, change it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.currentStatus = newStatus;
                    }
                });
            },

            searchParts() {
                if (this.searchQuery.length < 2) {
                    this.searchResults = [];
                    return;
                }

                const query = this.searchQuery.toLowerCase();
                this.searchResults = this.availableProducts.filter(p =>
                    (p.name && p.name.toLowerCase().includes(query)) ||
                    (p.sku && p.sku.toLowerCase().includes(query)) ||
                    (p.barcode && p.barcode.toLowerCase().includes(query))
                ).slice(0, 12);
            },

            stockKey(productId, storeId) {
                return Number(productId) + ':' + Number(storeId || this.defaultStoreId);
            },

            baseStock(productId, storeId) {
                return Number(this.stockMap[this.stockKey(productId, storeId)] || 0);
            },

            originalIssuedQty(productId, storeId) {
                return this.parts.reduce((total, part) => {
                    if (
                        Number(part.product_id) === Number(productId) &&
                        Number(part.original_store_id || this.defaultStoreId) === Number(storeId || this.defaultStoreId) &&
                        part.original_status === 'Issued'
                    ) {
                        return total + Number(part.original_quantity || 0);
                    }

                    return total;
                }, 0);
            },

            currentIssuedQty(productId, storeId) {
                return this.parts.reduce((total, part) => {
                    if (
                        Number(part.product_id) === Number(productId) &&
                        Number(part.store_id || this.defaultStoreId) === Number(storeId || this.defaultStoreId) &&
                        part.status === 'Issued'
                    ) {
                        return total + Number(part.quantity || 0);
                    }

                    return total;
                }, 0);
            },

            availableQtyForProduct(productId, storeId) {
                const qty = this.baseStock(productId, storeId)
                    + this.originalIssuedQty(productId, storeId)
                    - this.currentIssuedQty(productId, storeId);

                return Number(qty.toFixed(2));
            },

            availableQty(part) {
                return this.availableQtyForProduct(part.product_id, part.store_id);
            },

            addPart(product) {
                let available = this.baseStock(product.id, this.defaultStoreId) + this.originalIssuedQty(product.id, this.defaultStoreId);
                if (available <= 0) {
                    Swal.fire('Out of Stock', 'This part has no available quantity in the selected store.', 'warning');
                    return;
                }

                const existingIndex = this.parts.findIndex(p => Number(p.product_id) === Number(product.id) && Number(p.store_id) === Number(this.defaultStoreId) && p.status !== 'Returned');
                if (existingIndex >= 0) {
                    this.increasePart(this.parts[existingIndex]);
                } else {
                    this.parts.unshift({
                        id: null,
                        product_id: product.id,
                        product_name: product.name,
                        quantity: 1,
                        original_quantity: 0,
                        selling_price: parseFloat(product.retail_price) || 0,
                        status: 'Issued',
                        original_status: null,
                        store_id: this.defaultStoreId,
                        original_store_id: this.defaultStoreId
                    });
                }
                this.searchQuery = '';
                this.searchResults = [];
                this.recalculateTotals();
            },

            increasePart(part) {
                if (part.status === 'Issued' && this.availableQty(part) <= 0) {
                    Swal.fire('Stock Limit', 'No more available quantity in this store.', 'warning');
                    return;
                }

                part.quantity = Number(part.quantity || 0) + 1;
                this.recalculateTotals();
            },

            decreasePart(part) {
                part.quantity = Math.max(0.01, Number(part.quantity || 0) - 1);
                this.recalculateTotals();
            },

            removePart(index) {
                if (this.parts[index].id) {
                    // Saved parts are returned so stock can be restored correctly on save.
                    Swal.fire({
                        title: 'Return Part?',
                        text: 'This part has already been saved. Mark it as returned and restore stock?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: 'Yes, return it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.parts[index].status = 'Returned';
                            this.recalculateTotals();
                        }
                    });
                } else {
                    this.parts.splice(index, 1);
                    this.recalculateTotals();
                }
            },

            addServiceItem(name, amount = 0) {
                this.serviceItems.push({
                    id: null,
                    name,
                    amount: Number(amount || 0)
                });
                this.recalculateTotals();
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            },

            removeServiceItem(index) {
                this.serviceItems.splice(index, 1);
                this.recalculateTotals();
            },

            recalculateTotals() {
                let partsSum = 0;
                this.parts.forEach(p => {
                    if (p.status === 'Issued') {
                        partsSum += (parseFloat(p.quantity) * parseFloat(p.selling_price));
                    }
                });
                this.partsTotal = partsSum;
                this.repairChargesTotal = Object.values(this.charges).reduce((total, value) => total + Number(value || 0), 0);
                this.serviceItemsTotal = this.serviceItems.reduce((total, item) => total + Number(item.amount || 0), 0);
                const discountBase = this.partsTotal + this.repairChargesTotal + this.serviceItemsTotal;
                this.discountAmount = this.discountType === 'percentage'
                    ? Math.min(discountBase, discountBase * Math.min(Number(this.discountValue || 0), 100) / 100)
                    : Math.min(discountBase, Number(this.discountValue || 0));
                this.grandTotal = Math.max(0, discountBase - this.discountAmount);
            },

            formatMoney(amount) {
                const formatted = new Intl.NumberFormat('en-US', {
                    minimumFractionDigits: this.decimalPlaces,
                    maximumFractionDigits: this.decimalPlaces
                }).format(Number(amount || 0));

                return (this.currencySymbol + ' ' + formatted).trim();
            },

            async saveChanges() {
                if (this.isSaving) return;
                this.isSaving = true;

                try {
                    const response = await fetch(this.workspaceUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            status: this.currentStatus,
                            technician_id: this.technicianId || null,
                            parts: this.parts,
                            charges: this.charges,
                            service_items: this.serviceItems,
                            discount: {
                                type: this.discountType,
                                value: this.discountValue || 0
                            }
                        })
                    });

                    const raw = await response.text();
                    let data = {};
                    try {
                        data = raw ? JSON.parse(raw) : {};
                    } catch (parseError) {
                        throw new Error(raw ? raw.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 220) : 'Server returned an empty response.');
                    }

                    if (response.ok) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Workspace saved successfully.',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        let validationMessage = '';
                        if (response.status === 422 && data.errors) {
                            validationMessage = Object.values(data.errors).flat().join('\n');
                        }
                        Swal.fire('Error', 'Error saving changes: ' + (validationMessage || data.message || 'Unknown error'), 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire('Network Error', 'A network error occurred while saving: ' + (error.message || 'Please try again.'), 'error');
                } finally {
                    this.isSaving = false;
                }
            }
        }));
    });
</script>
