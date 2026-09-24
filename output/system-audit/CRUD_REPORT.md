# CRUD coverage and follow-up fixes

Updated 24 September 2026. “Seller” is treated as Supplier in this application.

## Main workflows

| Module | Create | Read | Update | Delete | Verification |
|---|---|---|---|---|---|
| Customers | Existing | Existing | Existing | Existing, guarded for sales | Existing feature suite; deletion now retains transaction history |
| Suppliers | Existing | Existing | Existing | Existing, guarded for purchases | Existing feature suite; deletion now retains transaction history |
| Purchases | Existing | Existing | Existing | Existing, stock/return checks | Full create/edit/delete stock and supplier-balance regression |
| Retail sales | POS | Invoice/list | POS edit | Sales list, cheque/return guards | Paid invoice edit/delete, stock, payment count and journal balances |
| Wholesale sales | POS | Invoice/list | POS edit | Sales list, cheque/return guards | Paid invoice edit/delete, stock, payment count and journal balances |
| Products | Existing | Existing | Existing | Existing | Existing product feature suite |
| Expenses | Existing | List | Added edit form and update action | Existing | Create/update/delete account balances; browser saves at 375px and 1440px |
| Chart of accounts | Existing | List | Added metadata edit form | Existing, transaction guard | Create/edit/delete; balance tampering ignored; browser saves at 375px and 1440px |

## Bugs corrected

- Sale edits previously appended a second payment allocation. They now preserve recorded payments; additional receipts are entered through Sales. The POS displays this rule.
- Replacing a sale journal previously deleted rows without reversing account balances. It now uses the journal reversal service.
- Journal cleanup now processes every entry for a reference instead of only the first.
- Sale deletion now reverses Sale and InvoicePayment journals, removes related invoice-payment/tax rows, and restores stock. Linked cheques or sale returns block deletion.
- Edits require sales.edit on backend submission and edit-data loading. Changing the customer, editing an invoice with cheques/returns, or reducing below already-paid value is rejected.
- Invoice editing retains its original date and does not award retail loyalty points again.
- Purchase deletion previously returned from an open database transaction when related returns existed. The guard now runs before the transaction.
- Purchase stock reversal no longer silently clamps a shortage to zero. It rejects the change when received stock has already been used. Purchase updates with returns are also blocked.
- Expense update reverses the previous expense journal and posts the replacement atomically; edit routes require expenses.edit.
- Account Active checkbox now sends a valid boolean value. Account editing changes identifying details/status without altering ledger balances.
- Customer/supplier soft deletion retains transaction history.

## Verification

- Full suite: 102 passed, 2 existing skips, 914 assertions.
- New expense/account edit forms saved successfully at 375px and 1440px; no JavaScript errors.
- Vite build, Blade compilation and Git whitespace checks passed.
- Tests used isolated SQLite data. No production records were changed and no deployment was performed.

## Registered operations by module

This is a route inventory, not a claim that every action below was manually exercised. Some operational modules use approve/reverse/finalize/cancel instead of unrestricted editing and deletion. Reports/settings do not represent CRUD entities.

| Controller | Registered actions |
|---|---|
| ActivityLogController | index |
| BarcodeController | index, print |
| BrandController | destroy, index, store, update |
| CapitalDrawingController | index, store |
| CategoryController | destroy, index, store, update |
| ChartOfAccountController | destroy, edit, index, store, update |
| ChequeReportController | excel, index, pdf |
| ChequeSettingsController | index, update |
| ChequeStatusController | cancel, pass, replace, returned |
| CustomerController | destroy, index, show, store, storePayment, update |
| DailyClosingController | index, store |
| DamagedStockController | destroy, index, store, update, updateStatus |
| DashboardController | index |
| DistributionController | dashboard, destroyLorry, expenses, load, lorries, lorryTransfer, productSearch, reconciliation, reports, returns, sales, settings, showLorry, stock, trips, updateLorry, updateTripStatus |
| ExpenseCategoryController | destroy, index, store, update |
| ExpenseController | destroy, edit, index, store, update |
| HoldSaleController | destroy, index, resume, show |
| HsCodeController | destroy, index, store, update |
| ImportShipmentController | create, destroy, edit, finalize, index, show, store, update |
| IssuedChequeController | create, index, store |
| JournalEntryController | destroy, index, store |
| LedgerBookController | index |
| MyLorryController | createOrder, customers, dashboard, expenses, sales, stock, storeOrder |
| PermissionController | index |
| ProductController | destroy, export, import, importTemplate, index, stockHistory, store, update |
| PurchaseController | create, destroy, download, edit, index, print, show, store, storeSupplier, update |
| PurchaseReturnController | create, destroy, index, show, store |
| ReceivedChequeController | create, index, show, store |
| ReportController | cashBank, customerDue, customerPayments, damagedStock, expenses, index, lowStock, productProfit, profitLoss, purchaseReturns, purchases, retailSales, sales, salesReturns, stock, stockMovement, supplierDue, supplierPayments, tax, userActivity, wholesaleSales |
| RetailPosController | index, store, storeCustomer |
| RoleController | destroy, index, store, update |
| SaleController | destroy, download, index, print, shared, show, storePayment |
| SaleReturnController | create, destroy, index, show, store |
| SettingsController | reset, show, update |
| StockAdjustmentController | create, index, report, show, store |
| StockTransferController | create, destroy, index, markCompleted, show, store |
| StoreController | destroy, index, store, update |
| StoreStockController | history, index |
| SupplierController | destroy, index, show, store, storePayment, update |
| TaxAdjustmentController | approve, create, index, store |
| TaxDashboardController | index |
| TaxPaymentController | approve, create, index, reverse, show, store |
| TaxPeriodController | create, index, show, store |
| TaxRateController | destroy, index, store, update |
| TaxReportController | index, paymentRegister, vatReturn |
| TaxTransactionController | index |
| UnitController | destroy, index, store, update |
| UserController | destroy, index, store, toggleStatus, update, updatePassword |
| WholesalePosController | index, store, storeCustomer |

## Limits and remaining scope

The main commercial CRUD workflows above are implemented and tested. This pass does not certify every action or every role combination in distribution, tax, journals, returns and stock-transfer workflows. Their existing business-specific actions remain in place; unrestricted CRUD was not added to posted records. Physical device testing and production-database concurrency testing remain outstanding.

Evidence: [backend results](crud-test-results.json), [browser results](crud-browser-results.json), [build](crud-build-results.txt).
