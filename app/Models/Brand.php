<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use \App\Traits\LogsActivity;

    use SoftDeletes;

    protected $fillable = ['name', 'slug', 'description', 'image'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
