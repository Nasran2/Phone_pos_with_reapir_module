<?php

use App\Models\Cheque;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductStoreStock;
use App\Models\RepairChargeTemplate;
use App\Models\RepairDevice;
use App\Models\RepairJob;
use App\Models\RepairProblem;
use App\Models\RepairStatus;
use App\Models\RepairTechnician;
use App\Models\StockHistory;
use App\Services\ChequeService;
use App\Services\Repairs\RepairInventoryService;
use App\Services\Repairs\RepairJobService;
use App\Services\Repairs\RepairPaymentService;
use App\Services\Repairs\RepairPricingService;
use App\Support\Features;
use App\Support\RepairMoney;
use App\Support\SystemSettings;
use Database\Seeders\AccountingSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

function repairIntake(array $overrides = []): array
{
    $customer = Customer::firstOrCreate(['name' => 'Repair Test Customer'], ['phone' => '0771234567', 'status' => true]);

    return array_replace_recursive(['customer_id' => $customer->id, 'store_id' => 1, 'intake_key' => (string) Str::uuid(), 'device' => ['device_type' => 'Phone', 'brand' => 'Apple', 'model' => 'Test Device', 'imei_1' => '123456789012345', 'serial_number' => 'REPAIR-SERIAL', 'unlock_type' => 'PIN', 'unlock_secret' => '7348', 'accessories_received' => ['Charger'], 'physical_condition' => ['Screen' => 'Cracked']], 'problem_ids' => [RepairProblem::first()->id], 'checklist' => ['Display' => 'Not working', 'Speaker' => 'Working'], 'urgent' => false, 'is_warranty' => false, 'estimated_cost' => '200.00', 'expected_completion_date' => today()->addDays(3)->toDateString()], $overrides);
}
function repairJob(): RepairJob
{
    return app(RepairJobService::class)->save(repairIntake());
}
function repairProduct(): Product
{
    return Product::create(['name' => 'Replacement Display', 'sku' => 'REPAIR-'.Str::random(8), 'cost_price' => 40, 'retail_price' => 100, 'is_active' => true]);
}
function repairCharge(RepairJob $job, string $amount = '50.00'): void
{
    $job->charges()->create(['name' => 'Labor snapshot', 'amount' => $amount]);
    DB::transaction(fn () => app(RepairPricingService::class)->refresh($job));
}
function repairPayment(RepairJob $job, array $overrides = []): array
{
    return array_replace_recursive(['amount' => '50.00', 'payment_method' => 'Cash', 'payment_date' => today()->toDateString(), 'is_advance' => false, 'request_key' => (string) Str::uuid()], $overrides);
}

beforeEach(function () {
    config(['features.repair' => true, 'features.wholesale' => false]);
    SystemSettings::clearCache();
    $this->actingAs(createUserWithRole('Super Admin'));
    $this->seed(AccountingSeeder::class);
});

test('repair workspace entry pages render', function (string $route) {
    $this->get(route($route))->assertOk();
})->with(['repairs.dashboard', 'repairs.index', 'repairs.create', 'repairs.technicians', 'repairs.settings', 'repairs.reports']);

