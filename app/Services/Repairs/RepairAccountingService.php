<?php

namespace App\Services\Repairs;

use App\Models\Account;
use App\Models\RepairJob;
use App\Services\JournalService;
use App\Support\RepairMoney as Money;
use Illuminate\Validation\ValidationException;

class RepairAccountingService
{
    /** @param array<int|string, int> $movements */
    public function post(RepairJob $job, string $reference, array $movements): void
    {
        $movements = array_filter($movements, fn ($cents) => $cents !== 0);
        if (! $movements) {
            return;
        }
        if (array_sum($movements) !== 0) {
            throw new \LogicException('Repair journal is unbalanced.');
        }
        $accounts = Account::whereIn('account_code', array_keys($movements))->orderBy('id')->lockForUpdate()->get()->keyBy('account_code');
        if ($accounts->count() !== count($movements)) {
            throw ValidationException::withMessages(['account' => 'The accounting chart is missing a required repair account.']);
        }
        $items = [];
        foreach ($movements as $code => $cents) {
            $items[] = ['account_id' => $accounts[$code]->id, 'debit' => $cents > 0 ? Money::decimal($cents) : 0, 'credit' => $cents < 0 ? Money::decimal(-$cents) : 0];
        }
        app(JournalService::class)->createEntry(now()->toDateString(), $reference, $job->id, $job->job_no, 'Device service '.$job->job_no, $items, true);
    }

    public function payment(RepairJob $job, int $cents, int $accountId): void
    {
        $account = Account::whereKey($accountId)->where('status', true)->where('account_type', 'Asset')->firstOrFail();
        $this->post($job, 'repair_payment', [$account->account_code => $cents, '1105' => -$cents]);
    }
}
