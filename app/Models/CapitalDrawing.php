<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CapitalDrawing extends Model {
    protected $fillable = ['type', 'transaction_date', 'amount', 'account_id', 'reference_no', 'notes', 'created_by'];
    protected $casts = ['transaction_date' => 'date', 'amount' => 'decimal:2'];
    public function account() { return $this->belongsTo(Account::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
