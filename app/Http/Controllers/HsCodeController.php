<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HsCodeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $hsCodes = \App\Models\HsCode::all();
        return view('hs_codes.index', compact('hsCodes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:hs_codes,code'],
            'description' => ['required', 'string', 'max:255'],
            'customs_duty_rate' => ['required', 'numeric', 'min:0'],
            'pal_rate' => ['required', 'numeric', 'min:0'],
            'cess_rate' => ['required', 'numeric', 'min:0'],
            'srid_rate' => ['required', 'numeric', 'min:0'],
            'sscl_rate' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['required', 'date'],
        ]);

        $validated['created_by'] = auth()->id();
        $validated['is_active'] = $request->has('is_active');

        \App\Models\HsCode::create($validated);

        return redirect()->route('hs-codes.index')->with('success', 'HS Code created successfully.');
    }

    public function update(Request $request, \App\Models\HsCode $hsCode)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:hs_codes,code,' . $hsCode->id],
            'description' => ['required', 'string', 'max:255'],
            'customs_duty_rate' => ['required', 'numeric', 'min:0'],
            'pal_rate' => ['required', 'numeric', 'min:0'],
            'cess_rate' => ['required', 'numeric', 'min:0'],
            'srid_rate' => ['required', 'numeric', 'min:0'],
            'sscl_rate' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['required', 'date'],
        ]);

        $validated['is_active'] = $request->has('is_active');

        $hsCode->update($validated);

        return redirect()->route('hs-codes.index')->with('success', 'HS Code updated successfully.');
    }

    public function destroy(\App\Models\HsCode $hsCode)
    {
        try {
            $hsCode->delete();
            return redirect()->route('hs-codes.index')->with('success', 'HS Code deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('hs-codes.index')->with('error', 'Cannot delete HS Code. It may be in use.');
        }
    }
}
