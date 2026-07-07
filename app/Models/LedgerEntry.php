<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model
{
    use \App\Traits\LogsActivity;

    use HasFactory;

    protected $fillable = ['chart_of_account_id', 'journal_entry_id', 'entry_date', 'debit', 'credit', 'balance'];

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'chart_of_account_id');
    }
}
