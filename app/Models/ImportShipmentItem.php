<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportShipmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_shipment_id', 'product_id', 'variant_id', 'hs_code_id',
        'quantity', 'foreign_unit_price', 'foreign_line_total', 'local_line_total',
        'allocated_freight', 'allocated_insurance', 'allocated_clearing', 'allocated_other',
        'customs_duty_amount', 'pal_amount', 'cess_amount', 'srid_amount', 'sscl_amount',
        'landed_unit_cost_local', 'total_landed_cost_local'
    ];

    public function shipment()
    {
        return $this->belongsTo(ImportShipment::class, 'import_shipment_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function hsCode()
    {
        return $this->belongsTo(HsCode::class);
    }
}
