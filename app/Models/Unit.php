<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use \App\Traits\LogsActivity;

    protected $fillable = ['name', 'short_name'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
