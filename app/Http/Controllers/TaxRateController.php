<?php

namespace App\Http\Controllers;

use App\Models\TaxRate;
use Illuminate\Http\Request;

class TaxRateController extends Controller
{
    public function index()
    {
        $taxRates = TaxRate::orderBy('tax_type')->orderBy('name')->get();
        return view('tax_rates.index', compact('taxRates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:tax_rates,code',
            'tax_type' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0',
            'calculation_method' => 'required|string|max:255',
            'tax_classification' => 'required|string|max:255',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['is_default'] = $request->has('is_default');
        $validated['is_active'] = $request->has('is_active');

        // If this is set as default, unset others for the same tax type
        if ($validated['is_default']) {
            TaxRate::where('tax_type', $validated['tax_type'])->update(['is_default' => false]);
        }

        TaxRate::create($validated);

        return redirect()->route('accounting.tax-rates.index')->with('success', 'Tax Rate created successfully.');
    }

    public function update(Request $request, TaxRate $taxRate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:tax_rates,code,' . $taxRate->id,
            'tax_type' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0',
            'calculation_method' => 'required|string|max:255',
            'tax_classification' => 'required|string|max:255',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['updated_by'] = auth()->id();
        $validated['is_default'] = $request->has('is_default');
        $validated['is_active'] = $request->has('is_active');

        // If this is set as default, unset others for the same tax type
        if ($validated['is_default']) {
            TaxRate::where('tax_type', $validated['tax_type'])->where('id', '!=', $taxRate->id)->update(['is_default' => false]);
        }

        $taxRate->update($validated);

        return redirect()->route('accounting.tax-rates.index')->with('success', 'Tax Rate updated successfully.');
    }

    public function destroy(TaxRate $taxRate)
    {
        $taxRate->delete();
        return redirect()->route('accounting.tax-rates.index')->with('success', 'Tax Rate deleted successfully.');
    }
}
