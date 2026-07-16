<?php
namespace App\Http\Controllers;
use App\Models\Cheque;use App\Services\ChequeAccessService;use Illuminate\Http\Request;
class ChequeCalendarController extends Controller { public function __invoke(Request$r,ChequeAccessService$a){$month=$r->integer('month')?:now()->month;$year=$r->integer('year')?:now()->year;$q=$a->scope(Cheque::with(['customer','supplier'])->whereYear('cheque_date',$year)->whereMonth('cheque_date',$month),$r->user());if($r->filled('type'))$q->where('cheque_type',$r->type);if($r->filled('status'))$q->where('status',$r->status);$cheques=$q->get()->groupBy(fn($c)=>$c->cheque_date->day);return view('cheques.calendar',compact('cheques','month','year'));} }
