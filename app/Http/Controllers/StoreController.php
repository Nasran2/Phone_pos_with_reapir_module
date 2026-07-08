<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        $query = Store::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('manager', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status') === 'active');
        }

        $stores = $query->latest()->paginate(10)->withQueryString();

        return view('stores.index', compact('stores'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'unique:stores,code'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'manager' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['status'] = $request->has('status');

        $store = Store::create($validated);
        
        ActivityLog::log('Create', 'Stores Module', "Created store: {$store->name} ({$store->code})");

        return back()->with('success', 'Store created successfully.');
    }

    public function update(Request $request, Store $store)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('stores')->ignore($store->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'manager' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['status'] = $request->has('status');

        if ($store->is_default && !$validated['status']) {
            return back()->with('error', 'Cannot deactivate the default store.');
        }

        $store->update($validated);
        
        ActivityLog::log('Update', 'Stores Module', "Updated store: {$store->name} ({$store->code})");

        return back()->with('success', 'Store updated successfully.');
    }

    public function destroy(Store $store)
    {
        if ($store->is_default) {
            return back()->with('error', 'Cannot delete the default store.');
        }

        if ($store->stock()->where('quantity', '>', 0)->exists()) {
            return back()->with('error', 'Cannot delete a store that currently holds stock. Transfer the stock first.');
        }

        $name = $store->name;
        $store->delete();

        ActivityLog::log('Delete', 'Stores Module', "Deleted store: {$name}");

        return back()->with('success', 'Store deleted successfully.');
    }
}
