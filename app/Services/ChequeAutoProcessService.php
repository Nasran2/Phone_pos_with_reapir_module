<?php
namespace App\Services;
use App\Models\Cheque;
use App\Models\Setting;
class ChequeAutoProcessService { public function __construct(private ChequeService $service){} public function process():int{if(Setting::get('cheque_auto_enabled','1')!=='1')return 0;$action=Setting::get('cheque_auto_action','passed');if(!in_array($action,['passed','returned']))return 0;$days=(int)Setting::get('cheque_auto_grace_days',7);$apply=Setting::get('cheque_auto_apply_to','both');$q=Cheque::open()->whereDate('cheque_date','<=',today()->subDays($days));if($apply!=='both')$q->where('cheque_type',$apply==='received'?'received':'issued');$count=0;$q->chunkById(100,function($rows)use($action,&$count){foreach($rows as$c){$action==='passed'?$this->service->pass($c,[],true):$this->service->returnCheque($c,['return_reason'=>'Automatic overdue action'],true);$count++;}});return$count;} }
