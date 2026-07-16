<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Cheque;
use App\Models\JournalEntry;

class ChequeAccountingService
{
    public function __construct(private JournalService $journals) {}

    public function created(Cheque $cheque): void
    {
        if ($cheque->cheque_type === 'received') {
            $this->entry($cheque, 'ChequeCreated', 'Cheque received and moved to hold', $this->account('1110'), $this->account('1105'));
        } else {
            $this->entry($cheque, 'ChequeCreated', 'Issued cheque moved to payable hold', $this->account('2105'), $this->account('2110'));
        }
    }

    public function passed(Cheque $cheque): void
    {
        if ($cheque->cheque_type === 'received') {
            $this->entry($cheque, 'ChequePassed', 'Received cheque cleared', $cheque->deposit_account_id ?: $this->defaultBank(), $this->account('1110'));
        } else {
            $this->entry($cheque, 'ChequePassed', 'Issued cheque cleared', $this->account('2110'), $cheque->source_account_id ?: $cheque->deposit_account_id ?: $this->defaultBank());
        }
    }

    public function returned(Cheque $cheque): void
    {
        if ($cheque->cheque_type === 'received') {
            $this->entry($cheque, 'ChequeReturned', 'Received cheque returned to customer due', $this->account('1105'), $this->account('1110'));
        } else {
            $this->entry($cheque, 'ChequeReturned', 'Issued cheque returned to supplier payable', $this->account('2110'), $this->account('2105'));
        }
    }

    private function entry(Cheque $cheque, string $type, string $description, ?int $debit, ?int $credit): void
    {
        if (! $debit || ! $credit || JournalEntry::where('reference_type', $type)->where('reference_id', $cheque->id)->exists()) return;
        $this->journals->createEntry(today(), $type, $cheque->id, $cheque->cheque_reference, $description, [
            ['account_id'=>$debit,'debit'=>(float)$cheque->amount,'credit'=>0],
            ['account_id'=>$credit,'debit'=>0,'credit'=>(float)$cheque->amount],
        ], true, $cheque->created_by);
    }

    private function account(string $code): ?int { return Account::where('account_code',$code)->value('id'); }
    private function defaultBank(): ?int { return Account::where('account_type','Asset')->where('account_name','like','%Bank%')->value('id') ?: Account::where('account_type','Asset')->where('account_name','like','%Cash%')->value('id'); }
}