test('intake stores encrypted credentials and snapshots without legacy plaintext', function (string $type, string $secret) {
    $data = repairIntake(['device' => ['unlock_type' => $type, 'unlock_secret' => $secret]]);
    $this->post(route('repairs.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
    $job = RepairJob::firstOrFail();
    $device = $job->device;
    expect($device->unlock_secret)->toBe($secret)->and($device->getRawOriginal('unlock_secret'))->not->toContain($secret)
        ->and($device->pin)->toBeNull()->and($device->password)->toBeNull()->and($device->pattern_lock)->toBeNull()
        ->and($job->problem_snapshot)->toBe([RepairProblem::first()->name])->and($job->checklist->items['before']['Display'])->toBe('Not working');
    expect($device->toJson())->not->toContain($secret)->and($job->load('device')->toJson())->not->toContain($secret);
    $this->get(route('repairs.show', $job))->assertOk()->assertDontSee($secret);
})->with([['PIN', '7348'], ['Password', 'privateCredential!'], ['Pattern', '1-2-5-8-9']]);

test('client supplied plaintext legacy fields are never accepted', function () {
    $data = repairIntake(['device' => ['pin' => 'PLAINTEXT', 'password' => 'PLAINTEXT', 'pattern_lock' => 'PLAINTEXT']]);
    $this->post(route('repairs.store'), $data)->assertSessionHasNoErrors();
    expect(RepairJob::first()->device->getRawOriginal('pin'))->toBeNull();
});

test('bad intake never flashes an unlock secret', function () {
    $data = repairIntake(['customer_id' => null]);
    $this->post(route('repairs.store'), $data)->assertSessionHasErrors('customer_id');
    expect(session()->getOldInput('device.unlock_secret'))->toBeNull();
});

test('invalid pattern is rejected', function (string $pattern) {
    $this->postJson(route('repairs.store'), repairIntake(['device' => ['unlock_type' => 'Pattern', 'unlock_secret' => $pattern]]))->assertUnprocessable();
})->with(['1-2', '1-2-3-3', '0-2-3-4', '1-2-3-4-5-6-7-8-9-1']);

test('intake request is idempotent and sequence increases', function () {
    $data = repairIntake();
    $this->post(route('repairs.store'), $data)->assertSessionHasNoErrors();
    $this->post(route('repairs.store'), $data)->assertSessionHasNoErrors();
    expect(RepairJob::count())->toBe(1);
    $first = RepairJob::first()->job_no;
    $second = repairJob()->job_no;
    expect($second)->not->toBe($first)->and((int) substr($second, -4))->toBe((int) substr($first, -4) + 1);
});

test('an initial advance is an actual financial payment', function () {
    $this->post(route('repairs.store'), repairIntake(['advance_amount' => '25.50', 'advance_method' => 'Cash']))->assertSessionHasNoErrors();
    $job = RepairJob::first();
    expect($job->payments()->count())->toBe(1)->and($job->paid_amount)->toBe('25.50')->and($job->advance_amount)->toBe('25.50')->and($job->customer->balance)->toBe('-25.50');
});

test('normal issued parts affect only the receiving store once', function () {
    $job = repairJob();
    $p = repairProduct();
    ProductStoreStock::create(['product_id' => $p->id, 'store_id' => 1, 'quantity' => 5]);
    $data = ['product_id' => $p->id, 'quantity' => '2.00', 'status' => 'Issued', 'request_key' => (string) Str::uuid()];
    $part = app(RepairInventoryService::class)->add($job, $data);
    app(RepairInventoryService::class)->add($job, $data);
    expect((float) ProductStoreStock::where('product_id', $p->id)->value('quantity'))->toBe(3.0)->and(StockHistory::where('reference_id', $job->id)->count())->toBe(1)->and($job->fresh()->parts_total)->toBe('200.00');
    app(RepairInventoryService::class)->change($job, $part, 'return');
    app(RepairInventoryService::class)->change($job, $part, 'return');
    expect((float) ProductStoreStock::where('product_id', $p->id)->value('quantity'))->toBe(5.0)->and(StockHistory::where('reference_id', $job->id)->count())->toBe(2)->and($job->fresh()->total_amount)->toBe('0.00');
});

test('reserved part neither bills nor deducts before issue', function () {
    $job = repairJob();
    $p = repairProduct();
    ProductStoreStock::create(['product_id' => $p->id, 'store_id' => 1, 'quantity' => 5]);
    $part = app(RepairInventoryService::class)->add($job, ['product_id' => $p->id, 'quantity' => '1.00', 'status' => 'Reserved', 'request_key' => (string) Str::uuid()]);
    expect((float) ProductStoreStock::where('product_id', $p->id)->value('quantity'))->toBe(5.0)->and($job->fresh()->total_amount)->toBe('0.00');
    app(RepairInventoryService::class)->change($job, $part, 'issue');
    app(RepairInventoryService::class)->change($job, $part, 'issue');
    expect((float) ProductStoreStock::where('product_id', $p->id)->value('quantity'))->toBe(4.0)->and($job->fresh()->total_amount)->toBe('100.00');
});

test('variant stock changes without changing parent store stock', function () {
    $job = repairJob();
    $p = repairProduct();
    ProductStoreStock::create(['product_id' => $p->id, 'store_id' => 1, 'quantity' => 10]);
    $v = $p->variants()->create(['sku' => 'REPAIR-V', 'color' => 'Black', 'cost_price' => 30, 'retail_price' => 80, 'stock_quantity' => 3, 'is_active' => true]);
    $part = app(RepairInventoryService::class)->add($job, ['product_id' => $p->id, 'variant_id' => $v->id, 'quantity' => '1.00', 'status' => 'Issued', 'request_key' => (string) Str::uuid()]);
    expect($v->fresh()->stock_quantity)->toBe('2.00')->and((float) ProductStoreStock::where('product_id', $p->id)->value('quantity'))->toBe(10.0)->and($part->selling_price)->toBe('80.00');
    app(RepairInventoryService::class)->change($job, $part, 'return');
    expect($v->fresh()->stock_quantity)->toBe('3.00');
});

test('missing variant and insufficient stock roll back every side effect', function () {
    $job = repairJob();
    $p = repairProduct();
    $p->variants()->create(['sku' => 'REPAIR-V', 'stock_quantity' => 0, 'is_active' => true]);
    $data = ['product_id' => $p->id, 'quantity' => 1, 'status' => 'Issued', 'request_key' => (string) Str::uuid()];
    $this->postJson(route('repairs.part', $job), $data)->assertUnprocessable();
    $data['variant_id'] = $p->variants()->first()->id;
    $this->postJson(route('repairs.part', $job), $data)->assertUnprocessable();
    expect($job->parts()->count())->toBe(0)->and(StockHistory::where('reference_id', $job->id)->count())->toBe(0);
});

test('global negative stock setting is respected', function () {
    DB::table('business_settings')->updateOrInsert(['key' => 'allow_negative_stock'], ['value' => '1']);
    SystemSettings::clearCache();
    $job = repairJob();
    $p = repairProduct();
    app(RepairInventoryService::class)->add($job, ['product_id' => $p->id, 'quantity' => 1, 'request_key' => (string) Str::uuid()]);
    expect((float) ProductStoreStock::where('product_id', $p->id)->value('quantity'))->toBe(-1.0);
});

test('price snapshots survive product and template changes', function () {
    $job = repairJob();
    $p = repairProduct();
    ProductStoreStock::create(['product_id' => $p->id, 'store_id' => 1, 'quantity' => 5]);
    $part = app(RepairInventoryService::class)->add($job, ['product_id' => $p->id, 'quantity' => 1, 'request_key' => (string) Str::uuid()]);
    repairCharge($job);
    $p->update(['retail_price' => 999, 'cost_price' => 999]);
    RepairChargeTemplate::first()->update(['default_amount' => 999]);
    DB::transaction(fn () => app(RepairPricingService::class)->refresh($job->refresh()));
    expect($job->total_amount)->toBe('150.00')->and($part->selling_price)->toBe('100.00');
});

test('server computes parts services discount tax and actual paid totals', function () {
    $job = repairJob();
    repairCharge($job, '100.01');
    $job->update(['discount_type' => 'percentage', 'discount_value' => '10.00', 'tax_rate' => '15.00']);
    DB::transaction(fn () => app(RepairPricingService::class)->refresh($job));
    expect($job->discount_amount)->toBe('10.00')->and($job->tax_amount)->toBe('13.50')->and($job->total_amount)->toBe('103.51');
    $this->post(route('repairs.payment', $job), repairPayment($job, ['amount' => '30.01', 'paid_amount' => 99999, 'total_amount' => 0]))->assertSessionHasNoErrors();
    expect($job->fresh()->paid_amount)->toBe('30.01')->and($job->fresh()->balance_amount)->toBe('73.50');
    expect(RepairMoney::multiplyDivide(9999999999999, 9999999999999, 9999999999999))->toBe(9999999999999);
});

test('payment retry posts customer and journal once', function () {
    $job = repairJob();
    repairCharge($job);
    $data = repairPayment($job);
    $this->post(route('repairs.payment', $job), $data)->assertSessionHasNoErrors();
    $this->post(route('repairs.payment', $job), $data)->assertSessionHasNoErrors();
    expect($job->payments()->count())->toBe(1)->and(JournalEntry::where('reference_type', 'repair_payment')->where('reference_id', $job->id)->count())->toBe(1)->and($job->fresh()->customer->balance)->toBe('0.00');
    foreach (JournalEntry::whereIn('reference_type', ['repair_billing', 'repair_payment'])->with('items')->get() as $journal) {
        expect((float) $journal->items->sum('debit'))->toBe((float) $journal->items->sum('credit'));
    }
});

test('cheque hold clears or returns through existing lifecycle', function (string $action) {
    $job = repairJob();
    repairCharge($job);
    $data = repairPayment($job, ['payment_method' => 'Cheque', 'cheque' => ['cheque_number' => 'REP-CHQ', 'cheque_date' => today()->toDateString(), 'bank_name' => 'Test Bank', 'account_holder' => 'Repair Customer']]);
    $payment = app(RepairPaymentService::class)->add($job, $data);
    $job = $job->fresh();
    expect($job->paid_amount)->toBe('0.00')->and($job->cheque_hold_amount)->toBe('50.00')->and($job->balance_amount)->toBe('50.00')->and($job->customer->balance)->toBe('0.00')->and($job->customer->cheque_hold_balance)->toBe('50.00');
    if ($action === 'pass') {
        app(ChequeService::class)->pass($payment->fresh()->cheque);
        app(ChequeService::class)->pass($payment->fresh()->cheque);
    } else {
        app(ChequeService::class)->returnCheque($payment->fresh()->cheque, ['return_charge' => 0]);
        app(ChequeService::class)->returnCheque($payment->fresh()->cheque, ['return_charge' => 0]);
    }
    $job = $job->fresh();
    expect($job->cheque_hold_amount)->toBe('0.00')->and($job->paid_amount)->toBe($action === 'pass' ? '50.00' : '0.00')->and($job->balance_amount)->toBe($action === 'pass' ? '0.00' : '50.00')->and($job->customer->balance)->toBe($action === 'pass' ? '0.00' : '50.00')->and($job->customer->cheque_hold_balance)->toBe('0.00');
})->with(['pass', 'return']);

test('custom status renames preserve stable ID and history snapshot', function () {
    $job = repairJob();
    $status = RepairStatus::create(['key' => 'custom_status', 'name' => 'Special inspection', 'color' => '#123456', 'sort_order' => 100, 'is_active' => true]);
    $this->post(route('repairs.status', $job), ['repair_status_id' => $status->id, 'note' => 'Customer contacted'])->assertSessionHasNoErrors();
    $status->update(['name' => 'Bench inspection']);
    $job = $job->fresh();
    expect($job->status_label)->toBe('Bench inspection')->and($job->statusLogs()->latest('id')->first()->to_status)->toBe('Special inspection');
    $this->get(route('repairs.show', $job))->assertOk()->assertSee('Bench inspection')->assertSee('Special inspection');
});

test('technician reassignment keeps assignment history', function () {
    $job = repairJob();
    foreach (['Alex', 'Sam'] as $name) {
        $tech = RepairTechnician::create(['name' => $name, 'status' => 'Active']);
        $this->post(route('repairs.assign', $job), ['technician_id' => $tech->id])->assertSessionHasNoErrors();
    }
    expect($job->assignments()->count())->toBe(2)->and($job->fresh()->technician->name)->toBe('Sam');
});

test('completed pricing cannot mutate and delivery requires cleared full payment', function () {
    $job = repairJob();
    repairCharge($job);
    $completed = RepairStatus::where('is_completed', true)->first();
    $delivered = RepairStatus::where('is_delivered', true)->first();
    $this->post(route('repairs.status', $job), ['repair_status_id' => $completed->id])->assertSessionHasNoErrors();
    $this->postJson(route('repairs.charge', $job), ['name' => 'Injected', 'amount' => 100])->assertUnprocessable();
    $this->postJson(route('repairs.status', $job), ['repair_status_id' => $delivered->id])->assertUnprocessable();
    app(RepairPaymentService::class)->add($job, repairPayment($job));
    $this->post(route('repairs.status', $job), ['repair_status_id' => $delivered->id])->assertSessionHasNoErrors();
    expect($job->fresh()->delivered_at)->not->toBeNull();
});

test('cancellation requires explicit issued part return and retains history', function () {
    $job = repairJob();
    $p = repairProduct();
    ProductStoreStock::create(['product_id' => $p->id, 'store_id' => 1, 'quantity' => 5]);
    app(RepairInventoryService::class)->add($job, ['product_id' => $p->id, 'quantity' => 1, 'request_key' => (string) Str::uuid()]);
    $cancel = RepairStatus::where('key', 'cancelled')->first();
    $this->postJson(route('repairs.status', $job), ['repair_status_id' => $cancel->id])->assertUnprocessable();
    $this->post(route('repairs.status', $job), ['repair_status_id' => $cancel->id, 'return_parts' => 1])->assertSessionHasNoErrors();
    expect((float) ProductStoreStock::where('product_id', $p->id)->value('quantity'))->toBe(5.0)->and($job->parts()->first()->status)->toBe('Returned')->and($job->activities()->count())->toBeGreaterThan(1);
});

test('financial jobs cannot be deleted', function () {
    $job = repairJob();
    repairCharge($job);
    app(RepairPaymentService::class)->add($job, repairPayment($job));
    $this->deleteJson(route('repairs.destroy', $job))->assertUnprocessable();
    expect($job->fresh()->deleted_at)->toBeNull()->and($job->payments()->count())->toBe(1);
});

test('authorized credential reveal is audited but never logged', function () {
    $job = repairJob();
    $this->postJson(route('repairs.credential', $job))->assertOk()->assertJson(['secret' => '7348'])->assertHeader('Cache-Control', 'no-store, private');
    expect($job->activities()->where('action', 'Credential revealed')->count())->toBe(1);
    expect($job->activities()->get()->toJson())->not->toContain('7348')->and(DB::table('activity_logs')->get()->toJson())->not->toContain('7348');
});

test('read only staff cannot reveal or mutate repair jobs', function () {
    $job = repairJob();
    $user = createUserWithRole('Repair Reader');
    $role = $user->roles->first();
    $role->permissions()->sync(DB::table('permissions')->where('name', 'repair.view')->pluck('id'));
    $this->actingAs($user);
    $this->get(route('repairs.show', $job))->assertOk()->assertDontSee('Reveal credential')->assertDontSee('Record payment');
    $this->postJson(route('repairs.credential', $job))->assertForbidden();
    $this->postJson(route('repairs.payment', $job), repairPayment($job))->assertForbidden();
    $this->postJson(route('repairs.status', $job), ['repair_status_id' => 1])->assertForbidden();
});

test('photos are private and scoped to their job', function () {
    Storage::fake('local');
    $job = repairJob();
    $other = repairJob();
    $this->post(route('repairs.photo', $job), ['type' => 'Damage', 'images' => [UploadedFile::fake()->image('damage.jpg')]])->assertSessionHasNoErrors();
    $image = $job->images()->first();
    Storage::disk('local')->assertExists($image->path);
    $this->get(route('repairs.image', [$job, $image]))->assertOk();
    $this->get(route('repairs.image', [$other, $image]))->assertNotFound();
    $this->postJson(route('repairs.photo', $job), ['type' => 'Damage', 'images' => [UploadedFile::fake()->create('unsafe.php', 1)]])->assertUnprocessable();
});

test('warranty months use calendar dates and notes respect print privacy', function () {
    $job = repairJob();
    $this->post(route('repairs.warranty', $job), ['warranty_type' => 'Months', 'duration' => 1, 'starts_at' => '2026-01-31', 'terms' => 'Parts warranty'])->assertSessionHasNoErrors();
    expect($job->fresh()->warranty->expires_at->toDateString())->toBe('2026-02-28');
    $job->notes()->create(['type' => 'Internal', 'note' => 'PRIVATE TECHNICIAN NOTE']);
    $job->notes()->create(['type' => 'Customer', 'note' => 'Customer care instructions']);
    foreach (['repairs.job-card', 'repairs.invoice'] as $route) {
        $this->get(route($route, $job))->assertOk()->assertSee('Customer care instructions')->assertDontSee('PRIVATE TECHNICIAN NOTE')->assertDontSee('7348')->assertDontSee('unlock_secret')->assertDontSee('PIN');
    }
});

test('all six reports render and exports work', function (string $report) {
    $job = repairJob();
    repairCharge($job);
    $this->get(route('repairs.reports', $report))->assertOk();
    $this->get(route('repairs.reports', ['report' => $report, 'export' => 'excel']))->assertOk();
    $this->get(route('repairs.reports', ['report' => $report, 'export' => 'pdf']))->assertOk();
})->with(['jobs', 'revenue', 'parts', 'technicians', 'statuses', 'outstanding']);

test('job card and invoice support PDF and receipt sizes', function () {
    $job = repairJob();
    foreach (['repairs.job-card', 'repairs.invoice'] as $route) {
        $this->get(route($route, [$job, 'paper' => '80mm', 'pdf' => 1]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }
});

test('repair search and customer history appear only while enabled', function () {
    $job = repairJob();
    $this->getJson(route('global-search', ['q' => 'REPAIR-SERIAL']))->assertOk()->assertJsonPath('results.0.type', 'Repair');
    $this->get(route('customers.show', $job->customer))->assertOk()->assertSee($job->job_no);
    config(['features.repair' => false]);
    $this->getJson(route('global-search', ['q' => 'REPAIR-SERIAL']))->assertOk()->assertJsonCount(0, 'results');
    $this->get(route('customers.show', $job->customer))->assertOk()->assertDontSee($job->job_no);
});

test('repair master status and charge catalog changes do not rewrite old jobs', function () {
    $job = repairJob();
    $old = $job->problem_snapshot;
    RepairProblem::first()->update(['name' => 'Renamed', 'is_active' => false]);
    expect($job->fresh()->problem_snapshot)->toBe($old);
    $this->post(route('repairs.catalog.store', 'charges'), ['name' => 'Microsoldering', 'default_amount' => '150.00', 'sort_order' => 1, 'is_active' => 1])->assertSessionHasNoErrors();
    $this->post(route('repairs.catalog.store', 'statuses'), ['name' => 'Board testing', 'color' => '#123456', 'sort_order' => 50, 'is_active' => 1])->assertSessionHasNoErrors();
    expect(RepairStatus::where('name', 'Board testing')->exists())->toBeTrue();
});

test('legacy credential conversion is encrypted retry safe and conflict safe', function () {
    $job = repairJob();
    $job->device->forceFill(['unlock_secret' => null, 'unlock_type' => 'None', 'pin' => 'LEGACY-PIN'])->save();
    $this->artisan('repairs:encrypt-credentials', ['--dry-run' => true])->assertSuccessful();
    expect($job->device->fresh()->pin)->toBe('LEGACY-PIN');
    $this->artisan('repairs:encrypt-credentials')->assertSuccessful();
    $this->artisan('repairs:encrypt-credentials')->assertSuccessful();
    expect($job->device->fresh()->pin)->toBeNull()->and($job->device->fresh()->unlock_secret)->toBe('LEGACY-PIN');
});

test('disabled repair routes return 404 without changing history', function (string $method, string $route) {
    $job = repairJob();
    config(['features.repair' => false]);
    $url = route($route, in_array($route, ['repairs.dashboard', 'repairs.index', 'repairs.create', 'repairs.store', 'repairs.settings', 'repairs.settings.update', 'repairs.technicians', 'repairs.technicians.store', 'repairs.reports', 'repairs.customers', 'repairs.customers.store', 'repairs.products']) ? [] : $job);
    $this->{$method}($url)->assertNotFound();
    expect(RepairJob::count())->toBe(1)->and(RepairDevice::count())->toBe(1);
})->with([
    ['get', 'repairs.dashboard'], ['get', 'repairs.index'], ['get', 'repairs.create'], ['post', 'repairs.store'], ['get', 'repairs.show'], ['get', 'repairs.edit'], ['put', 'repairs.update'], ['delete', 'repairs.destroy'], ['post', 'repairs.status'], ['post', 'repairs.assign'], ['post', 'repairs.part'], ['post', 'repairs.payment'], ['post', 'repairs.credential'], ['post', 'repairs.photo'], ['get', 'repairs.invoice'], ['get', 'repairs.job-card'], ['get', 'repairs.reports'], ['get', 'repairs.settings'], ['put', 'repairs.settings.update'], ['get', 'repairs.technicians'], ['post', 'repairs.technicians.store'], ['get', 'repairs.customers'], ['post', 'repairs.customers.store'], ['get', 'repairs.products'],
]);

test('disabled repair hides sidebar dashboard permission groups and login', function (string $route) {
    config(['features.repair' => false]);
    $response = $this->get(route($route))->assertOk();
    $response->assertDontSee('repairs/jobs')->assertDontSee('Repair Management')->assertDontSee('repair.credentials.view')->assertDontSee('Repair balance');
})->with(['dashboard', 'products.index', 'customers.index', 'roles.index', 'permissions.index', 'accounting.accounts.index', 'settings.general', 'pos.retail']);

test('feature combinations are independent and reenable restores data', function (bool $wholesale, bool $repair) {
    $job = repairJob();
    config(['features.wholesale' => $wholesale, 'features.repair' => $repair]);
    expect(Features::wholesaleEnabled())->toBe($wholesale)->and(Features::repairEnabled())->toBe($repair)->and(auth()->user()->hasPermission('repair.credentials.view'))->toBe($repair);
    $this->get(route('repairs.show', $job))->assertStatus($repair ? 200 : 404);
    $this->get(route('pos.wholesale'))->assertStatus($wholesale ? 200 : 404);
    auth()->logout();
    $login = $this->get(route('login'))->assertOk();
    if (! $repair) {
        $login->assertDontSee('Repair')->assertDontSee('repairs');
    }if (! $wholesale) {
        $login->assertDontSee('Wholesale');
    }
    $this->actingAs(createUserWithRole('Super Admin'));
    config(['features.repair' => true]);
    $this->get(route('repairs.show', $job))->assertOk();
    expect($job->fresh()->device->unlock_secret)->toBe('7348');
})->with([[true, true], [true, false], [false, true], [false, false]]);

test('quick customer creation reuses a formatted phone and rejects invalid numbers', function () {
    $customer = Customer::create(['name' => 'Existing contact', 'phone' => '(077) 999-8888', 'status' => true]);
    $this->postJson(route('repairs.customers.store'), ['name' => 'Duplicate contact', 'phone' => '0779998888'])->assertOk()->assertJsonPath('id', $customer->id);
    expect(Customer::where('name', 'Duplicate contact')->exists())->toBeFalse();
    $this->postJson(route('repairs.customers.store'), ['name' => 'New contact', 'phone' => '077 888-7777'])->assertOk()->assertJsonPath('phone', '0778887777');
    $this->postJson(route('repairs.customers.store'), ['name' => 'Invalid', 'phone' => 'letters'])->assertUnprocessable();
});

test('custom function tests compare before and after without duplicating accessories', function () {
    $data = repairIntake(['accessory_other' => '32GB SD Card', 'checklist' => ['Wireless charging' => 'Not working']]);
    $this->post(route('repairs.store'), $data)->assertSessionHasNoErrors();
    $job = RepairJob::firstOrFail();
    expect($job->device->accessories_received)->toBe(['Charger', '32GB SD Card']);
    $this->post(route('repairs.checklist', $job), ['after' => ['Wireless charging' => 'Working']])->assertSessionHasNoErrors();
    $this->get(route('repairs.show', $job))->assertOk()->assertSee('Wireless charging')->assertSee('Not working')->assertSee('Working');
    expect($job->checklist->fresh()->items['after']['Wireless charging'])->toBe('Working');
});

test('inclusive tax is captured from global settings and journals reconcile the total', function () {
    DB::table('repair_settings')->updateOrInsert(['key' => 'enable_tax'], ['value' => '1']);
    DB::table('tax_settings')->updateOrInsert(['key' => 'default_sale_tax'], ['value' => '10']);
    SystemSettings::clearCache();
    DB::table('tax_settings')->updateOrInsert(['key' => 'default_tax_mode'], ['value' => 'inclusive']);
    SystemSettings::clearCache();
    $job = repairJob();
    repairCharge($job, '110.00');
    expect($job->fresh()->tax_mode)->toBe('inclusive')->and($job->fresh()->tax_amount)->toBe('10.00')->and($job->fresh()->total_amount)->toBe('110.00');
    expect($job->fresh()->ledger_revenue)->toBe(['parts' => 0, 'services' => 10000, 'tax' => 1000]);
    DB::table('tax_settings')->updateOrInsert(['key' => 'default_sale_tax'], ['value' => '25']);
    SystemSettings::clearCache();
    DB::transaction(fn () => app(RepairPricingService::class)->refresh($job->refresh()));
    expect($job->fresh()->total_amount)->toBe('110.00')->and($job->fresh()->customer->balance)->toBe('110.00');
});

test('delivered unpaid jobs can settle a remaining balance when delivery policy permits it', function () {
    DB::table('repair_settings')->updateOrInsert(['key' => 'require_full_payment'], ['value' => '0']);
    $job = repairJob();
    repairCharge($job);
    $this->post(route('repairs.status', $job), ['repair_status_id' => RepairStatus::where('is_delivered', true)->value('id')])->assertSessionHasNoErrors();
    $this->get(route('repairs.show', $job))->assertOk()->assertSee('Record payment');
    $this->post(route('repairs.payment', $job), repairPayment($job))->assertSessionHasNoErrors();
    expect($job->fresh()->balance_amount)->toBe('0.00')->and($job->fresh()->customer->balance)->toBe('0.00');
});

test('cached configuration preserves both independent module flags', function (string $wholesale, string $repair) {
    $path = tempnam(sys_get_temp_dir(), 'repair-config-');
    unlink($path);
    try {
        $environment = ['WHOLESALE' => $wholesale, 'REPAIR' => $repair, 'APP_CONFIG_CACHE' => $path, 'PAO_DISABLE' => '1'];
        (new Process([PHP_BINARY, 'artisan', 'config:cache'], base_path(), $environment))->mustRun();
        $environment['WHOLESALE'] = $wholesale === 'true' ? 'false' : 'true';
        $environment['REPAIR'] = $repair === 'true' ? 'false' : 'true';
        $script = 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo json_encode([App\Support\Features::wholesaleEnabled(),App\Support\Features::repairEnabled()]);';
        $probe = new Process([PHP_BINARY, '-r', $script], base_path(), $environment);
        $probe->mustRun();
        expect($probe->getOutput())->toBe(json_encode([$wholesale === 'true', $repair === 'true']));
    } finally {
        if (file_exists($path)) {
            unlink($path);
        }
    }
})->with([['false', 'false'], ['false', 'true'], ['true', 'false'], ['true', 'true']]);

test('repair cheque replacement keeps one payment and still clears with the module disabled', function () {
    $job = repairJob();
    repairCharge($job);
    $payment = app(RepairPaymentService::class)->add($job, repairPayment($job, ['payment_method' => 'Cheque', 'cheque' => ['cheque_number' => 'ORIGINAL-REPAIR', 'cheque_date' => today()->toDateString(), 'bank_name' => 'Test Bank', 'account_holder' => 'Customer']]));
    $service = app(ChequeService::class);
    $service->returnCheque($payment->fresh()->cheque, ['return_charge' => 0]);
    $replacement = $service->replace($payment->fresh()->cheque, ['cheque_number' => 'REPLACEMENT-REPAIR', 'cheque_date' => today()->toDateString(), 'bank_name' => 'Test Bank', 'account_holder' => 'Customer', 'amount' => 50]);
    expect($replacement->invoice_payment_id)->toBe($payment->invoice_payment_id)->and($job->payments()->count())->toBe(1)->and($job->fresh()->cheque_hold_amount)->toBe('50.00');
    config(['features.repair' => false]);
    $service->pass($replacement);
    $service->pass($replacement);
    expect($job->fresh()->balance_amount)->toBe('0.00')->and($job->fresh()->paid_amount)->toBe('50.00')->and($job->fresh()->customer->balance)->toBe('0.00');
    config(['features.repair' => true]);
    $this->get(route('repairs.show', $job))->assertOk();
});

test('mismatched replacement cheque rolls back without corrupting the original payment', function () {
    $job = repairJob();
    repairCharge($job);
    $payment = app(RepairPaymentService::class)->add($job, repairPayment($job, ['payment_method' => 'Cheque', 'cheque' => ['cheque_number' => 'REPAIR-ORIGINAL-AMOUNT', 'cheque_date' => today()->toDateString(), 'bank_name' => 'Test Bank', 'account_holder' => 'Customer']]));
    $service = app(ChequeService::class);
    $service->returnCheque($payment->fresh()->cheque, ['return_charge' => 0]);
    expect(fn () => $service->replace($payment->fresh()->cheque, ['cheque_number' => 'BAD-REPLACEMENT', 'cheque_date' => today()->toDateString(), 'bank_name' => 'Test Bank', 'account_holder' => 'Customer', 'amount' => 40]))->toThrow(ValidationException::class);
    expect($job->fresh()->balance_amount)->toBe('50.00')->and($payment->fresh()->status)->toBe('returned')->and(Cheque::where('cheque_number', 'BAD-REPLACEMENT')->exists())->toBeFalse();
});

test('legacy opening figures remain unchanged and are never journaled twice', function () {
    $job = repairJob();
    $job->update(['intake_key' => null, 'labor_charge' => 100, 'diagnosis_fee' => 20, 'discount_amount' => 10, 'discount_value' => 0, 'tax_amount' => 11, 'total_amount' => 121, 'paid_amount' => 30, 'balance_amount' => 91]);
    $job->customer->update(['balance' => 91]);
    DB::table('repair_devices')->where('repair_job_id', $job->id)->update(['accessories_received' => 'Charger, SIM Card']);
    $migration = require database_path('migrations/2026_10_04_000003_preserve_legacy_repair_financial_snapshots.php');
    $migration->up();
    $migration->up();
    DB::transaction(fn () => app(RepairPricingService::class)->refresh($job->refresh()));
    expect($job->fresh()->total_amount)->toBe('121.00')->and($job->fresh()->paid_amount)->toBe('30.00')->and($job->fresh()->balance_amount)->toBe('91.00')->and($job->fresh()->customer->balance)->toBe('91.00')->and(JournalEntry::count())->toBe(0)->and($job->payments()->count())->toBe(1)->and($job->device->fresh()->accessories_received)->toBe(['Charger', 'SIM Card']);
});

test('warranty accepts duration from normal HTML forms', function (string $type, string $duration, string $expires) {
    $job = repairJob();
    $this->post(route('repairs.warranty', $job), ['warranty_type' => $type, 'duration' => $duration, 'starts_at' => '2026-01-31', 'terms' => 'Workmanship'])->assertSessionHasNoErrors()->assertRedirect();
    expect($job->warranty->fresh()->expires_at->toDateString())->toBe($expires);
})->with([['Days', '30', '2026-03-02'], ['Months', '1', '2026-02-28']]);

test('repair revenue date filters follow collections on older jobs', function () {
    $job = repairJob();
    repairCharge($job);
    $job->update(['created_at' => today()->subDay()]);
    app(RepairPaymentService::class)->add($job, repairPayment($job));
    $response = $this->get(route('repairs.reports', ['report' => 'revenue', 'from' => today()->toDateString(), 'to' => today()->toDateString()]))->assertOk()->assertSee($job->job_no);
    expect((float) $response->viewData('rows')[0][8])->toBe(50.0);
    $response = $this->get(route('repairs.reports', ['report' => 'revenue', 'from' => today()->subDay()->toDateString(), 'to' => today()->subDay()->toDateString()]))->assertOk();
    expect($response->viewData('rows'))->toBe([]);
});

test('cheque collections appear on the clearance date rather than the intake date', function () {
    $job = repairJob();
    repairCharge($job);
    $payment = app(RepairPaymentService::class)->add($job, repairPayment($job, ['payment_date' => today()->subDay()->toDateString(), 'payment_method' => 'Cheque', 'cheque' => ['cheque_number' => 'CLEARANCE-DATE', 'cheque_date' => today()->toDateString(), 'bank_name' => 'Test Bank', 'account_holder' => 'Customer']]));
    app(ChequeService::class)->pass($payment->fresh()->cheque);
    $this->get(route('repairs.dashboard'))->assertOk()->assertViewHas('payments', 50);
    $response = $this->get(route('repairs.reports', ['report' => 'revenue', 'from' => today()->toDateString(), 'to' => today()->toDateString()]))->assertOk();
    expect((float) $response->viewData('rows')[0][8])->toBe(50.0)->and($payment->fresh()->cleared_at->isToday())->toBeTrue();
});
