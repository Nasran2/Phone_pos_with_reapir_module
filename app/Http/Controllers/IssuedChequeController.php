<?php
namespace App\Http\Controllers;
use App\Http\Requests\StoreChequeRequest;
use App\Models\Cheque;
use App\Services\ChequeAccessService;
use App\Services\ChequeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class IssuedChequeController extends Controller { public function index(Request$r,ChequeAccessService$a){$q=$a->scope(Cheque::with(['customer','supplier'])->where('cheque_type','issued'),$r->user());app(ChequeReportController::class)->filters($q,$r);$cheques=$q->orderBy('cheque_date')->paginate(25)->withQueryString();$type='issued';$title='Issued Cheques';return view('cheques.index',compact('cheques','type','title'));} public function create(){return view('cheques.form',['type'=>'issued','customers'=>collect(),'suppliers'=>DB::table('suppliers')->whereNull('deleted_at')->orderBy('name')->get(),'sales'=>collect(),'purchases'=>DB::table('purchases')->orderByDesc('id')->limit(200)->get(),'accounts'=>DB::table('accounts')->where('status',true)->orderBy('account_name')->get(),'stores'=>DB::table('stores')->where('status',true)->get(),'lorries'=>DB::table('lorries')->whereNull('deleted_at')->get()]);} public function store(StoreChequeRequest$r,ChequeService$s){$c=$s->create($r->validated(),$r->file('attachment'));return redirect()->route('cheques.show',$c)->with('success','Issued cheque saved and moved to hold.');} }
