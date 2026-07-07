<?php
namespace App\Services;

use App\Models\JournalEntry;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

class JournalService
{
    /**
     * Create a double-entry journal.
     * $items format: [['account_id' => 1, 'debit' => 100, 'credit' => 0, 'notes' => ''], ...]
     */
    public function createEntry($date, $type, $reference_id, $reference_no, $description, array $items, $isAuto = true, $createdBy = null)
    {
        $createdBy = $createdBy ?? auth()->id();

        return DB::transaction(function () use ($date, $type, $reference_id, $reference_no, $description, $items, $isAuto, $createdBy) {
            $journal = JournalEntry::create([
                'journal_no' => 'JRN-' . strtoupper(uniqid()),
                'journal_date' => $date,
                'reference_type' => $type,
                'reference_id' => $reference_id,
                'reference_no' => $reference_no,
                'description' => $description,
                'is_auto_generated' => $isAuto,
                'created_by' => $createdBy
            ]);

            foreach ($items as $item) {
                if ($item['debit'] > 0 || $item['credit'] > 0) {
                    $journal->items()->create([
                        'account_id' => $item['account_id'],
                        'debit' => $item['debit'] ?? 0,
                        'credit' => $item['credit'] ?? 0,
                        'notes' => $item['notes'] ?? null,
                    ]);

                    // Update account balance
                    $this->updateAccountBalance($item['account_id'], $item['debit'] ?? 0, $item['credit'] ?? 0);
                }
            }

            return $journal;
        });
    }

    private function updateAccountBalance($accountId, $debit, $credit)
    {
        $account = Account::find($accountId);
        if (!$account) return;

        // Asset & Expense increase with Debit.
        // Liability, Equity, Income increase with Credit.
        if (in_array($account->account_type, ['Asset', 'Expense'])) {
            $account->current_balance += $debit;
            $account->current_balance -= $credit;
        } else {
            $account->current_balance += $credit;
            $account->current_balance -= $debit;
        }
        $account->save();
    }

    public function deleteEntryByReference($type, $reference_id)
    {
        $journal = JournalEntry::where('reference_type', $type)->where('reference_id', $reference_id)->first();
        if ($journal) {
            $this->deleteEntry($journal);
        }
    }

    public function deleteEntry(JournalEntry $journal)
    {
        DB::transaction(function () use ($journal) {
            foreach ($journal->items as $item) {
                // Reverse the balance
                $this->updateAccountBalance($item->account_id, $item->credit, $item->debit); // Swapped debit/credit
            }
            $journal->delete();
        });
    }
}
