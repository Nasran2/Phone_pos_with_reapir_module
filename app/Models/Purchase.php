<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use \App\Traits\LogsActivity;

    protected $fillable = [
        'purchase_no',
        'supplier_id',
        'store_id',
        'invoice_no',
        'reference_no',
        'purchase_date',
        'subtotal',
        'discount_type',
        'discount_amount',
        'tax_amount',
        'shipping_cost',
        'shipping_handling',
        'grand_total',
        'total_amount',
        'paid_amount',
        'cheque_hold_amount',
        'due_amount',
        'status',
        'document_path',
        'notes',
        'stock_updated',
        'created_by',
        'taxable_total',
        'included_tax_total',
        'added_tax_total',
        'tax_total',
        'rounding_amount',
        'payable_total',
        'discount_total',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'cheque_hold_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'stock_updated' => 'boolean',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments()
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function cheques()
    {
        return $this->hasMany(Cheque::class);
    }

    public function returns()
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function displayNo(): string
    {
        return $this->purchase_no ?: $this->invoice_no;
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
