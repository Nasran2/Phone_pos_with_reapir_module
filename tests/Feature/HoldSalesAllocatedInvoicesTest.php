<?php

use App\Models\Customer;
use App\Models\HoldSale;
use App\Models\Permission;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->admin = createUserWithRole('Super Admin');
    $this->actingAs($this->admin);
    $this->customer = Customer::create(['name' => 'Allocated Bill Customer', 'phone' => '0771234567']);
    $this->makeBill = fn (array $attributes = []) => Sale::create(array_merge([
        'customer_id' => $this->customer->id, 'invoice_no' => 'BILL-'.str()->uuid(),
        'sale_date' => today(), 'pos_type' => 'wholesale', 'total_amount' => 1000,
        'payable_amount' => 1000, 'paid_amount' => 0, 'due_amount' => 1000,
        'payment_status' => 'Due', 'status' => 'completed', 'created_by' => $this->admin->id,
    ], $attributes));
    $this->hold = HoldSale::create([
        'customer_id' => $this->customer->id, 'reference_no' => 'HOLD-123', 'hold_date' => today(),
        'pos_type' => 'wholesale', 'total_amount' => 500, 'estimated_total' => 500, 'created_by' => $this->admin->id,
    ]);
});

test('hold sales shows allocated bill links including paid and cheque-held invoices', function () {
    $due = ($this->makeBill)();
    $paid = ($this->makeBill)(['payment_status' => 'Paid', 'paid_amount' => 1000, 'due_amount' => 0, 'stock_release_on_payment' => false]);
    $cheque = ($this->makeBill)(['payment_status' => 'Cheque Hold', 'due_amount' => 0, 'cheque_hold_amount' => 1000]);
    $response = $this->get(route('hold-sales.index'))->assertOk();
    expect($response->viewData('allocatedSales')->pluck('id')->all())->toEqualCanonicalizing([$due->id, $paid->id, $cheque->id]);
    foreach ([$due, $paid, $cheque] as $bill) {
        $response->assertSee($bill->invoice_no)->assertSee(route('sales.show', $bill), false);
        $this->get(route('sales.show', $bill))->assertOk()->assertSee($bill->invoice_no);
    }
    $response->assertSee('Allocated Invoices')->assertSee('Held POS Carts')->assertSee('HOLD-123')
        ->assertSee(route('hold-sales.show', $this->hold), false);
});

test('allocation list excludes released cancelled and draft invoices and returns restored allocations', function () {
    $released = ($this->makeBill)(['stock_deducted_at' => now()]);
    foreach (['cancelled', 'draft', 'quotation'] as $status) {
        ($this->makeBill)(['status' => $status]);
    }
    $this->get(route('hold-sales.index'))->assertOk()->assertViewHas('allocatedSales', fn ($rows) => $rows->total() === 0);
    $released->update(['stock_deducted_at' => null, 'stock_release_on_payment' => false]);
    $this->get(route('hold-sales.index'))->assertOk()->assertViewHas('allocatedSales', fn ($rows) => $rows->pluck('id')->all() === [$released->id]);
});

test('bill customer type date and cashier filters apply to both hold lists', function () {
    $bill = ($this->makeBill)(['invoice_no' => 'ALLOC-SEARCH']);
    ($this->makeBill)(['pos_type' => 'retail']);
    ($this->makeBill)()->forceFill(['created_at' => now()->subDays(3)])->save();
    ($this->makeBill)(['created_by' => createUserWithRole('Super Admin')->id]);

    $response = $this->get(route('hold-sales.index', [
        'search' => '0771234567', 'sale_type' => 'wholesale', 'cashier_id' => $this->admin->id,
        'date_from' => today()->toDateString(), 'date_to' => today()->toDateString(),
    ]))->assertOk();
    expect($response->viewData('allocatedSales')->pluck('id')->all())->toBe([$bill->id])
        ->and($response->viewData('holds')->pluck('id')->all())->toBe([$this->hold->id]);
    $this->get(route('hold-sales.index', ['search' => 'ALLOC-SEARCH']))->assertOk()
        ->assertViewHas('allocatedSales', fn ($rows) => $rows->pluck('id')->all() === [$bill->id])
        ->assertViewHas('holds', fn ($rows) => $rows->total() === 0);
});

test('allocated invoices and held carts paginate independently', function () {
    for ($i = 1; $i <= 13; $i++) {
        ($this->makeBill)();
    }
    $response = $this->get(route('hold-sales.index', ['allocated_page' => 2]))->assertOk();
    expect($response->viewData('allocatedSales')->count())->toBe(1)
        ->and($response->viewData('allocatedSales')->currentPage())->toBe(2)
        ->and($response->viewData('holds')->currentPage())->toBe(1)
        ->and($response->viewData('holds')->pluck('id')->all())->toBe([$this->hold->id]);
});

test('hold-only permission does not expose allocated invoice details', function () {
    $bill = ($this->makeBill)();
    $viewer = createUserWithRole('Hold Queue Viewer');
    $permission = Permission::firstOrCreate(['name' => 'hold_sales.view'], ['guard_name' => 'web']);
    DB::table('role_permissions')->insert(['role_id' => $viewer->roles()->first()->id, 'permission_id' => $permission->id]);
    $this->actingAs($viewer)->get(route('hold-sales.index'))->assertOk()
        ->assertViewHas('allocatedSales', null)->assertDontSee($bill->invoice_no)->assertSee('HOLD-123');
});

test('lorry users see only allocated invoices from their active assignments', function () {
    $viewer = createUserWithRole('Lorry Hold Viewer');
    $viewer->update(['user_type' => 'lorry']);
    $permission = Permission::firstOrCreate(['name' => 'hold_sales.view'], ['guard_name' => 'web']);
    DB::table('role_permissions')->insert(['role_id' => $viewer->roles()->first()->id, 'permission_id' => $permission->id]);
    $assigned = DB::table('lorries')->insertGetId(['lorry_no' => 'HOLD-LORRY', 'name' => 'Assigned Lorry']);
    $other = DB::table('lorries')->insertGetId(['lorry_no' => 'OTHER-HOLD-LORRY', 'name' => 'Other Lorry']);
    DB::table('lorry_user_assignments')->insert(['user_id' => $viewer->id, 'lorry_id' => $assigned, 'lorry_role' => 'salesman', 'active_from' => today()->toDateString(), 'is_active' => true]);
    $ownBill = ($this->makeBill)();
    $ownBill->forceFill(['source_type' => 'lorry', 'lorry_id' => $assigned])->save();
    ($this->makeBill)()->forceFill(['source_type' => 'lorry', 'lorry_id' => $other])->save();
    ($this->makeBill)();
    $this->actingAs($viewer)->get(route('hold-sales.index'))->assertOk()
        ->assertViewHas('allocatedSales', fn ($rows) => $rows->pluck('id')->all() === [$ownBill->id]);
});
