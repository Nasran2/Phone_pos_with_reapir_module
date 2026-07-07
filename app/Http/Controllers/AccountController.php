<?php
namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index()
    {
        $accounts = Account::orderBy('code')->paginate(50);
        return view('accounting.accounts.index', compact('accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:chart_of_accounts',
            'name' => 'required|string|max:255',
            'type' => 'required|in:Asset,Liability,Equity,Revenue,Expense',
            'is_active' => 'boolean'
        ]);
        
        $validated['group_id'] = 1; // Default
        $validated['balance'] = 0;

        Account::create($validated);
        return redirect()->route('accounting.accounts.index')->with('success', 'Account created successfully.');
    }

    public function update(Request $request, Account $account)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:chart_of_accounts,code,' . $account->id,
            'name' => 'required|string|max:255',
            'type' => 'required|in:Asset,Liability,Equity,Revenue,Expense',
            'is_active' => 'boolean'
        ]);

        $account->update($validated);
        return redirect()->route('accounting.accounts.index')->with('success', 'Account updated successfully.');
    }

    public function destroy(Account $account)
    {
        if ($account->ledger_entries()->exists() || \App\Models\Expense::where('chart_of_account_id', $account->id)->exists()) {
            return redirect()->route('accounting.accounts.index')->with('error', 'Cannot delete account with existing transactions.');
        }
        $account->delete();
        return redirect()->route('accounting.accounts.index')->with('success', 'Account deleted successfully.');
    }
}