<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $brands = Brand::withCount('products')->get();
        return view('brands.index', compact('brands'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'max:2048'], // Max 2MB
        ]);

        $slug = Str::slug($validated['name']);
        
        // Ensure slug is unique
        $originalSlug = $slug;
        $count = 1;
        while (Brand::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('brands', 'public');
        }

        $brand = Brand::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'image' => $logoPath,
        ]);

        ActivityLog::log('Create', 'Products Module', "Created brand: {$brand->name}");

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Brand created successfully.',
                'brand' => [
                    'id' => $brand->id,
                    'name' => $brand->name,
                ],
            ]);
        }

        return redirect()->route('brands.index')->with('success', 'Brand created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ];

        if ($request->hasFile('logo')) {
            // Delete old image if exists
            if ($brand->image) {
                Storage::disk('public')->delete($brand->image);
            }
            $updateData['image'] = $request->file('logo')->store('brands', 'public');
        }

        $brand->update($updateData);

        ActivityLog::log('Update', 'Products Module', "Updated brand details for: {$brand->name}");

        return redirect()->route('brands.index')->with('success', 'Brand updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Brand $brand)
    {
        $brandName = $brand->name;

        // Soft delete the brand
        $brand->delete();

        ActivityLog::log('Delete', 'Products Module', "Soft deleted brand: {$brandName}");

        return redirect()->route('brands.index')->with('success', 'Brand deleted successfully.');
    }
}
