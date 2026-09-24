<?php
namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class ChartOfAccountController extends Controller
{
    public function index(Request $request)
    {
        $query = Account::with('parent')->orderBy('account_code');
        
        if ($request->filled('type')) {
            $query->where('account_type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status == 'active' ? 1 : 0);
        }
        if ($request->filled('search')) {
            $query->where(fn ($search) => $search->where('account_name', 'like', '%' . $request->search . '%')
                  ->orWhere('account_code', 'like', '%' . $request->search . '%'));
        }

        $accounts = $query->paginate(20)->withQueryString();
        return view('accounting.accounts.index', compact('accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_code' => 'required|string|unique:accounts',
            'account_name' => 'required|string|max:255',
            'account_type' => 'required|in:Asset,Liability,Equity,Income,Expense',
            'parent_id' => 'nullable|exists:accounts,id',
            'opening_balance' => 'nullable|numeric',
            'description' => 'nullable|string',
            'status' => 'boolean'
        ]);

        $validated['current_balance'] = $validated['opening_balance'] ?? 0;
        $validated['status'] = $request->has('status');

        Account::create($validated);
        return redirect()->route('accounting.accounts.index')->with('success', 'Account created successfully.');
    }

    public function destroy(Account $account)
    {
        if ($account->journalItems()->exists()) {
            return redirect()->route('accounting.accounts.index')->with('error', 'Cannot delete account with existing transactions.');
        }
        $account->delete();
        return redirect()->route('accounting.accounts.index')->with('success', 'Account deleted successfully.');
    }
}
