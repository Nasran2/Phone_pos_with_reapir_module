<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    protected $fillable = [
        'adjustment_no',
        'adjustment_date',
        'adjustment_type',
        'status',
        'reason',
        'notes',
        'document_path',
        'created_by',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
