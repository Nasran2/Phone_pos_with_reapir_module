<x-layouts::app :title="__('Distribution Management')">
@php
    $money = fn ($amount) => \App\Support\SystemSettings::money((float) $amount);
    $activeClass = 'bg-blue-600 text-white border-blue-600 shadow-sm shadow-blue-500/20';
    $idleClass = 'bg-white/90 text-slate-650 border-slate-200 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700';
@endphp

<style>
    .distribution-page input:not([type="hidden"]):not([type="checkbox"]),
    .distribution-page select,
    .distribution-page textarea {
        min-height: 42px;
        width: 100%;
        border: 1.5px solid #cbd5e1 !important;
        background-color: #ffffff !important;
        color: #0f172a;
        border-radius: 10px;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        font-weight: 500;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), inset 0 1px 0 rgba(255, 255, 255, 0.9);
        transition: border-color 160ms ease, box-shadow 160ms ease, background-color 160ms ease;
    }

    .distribution-page textarea {
        min-height: 80px;
        line-height: 1.5;
    }

    .distribution-page input::placeholder,
    .distribution-page textarea::placeholder {
        color: #94a3b8;
        font-weight: 400;
    }

    .distribution-page input:focus,
    .distribution-page select:focus,
    .distribution-page textarea:focus {
        outline: none;
        border-color: #2563eb !important;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.14), 0 8px 20px rgba(37, 99, 235, 0.08);
        background-color: #ffffff !important;
    }

    .distribution-page input[type="date"],
    .distribution-page input[type="number"],
    .distribution-page select {
        appearance: auto;
    }

    .distribution-page label {
        color: #475569;
    }
</style>

<div class="bg-gradient-to-r from-sky-50 via-white to-emerald-50 border-b border-slate-200 min-h-20 px-6 py-5 sticky top-0 z-20">
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
        <div class="max-w-md">
            <div class="flex items-center gap-2 text-[11px] uppercase tracking-wide font-bold text-slate-400">
                <span>Dashboard</span><span>/</span><span>Distribution</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 mt-1">
                @switch($page)
                    @case('dashboard') Distribution Dashboard @break
                    @case('lorries') Lorry Management @break
                    @case('lorry-profile') {{ $lorry->lorry_no }} - {{ $lorry->name }} @break
                    @case('stock') Lorry Stock @break
                    @case('transfer') {{ $title }} @break
                    @case('trips') Distribution Trips @break
                    @case('sales') Lorry Sales @break
                    @case('sale-returns') Lorry Sale Returns @break
                    @case('expenses') Lorry Expenses @break
                    @case('collections') Lorry Collections @break
                    @case('reconciliation') Daily Reconciliation @break
                    @case('reports') Distribution Reports @break
                    @case('settings') Distribution Settings @break
                    @default Distribution
                @endswitch
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Manage lorry inventory, field sales, expenses, collections, and daily reconciliation.
            </p>
        </div>
        <div class="flex flex-wrap gap-2 max-w-5xl rounded-2xl border border-white/80 bg-white/55 p-2 shadow-sm">
            @foreach($menu as $item)
                @if(auth()->user()->hasPermission($item['permission']))
                <a href="{{ route($item['route']) }}" class="px-3 py-2 rounded-lg border text-[11px] font-bold transition {{ request()->routeIs($item['route']) ? $activeClass : $idleClass }}">
                    {{ $item['label'] }}
                </a>
                @endif
            @endforeach
        </div>
    </div>
</div>

