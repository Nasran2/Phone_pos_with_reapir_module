<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Account extends Model {
    protected $fillable = ['account_code', 'account_name', 'account_type', 'parent_id', 'opening_balance', 'current_balance', 'status', 'description'];
    protected $casts = ['status' => 'boolean', 'opening_balance' => 'decimal:2', 'current_balance' => 'decimal:2'];
    public function parent() { return $this->belongsTo(Account::class, 'parent_id'); }
    public function children() { return $this->hasMany(Account::class, 'parent_id'); }
    public function journalItems() { return $this->hasMany(JournalEntryItem::class); }
}
