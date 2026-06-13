<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $units = Unit::withCount('products')->get();
        return view('units.index', compact('units'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:units,name'],
            'short_name' => ['required', 'string', 'max:20', 'unique:units,short_name'],
        ]);

        $unit = Unit::create($validated);

        ActivityLog::log('Create', 'Products Module', "Created unit: {$unit->name} ({$unit->short_name})");

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Unit created successfully.',
                'unit' => [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'short_name' => $unit->short_name,
                ],
            ]);
        }

        return redirect()->route('units.index')->with('success', 'Unit created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:units,name,' . $unit->id],
            'short_name' => ['required', 'string', 'max:20', 'unique:units,short_name,' . $unit->id],
        ]);

        $unit->update($validated);

        ActivityLog::log('Update', 'Products Module', "Updated unit details for: {$unit->name}");

        return redirect()->route('units.index')->with('success', 'Unit updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Unit $unit)
    {
        // Check if products are assigned to this unit
        if ($unit->products()->count() > 0) {
            return redirect()->route('units.index')->with('error', "Unit '{$unit->name}' cannot be deleted because it is assigned to existing products.");
        }

        $unitName = $unit->name;
        $unit->delete();

        ActivityLog::log('Delete', 'Products Module', "Deleted unit: {$unitName}");

        return redirect()->route('units.index')->with('success', 'Unit deleted successfully.');
    }
}
