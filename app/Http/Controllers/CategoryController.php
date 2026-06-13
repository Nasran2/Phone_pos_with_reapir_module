<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::with('parent')->withCount('products')->get();
        $parentCategories = Category::whereNull('parent_id')->get();

        return view('categories.index', compact('categories', 'parentCategories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'exists:categories,id'],
        ]);

        $slug = Str::slug($validated['name']);
        
        // Ensure slug is unique
        $originalSlug = $slug;
        $count = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        ActivityLog::log('Create', 'Products Module', "Created category: {$category->name}");

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Category created successfully.',
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                ],
            ]);
        }

        return redirect()->route('categories.index')->with('success', 'Category created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'exists:categories,id', 'different:id'],
        ]);

        $parentId = $validated['parent_id'] ?? null;
        if ($parentId && $parentId == $category->id) {
            return redirect()->back()->withErrors(['parent_id' => 'A category cannot be its own parent.']);
        }

        $category->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'parent_id' => $parentId,
        ]);

        ActivityLog::log('Update', 'Products Module', "Updated category: {$category->name}");

        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        $categoryName = $category->name;

        // Soft delete the category
        $category->delete();

        ActivityLog::log('Delete', 'Products Module', "Soft deleted category: {$categoryName}");

        return redirect()->route('categories.index')->with('success', 'Category deleted successfully.');
    }
}
