<?php
namespace App\Console\Commands;
use App\Services\ChequeReminderService;
use Illuminate\Console\Command;
class SendChequeReminders extends Command { protected $signature='cheques:send-reminders'; protected $description='Generate due cheque reminders'; public function handle(ChequeReminderService $s):int{$this->info($s->generate().' reminder(s) generated.');return self::SUCCESS;} }
