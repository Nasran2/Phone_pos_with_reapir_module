<?php
namespace App\Services;
use App\Models\Cheque;
use App\Models\ChequeReminder;
use App\Models\Setting;
class ChequeReminderService { public function generate():int{if(Setting::get('cheque_reminders_enabled','1')!=='1')return 0;$days=(int)Setting::get('cheque_reminder_days',3);$count=0;Cheque::open()->whereDate('cheque_date','<=',today()->addDays($days))->each(function($c)use(&$count){$type=$c->cheque_date->isPast()?'overdue':($c->cheque_date->isToday()?'due_today':'upcoming');$r=ChequeReminder::firstOrCreate(['cheque_id'=>$c->id,'reminder_date'=>today(),'reminder_type'=>$type,'channel'=>'in_app'],['sent_at'=>now(),'status'=>'sent']);if($r->wasRecentlyCreated)$count++;});return$count;} }
