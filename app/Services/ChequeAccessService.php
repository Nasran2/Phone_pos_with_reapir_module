<?php
namespace App\Services;
use App\Models\Cheque;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
class ChequeAccessService { public function scope(Builder $q,User $u):Builder{if($u->user_type==='lorry'){$ids=$u->lorryAssignments()->where('is_active',true)->pluck('lorry_id');return$q->whereIn('lorry_id',$ids);}return$q;} public function authorize(Cheque $c,User $u):void{if($u->user_type==='lorry')abort_unless($u->lorryAssignments()->where('is_active',true)->where('lorry_id',$c->lorry_id)->exists(),403);} }
