<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairPayment extends Model
{
    protected $guarded = [];

    protected $casts = ['amount' => 'decimal:2', 'payment_date' => 'date', 'cleared_at' => 'datetime'];

    /** @return BelongsTo<RepairJob, $this> */
    public function job(): BelongsTo
    {
        return $this->belongsTo(RepairJob::class, 'repair_job_id');
    }

    /** @return BelongsTo<Cheque, $this> */
    public function cheque(): BelongsTo
    {
        return $this->belongsTo(Cheque::class);
    }

    /** @return BelongsTo<InvoicePayment, $this> */
    public function invoicePayment(): BelongsTo
    {
        return $this->belongsTo(InvoicePayment::class);
    }
}