<div class="distribution-page p-6 md:p-8 bg-gradient-to-br from-slate-50 via-sky-50/40 to-emerald-50/30 min-h-screen">
    <div class="max-w-[1600px] mx-auto space-y-6">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($page === 'dashboard')
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                @foreach($cards as $card)
                    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $card['label'] }}</div>
                        <div class="text-2xl font-black text-slate-900 mt-3">{{ $card['value'] }}</div>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <section class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="font-black text-slate-900">Recent Stock Movements</h2>
                        <a href="{{ route('distribution.stock.index') }}" class="text-xs font-bold text-blue-600">View stock</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                                <tr><th class="text-left px-5 py-3">No</th><th class="text-left px-5 py-3">Type</th><th class="text-left px-5 py-3">From</th><th class="text-left px-5 py-3">To</th><th class="text-left px-5 py-3">Status</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($recentTransfers as $transfer)
                                    <tr>
                                        <td class="px-5 py-4 font-bold text-slate-800">{{ $transfer->transfer_no }}</td>
                                        <td class="px-5 py-4 text-slate-600">{{ str_replace('_', ' ', ucfirst($transfer->transfer_type)) }}</td>
                                        <td class="px-5 py-4 text-slate-600">{{ $transfer->from_store_name ?? $transfer->from_lorry_no ?? '-' }}</td>
                                        <td class="px-5 py-4 text-slate-600">{{ $transfer->to_store_name ?? $transfer->to_lorry_no ?? '-' }}</td>
                                        <td class="px-5 py-4"><span class="px-2 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700">{{ ucfirst($transfer->status) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">No distribution movements yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h2 class="font-black text-slate-900">Lorry Low Stock Alerts</h2>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse($lowStocks as $stock)
                            <div class="px-5 py-4 flex items-center justify-between gap-4">
                                <div>
                                    <div class="font-bold text-slate-900">{{ $stock->product_name }}</div>
                                    <div class="text-xs text-slate-500">{{ $stock->lorry_no }} - {{ $stock->lorry_name }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-black text-rose-600">{{ number_format($stock->quantity, 2) }}</div>
                                    <div class="text-[11px] text-slate-400">Min {{ number_format($stock->minimum_stock, 2) }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="px-5 py-10 text-center text-slate-400">No lorry low stock alerts.</div>
                        @endforelse
                    </div>
                </section>
            </div>
        @endif

        @if($page === 'lorries')
            <div class="grid grid-cols-1 xl:grid-cols-[520px_1fr] gap-6">
                <form method="POST" action="{{ route('distribution.lorries.index') }}" class="bg-gradient-to-br from-sky-50 via-white to-emerald-50 border border-sky-100 rounded-[22px] shadow-sm p-6 space-y-5">
                    @csrf
                    <div>
                        <div class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-blue-700">New vehicle</div>
                        <h2 class="mt-3 text-xl font-black text-slate-950">Add Lorry</h2>
                        <p class="text-sm text-slate-600 mt-1">Create lorry profiles for route inventory and trip tracking.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-1 gap-4">
                        <label class="block text-xs font-black uppercase tracking-wide">Lorry Number *
                            <input name="lorry_no" value="{{ old('lorry_no') }}" required placeholder="LRY-003" class="mt-2">
                        </label>
                        <label class="block text-xs font-black uppercase tracking-wide">Display Name *
                            <input name="name" value="{{ old('name') }}" required placeholder="Colombo Route Lorry" class="mt-2">
                        </label>
                        <label class="block text-xs font-black uppercase tracking-wide">Plate Number
                            <input name="plate_no" value="{{ old('plate_no') }}" placeholder="WP ABC 1234" class="mt-2">
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label class="block text-xs font-black uppercase tracking-wide">Driver Name
                                <input name="driver_name" value="{{ old('driver_name') }}" placeholder="Driver name" class="mt-2">
                            </label>
                            <label class="block text-xs font-black uppercase tracking-wide">Driver Phone
                                <input name="driver_phone" value="{{ old('driver_phone') }}" placeholder="0770000000" class="mt-2">
                            </label>
                        </div>
                        <label class="block text-xs font-black uppercase tracking-wide">Route / Area
                            <input name="route_area" value="{{ old('route_area') }}" placeholder="Colombo Metro" class="mt-2">
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label class="block text-xs font-black uppercase tracking-wide">Opening Odometer
                                <input name="opening_odometer" type="number" min="0" step="any" value="{{ old('opening_odometer', 0) }}" placeholder="0" class="mt-2">
                            </label>
                            <label class="block text-xs font-black uppercase tracking-wide">Status
                                <select name="status" class="mt-2">
                                    <option value="active">Active</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </label>
                        </div>
                    </div>
                    <label class="block text-xs font-black uppercase tracking-wide">Notes
                        <textarea name="notes" rows="3" placeholder="Any license, route, or vehicle notes" class="mt-2">{{ old('notes') }}</textarea>
                    </label>
                    <section class="rounded-2xl border border-blue-200 bg-blue-50/70 p-4 space-y-4">
                        <div><h3 class="font-black text-slate-900">Assigned User</h3><p class="mt-1 text-xs normal-case font-medium text-slate-500">Optional now. You can also assign more users after creating the lorry.</p></div>
                        <label class="block text-xs font-black uppercase tracking-wide">Select Lorry Manager
                            <select name="assigned_user_id" class="mt-2"><option value="">No Lorry Manager assigned yet</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(old('assigned_user_id') == $user->id)>{{ $user->name }} — {{ $user->email }}</option>@endforeach</select>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label class="block text-xs font-black uppercase tracking-wide">Role in Lorry<input value="Lorry Manager" readonly class="mt-2 bg-slate-100"></label>
                            <label class="block text-xs font-black uppercase tracking-wide">Start Date<input type="date" name="assignment_start_date" value="{{ old('assignment_start_date', today()->toDateString()) }}" class="mt-2"></label>
                        </div>
                    </section>
                    <button class="w-full rounded-2xl bg-blue-600 px-4 py-4 text-sm font-black text-white shadow-lg shadow-blue-500/20 hover:bg-blue-700">Save Lorry</button>
                </form>

                <section class="bg-white/95 border border-slate-200 rounded-[22px] shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-gradient-to-r from-white to-slate-50">
                        <form class="grid grid-cols-1 md:grid-cols-[1fr_180px_auto] gap-3">
                            <input name="q" value="{{ request('q') }}" placeholder="Search lorry, plate, or driver">
                            <select name="status">
                                <option value="">All statuses</option>
                                @foreach(['active','maintenance','inactive'] as $status)
                                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                            <button class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-black text-white shadow-sm">Filter</button>
                        </form>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                                <tr><th class="text-left px-5 py-3">Lorry</th><th class="text-left px-5 py-3">Driver</th><th class="text-left px-5 py-3">Route</th><th class="text-left px-5 py-3">Status</th><th class="text-right px-5 py-3">Actions</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($lorries as $item)
                                    <tr>
                                        <td class="px-5 py-4">
                                            <a href="{{ route('distribution.lorries.show', $item->id) }}" class="font-black text-blue-700">{{ $item->lorry_no }}</a>
                                            <div class="text-xs text-slate-500">{{ $item->name }} @if($item->plate_no) / {{ $item->plate_no }} @endif</div>
                                        </td>
                                        <td class="px-5 py-4 text-slate-700">{{ $item->driver_name ?? '-' }}<div class="text-xs text-slate-400">{{ $item->driver_phone }}</div></td>
                                        <td class="px-5 py-4 text-slate-600">{{ $item->route_area ?? '-' }}</td>
                                        <td class="px-5 py-4"><span class="px-2 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700">{{ ucfirst($item->status) }}</span></td>
                                        <td class="px-5 py-4 text-right">
                                            <a href="{{ route('distribution.lorries.show', $item->id) }}" class="text-xs font-bold text-blue-600">Open</a>
                                            @if(auth()->user()->hasPermission('lorries.delete'))
                                            <form method="POST" action="{{ route('distribution.lorries.destroy', $item->id) }}" class="inline ml-3" onsubmit="return confirmAction(event, 'Remove this lorry from active list?')">
                                                @csrf @method('DELETE')
                                                <button class="text-xs font-bold text-rose-600">Remove</button>
                                            </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">No lorries found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4">{{ $lorries->links() }}</div>
                </section>
            </div>
        @endif

        @if($page === 'lorry-profile')
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <form method="POST" action="{{ route('distribution.lorries.update', $lorry->id) }}" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 space-y-4">
                    @csrf @method('PUT')
                    <h2 class="font-black text-slate-900">Lorry Details</h2>
                    <input name="lorry_no" value="{{ old('lorry_no', $lorry->lorry_no) }}" required class="w-full rounded-xl border-slate-200 text-sm">
                    <input name="name" value="{{ old('name', $lorry->name) }}" required class="w-full rounded-xl border-slate-200 text-sm">
                    <input name="plate_no" value="{{ old('plate_no', $lorry->plate_no) }}" placeholder="Plate number" class="w-full rounded-xl border-slate-200 text-sm">
                    <input name="driver_name" value="{{ old('driver_name', $lorry->driver_name) }}" placeholder="Driver name" class="w-full rounded-xl border-slate-200 text-sm">
                    <input name="driver_phone" value="{{ old('driver_phone', $lorry->driver_phone) }}" placeholder="Driver phone" class="w-full rounded-xl border-slate-200 text-sm">
                    <input name="route_area" value="{{ old('route_area', $lorry->route_area) }}" placeholder="Route area" class="w-full rounded-xl border-slate-200 text-sm">
                    <input name="current_odometer" type="number" min="0" step="any" value="{{ old('current_odometer', $lorry->current_odometer) }}" class="w-full rounded-xl border-slate-200 text-sm">
                    <select name="status" class="w-full rounded-xl border-slate-200 text-sm">
                        @foreach(['active','maintenance','inactive'] as $status)
                            <option value="{{ $status }}" @selected($lorry->status === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    <textarea name="notes" rows="3" class="w-full rounded-xl border-slate-200 text-sm">{{ old('notes', $lorry->notes) }}</textarea>
                    <button class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white">Update Lorry</button>
                </form>

                <section class="xl:col-span-2 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex justify-between">
                        <h2 class="font-black text-slate-900">Current Lorry Stock</h2>
                        <a href="{{ route('distribution.load', ['to_lorry_id' => $lorry->id]) }}" class="text-xs font-bold text-blue-600">Load stock</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                                <tr><th class="text-left px-5 py-3">Product</th><th class="text-right px-5 py-3">Qty</th><th class="text-right px-5 py-3">Value</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($stocks as $stock)
                                    <tr><td class="px-5 py-4 font-bold text-slate-800">{{ $stock->product_name }}<div class="text-xs text-slate-400">{{ $stock->sku }}</div></td><td class="px-5 py-4 text-right font-black">{{ number_format($stock->quantity, 2) }}</td><td class="px-5 py-4 text-right font-bold">{{ $money($stock->quantity * $stock->cost_price) }}</td></tr>
                                @empty
                                    <tr><td colspan="3" class="px-5 py-10 text-center text-slate-400">No stock loaded to this lorry yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4">{{ $stocks->links() }}</div>
                </section>
            </div>
        @endif

        @if($page === 'stock')
            <section class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100">
                    <form class="grid grid-cols-1 md:grid-cols-[240px_1fr_auto] gap-3">
                        <select name="lorry_id" class="rounded-xl border-slate-200 text-sm">
                            <option value="">All lorries</option>
                            @foreach($lorries as $lorryOption)
                                <option value="{{ $lorryOption->id }}" @selected(request('lorry_id') == $lorryOption->id)>{{ $lorryOption->lorry_no }} - {{ $lorryOption->name }}</option>
                            @endforeach
                        </select>
                        <input name="q" value="{{ request('q') }}" placeholder="Search product, SKU, barcode" class="rounded-xl border-slate-200 text-sm">
                        <button class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white">Filter</button>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                            <tr><th class="text-left px-5 py-3">Lorry</th><th class="text-left px-5 py-3">Product</th><th class="text-right px-5 py-3">Qty</th><th class="text-right px-5 py-3">Cost Value</th><th class="text-right px-5 py-3">Wholesale Value</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($stocks as $stock)
                                <tr>
                                    <td class="px-5 py-4"><div class="font-bold text-slate-900">{{ $stock->lorry_no }}</div><div class="text-xs text-slate-500">{{ $stock->lorry_name }}</div></td>
                                    <td class="px-5 py-4"><div class="font-bold text-slate-900">{{ $stock->product_name }}</div><div class="text-xs text-slate-400">{{ $stock->sku }} {{ $stock->variant_sku ? '/ '.$stock->variant_sku : '' }}</div></td>
                                    <td class="px-5 py-4 text-right font-black {{ $stock->quantity <= $stock->minimum_stock && $stock->minimum_stock > 0 ? 'text-rose-600' : 'text-slate-900' }}">{{ number_format($stock->quantity, 2) }}</td>
                                    <td class="px-5 py-4 text-right font-bold">{{ $money($stock->quantity * $stock->cost_price) }}</td>
                                    <td class="px-5 py-4 text-right font-bold text-emerald-700">{{ $money($stock->quantity * $stock->wholesale_price) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">No lorry stock found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4">{{ $stocks->links() }}</div>
            </section>
        @endif

        @if($page === 'transfer')
            <div x-data="distributionTransfer('{{ route('distribution.product-search') }}', '{{ $type }}')" class="grid grid-cols-1 xl:grid-cols-[1fr_420px] gap-6">
                <form method="POST" class="bg-white/95 border border-sky-100 rounded-[22px] shadow-sm overflow-hidden">
                    @csrf
                    <div class="p-6 border-b border-sky-100 bg-gradient-to-r from-sky-50 via-white to-emerald-50">
                        <div class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-emerald-700">Stock movement</div>
                        <h2 class="mt-3 text-xl font-black text-slate-950">{{ $title }}</h2>
                        <p class="text-sm text-slate-600 mt-1">{{ $subtitle }}</p>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @if($type === 'store_to_lorry')
                                <label class="block text-xs font-black uppercase tracking-wide">From Store
                                    <select name="from_store_id" x-model="fromStoreId" class="mt-2" required>
                                        @foreach($stores as $store)<option value="{{ $store->id }}">{{ $store->name }}</option>@endforeach
                                    </select>
                                </label>
                                <label class="block text-xs font-black uppercase tracking-wide">To Lorry
                                    <select name="to_lorry_id" x-model="toLorryId" class="mt-2" required>
                                        @foreach($lorries as $lorry)<option value="{{ $lorry->id }}" @selected(request('to_lorry_id') == $lorry->id)>{{ $lorry->lorry_no }} - {{ $lorry->name }}</option>@endforeach
                                    </select>
                                </label>
                            @elseif($type === 'lorry_to_store')
                                <label class="block text-xs font-black uppercase tracking-wide">From Lorry
                                    <select name="from_lorry_id" x-model="fromLorryId" class="mt-2" required>
                                        @foreach($lorries as $lorry)<option value="{{ $lorry->id }}">{{ $lorry->lorry_no }} - {{ $lorry->name }}</option>@endforeach
                                    </select>
                                </label>
                                <label class="block text-xs font-black uppercase tracking-wide">To Store
                                    <select name="to_store_id" class="mt-2" required>
                                        @foreach($stores as $store)<option value="{{ $store->id }}">{{ $store->name }}</option>@endforeach
                                    </select>
                                </label>
                            @else
                                <label class="block text-xs font-black uppercase tracking-wide">From Lorry
                                    <select name="from_lorry_id" x-model="fromLorryId" class="mt-2" required>
                                        @foreach($lorries as $lorry)<option value="{{ $lorry->id }}">{{ $lorry->lorry_no }} - {{ $lorry->name }}</option>@endforeach
                                    </select>
                                </label>
                                <label class="block text-xs font-black uppercase tracking-wide">To Lorry
                                    <select name="to_lorry_id" x-model="toLorryId" class="mt-2" required>
                                        @foreach($lorries as $lorry)<option value="{{ $lorry->id }}">{{ $lorry->lorry_no }} - {{ $lorry->name }}</option>@endforeach
                                    </select>
                                </label>
                            @endif
                            <label class="block text-xs font-black uppercase tracking-wide">Date
                                <input name="transfer_date" type="date" value="{{ old('transfer_date', now()->toDateString()) }}" class="mt-2" required>
                            </label>
                            <label class="block text-xs font-black uppercase tracking-wide md:col-span-3">Trip (optional)
                                <select name="trip_id" class="mt-2">
                                    <option value="">No trip link</option>
                                    @foreach($trips as $trip)<option value="{{ $trip->id }}">{{ $trip->trip_no }} / Lorry #{{ $trip->lorry_id }} / {{ $trip->route_name }}</option>@endforeach
                                </select>
                            </label>
                        </div>

                        <div class="relative">
                            <input x-model="query" @input.debounce.350ms="search()" placeholder="Search product by name, SKU, or barcode" class="rounded-2xl">
                            <div x-show="results.length" x-cloak class="absolute z-20 mt-2 w-full bg-white border border-slate-200 rounded-2xl shadow-xl max-h-80 overflow-auto">
                                <template x-for="product in results" :key="product.id">
                                    <button type="button" @click="add(product)" class="w-full px-5 py-3 text-left hover:bg-blue-50 border-b border-slate-100">
                                        <span class="block font-bold text-slate-900" x-text="product.name"></span>
                                        <span class="text-xs text-slate-500" x-text="`${product.sku || ''} / Available ${Number(product.stock).toFixed(2)} / ${Number(product.wholesale_price || product.retail_price || 0).toFixed(2)}`"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                                    <tr><th class="text-left px-4 py-3">Product</th><th class="text-right px-4 py-3">Available</th><th class="text-right px-4 py-3">Qty</th><th class="text-right px-4 py-3">Cost</th><th class="px-4 py-3"></th></tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="(item, index) in items" :key="item.product_id">
                                        <tr>
                                            <td class="px-4 py-3">
                                                <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id">
                                                <input type="hidden" :name="`items[${index}][variant_id]`" :value="item.variant_id || ''">
                                                <div class="font-bold text-slate-900" x-text="item.name"></div>
                                                <div class="text-xs text-slate-400" x-text="item.sku"></div>
                                            </td>
                                            <td class="px-4 py-3 text-right font-bold text-emerald-700" x-text="Number(item.stock).toFixed(2)"></td>
                                            <td class="px-4 py-3"><input type="number" min="0.01" step="any" :name="`items[${index}][quantity]`" x-model="item.quantity" class="w-28 rounded-xl border-slate-200 text-right text-sm" required></td>
                                            <td class="px-4 py-3"><input type="number" min="0" step="any" :name="`items[${index}][cost_price]`" x-model="item.cost_price" class="w-32 rounded-xl border-slate-200 text-right text-sm"></td>
                                            <td class="px-4 py-3 text-right"><button type="button" @click="items.splice(index, 1)" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-100">Remove</button></td>
                                        </tr>
                                    </template>
                                    <tr x-show="items.length === 0"><td colspan="5" class="px-5 py-10 text-center text-slate-400">Search and add products for this movement.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <textarea name="notes" rows="3" placeholder="Notes"></textarea>
                        <button class="rounded-2xl bg-blue-600 px-6 py-4 text-sm font-black text-white shadow-lg shadow-blue-500/20 hover:bg-blue-700">{{ $buttonLabel }}</button>
                    </div>
                </form>

                <section class="bg-white/95 border border-slate-200 rounded-[22px] shadow-sm overflow-hidden h-fit">
                    <div class="px-5 py-4 border-b border-slate-100 bg-gradient-to-r from-white to-blue-50"><h2 class="font-black text-slate-900">Recent {{ $title }}</h2></div>
                    <div class="divide-y divide-slate-100">
                        @forelse($recentTransfers as $transfer)
                            <div class="px-5 py-4">
                                <div class="font-bold text-slate-900">{{ $transfer->transfer_no }}</div>
                                <div class="text-xs text-slate-500">{{ $transfer->from_store_name ?? $transfer->from_lorry_no ?? '-' }} → {{ $transfer->to_store_name ?? $transfer->to_lorry_no ?? '-' }}</div>
                            </div>
                        @empty
                            <div class="px-5 py-10 text-center text-slate-400">No transfers yet.</div>
                        @endforelse
                    </div>
                </section>
            </div>
        @endif

        @if(in_array($page, ['trips','sale-returns','expenses','collections','reconciliation','settings'], true))
            @include('distribution.partials.forms')
        @endif

        @if($page === 'sales')
            <section class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100">
                    <form class="grid grid-cols-1 md:grid-cols-[220px_160px_160px_auto] gap-3">
                        <select name="lorry_id" class="rounded-xl border-slate-200 text-sm"><option value="">All lorries</option>@foreach($lorries as $lorry)<option value="{{ $lorry->id }}" @selected(request('lorry_id') == $lorry->id)>{{ $lorry->lorry_no }}</option>@endforeach</select>
                        <input name="from" type="date" value="{{ request('from') }}" class="rounded-xl border-slate-200 text-sm">
                        <input name="to" type="date" value="{{ request('to') }}" class="rounded-xl border-slate-200 text-sm">
                        <button class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white">Filter</button>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500"><tr><th class="text-left px-5 py-3">Invoice</th><th class="text-left px-5 py-3">Customer</th><th class="text-left px-5 py-3">Lorry / Trip</th><th class="text-right px-5 py-3">Total</th><th class="text-right px-5 py-3">Due</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($sales as $sale)
                            <tr><td class="px-5 py-4 font-bold">{{ $sale->invoice_no }}</td><td class="px-5 py-4">{{ $sale->customer_name ?? 'Walk-in' }}</td><td class="px-5 py-4">{{ $sale->lorry_no ?? '-' }}<div class="text-xs text-slate-400">{{ $sale->trip_no }}</div></td><td class="px-5 py-4 text-right font-bold">{{ $money($sale->payable_amount) }}</td><td class="px-5 py-4 text-right font-bold text-rose-600">{{ $money($sale->due_amount) }}</td></tr>
                            @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">No lorry sales found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4">{{ $sales->links() }}</div>
            </section>
        @endif

        @if($page === 'reports')
            <section class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
                <form class="grid grid-cols-1 md:grid-cols-[220px_160px_160px_auto] gap-3 mb-5">
                    <select name="lorry_id" class="rounded-xl border-slate-200 text-sm"><option value="">All lorries</option>@foreach($lorries as $lorry)<option value="{{ $lorry->id }}" @selected($lorryId == $lorry->id)>{{ $lorry->lorry_no }}</option>@endforeach</select>
                    <input name="from" type="date" value="{{ $from }}" class="rounded-xl border-slate-200 text-sm">
                    <input name="to" type="date" value="{{ $to }}" class="rounded-xl border-slate-200 text-sm">
                    <button class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white">Run Report</button>
                </form>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
                    @foreach($summary as $card)
                    <div class="relative overflow-hidden rounded-2xl border border-{{ $card['color'] ?? 'slate' }}-200 bg-gradient-to-br from-white to-{{ $card['color'] ?? 'slate' }}-50/50 p-5 shadow-sm transition hover:shadow-md">
                        <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-{{ $card['color'] ?? 'slate' }}-100/40 blur-2xl"></div>
                        <div class="relative z-10">
                            <div class="text-[11px] font-extrabold uppercase tracking-widest text-{{ $card['color'] ?? 'slate' }}-600/70">{{ $card['label'] }}</div>
                            <div class="text-2xl font-black text-{{ $card['color'] ?? 'slate' }}-900 mt-2 tracking-tight">{{ $card['value'] }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </section>
            <section class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100"><h2 class="font-black text-slate-900">Stock By Lorry</h2></div>
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500"><tr><th class="text-left px-5 py-3">Lorry</th><th class="text-right px-5 py-3">Units</th><th class="text-right px-5 py-3">Cost Value</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($stockByLorry as $row)<tr><td class="px-5 py-4 font-bold">{{ $row->lorry_no }} - {{ $row->name }}</td><td class="px-5 py-4 text-right">{{ number_format($row->qty, 2) }}</td><td class="px-5 py-4 text-right font-bold">{{ $money($row->value) }}</td></tr>@endforeach
                    </tbody>
                </table>
            </section>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Sales & Profit Breakdown -->
                <section class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden flex flex-col max-h-[500px]">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50 sticky top-0"><h2 class="font-black text-slate-900">Sales & Profit Breakdown</h2><p class="text-xs text-slate-500 mt-1">By product for the selected period</p></div>
                    <div class="overflow-y-auto flex-1">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500 sticky top-0 shadow-sm"><tr><th class="text-left px-5 py-3">Product</th><th class="text-right px-5 py-3">Qty</th><th class="text-right px-5 py-3">Sales</th><th class="text-right px-5 py-3">Profit</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($salesBreakdown as $row)
                                <tr><td class="px-5 py-3 font-bold">{{ $row->product_name }}</td><td class="px-5 py-3 text-right">{{ number_format($row->total_qty, 2) }}</td><td class="px-5 py-3 text-right font-bold text-slate-700">{{ $money($row->total_sales) }}</td><td class="px-5 py-3 text-right font-bold text-emerald-600">{{ $money($row->total_profit) }}</td></tr>
                                @empty
                                <tr><td colspan="4" class="px-5 py-10 text-center text-slate-400">No sales data for this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
                
                <!-- Expenses Breakdown -->
                <section class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden flex flex-col max-h-[500px]">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50 sticky top-0"><h2 class="font-black text-slate-900">Expenses Breakdown</h2><p class="text-xs text-slate-500 mt-1">By category for the selected period</p></div>
                    <div class="overflow-y-auto flex-1">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500 sticky top-0 shadow-sm"><tr><th class="text-left px-5 py-3">Category</th><th class="text-right px-5 py-3">Amount</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($expensesBreakdown as $row)
                                <tr><td class="px-5 py-3 font-bold">{{ $row->category_name }}</td><td class="px-5 py-3 text-right font-bold text-rose-600">{{ $money($row->total_amount) }}</td></tr>
                                @empty
                                <tr><td colspan="2" class="px-5 py-10 text-center text-slate-400">No expense data for this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
                </section>
            </div>
            
            <!-- Invoices List -->
            <section class="mt-6 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden flex flex-col max-h-[500px]">
                <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50 sticky top-0"><h2 class="font-black text-slate-900">Sales Invoices Breakdown</h2><p class="text-xs text-slate-500 mt-1">Individual invoices for the selected period</p></div>
                <div class="overflow-y-auto flex-1">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500 sticky top-0 shadow-sm">
                            <tr>
                                <th class="text-left px-5 py-3">Invoice</th>
                                <th class="text-left px-5 py-3">Customer</th>
                                <th class="text-left px-5 py-3">Lorry</th>
                                <th class="text-right px-5 py-3">Total</th>
                                <th class="text-right px-5 py-3 text-emerald-600">Cash</th>
                                <th class="text-right px-5 py-3 text-blue-600">Bank</th>
                                <th class="text-right px-5 py-3 text-purple-600">Cheque</th>
                                <th class="text-right px-5 py-3 text-rose-600">Due</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($salesList as $sale)
                            <tr>
                                <td class="px-5 py-3 font-bold">{{ $sale->invoice_no }}</td>
                                <td class="px-5 py-3">{{ $sale->customer_name ?? 'Walk-in' }}</td>
                                <td class="px-5 py-3">{{ $sale->lorry_no ?? '-' }}</td>
                                <td class="px-5 py-3 text-right font-bold text-slate-700">{{ $money($sale->payable_amount) }}</td>
                                <td class="px-5 py-3 text-right font-bold {{ $sale->cash_paid > 0 ? 'text-emerald-600' : 'text-slate-300' }}">{{ $money($sale->cash_paid) }}</td>
                                <td class="px-5 py-3 text-right font-bold {{ $sale->bank_paid > 0 ? 'text-blue-600' : 'text-slate-300' }}">{{ $money($sale->bank_paid) }}</td>
                                <td class="px-5 py-3 text-right font-bold {{ $sale->cheque_paid > 0 ? 'text-purple-600' : 'text-slate-300' }}">
                                    {{ $money($sale->cheque_paid) }}
                                    @if($sale->cheque_paid > 0 && !empty($sale->cheque_statuses))
                                        <div class="mt-1 flex flex-wrap justify-end gap-1">
                                            @foreach($sale->cheque_statuses as $status)
                                                @php
                                                    $statusColor = match($status) {
                                                        'cleared', 'passed' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                                        'bounced', 'returned', 'rejected' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                                        'hold' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                                        default => 'bg-slate-50 text-slate-700 ring-slate-600/20'
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-medium ring-1 ring-inset {{ $statusColor }}">{{ ucfirst($status) }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right font-bold {{ $sale->due_amount > 0 ? 'text-rose-600' : 'text-slate-300' }}">{{ $money($sale->due_amount) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="px-5 py-10 text-center text-slate-400">No invoices for this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</div>

<script src="//unpkg.com/alpinejs" defer></script>
<script>
function distributionTransfer(searchUrl, type) {
    return {
        query: '',
        results: [],
        items: [],
        fromStoreId: '{{ $stores->first()->id ?? '' }}',
        fromLorryId: '{{ $lorries->first()->id ?? '' }}',
        toLorryId: '{{ request('to_lorry_id', $lorries->first()->id ?? '') }}',
        async search() {
            if (this.query.length < 1) {
                this.results = [];
                return;
            }
            const params = new URLSearchParams({
                q: this.query,
                source: type === 'store_to_lorry' ? 'store' : 'lorry',
                store_id: this.fromStoreId || '',
                lorry_id: this.fromLorryId || '',
            });
            const response = await fetch(`${searchUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
            this.results = await response.json();
        },
        add(product) {
            if (this.items.some(item => item.product_id === product.id)) {
                this.results = [];
                this.query = '';
                return;
            }
            this.items.push({
                product_id: product.id,
                variant_id: null,
                name: product.name,
                sku: product.sku,
                stock: Number(product.stock || 0),
                quantity: 1,
                cost_price: Number(product.cost_price || 0),
            });
            this.results = [];
            this.query = '';
        }
    };
}
</script>
</x-layouts::app>
