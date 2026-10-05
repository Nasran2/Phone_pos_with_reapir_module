<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property numeric-string $cheque_hold_balance
 */
class Customer extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'tax_number',
        'points',
        'balance',
        'cheque_hold_balance',
        'opening_balance',
        'credit_limit',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'cheque_hold_balance' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'status' => 'boolean',
        'points' => 'integer',
    ];

    /** @return HasMany<RepairJob, $this> */
    public function repairJobs(): HasMany
    {
        return $this->hasMany(RepairJob::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function transactions()
    {
        return $this->hasMany(CustomerTransaction::class);
    }

    public function cheques()
    {
        return $this->hasMany(Cheque::class);
    }
}
