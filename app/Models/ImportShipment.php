<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportShipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_no', 'shipment_date', 'supplier_id', 'currency_id', 'exchange_rate',
        'foreign_total_amount', 'local_total_amount', 'total_freight_local', 'total_insurance_local',
        'total_clearing_local', 'total_other_costs_local', 'total_customs_levies_local',
        'total_landed_cost_local', 'cost_allocation_method', 'status', 'store_id', 'notes', 'created_by'
    ];

    public function items()
    {
        return $this->hasMany(ImportShipmentItem::class);
    }

    public function costAllocations()
    {
        return $this->hasMany(ImportCostAllocation::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
