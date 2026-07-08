<?php
$dir = __DIR__ . '/app/Models/';

$store = <<<PHP
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use SoftDeletes, \App\Traits\LogsActivity;

    protected \$fillable = ['name', 'code', 'phone', 'email', 'address', 'manager', 'is_default', 'status', 'notes'];

    public function stock()
    {
        return \$this->hasMany(ProductStoreStock::class);
    }
}
PHP;
file_put_contents(\$dir . 'Store.php', \$store);

$stock = <<<PHP
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ProductStoreStock extends Model
{
    use \App\Traits\LogsActivity;

    protected \$table = 'product_store_stock';
    
    protected \$fillable = ['product_id', 'store_id', 'quantity', 'minimum_stock', 'maximum_stock', 'last_purchase', 'last_sale'];

    protected \$casts = [
        'last_purchase' => 'datetime',
        'last_sale' => 'datetime'
    ];

    public function product()
    {
        return \$this->belongsTo(Product::class);
    }

    public function store()
    {
        return \$this->belongsTo(Store::class);
    }
}
PHP;
file_put_contents(\$dir . 'ProductStoreStock.php', \$stock);

$transfer = <<<PHP
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use \App\Traits\LogsActivity;

    protected \$fillable = ['transfer_no', 'from_store_id', 'to_store_id', 'transfer_date', 'status', 'notes', 'created_by', 'approved_by'];

    protected \$casts = [
        'transfer_date' => 'date'
    ];

    public function fromStore()
    {
        return \$this->belongsTo(Store::class, 'from_store_id');
    }

    public function toStore()
    {
        return \$this->belongsTo(Store::class, 'to_store_id');
    }

    public function items()
    {
        return \$this->hasMany(StockTransferItem::class);
    }
}
PHP;
file_put_contents(\$dir . 'StockTransfer.php', \$transfer);

$transferItem = <<<PHP
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    use \App\Traits\LogsActivity;

    protected \$fillable = ['stock_transfer_id', 'product_id', 'quantity'];

    public function product()
    {
        return \$this->belongsTo(Product::class);
    }
}
PHP;
file_put_contents(\$dir . 'StockTransferItem.php', \$transferItem);

$barcode = <<<PHP
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BarcodeTemplate extends Model
{
    use \App\Traits\LogsActivity;

    protected \$fillable = ['name', 'paper_size', 'content_settings', 'is_default'];

    protected \$casts = [
        'content_settings' => 'array',
        'is_default' => 'boolean'
    ];
}
PHP;
file_put_contents(\$dir . 'BarcodeTemplate.php', \$barcode);

echo "Models updated successfully.\n";
