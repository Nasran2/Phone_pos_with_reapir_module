<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WholesalePosController;
use App\Http\Controllers\RetailPosController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\HoldSaleController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\StoreStockController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\DamagedStockController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DailyClosingController;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\LedgerBookController;
use App\Http\Controllers\CapitalDrawingController;

Route::get('/link-storage', function () {
    \Illuminate\Support\Facades\Artisan::call('storage:link');
    return 'Storage linked successfully!';
});

Route::redirect('/', 'login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('permission:dashboard.view');

    // POS Routes
    Route::get('pos/wholesale', [WholesalePosController::class, 'index'])
        ->name('pos.wholesale')
        ->middleware('permission:wholesale_pos.view');
    Route::post('pos/wholesale/store', [WholesalePosController::class, 'store'])
        ->name('pos.wholesale.store')
        ->middleware('permission:wholesale_pos.create_sale');
    Route::post('pos/wholesale/customer', [WholesalePosController::class, 'storeCustomer'])
        ->name('pos.wholesale.customer')
        ->middleware('permission:customers.create');

    Route::get('pos/retail', [RetailPosController::class, 'index'])
        ->name('pos.retail')
        ->middleware('permission:retail_pos.view');
    Route::post('pos/retail/store', [RetailPosController::class, 'store'])
        ->name('pos.retail.store')
        ->middleware('permission:retail_pos.create_sale');
    Route::post('pos/retail/customer', [RetailPosController::class, 'storeCustomer'])
        ->name('pos.retail.customer')
        ->middleware('permission:customers.create');

    // User Management - Users
    Route::get('users', [UserController::class, 'index'])
        ->name('users.index')
        ->middleware('permission:users.view');
    Route::post('users', [UserController::class, 'store'])
        ->name('users.store')
        ->middleware('permission:users.create');
    Route::put('users/{user}', [UserController::class, 'update'])
        ->name('users.update')
        ->middleware('permission:users.edit');
    Route::put('users/{user}/password', [UserController::class, 'updatePassword'])
        ->name('users.update-password')
        ->middleware('permission:users.edit');
    Route::patch('users/{user}/status', [UserController::class, 'toggleStatus'])
        ->name('users.toggle-status')
        ->middleware('permission:users.edit');
    Route::delete('users/{user}', [UserController::class, 'destroy'])
        ->name('users.destroy')
        ->middleware('permission:users.delete');

    // User Management - Roles
    Route::get('roles', [RoleController::class, 'index'])
        ->name('roles.index')
        ->middleware('permission:roles.view');
    Route::post('roles', [RoleController::class, 'store'])
        ->name('roles.store')
        ->middleware('permission:roles.create');
    Route::put('roles/{role}', [RoleController::class, 'update'])
        ->name('roles.update')
        ->middleware('permission:roles.edit');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])
        ->name('roles.destroy')
        ->middleware('permission:roles.delete');

    // User Management - Permissions
    Route::get('permissions', [PermissionController::class, 'index'])
        ->name('permissions.index')
        ->middleware('permission:permissions.view');

    // Activity Logs
    Route::get('activity-logs', [ActivityLogController::class, 'index'])
        ->name('activity-logs.index')
        ->middleware('permission:activity_logs.view');

    // Products
    Route::get('products/export', [ProductController::class, 'export'])
        ->name('products.export')
        ->middleware('permission:products.view');
    Route::post('products/import', [ProductController::class, 'import'])
        ->name('products.import')
        ->middleware('permission:products.create');
    Route::get('products/{product}/stock-history', [ProductController::class, 'stockHistory'])
        ->name('products.stock-history')
        ->middleware('permission:products.view');
    Route::get('products', [ProductController::class, 'index'])
        ->name('products.index')
        ->middleware('permission:products.view');
    Route::post('products', [ProductController::class, 'store'])
        ->name('products.store')
        ->middleware('permission:products.create');
    Route::put('products/{product}', [ProductController::class, 'update'])
        ->name('products.update')
        ->middleware('permission:products.edit');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])
        ->name('products.destroy')
        ->middleware('permission:products.delete');

    // Categories
    Route::get('categories', [CategoryController::class, 'index'])
        ->name('categories.index')
        ->middleware('permission:categories.view');
    Route::post('categories', [CategoryController::class, 'store'])
        ->name('categories.store')
        ->middleware('permission:categories.create');
    Route::put('categories/{category}', [CategoryController::class, 'update'])
        ->name('categories.update')
        ->middleware('permission:categories.edit');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])
        ->name('categories.destroy')
        ->middleware('permission:categories.delete');

    // Brands
    Route::get('brands', [BrandController::class, 'index'])
        ->name('brands.index')
        ->middleware('permission:brands.view');
    Route::post('brands', [BrandController::class, 'store'])
        ->name('brands.store')
        ->middleware('permission:brands.create');
    Route::put('brands/{brand}', [BrandController::class, 'update'])
        ->name('brands.update')
        ->middleware('permission:brands.edit');
    Route::delete('brands/{brand}', [BrandController::class, 'destroy'])
        ->name('brands.destroy')
        ->middleware('permission:brands.delete');

    // Units
    Route::get('units', [UnitController::class, 'index'])
        ->name('units.index')
        ->middleware('permission:units.view');
    Route::post('units', [UnitController::class, 'store'])
        ->name('units.store')
        ->middleware('permission:units.create');
    Route::put('units/{unit}', [UnitController::class, 'update'])
        ->name('units.update')
        ->middleware('permission:units.edit');
    Route::delete('units/{unit}', [UnitController::class, 'destroy'])
        ->name('units.destroy')
        ->middleware('permission:units.delete');

    // Purchases
    Route::get('purchases', [PurchaseController::class, 'index'])
        ->name('purchases.index')
        ->middleware('permission:purchase.view');
    Route::get('purchases/create', [PurchaseController::class, 'create'])
        ->name('purchases.create')
        ->middleware('permission:purchase.create');
    Route::post('purchases', [PurchaseController::class, 'store'])
        ->name('purchases.store')
        ->middleware('permission:purchase.create');
    Route::post('purchases/suppliers', [PurchaseController::class, 'storeSupplier'])
        ->name('purchases.suppliers.store')
        ->middleware('permission:suppliers.create');
    Route::get('purchases/{purchase}', [PurchaseController::class, 'show'])
        ->name('purchases.show')
        ->middleware('permission:purchase.view');
    Route::get('purchases/{purchase}/edit', [PurchaseController::class, 'edit'])
        ->name('purchases.edit')
        ->middleware('permission:purchase.edit');
    Route::put('purchases/{purchase}', [PurchaseController::class, 'update'])
        ->name('purchases.update')
        ->middleware('permission:purchase.edit');
    Route::delete('purchases/{purchase}', [PurchaseController::class, 'destroy'])
        ->name('purchases.destroy')
        ->middleware('permission:purchase.delete');
    Route::get('purchases/{purchase}/print', [PurchaseController::class, 'print'])
        ->name('purchases.print')
        ->middleware('permission:purchase.print');
    Route::get('purchases/{purchase}/download', [PurchaseController::class, 'download'])
        ->name('purchases.download')
        ->middleware('permission:purchase.download');

    // Purchase Returns
    Route::get('purchase-returns', [PurchaseReturnController::class, 'index'])
        ->name('purchase-returns.index')
        ->middleware('permission:purchase.return');
    Route::get('purchase-returns/create', [PurchaseReturnController::class, 'create'])
        ->name('purchase-returns.create')
        ->middleware('permission:purchase.return');
    Route::post('purchase-returns', [PurchaseReturnController::class, 'store'])
        ->name('purchase-returns.store')
        ->middleware('permission:purchase.return');
    Route::get('purchase-returns/{purchaseReturn}', [PurchaseReturnController::class, 'show'])
        ->name('purchase-returns.show')
        ->middleware('permission:purchase.return');
    Route::delete('purchase-returns/{purchaseReturn}', [PurchaseReturnController::class, 'destroy'])
        ->name('purchase-returns.destroy')
        ->middleware('permission:purchase.delete');

    // Sales Ledger
    Route::get('sales', [SaleController::class, 'index'])
        ->name('sales.index')
        ->middleware('permission:sales.view');
    Route::get('sales/{sale}', [SaleController::class, 'show'])
        ->name('sales.show')
        ->middleware('permission:sales.invoice_view');
    Route::get('sales/{sale}/print', [SaleController::class, 'print'])
        ->name('sales.print')
        ->middleware('permission:sales.print');
    Route::get('sales/{sale}/download', [SaleController::class, 'download'])
        ->name('sales.download')
        ->middleware('permission:sales.pdf');
    Route::post('sales/{sale}/payments', [SaleController::class, 'storePayment'])
        ->name('sales.payments.store')
        ->middleware('permission:sales.edit_payment');
    Route::delete('sales/{sale}', [SaleController::class, 'destroy'])
        ->name('sales.destroy')
        ->middleware('permission:sales.delete');

    // Sales Returns
    Route::get('sales-returns', [SaleReturnController::class, 'index'])
        ->name('sales-returns.index')
        ->middleware('permission:sales_return.view');
    Route::get('sales-returns/create', [SaleReturnController::class, 'create'])
        ->name('sales-returns.create')
        ->middleware('permission:sales_return.create');
    Route::post('sales-returns', [SaleReturnController::class, 'store'])
        ->name('sales-returns.store')
        ->middleware('permission:sales_return.create');
    Route::get('sales-returns/{saleReturn}', [SaleReturnController::class, 'show'])
        ->name('sales-returns.show')
        ->middleware('permission:sales_return.view');
    Route::delete('sales-returns/{saleReturn}', [SaleReturnController::class, 'destroy'])
        ->name('sales-returns.destroy')
        ->middleware('permission:sales_return.delete');

    // Hold Sales
    Route::get('hold-sales', [HoldSaleController::class, 'index'])
        ->name('hold-sales.index')
        ->middleware('permission:hold_sales.view');
    Route::get('hold-sales/{holdSale}', [HoldSaleController::class, 'show'])
        ->name('hold-sales.show')
        ->middleware('permission:hold_sales.view');
    Route::post('hold-sales/{holdSale}/resume', [HoldSaleController::class, 'resume'])
        ->name('hold-sales.resume')
        ->middleware('permission:hold_sales.resume');
    Route::delete('hold-sales/{holdSale}', [HoldSaleController::class, 'destroy'])
        ->name('hold-sales.destroy')
        ->middleware('permission:hold_sales.delete');

    // Customers
    Route::get('customers', [CustomerController::class, 'index'])
        ->name('customers.index')
        ->middleware('permission:customers.view');
    Route::post('customers', [CustomerController::class, 'store'])
        ->name('customers.store')
        ->middleware('permission:customers.create');
    Route::post('customers/{customer}/payments', [CustomerController::class, 'storePayment'])
        ->name('customers.payments.store')
        ->middleware('permission:customers.payment');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])
        ->name('customers.show')
        ->middleware('permission:customers.view');
    Route::put('customers/{customer}', [CustomerController::class, 'update'])
        ->name('customers.update')
        ->middleware('permission:customers.edit');
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])
        ->name('customers.destroy')
        ->middleware('permission:customers.delete');

    // Suppliers
    Route::get('suppliers', [SupplierController::class, 'index'])
        ->name('suppliers.index')
        ->middleware('permission:suppliers.view');
    Route::post('suppliers', [SupplierController::class, 'store'])
        ->name('suppliers.store')
        ->middleware('permission:suppliers.create');
    Route::post('suppliers/{supplier}/payments', [SupplierController::class, 'storePayment'])
        ->name('suppliers.payments.store')
        ->middleware('permission:suppliers.payment');
    Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])
        ->name('suppliers.show')
        ->middleware('permission:suppliers.view');
    Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])
        ->name('suppliers.update')
        ->middleware('permission:suppliers.edit');
    Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])
        ->name('suppliers.destroy')
        ->middleware('permission:suppliers.delete');

    // Store Stock
    Route::get('stock', [StoreStockController::class, 'index'])
        ->name('stock.index')
        ->middleware('permission:stock.view');
    Route::get('stock/history/{product}', [StoreStockController::class, 'history'])
        ->name('stock.history')
        ->middleware('permission:stock.history');

    // Stock Adjustments
    Route::get('stock-adjustments', [StockAdjustmentController::class, 'index'])
        ->name('stock-adjustments.index')
        ->middleware('permission:stock.adjustment.view');
    Route::get('stock-adjustments/create', [StockAdjustmentController::class, 'create'])
        ->name('stock-adjustments.create')
        ->middleware('permission:stock.adjustment.create');
    Route::post('stock-adjustments', [StockAdjustmentController::class, 'store'])
        ->name('stock-adjustments.store')
        ->middleware('permission:stock.adjustment.create');
    Route::get('stock-adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'show'])
        ->name('stock-adjustments.show')
        ->middleware('permission:stock.adjustment.view');

    // Damaged Stock
    Route::get('damaged-stocks', [DamagedStockController::class, 'index'])
        ->name('damaged-stocks.index')
        ->middleware('permission:damaged_stock.view');
    Route::post('damaged-stocks', [DamagedStockController::class, 'store'])
        ->name('damaged-stocks.store')
        ->middleware('permission:damaged_stock.create');
    Route::put('damaged-stocks/{damagedStock}', [DamagedStockController::class, 'update'])
        ->name('damaged-stocks.update')
        ->middleware('permission:damaged_stock.edit');
    Route::delete('damaged-stocks/{damagedStock}', [DamagedStockController::class, 'destroy'])
        ->name('damaged-stocks.destroy')
        ->middleware('permission:damaged_stock.delete');
    // Damaged Stock
    Route::post('damaged-stocks/{damagedStock}/status', [DamagedStockController::class, 'updateStatus'])
        ->name('damaged-stocks.status')
        ->middleware('permission:damaged_stock.return');

    // Accounting & Expenses
    Route::prefix('accounting')->name('accounting.')->group(function () {
        Route::resource('accounts', ChartOfAccountController::class)->middleware('permission:accounting.view');
        Route::resource('expense-categories', ExpenseCategoryController::class)->middleware('permission:expenses.view');
        Route::resource('expenses', ExpenseController::class)->middleware('permission:expenses.view');
        Route::resource('journal-entries', JournalEntryController::class)->middleware('permission:accounting.view');
        Route::get('ledger', [LedgerBookController::class, 'index'])->name('ledger.index')->middleware('permission:accounting.view');
        Route::resource('capital-drawings', CapitalDrawingController::class)->middleware('permission:accounting.view');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index')->middleware('permission:reports.view');
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales')->middleware('permission:reports.sales');
        Route::get('/retail-sales', [ReportController::class, 'retailSales'])->name('retail_sales')->middleware('permission:reports.retail_sales');
        Route::get('/wholesale-sales', [ReportController::class, 'wholesaleSales'])->name('wholesale_sales')->middleware('permission:reports.wholesale_sales');
        Route::get('/sales-returns', [ReportController::class, 'salesReturns'])->name('sales_returns')->middleware('permission:reports.sales_returns');
        Route::get('/purchases', [ReportController::class, 'purchases'])->name('purchases')->middleware('permission:reports.purchase');
        Route::get('/purchase-returns', [ReportController::class, 'purchaseReturns'])->name('purchase_returns')->middleware('permission:reports.purchase_returns');
        Route::get('/stock', [ReportController::class, 'stock'])->name('stock')->middleware('permission:reports.stock');
        Route::get('/low-stock', [ReportController::class, 'lowStock'])->name('low_stock')->middleware('permission:reports.low_stock');
        Route::get('/stock-movement', [ReportController::class, 'stockMovement'])->name('stock_movement')->middleware('permission:reports.stock_movement');
        Route::get('/damaged-stock', [ReportController::class, 'damagedStock'])->name('damaged_stock')->middleware('permission:reports.damaged_stock');
        Route::get('/product-profit', [ReportController::class, 'productProfit'])->name('product_profit')->middleware('permission:reports.product_profit');
        Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->name('profit_loss')->middleware('permission:reports.profit_loss');
        Route::get('/expenses', [ReportController::class, 'expenses'])->name('expenses')->middleware('permission:reports.expense');
        Route::get('/customer-due', [ReportController::class, 'customerDue'])->name('customer_due')->middleware('permission:reports.customer_due');
        Route::get('/supplier-due', [ReportController::class, 'supplierDue'])->name('supplier_due')->middleware('permission:reports.supplier_due');
        Route::get('/customer-payments', [ReportController::class, 'customerPayments'])->name('customer_payments')->middleware('permission:reports.customer_payment');
        Route::get('/supplier-payments', [ReportController::class, 'supplierPayments'])->name('supplier_payments')->middleware('permission:reports.supplier_payment');
        Route::get('/cash-bank', [ReportController::class, 'cashBank'])->name('cash_bank')->middleware('permission:reports.cash_bank');
        Route::get('/tax', [ReportController::class, 'tax'])->name('tax')->middleware('permission:reports.tax');
        Route::get('/user-activity', [ReportController::class, 'userActivity'])->name('user_activity')->middleware('permission:reports.user_activity');
        
        Route::get('/daily-closing', [DailyClosingController::class, 'index'])->name('daily_closing')->middleware('permission:reports.daily_closing');
        Route::post('/daily-closing', [DailyClosingController::class, 'store'])->name('daily_closing.store')->middleware('permission:reports.daily_closing');
    });
});

require __DIR__.'/settings.php';
