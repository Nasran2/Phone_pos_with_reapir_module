<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Cheque;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ChequeService
{
    public function __construct(private ChequeBalanceService $balances, private ChequeAccountingService $accounting) {}

    public function create(array $data, $attachment=null, bool $replacement=false): Cheque
    {
        return DB::transaction(function () use ($data,$attachment,$replacement) {
            $this->validateDate($data['cheque_date']);
            $duplicate=Cheque::where('cheque_number',$data['cheque_number'])->where('bank_name',$data['bank_name'])->where('account_number',$data['account_number']??null)->exists();
            if($duplicate) throw ValidationException::withMessages(['cheque_number'=>'This cheque number already exists for the same bank/account.']);
            $prefix=$data['cheque_type']==='received'?Setting::get('cheque_received_prefix','RCHQ'):Setting::get('cheque_issued_prefix','ICHQ');
            $data['cheque_reference']=$prefix.'-'.now()->format('ymd').'-'.str_pad((string)((Cheque::max('id')??0)+1),5,'0',STR_PAD_LEFT);
            $data['hold_amount']=$data['amount']; $data['status']='pending'; $data['created_by']=auth()->id();
            $data['party_type']=$data['cheque_type']==='received'?'customer':($data['supplier_id']??null?'supplier':'payee');
            $data['received_by']=$data['cheque_type']==='received'?auth()->id():null; $data['issued_by']=$data['cheque_type']==='issued'?auth()->id():null;
            if($attachment) $data['attachment_path']=$attachment->store('cheques','public');
            unset($data['attachment']);
            $cheque=Cheque::create($data);
            $this->balances->placeOnHold($cheque);
            $this->accounting->created($cheque);
            $this->log($cheque,null,'pending',$replacement?'replacement_created':'created','Cheque created and amount moved to hold.');
            ActivityLog::log('Create','Cheque Management',"Created {$cheque->cheque_type} cheque {$cheque->cheque_reference}");
            return $cheque;
        });
    }

    public function pass(Cheque $cheque,array $data=[],bool $system=false): Cheque
    {
        return DB::transaction(function()use($cheque,$data,$system){$cheque=Cheque::lockForUpdate()->findOrFail($cheque->id);if($cheque->status==='passed')return $cheque;if(!$this->open($cheque))throw ValidationException::withMessages(['status'=>'Only pending cheques can be passed.']);$old=$cheque->status;if(array_key_exists('deposit_account_id',$data)){$cheque->deposit_account_id=$data['deposit_account_id'];$cheque->save();}$this->balances->pass($cheque);$this->accounting->passed($cheque);$cheque->update(['previous_status'=>$old,'status'=>'passed','hold_amount'=>0,'passed_amount'=>$cheque->amount,'passed_at'=>now(),'passed_by'=>$system?null:auth()->id(),'cleared_date'=>$data['cleared_date']??today(),'deposit_reference'=>$data['deposit_reference']??null,'notes'=>$this->notes($cheque,$data['notes']??null),'auto_processed'=>$system,'auto_process_date'=>$system?today():$cheque->auto_process_date]);$this->log($cheque,$old,'passed',$system?'auto_passed':'passed',$data['notes']??'Cheque cleared.',$system);ActivityLog::log('Update','Cheque Management',"Marked cheque {$cheque->cheque_reference} passed");return $cheque;});
    }

    public function returnCheque(Cheque $cheque,array $data=[],bool $system=false): Cheque
    {
        return DB::transaction(function()use($cheque,$data,$system){$cheque=Cheque::lockForUpdate()->findOrFail($cheque->id);if($cheque->status==='returned')return $cheque;if(!$this->open($cheque))throw ValidationException::withMessages(['status'=>'Only pending cheques can be returned.']);$old=$cheque->status;$charge=(float)($data['return_charge']??Setting::get('cheque_default_return_charge',0));$this->balances->releaseToDue($cheque,Setting::get('cheque_add_return_charge_to_due','1')==='1'?$charge:0);$this->accounting->returned($cheque);$cheque->update(['previous_status'=>$old,'status'=>'returned','hold_amount'=>0,'returned_amount'=>$cheque->amount,'returned_at'=>now(),'returned_by'=>$system?null:auth()->id(),'return_date'=>$data['return_date']??today(),'return_reason'=>$data['return_reason']??($system?'Automatic overdue action':'Other'),'return_charge'=>$charge,'notes'=>$this->notes($cheque,$data['notes']??null),'auto_processed'=>$system,'auto_process_date'=>$system?today():$cheque->auto_process_date]);$this->log($cheque,$old,'returned',$system?'auto_returned':'returned',$data['notes']??$cheque->return_reason,$system);ActivityLog::log('Update','Cheque Management',"Marked cheque {$cheque->cheque_reference} returned");return $cheque;});
    }

    public function cancel(Cheque $cheque,string $note=''): Cheque { return DB::transaction(function()use($cheque,$note){$cheque=Cheque::lockForUpdate()->findOrFail($cheque->id);if(!$this->open($cheque))throw ValidationException::withMessages(['status'=>'Only pending cheques can be cancelled.']);$old=$cheque->status;$this->balances->releaseToDue($cheque);$this->accounting->returned($cheque);$cheque->update(['previous_status'=>$old,'status'=>'cancelled','hold_amount'=>0,'notes'=>$this->notes($cheque,$note)]);$this->log($cheque,$old,'cancelled','cancelled',$note?:'Cheque cancelled.');return $cheque;}); }

    public function replace(Cheque $original,array $data,$attachment=null): Cheque { return DB::transaction(function()use($original,$data,$attachment){$original=Cheque::lockForUpdate()->findOrFail($original->id);if(!in_array($original->status,['returned','bounced']))throw ValidationException::withMessages(['status'=>'Only returned cheques can be replaced.']);$data=array_merge($original->only(['cheque_type','customer_id','supplier_id','payee_name','sale_id','purchase_id','repair_job_id','expense_id','store_id','lorry_id','trip_id','deposit_account_id','source_account_id']),$data,['previous_cheque_id'=>$original->id]);$new=$this->create($data,$attachment,true);$original->update(['status'=>'replaced','replacement_cheque_id'=>$new->id]);$this->log($original,'returned','replaced','replaced',"Replaced by {$new->cheque_reference}");return $new;}); }

    private function validateDate($date):void{$date=\Illuminate\Support\Carbon::parse($date);if($date->isPast()&&!$date->isToday()&&Setting::get('cheque_allow_backdated','1')!=='1')throw ValidationException::withMessages(['cheque_date'=>'Backdated cheques are disabled.']);if($date->isFuture()&&Setting::get('cheque_allow_future','1')!=='1')throw ValidationException::withMessages(['cheque_date'=>'Future cheques are disabled.']);}
    private function open(Cheque $c):bool{return in_array($c->status,['pending','upcoming','due_today','overdue','deposited']);}
    private function log(Cheque $c,$old,$new,$action,$note,$system=false):void{DB::table('cheque_status_logs')->insert(['cheque_id'=>$c->id,'previous_status'=>$old,'new_status'=>$new,'note'=>$note,'action_type'=>$action,'action_by'=>$system?null:auth()->id(),'is_system_action'=>$system,'created_at'=>now()]);}
    private function notes(Cheque $c,?string $note):?string{return $note?trim(($c->notes?$c->notes."\n":'').$note):$c->notes;}
}
