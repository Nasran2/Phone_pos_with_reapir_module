<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AccountTransfer extends Model {
    use \App\Traits\LogsActivity;

    protected $fillable = ['transfer_date', 'from_account_id', 'to_account_id', 'amount', 'reference_no', 'notes', 'created_by'];
    protected $casts = ['transfer_date' => 'date', 'amount' => 'decimal:2'];
    public function fromAccount() { return $this->belongsTo(Account::class, 'from_account_id'); }
    public function toAccount() { return $this->belongsTo(Account::class, 'to_account_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
