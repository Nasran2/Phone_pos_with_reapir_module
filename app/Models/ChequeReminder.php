<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ChequeReminder extends Model { protected $guarded=[]; protected $casts=['reminder_date'=>'date','sent_at'=>'datetime']; }
