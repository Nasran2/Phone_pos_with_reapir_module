<?php

$dir = __DIR__ . '/app/Models';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$models = [
    'ExpenseCategory' => <<<PHP
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ExpenseCategory extends Model {
    protected \$fillable = ['name', 'description'];
    public function expenses() { return \$this->hasMany(Expense::class); }
}
PHP
,
    'Expense' => <<<PHP
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Expense extends Model {
    protected \$fillable = ['expense_no', 'expense_date', 'expense_category_id', 'title', 'amount', 'payment_method', 'paid_from_account_id', 'reference_no', 'description', 'attachment_path', 'created_by'];
    protected \$casts = ['expense_date' => 'date', 'amount' => 'decimal:2'];
    public function category() { return \$this->belongsTo(ExpenseCategory::class, 'expense_category_id'); }
    public function paidFrom() { return \$this->belongsTo(Account::class, 'paid_from_account_id'); }
    public function creator() { return \$this->belongsTo(User::class, 'created_by'); }
}
PHP
,
    'Account' => <<<PHP
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Account extends Model {
    protected \$fillable = ['account_code', 'account_name', 'account_type', 'parent_id', 'opening_balance', 'current_balance', 'status', 'description'];
    protected \$casts = ['status' => 'boolean', 'opening_balance' => 'decimal:2', 'current_balance' => 'decimal:2'];
    public function parent() { return \$this->belongsTo(Account::class, 'parent_id'); }
    public function children() { return \$this->hasMany(Account::class, 'parent_id'); }
    public function journalItems() { return \$this->hasMany(JournalEntryItem::class); }
}
PHP
,
    'JournalEntry' => <<<PHP
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class JournalEntry extends Model {
    protected \$fillable = ['journal_no', 'journal_date', 'reference_type', 'reference_id', 'reference_no', 'description', 'is_auto_generated', 'created_by'];
    protected \$casts = ['journal_date' => 'date', 'is_auto_generated' => 'boolean'];
    public function items() { return \$this->hasMany(JournalEntryItem::class); }
    public function creator() { return \$this->belongsTo(User::class, 'created_by'); }
}
PHP
,
    'JournalEntryItem' => <<<PHP
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class JournalEntryItem extends Model {
    protected \$fillable = ['journal_entry_id', 'account_id', 'debit', 'credit', 'notes'];
    protected \$casts = ['debit' => 'decimal:2', 'credit' => 'decimal:2'];
    public function journal() { return \$this->belongsTo(JournalEntry::class, 'journal_entry_id'); }
    public function account() { return \$this->belongsTo(Account::class); }
}
PHP
,
    'CapitalDrawing' => <<<PHP
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CapitalDrawing extends Model {
    protected \$fillable = ['type', 'transaction_date', 'amount', 'account_id', 'reference_no', 'notes', 'created_by'];
    protected \$casts = ['transaction_date' => 'date', 'amount' => 'decimal:2'];
    public function account() { return \$this->belongsTo(Account::class); }
    public function creator() { return \$this->belongsTo(User::class, 'created_by'); }
}
PHP
,
    'AccountTransfer' => <<<PHP
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AccountTransfer extends Model {
    protected \$fillable = ['transfer_date', 'from_account_id', 'to_account_id', 'amount', 'reference_no', 'notes', 'created_by'];
    protected \$casts = ['transfer_date' => 'date', 'amount' => 'decimal:2'];
    public function fromAccount() { return \$this->belongsTo(Account::class, 'from_account_id'); }
    public function toAccount() { return \$this->belongsTo(Account::class, 'to_account_id'); }
    public function creator() { return \$this->belongsTo(User::class, 'created_by'); }
}
PHP
];

foreach (\$models as \$name => \$content) {
    file_put_contents(\$dir . '/' . \$name . '.php', \$content);
}

echo "Models generated successfully.\n";
