<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @property int|null $invoice_payment_id
 */
class Cheque extends Model
{
    protected $guarded = [];

    protected $casts = [
        'cheque_date' => 'date', 'cleared_date' => 'date', 'return_date' => 'date',
        'passed_at' => 'datetime', 'returned_at' => 'datetime', 'auto_process_date' => 'date',
        'amount' => 'decimal:2', 'hold_amount' => 'decimal:2', 'passed_amount' => 'decimal:2',
        'returned_amount' => 'decimal:2', 'return_charge' => 'decimal:2', 'auto_processed' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function invoicePayment()
    {
        return $this->belongsTo(InvoicePayment::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function depositAccount()
    {
        return $this->belongsTo(Account::class, 'deposit_account_id');
    }

    public function statusLogs()
    {
        return $this->hasMany(ChequeStatusLog::class)->latest('id');
    }

    public function reminders()
    {
        return $this->hasMany(ChequeReminder::class);
    }

    public function replacement()
    {
        return $this->belongsTo(self::class, 'replacement_cheque_id');
    }

    public function original()
    {
        return $this->belongsTo(self::class, 'previous_cheque_id');
    }

    public function getLorryAttribute()
    {
        return $this->lorry_id ? DB::table('lorries')->where('id', $this->lorry_id)->first() : null;
    }

    public function getDisplayStatusAttribute(): string
    {
        if (! in_array($this->status, ['pending', 'upcoming', 'due_today', 'overdue'], true)) {
            return (string) $this->status;
        }
        if (! $this->cheque_date) {
            return (string) $this->status;
        }
        if ($this->cheque_date->isToday()) {
            return 'due_today';
        }
        if ($this->cheque_date->isPast()) {
            return 'overdue';
        }

        return $this->cheque_date->diffInDays(today()) <= (int) Setting::get('cheque_reminder_days', 3) ? 'upcoming' : 'pending';
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['pending', 'upcoming', 'due_today', 'overdue', 'deposited']);
    }
}
