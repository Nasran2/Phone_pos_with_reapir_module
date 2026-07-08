<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use \App\Traits\LogsActivity;

    protected $fillable = ['transfer_no', 'from_store_id', 'to_store_id', 'transfer_date', 'status', 'notes', 'created_by', 'approved_by'];

    protected $casts = [
        'transfer_date' => 'date'
    ];

    public function fromStore()
    {
        return $this->belongsTo(Store::class, 'from_store_id');
    }

    public function toStore()
    {
        return $this->belongsTo(Store::class, 'to_store_id');
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }
}
