<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ChequeStatusLog extends Model { public $timestamps = false; protected $guarded = []; protected $casts = ['created_at'=>'datetime','is_system_action'=>'boolean']; public function user(){return $this->belongsTo(User::class,'action_by');} }
