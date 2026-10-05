<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read Customer $customer
 * @property-read RepairDevice|null $device
 * @property-read RepairStatus|null $repairStatus
 * @property-read RepairTechnician|null $technician
 * @property-read RepairChecklist|null $checklist
 * @property-read RepairWarranty|null $warranty
 */
class RepairJob extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['intake_key'];

    protected $casts = ['problem_snapshot' => 'array', 'ledger_revenue' => 'array', 'urgent' => 'boolean', 'is_warranty' => 'boolean', 'expected_completion_date' => 'date', 'completed_at' => 'datetime', 'delivered_at' => 'datetime', 'approved_at' => 'datetime', 'advance_amount' => 'decimal:2', 'labor_charge' => 'decimal:2', 'discount_amount' => 'decimal:2', 'tax_amount' => 'decimal:2', 'ledger_due' => 'decimal:2', 'ledger_hold' => 'decimal:2', 'estimated_cost' => 'decimal:2', 'parts_total' => 'decimal:2', 'paid_amount' => 'decimal:2', 'total_amount' => 'decimal:2', 'balance_amount' => 'decimal:2', 'discount_value' => 'decimal:2', 'tax_rate' => 'decimal:2', 'cheque_hold_amount' => 'decimal:2'];

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    /** @return BelongsTo<Store, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /** @return BelongsTo<RepairTechnician, $this> */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(RepairTechnician::class, 'primary_technician_id')->withTrashed();
    }

    /** @return BelongsTo<RepairStatus, $this> */
    public function repairStatus(): BelongsTo
    {
        return $this->belongsTo(RepairStatus::class);
    }

    /** @return HasOne<RepairDevice, $this> */
    public function device(): HasOne
    {
        return $this->hasOne(RepairDevice::class);
    }

    /** @return HasOne<RepairChecklist, $this> */
    public function checklist(): HasOne
    {
        return $this->hasOne(RepairChecklist::class);
    }

    /** @return HasOne<RepairWarranty, $this> */
    public function warranty(): HasOne
    {
        return $this->hasOne(RepairWarranty::class);
    }

    /** @return HasMany<RepairPart, $this> */
    public function parts(): HasMany
    {
        return $this->hasMany(RepairPart::class);
    }

    /** @return HasMany<RepairChargeItem, $this> */
    public function charges(): HasMany
    {
        return $this->hasMany(RepairChargeItem::class);
    }

    /** @return HasMany<RepairPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(RepairPayment::class);
    }

    /** @return HasMany<RepairImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(RepairImage::class);
    }

    /** @return HasMany<RepairAssignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(RepairAssignment::class);
    }

    /** @return HasMany<RepairStatusLog, $this> */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(RepairStatusLog::class);
    }

    /** @return HasMany<RepairNote, $this> */
    public function notes(): HasMany
    {
        return $this->hasMany(RepairNote::class);
    }

    /** @return HasMany<RepairActivityLog, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(RepairActivityLog::class);
    }

    /** @return HasMany<RepairNotification, $this> */
    public function notifications(): HasMany
    {
        return $this->hasMany(RepairNotification::class);
    }

    /** @return MorphMany<InvoicePayment, $this> */
    public function invoicePayments(): MorphMany
    {
        return $this->morphMany(InvoicePayment::class, 'payable');
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->repairStatus ? $this->repairStatus->name : $this->status;
    }

    public function getFinalizedAttribute(): bool
    {
        return (bool) ($this->completed_at || $this->delivered_at || $this->repairStatus?->is_completed || $this->repairStatus?->is_cancelled);
    }

    /** @param Builder<static> $query
     * @return Builder<static> */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        $term = '%'.$search.'%';

        return $query->where(function ($q) use ($term, $search) {
            $q->where('job_no', 'like', $term)->orWhere('barcode', 'like', $term)
                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $term)->orWhere('phone', 'like', $term)->orWhere('id', $search))
                ->orWhereHas('device', fn ($d) => $d->where('imei_1', 'like', $term)->orWhere('imei_2', 'like', $term)->orWhere('serial_number', 'like', $term)->orWhere('model', 'like', $term)->orWhere('brand', 'like', $term));
        });
    }
}
