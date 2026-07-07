<?php
namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::withCount('expenses')->paginate(20);
        return view('accounting.expense_categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories',
            'description' => 'nullable|string',
        ]);

        $category = ExpenseCategory::create($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'category' => $category,
                'message' => 'Category created successfully.'
            ]);
        }

        return redirect()->route('accounting.expense-categories.index')->with('success', 'Category created successfully.');
    }

    public function update(Request $request, ExpenseCategory $expense_category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name,' . $expense_category->id,
            'description' => 'nullable|string',
        ]);

        $expense_category->update($validated);

        return redirect()->route('accounting.expense-categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(ExpenseCategory $expense_category)
    {
        if ($expense_category->expenses()->exists()) {
            return redirect()->route('accounting.expense-categories.index')->with('error', 'Cannot delete category that is used in expenses.');
        }

        $expense_category->delete();

        return redirect()->route('accounting.expense-categories.index')->with('success', 'Category deleted successfully.');
    }
}
