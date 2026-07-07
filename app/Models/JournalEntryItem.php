<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class JournalEntryItem extends Model {
    use \App\Traits\LogsActivity;

    protected $fillable = ['journal_entry_id', 'account_id', 'debit', 'credit', 'notes'];
    protected $casts = ['debit' => 'decimal:2', 'credit' => 'decimal:2'];
    public function journal() { return $this->belongsTo(JournalEntry::class, 'journal_entry_id'); }
    public function account() { return $this->belongsTo(Account::class); }
}
