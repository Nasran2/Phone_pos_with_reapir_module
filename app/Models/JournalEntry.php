<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class JournalEntry extends Model {
    use \App\Traits\LogsActivity;

    protected $fillable = ['journal_no', 'journal_date', 'reference_type', 'reference_id', 'reference_no', 'description', 'is_auto_generated', 'created_by'];
    protected $casts = ['journal_date' => 'date', 'is_auto_generated' => 'boolean'];
    public function items() { return $this->hasMany(JournalEntryItem::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
