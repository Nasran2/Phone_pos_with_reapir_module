<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LorryUserAssignment extends Model
{
    protected $guarded = [];
    protected $casts = ['is_primary'=>'boolean','is_active'=>'boolean','can_sell'=>'boolean','can_create_orders'=>'boolean','can_collect'=>'boolean','can_add_expenses'=>'boolean','can_view_cost'=>'boolean','can_view_profit'=>'boolean','can_reconcile'=>'boolean','active_from'=>'date','active_until'=>'date'];
}
