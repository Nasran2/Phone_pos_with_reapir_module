<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoicePayment extends Model
{
    protected $guarded = [];
    protected $casts = ['amount'=>'decimal:2','cleared_amount'=>'decimal:2','hold_amount'=>'decimal:2'];
    public function payable() { return $this->morphTo(); }
    public function cheque() { return $this->hasOne(Cheque::class); }
    public function account() { return $this->belongsTo(Account::class); }
}
