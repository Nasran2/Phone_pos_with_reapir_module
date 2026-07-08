<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BarcodeTemplate extends Model
{
    use \App\Traits\LogsActivity;

    protected $fillable = ['name', 'paper_size', 'content_settings', 'is_default'];

    protected $casts = [
        'content_settings' => 'array',
        'is_default' => 'boolean'
    ];
}
