<?php
$content = file_get_contents(__DIR__ . '/routes/web.php');

$missing = "use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\StoreStockController;
use App\Http\Controllers\StockAdjustmentController;
";

if (strpos($content, "use App\Http\Controllers\CustomerController;") === false) {
    $content = str_replace("use App\Http\Controllers\DamagedStockController;", $missing . "use App\Http\Controllers\DamagedStockController;", $content);
    file_put_contents(__DIR__ . '/routes/web.php', $content);
    echo "Fixed missing imports.";
} else {
    echo "Imports already exist.";
}
