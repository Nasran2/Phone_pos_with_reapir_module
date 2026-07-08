<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use SoftDeletes, \App\Traits\LogsActivity;

    protected $fillable = ['name', 'code', 'phone', 'email', 'address', 'manager', 'is_default', 'status', 'notes'];

    public function stock()
    {
        return $this->hasMany(ProductStoreStock::class);
    }
}
