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

            <form method="POST" action="{{ route('repairs.jobs.store') }}" enctype="multipart/form-data" class="space-y-6" x-data="checkInForm()">
                @csrf

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-5">
                        <h2 class="text-lg font-black text-slate-900">Customer Details</h2>
                        <p class="mt-1 text-sm text-slate-500">Select an existing customer or fill the new customer fields below.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-5 p-6 lg:grid-cols-2">
                        <div class="lg:col-span-2 relative" @click.away="customerDropdownOpen = false">
                            <label class="text-sm font-extrabold text-slate-800">Existing Customer</label>
                            <!-- Custom Select Input -->
                            <div class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 cursor-pointer flex justify-between items-center" @click="customerDropdownOpen = !customerDropdownOpen">
                                <span x-text="selectedCustomerName()"></span>
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                            <input type="hidden" name="customer_id" :value="customerId">
                            
                            <!-- Dropdown List -->
                            <div x-show="customerDropdownOpen" class="absolute z-40 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden" x-transition style="display: none;">
                                <div class="p-2 border-b border-slate-100 bg-slate-50">
                                    <input type="text" x-model="customerSearch" placeholder="Search customer by name or phone..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                </div>
                                <div class="max-h-60 overflow-y-auto p-1">
                                    <div @click="customerId = ''; customerDropdownOpen = false" class="px-3 py-2 hover:bg-slate-50 cursor-pointer rounded-lg text-sm text-slate-700 font-extrabold border-b border-slate-50 text-blue-600 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        Create a new customer
                                    </div>
                                    <template x-for="customer in filteredCustomers" :key="customer.id">
                                        <div @click="customerId = customer.id; customerDropdownOpen = false" class="px-3 py-2 hover:bg-slate-50 cursor-pointer rounded-lg text-sm border-b border-slate-50">
                                            <span class="block font-bold text-slate-800" x-text="customer.name"></span>
                                            <span class="block text-xs text-slate-500" x-text="customer.phone || 'No phone'"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-extrabold text-slate-800">Customer Name</label>
                            <input name="customer_name" x-model="customerName" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="text-sm font-extrabold text-slate-800">Phone</label>
                            <input name="customer_phone" x-model="customerPhone" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="text-sm font-extrabold text-slate-800">WhatsApp</label>
                            <input name="customer_whatsapp" x-model="customerWhatsapp" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="text-sm font-extrabold text-slate-800">Email</label>
                            <input type="email" name="customer_email" x-model="customerEmail" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="text-sm font-extrabold text-slate-800">NIC / ID</label>
                            <input name="customer_nic" x-model="customerNic" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="text-sm font-extrabold text-slate-800">Address</label>
                            <input name="customer_address" x-model="customerAddress" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
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
                            <div class="flex justify-between items-center mb-2">
                                <label class="text-sm font-extrabold text-slate-800">Device Type</label>
                                <button type="button" @click="toggleCustomDeviceType()" class="text-xs font-bold text-blue-600 hover:text-blue-800" x-text="isCustomDeviceType ? 'Select from list' : '+ Add New'"></button>
                            </div>
                            <template x-if="!isCustomDeviceType">
                                <select name="device_type" x-model="deviceTypeSelect" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                    @foreach($deviceTypes as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                            </template>
                            <template x-if="isCustomDeviceType">
                                <input name="device_type" x-model="deviceTypeCustom" placeholder="Enter custom device type" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            </template>
                        </div>
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label class="text-sm font-extrabold text-slate-800">Brand</label>
                                <button type="button" @click="toggleCustomBrand()" class="text-xs font-bold text-blue-600 hover:text-blue-800" x-text="isCustomBrand ? 'Select from list' : '+ Add New'"></button>
                            </div>
                            <template x-if="!isCustomBrand">
                                <select name="brand" x-model="brandSelect" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand }}">{{ $brand }}</option>
                                    @endforeach
                                </select>
                            </template>
                            <template x-if="isCustomBrand">
                                <input name="brand" x-model="brandCustom" placeholder="Enter custom brand" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            </template>
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
                        @foreach(['color' => 'Color', 'storage' => 'Storage', 'ram' => 'RAM', 'carrier' => 'Carrier / Network', 'password' => 'Password', 'pin' => 'PIN'] as $field => $label)
                            <div>
                                <label class="text-sm font-extrabold text-slate-800">{{ $label }}</label>
                                <input name="{{ $field }}" value="{{ old($field) }}" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                            </div>
                        @endforeach
                        
                        <div class="lg:col-span-3" x-data="patternLock()">
                            <label class="text-sm font-extrabold text-slate-800">Pattern Lock</label>
                            <div class="mt-2 flex flex-col sm:flex-row items-start sm:items-center gap-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                
                                <!-- Pattern Lock UI -->
                                <div class="relative w-56 h-56 bg-white rounded-xl shadow-sm border border-slate-200 p-4 touch-none select-none"
                                     @mousedown="startDrawing($event)"
                                     @mousemove="draw($event)"
                                     @mouseup="stopDrawing"
                                     @mouseleave="stopDrawing"
                                     @touchstart.prevent="startDrawing($event.touches[0])"
                                     @touchmove.prevent="draw($event.touches[0])"
                                     @touchend="stopDrawing">
                                     
                                    <!-- SVG Lines -->
                                    <svg class="absolute inset-0 w-full h-full pointer-events-none z-10">
                                        <template x-for="(line, index) in lines" :key="index">
                                            <line :x1="line.x1" :y1="line.y1" :x2="line.x2" :y2="line.y2" stroke="#3b82f6" stroke-width="4" stroke-linecap="round"></line>
                                        </template>
                                        <template x-if="activeLine">
                                            <line :x1="activeLine.x1" :y1="activeLine.y1" :x2="activeLine.x2" :y2="activeLine.y2" stroke="#93c5fd" stroke-width="4" stroke-linecap="round"></line>
                                        </template>
                                    </svg>
                        
                                    <!-- Dots -->
                                    <div class="grid grid-cols-3 gap-3 h-full w-full relative z-20" x-ref="gridContainer">
                                        <template x-for="i in 9" :key="i">
                                            <div class="flex items-center justify-center h-full w-full">
                                                <div class="w-10 h-10 rounded-full border-2 transition-all flex items-center justify-center cursor-crosshair"
                                                     :class="pattern.includes(i) ? 'border-blue-500 bg-blue-100 scale-110' : 'border-slate-300 bg-slate-50 hover:bg-slate-100'"
                                                     :data-dot="i">
                                                     <div class="w-3 h-3 rounded-full" :class="pattern.includes(i) ? 'bg-blue-600' : 'bg-transparent'"></div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                        
                                <div class="flex-1 w-full">
                                    <p class="text-sm font-bold text-slate-700 mb-2">Draw Pattern Sequence</p>
                                    <p class="text-xs text-slate-500 mb-4">Click/touch and drag across the dots to record the device's pattern lock.</p>
                                    
                                    <div class="flex items-center gap-3">
                                        <input type="text" name="pattern_lock" x-model="patternString" readonly placeholder="Pattern will appear here" class="flex-1 rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 bg-slate-100 text-slate-500 cursor-not-allowed font-mono tracking-widest font-bold">
                                        <button type="button" @click="resetPattern" class="rounded-xl bg-white border border-slate-200 px-4 py-3 text-sm font-extrabold text-slate-700 hover:bg-slate-50 hover:text-red-600 transition-colors">Clear</button>
                                    </div>
                                </div>
                            </div>
                        </div>
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
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('checkInForm', () => ({
                customers: @json($customers),
                customerId: '{{ old("customer_id") }}',
                customerSearch: '',
                customerDropdownOpen: false,

                customerName: '{{ old("customer_name") }}',
                customerPhone: '{{ old("customer_phone") }}',
                customerWhatsapp: '{{ old("customer_whatsapp") }}',
                customerEmail: '{{ old("customer_email") }}',
                customerNic: '{{ old("customer_nic") }}',
                customerAddress: '{{ old("customer_address") }}',

                deviceTypeSelect: '{{ old("device_type", "") }}',
                deviceTypeCustom: '',
                isCustomDeviceType: false,

                brandSelect: '{{ old("brand", "") }}',
                brandCustom: '',
                isCustomBrand: false,

                init() {
                    this.$watch('customerId', value => {
                        if (value) {
                            let c = this.customers.find(c => c.id == value);
                            if (c) {
                                this.customerName = c.name || '';
                                this.customerPhone = c.phone || '';
                                this.customerWhatsapp = c.phone || '';
                                this.customerEmail = c.email || '';
                                this.customerAddress = c.address || '';
                                this.customerNic = ''; // Extract if we could, but better leave blank for manual entry
                            }
                        } else {
                            this.customerName = '';
                            this.customerPhone = '';
                            this.customerWhatsapp = '';
                            this.customerEmail = '';
                            this.customerNic = '';
                            this.customerAddress = '';
                        }
                    });

                    // Set initial custom values for Device Type if old input doesn't match standard
                    let standardDevices = @json($deviceTypes);
                    if (this.deviceTypeSelect && !standardDevices.includes(this.deviceTypeSelect)) {
                        this.isCustomDeviceType = true;
                        this.deviceTypeCustom = this.deviceTypeSelect;
                    }

                    // Set initial custom values for Brand if old input doesn't match standard
                    let standardBrands = @json($brands);
                    if (this.brandSelect && !standardBrands.includes(this.brandSelect)) {
                        this.isCustomBrand = true;
                        this.brandCustom = this.brandSelect;
                    }
                },

                get filteredCustomers() {
                    let term = this.customerSearch.toLowerCase();
                    if (!term) return this.customers;
                    return this.customers.filter(c => 
                        c.name.toLowerCase().includes(term) || 
                        (c.phone && c.phone.toLowerCase().includes(term))
                    );
                },

                selectedCustomerName() {
                    if (!this.customerId) return 'Create a new customer';
                    let c = this.customers.find(c => c.id == this.customerId);
                    return c ? c.name + ' / ' + (c.phone || 'No phone') : 'Create a new customer';
                },

                toggleCustomDeviceType() {
                    this.isCustomDeviceType = !this.isCustomDeviceType;
                    if (this.isCustomDeviceType) {
                        this.deviceTypeCustom = '';
                    } else {
                        this.deviceTypeSelect = @json($deviceTypes)[0];
                    }
                },

                toggleCustomBrand() {
                    this.isCustomBrand = !this.isCustomBrand;
                    if (this.isCustomBrand) {
                        this.brandCustom = '';
                    } else {
                        this.brandSelect = @json($brands)[0];
                    }
                }
            }));
            
            Alpine.data('patternLock', () => ({
                pattern: [],
                isDrawing: false,
                activeLine: null,
                dotPositions: {},
                
                init() {
                    let oldPattern = '{{ old("pattern_lock") }}';
                    if (oldPattern) {
                        this.pattern = oldPattern.split('').map(Number);
                    }
                    
                    this.$nextTick(() => {
                        this.calculateDotPositions();
                    });
                    
                    window.addEventListener('resize', () => this.calculateDotPositions());
                },
                
                get patternString() {
                    return this.pattern.join('');
                },
                
                get lines() {
                    let drawnLines = [];
                    for (let i = 0; i < this.pattern.length - 1; i++) {
                        let startDot = this.dotPositions[this.pattern[i]];
                        let endDot = this.dotPositions[this.pattern[i+1]];
                        if (startDot && endDot) {
                            drawnLines.push({
                                x1: startDot.x, y1: startDot.y,
                                x2: endDot.x, y2: endDot.y
                            });
                        }
                    }
                    return drawnLines;
                },
                
                calculateDotPositions() {
                    let container = this.$refs.gridContainer;
                    if (!container) return;
                    let rect = container.getBoundingClientRect();
                    
                    for (let i = 1; i <= 9; i++) {
                        let dot = this.$el.querySelector(`[data-dot="${i}"]`);
                        if (dot) {
                            let dotRect = dot.getBoundingClientRect();
                            this.dotPositions[i] = {
                                x: (dotRect.left - rect.left) + (dotRect.width / 2),
                                y: (dotRect.top - rect.top) + (dotRect.height / 2)
                            };
                        }
                    }
                },
                
                startDrawing(e) {
                    this.pattern = [];
                    this.isDrawing = true;
                    this.calculateDotPositions(); 
                    this.checkDotIntersection(e);
                },
                
                draw(e) {
                    if (!this.isDrawing) return;
                    
                    let container = this.$refs.gridContainer;
                    let rect = container.getBoundingClientRect();
                    let currentX = e.clientX - rect.left;
                    let currentY = e.clientY - rect.top;
                    
                    if (this.pattern.length > 0) {
                        let lastDot = this.dotPositions[this.pattern[this.pattern.length - 1]];
                        this.activeLine = {
                            x1: lastDot.x, y1: lastDot.y,
                            x2: currentX, y2: currentY
                        };
                    }
                    
                    this.checkDotIntersection(e);
                },
                
                stopDrawing() {
                    this.isDrawing = false;
                    this.activeLine = null;
                },
                
                checkDotIntersection(e) {
                    let element = document.elementFromPoint(e.clientX, e.clientY);
                    if (element) {
                        let dotEl = element.closest('[data-dot]');
                        if (dotEl) {
                            let dot = parseInt(dotEl.getAttribute('data-dot'));
                            if (!this.pattern.includes(dot)) {
                                this.pattern.push(dot);
                            }
                        }
                    }
                },
                
                resetPattern() {
                    this.pattern = [];
                }
            }));
        });
    </script>
</x-layouts::app>
