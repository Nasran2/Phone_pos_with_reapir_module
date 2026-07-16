<?php
namespace App\Console\Commands;
use App\Services\ChequeAutoProcessService;
use Illuminate\Console\Command;
class ProcessOverdueCheques extends Command { protected $signature='cheques:process-overdue'; protected $description='Apply configured automatic cheque action'; public function handle(ChequeAutoProcessService $s):int{$this->info($s->process().' cheque(s) processed.');return self::SUCCESS;} }
