<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\InvoicePayment;
use App\Models\Sale;
use App\Models\Account;
use App\Services\JournalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MixedPaymentService
{
    private const CLEARED = ['Cash','Card','Bank Transfer','Bank','QR Payment','Other'];

    public function __construct(private ChequeService $cheques, private JournalService $journals) {}

    public function rowsFromRequest(array $input, float $payable): array
    {
        if (! empty($input['payment_rows']) && is_array($input['payment_rows'])) {
            return array_values($input['payment_rows']);
        }

        $rows = [];
        $legacyPayments = $input['payments'] ?? null;
        if (is_array($legacyPayments)) {
            foreach ($legacyPayments as $method => $amount) {
                if ((float) $amount <= 0) continue;
                $rows[] = $this->legacyRow((string) $method, (float) $amount, $input);
            }
        } elseif (! empty($input['payment_method']) && (float) ($input['paid_amount'] ?? 0) > 0) {
            $rows[] = $this->legacyRow((string) $input['payment_method'], (float) $input['paid_amount'], $input);
        }

        $allocated = collect($rows)->sum(fn ($row) => (float) $row['amount']);
        if ($allocated < $payable - .009) {
            $rows[] = ['method' => 'Customer Credit / Due', 'amount' => round($payable - $allocated, 2)];
        }

        return $rows;
    }

    private function legacyRow(string $method, float $amount, array $input): array
    {
        $row = [
            'method' => $method,
            'amount' => round($amount, 2),
            'reference_no' => data_get($input, "payment_references.$method", $input['payment_reference'] ?? null),
        ];
        if ($method === 'Cheque') {
            $cheque = $input['cheque'] ?? [];
            $row['cheque'] = [
                'cheque_number' => $cheque['cheque_number'] ?? null,
                'cheque_date' => $cheque['cheque_date'] ?? null,
                'bank_name' => $cheque['bank_name'] ?? null,
                'branch_name' => $cheque['branch_name'] ?? null,
                'account_holder' => $cheque['account_holder'] ?? $cheque['account_holder_name'] ?? null,
                'account_number' => $cheque['account_number'] ?? null,
                'notes' => $cheque['notes'] ?? null,
            ];
        }
        return $row;
    }

    public function allocateSale(Sale $sale, array $rows, array $context=[]): array
    {
        return DB::transaction(function () use ($sale, $rows, $context) {
            $rows = array_values(array_filter($rows, fn ($row) => is_array($row) && ($row['method'] ?? null)));
            if (! $rows) throw ValidationException::withMessages(['payment_rows'=>'Add at least one payment method.']);
            foreach ($rows as $i => $row) if ((float)($row['amount'] ?? 0) <= 0) throw ValidationException::withMessages(["payment_rows.$i.amount"=>'Every payment amount must be greater than zero.']);

            $total = round(collect($rows)->sum(fn($r)=>(float)$r['amount']),2);
            $invoice = round((float)$sale->payable_amount,2);
            $overpayment = \App\Models\Setting::get('cheque_allow_overpayment','0') === '1' && auth()->user()?->hasPermission('payments.allow_overpayment');
            if ($total < $invoice-.009) throw ValidationException::withMessages(['payment_rows'=>\App\Support\SystemSettings::money($invoice-$total).' is still unallocated. Add another payment method or mark it as Due.']);
            if ($total > $invoice+.009 && ! $overpayment) throw ValidationException::withMessages(['payment_rows'=>'Allocated payment exceeds the invoice total by '.\App\Support\SystemSettings::money($total-$invoice).'.']);

            $seen=[]; $cleared=0; $hold=0; $due=0;
            foreach ($rows as $i => $row) {
                $method = trim($row['method']); $amount = round((float)$row['amount'],2);
                if ($method === 'Cheque') {
                    $details = $row['cheque'] ?? [];
                    foreach (['cheque_number','cheque_date','bank_name','account_holder'] as $field) if (blank($details[$field]??null)) throw ValidationException::withMessages(["payment_rows.$i.cheque.$field"=>ucwords(str_replace('_',' ',$field)).' is required.']);
                    $key=strtolower($details['cheque_number'].'|'.$details['bank_name'].'|'.($details['account_number']??''));
                    if(isset($seen[$key])) throw ValidationException::withMessages(["payment_rows.$i.cheque.cheque_number"=>'This cheque is duplicated in the payment list.']);
                    $seen[$key]=true;
                }
                $status = $method==='Cheque'?'hold':($method==='Customer Credit / Due'?'due':'cleared');
                $payment=InvoicePayment::create(['payable_type'=>Sale::class,'payable_id'=>$sale->id,'payment_method'=>$method,'amount'=>$amount,'cleared_amount'=>$status==='cleared'?$amount:0,'hold_amount'=>$status==='hold'?$amount:0,'status'=>$status,'reference_no'=>$row['reference_no']??null,'account_id'=>$row['account_id']??null,'notes'=>$row['notes']??null,'created_by'=>auth()->id()]);
                if($status==='cleared'){
                    $cleared+=$amount;
                    DB::table('sale_payments')->insert(['sale_id'=>$sale->id,'payment_date'=>today(),'amount'=>$amount,'status'=>'cleared','invoice_payment_id'=>$payment->id,'payment_method'=>$method,'reference_no'=>$row['reference_no']??null,'note'=>$row['notes']??'Mixed payment','created_by'=>auth()->id(),'created_at'=>now(),'updated_at'=>now()]);
                    $sale->paid_amount=(float)$sale->paid_amount+$amount; $sale->due_amount=max(0,(float)$sale->due_amount-$amount); $sale->save();
                    
                    $assetAccount = in_array(strtolower($method), ['cash', '']) 
                        ? Account::where('account_type','Asset')->where('account_name','like','%Cash%')->value('id') 
                        : Account::where('account_type','Asset')->where('account_name','like','%Bank%')->value('id');
                    if (!$assetAccount) $assetAccount = Account::where('account_type','Asset')->where('account_name','like','%Cash%')->value('id');
                    $customerAccount = Account::where('account_code','1105')->value('id');
                    
                    if ($assetAccount && $customerAccount) {
                        $this->journals->createEntry(today(), 'SalePayment', $payment->id, $row['reference_no']??$sale->invoice_no, 'Payment received for Sale ' . $sale->invoice_no, [
                            ['account_id'=>$assetAccount,'debit'=>$amount,'credit'=>0],
                            ['account_id'=>$customerAccount,'debit'=>0,'credit'=>$amount]
                        ], true, auth()->id());
                    }

                    if($sale->customer_id) {
                        $deduct = min($amount,(float)\App\Models\Customer::whereKey($sale->customer_id)->value('balance'));
                        \App\Models\Customer::whereKey($sale->customer_id)->decrement('balance', $deduct);
                        $cBalance = \App\Models\Customer::whereKey($sale->customer_id)->value('balance');
                        if ($deduct > 0) {
                            \App\Models\CustomerTransaction::create([
                                'customer_id' => $sale->customer_id,
                                'transactionable_type' => \App\Models\InvoicePayment::class,
                                'transactionable_id' => $payment->id,
                                'transaction_date' => today(),
                                'type' => 'sale_payment',
                                'debit' => 0,
                                'credit' => $deduct,
                                'balance_after' => $cBalance,
                                'reference_no' => $sale->invoice_no,
                                'note' => 'Payment received: ' . $method,
                                'created_by' => auth()->id(),
                            ]);
                        }
                    }
                } elseif($status==='hold'){
                    $details=$row['cheque'];
                    $cheque=$this->cheques->create(['invoice_payment_id'=>$payment->id,'cheque_type'=>'received','customer_id'=>$sale->customer_id,'sale_id'=>$sale->id,'store_id'=>$context['store_id']??$sale->store_id,'lorry_id'=>$context['lorry_id']??null,'trip_id'=>$context['trip_id']??null,'cheque_number'=>$details['cheque_number'],'cheque_date'=>$details['cheque_date'],'bank_name'=>$details['bank_name'],'branch_name'=>$details['branch_name']??null,'account_holder'=>$details['account_holder'],'account_number'=>$details['account_number']??null,'amount'=>$amount,'reference_number'=>$row['reference_no']??null,'received_from'=>$sale->customer?->name,'notes'=>$details['notes']??$row['notes']??null]);
                    $hold+=$amount;
                    DB::table('sale_payments')->insert(['sale_id'=>$sale->id,'payment_date'=>today(),'amount'=>$amount,'status'=>'hold','cheque_id'=>$cheque->id,'invoice_payment_id'=>$payment->id,'payment_method'=>'Cheque','reference_no'=>$row['reference_no']??null,'note'=>$details['notes']??null,'created_by'=>auth()->id(),'created_at'=>now(),'updated_at'=>now()]);
                } else $due+=$amount;
            }
            $sale->refresh();
            $paid=(float)$sale->paid_amount; $holdNow=(float)$sale->cheque_hold_amount; $dueNow=(float)$sale->due_amount;
            $sale->payment_status = match(true) {
                $dueNow<=.009 && $holdNow<=.009 => 'Paid',
                $paid>.009 && $holdNow>.009 && $dueNow<=.009 => 'Partial Paid + Cheque Hold',
                $paid<=.009 && $holdNow>.009 && $dueNow<=.009 => 'Cheque Hold',
                $paid>.009 && $holdNow>.009 && $dueNow>.009 => 'Partial + Cheque Hold + Due',
                $holdNow>.009 && $dueNow>.009 => 'Cheque Hold + Due',
                $paid>.009 => 'Partial',
                default => 'Due',
            };
            $sale->save();
            return compact('cleared','hold','due');
        });
    }
}
