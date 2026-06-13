<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DamagedStock extends Model
{
    protected $table = 'damaged_stocks';

    protected $fillable = [
        'damage_no',
        'product_id',
        'quantity',
        'damage_type',
        'reason',
        'status',
        'estimated_loss',
        'responsible_person',
        'document_path',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'estimated_loss' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
