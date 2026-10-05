<?php

namespace App\Models;

use App\Support\Features;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use LogsActivity;

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->when(! Features::wholesaleEnabled(), fn ($q) => $q
            ->where('account_code', '!=', '4002')->where('account_name', 'not like', '%wholesale%'))
            ->when(! Features::repairEnabled(), fn ($q) => $q->whereNotIn('account_code', ['4010', '4011', '5010', '2210']));
    }

    public function getAccountNameAttribute(?string $value): string
    {
        if (! Features::repairEnabled() && in_array($this->account_code, ['4010', '4011', '5010', '2210'])) {
            return 'Device Service (Historical)';
        }

        return ! Features::wholesaleEnabled() && str_contains(strtolower((string) $value), 'wholesale')
            ? 'Sales Income (Historical)' : (string) $value;
    }

    protected $fillable = ['account_code', 'account_name', 'account_type', 'parent_id', 'opening_balance', 'current_balance', 'status', 'description'];

    protected $casts = ['status' => 'boolean', 'opening_balance' => 'decimal:2', 'current_balance' => 'decimal:2'];

    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function journalItems()
    {
        return $this->hasMany(JournalEntryItem::class);
    }
}
