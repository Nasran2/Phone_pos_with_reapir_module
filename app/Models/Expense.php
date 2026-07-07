<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Expense extends Model {
    use \App\Traits\LogsActivity;

    protected $fillable = ['expense_no', 'expense_date', 'expense_category_id', 'title', 'amount', 'payment_method', 'paid_from_account_id', 'reference_no', 'description', 'attachment_path', 'created_by'];
    protected $casts = ['expense_date' => 'date', 'amount' => 'decimal:2'];
    public function category() { return $this->belongsTo(ExpenseCategory::class, 'expense_category_id'); }
    public function paidFrom() { return $this->belongsTo(Account::class, 'paid_from_account_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
