<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportCostAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_shipment_id', 'cost_type', 'amount_local', 'allocation_method', 'notes'
    ];

    public function shipment()
    {
        return $this->belongsTo(ImportShipment::class, 'import_shipment_id');
    }
}
