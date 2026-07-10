<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RepairDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedPermissionsAndRole();

            $userId = DB::table('users')->value('id');
            $storeId = $this->defaultStoreId();
            $customers = $this->seedCustomers($userId);
            $technicians = $this->seedTechnicians();
            $products = $this->repairProducts($userId, $storeId);

            $jobs = [
                [
                    'job_no' => 'REP-DEMO-0001',
                    'customer' => 'repair.demo.1@local.test',
                    'technician' => null,
                    'status' => 'Checked In',
                    'device' => ['Phone', 'Apple', 'iPhone 13 Pro', '358240111111111', 'IP13P-DEMO-01', 'Graphite', '128GB', '6GB'],
                    'problem' => 'Customer says the device does not charge unless the cable is held at an angle.',
                    'request' => 'Check charging port and battery health before replacing parts.',
                    'estimate' => 6500,
                    'labor' => 3500,
                    'urgent' => true,
                    'warranty' => false,
                    'days' => 2,
                    'parts' => [['index' => 0, 'qty' => 1, 'price' => 3000, 'status' => 'Reserved']],
                    'payments' => [],
                ],
                [
                    'job_no' => 'REP-DEMO-0002',
                    'customer' => 'repair.demo.2@local.test',
                    'technician' => 'nimal.repair@local.test',
                    'status' => 'Under Repair',
                    'device' => ['Phone', 'Samsung', 'Galaxy A52', '359990222222222', 'SMA52-DEMO-02', 'Blue', '256GB', '8GB'],
                    'problem' => 'Screen is cracked and touch is responding only on the top half.',
                    'request' => 'Replace display and test fingerprint, camera, and charging.',
                    'estimate' => 18500,
                    'labor' => 2500,
                    'urgent' => false,
                    'warranty' => false,
                    'days' => 1,
                    'parts' => [['index' => 1, 'qty' => 1, 'price' => 16000, 'status' => 'Issued']],
                    'payments' => [['amount' => 5000, 'method' => 'Cash', 'ref' => 'ADV-DEMO-02']],
                ],
                [
                    'job_no' => 'REP-DEMO-0003',
                    'customer' => 'repair.demo.3@local.test',
                    'technician' => 'fathima.board@local.test',
                    'status' => 'Waiting Approval',
                    'device' => ['Tablet', 'Apple', 'iPad 9th Gen', null, 'IPAD9-DEMO-03', 'Silver', '64GB', '3GB'],
                    'problem' => 'Device powers on but the display has no backlight.',
                    'request' => 'Diagnose board fault and call before repair.',
                    'estimate' => 12000,
                    'labor' => 5000,
                    'urgent' => false,
                    'warranty' => false,
                    'days' => 3,
                    'parts' => [],
                    'payments' => [],
                ],
                [
                    'job_no' => 'REP-DEMO-0004',
                    'customer' => 'repair.demo.4@local.test',
                    'technician' => 'nimal.repair@local.test',
                    'status' => 'Ready for Pickup',
                    'device' => ['Phone', 'Xiaomi', 'Redmi Note 12', '861110444444444', 'RN12-DEMO-04', 'Mint', '128GB', '6GB'],
                    'problem' => 'Back camera is blurry after device dropped.',
                    'request' => 'Replace camera lens and clean dust inside camera area.',
                    'estimate' => 7200,
                    'labor' => 2200,
                    'urgent' => false,
                    'warranty' => false,
                    'days' => -1,
                    'parts' => [['index' => 2, 'qty' => 1, 'price' => 5000, 'status' => 'Issued']],
                    'payments' => [['amount' => 7200, 'method' => 'Card', 'ref' => 'CARD-DEMO-04']],
                ],
                [
                    'job_no' => 'REP-DEMO-0005',
                    'customer' => 'repair.demo.1@local.test',
                    'technician' => 'fathima.board@local.test',
                    'status' => 'Delivered',
                    'device' => ['Phone', 'Oppo', 'A78', '862220555555555', 'OPPA78-DEMO-05', 'Black', '128GB', '8GB'],
                    'problem' => 'Microphone not working during calls.',
                    'request' => 'Repair under previous service warranty if applicable.',
                    'estimate' => 4800,
                    'labor' => 1800,
                    'urgent' => false,
                    'warranty' => true,
                    'days' => -5,
                    'parts' => [['index' => 3, 'qty' => 1, 'price' => 3000, 'status' => 'Issued']],
                    'payments' => [['amount' => 4800, 'method' => 'Cash', 'ref' => 'CASH-DEMO-05']],
                ],
            ];

            foreach ($jobs as $job) {
                $this->seedJob($job, $customers, $technicians, $products, $storeId, $userId);
            }
        });
    }

    private function seedPermissionsAndRole(): void
    {
        $permissions = [
            'repair.view',
            'repair.create',
            'repair.edit',
            'repair.delete',
            'repair.assign',
            'repair.complete',
            'repair.deliver',
            'repair.invoice',
            'repair.parts',
            'repair.report',
            'repair.settings',
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission],
                ['guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]
            );
        }

        $repairRoleId = DB::table('roles')->updateOrInsert(
            ['name' => 'Repair Technician'],
            [
                'description' => 'Access to repair job cards, check-ins, assignments, parts, and status updates.',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $repairRoleId = DB::table('roles')->where('name', 'Repair Technician')->value('id');

        $repairPermissionIds = DB::table('permissions')->whereIn('name', [
            'dashboard.view',
            'repair.view',
            'repair.create',
            'repair.edit',
            'repair.assign',
            'repair.complete',
            'repair.deliver',
            'repair.parts',
            'customers.view',
            'customers.create',
            'products.view',
            'stock.view',
        ])->pluck('id');

        foreach ($repairPermissionIds as $permissionId) {
            DB::table('role_permissions')->updateOrInsert([
                'role_id' => $repairRoleId,
                'permission_id' => $permissionId,
            ]);
        }

        $adminRoleIds = DB::table('roles')->whereIn('name', ['Super Admin', 'Admin', 'Administrator'])->pluck('id');
        $allRepairPermissionIds = DB::table('permissions')->whereIn('name', $permissions)->pluck('id');
        foreach ($adminRoleIds as $roleId) {
            foreach ($allRepairPermissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }

        $userId = DB::table('users')->updateOrInsert(
            ['username' => 'repairtech'],
            [
                'name' => 'Repair Technician',
                'email' => 'repairtech@twinsofte.com',
                'phone' => '0772223333',
                'password' => Hash::make('password'),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $userId = DB::table('users')->where('username', 'repairtech')->value('id');
        DB::table('user_roles')->updateOrInsert([
            'user_id' => $userId,
            'role_id' => $repairRoleId,
        ]);
    }

    private function defaultStoreId(): int
    {
        $storeId = DB::table('stores')->where('is_default', true)->value('id') ?: DB::table('stores')->value('id');
        if ($storeId) {
            return (int) $storeId;
        }

        return (int) DB::table('stores')->insertGetId([
            'name' => 'Main Store',
            'code' => 'MS01',
            'status' => true,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedCustomers(?int $userId): array
    {
        $customers = [
            'repair.demo.1@local.test' => ['name' => 'Fasmir Nazeer', 'phone' => '0777001001', 'address' => 'Colombo 03'],
            'repair.demo.2@local.test' => ['name' => 'Ayesha Perera', 'phone' => '0777001002', 'address' => 'Dehiwala'],
            'repair.demo.3@local.test' => ['name' => 'Mohamed Rizwan', 'phone' => '0777001003', 'address' => 'Kandy'],
            'repair.demo.4@local.test' => ['name' => 'Kavindi Silva', 'phone' => '0777001004', 'address' => 'Nugegoda'],
        ];

        $ids = [];
        foreach ($customers as $email => $customer) {
            DB::table('customers')->updateOrInsert(
                ['email' => $email],
                [
                    'name' => $customer['name'],
                    'phone' => $customer['phone'],
                    'address' => $customer['address'],
                    'status' => true,
                    'notes' => 'Repair module demo customer.',
                    'created_by' => $userId,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $ids[$email] = (int) DB::table('customers')->where('email', $email)->value('id');
        }

        return $ids;
    }

    private function seedTechnicians(): array
    {
        $technicians = [
            'nimal.repair@local.test' => ['name' => 'Nimal Perera', 'phone' => '0777102001', 'specialization' => 'Displays, batteries, charging ports'],
            'fathima.board@local.test' => ['name' => 'Fathima Hameed', 'phone' => '0777102002', 'specialization' => 'Board repairs and water damage'],
        ];

        $ids = [];
        foreach ($technicians as $email => $technician) {
            DB::table('repair_technicians')->updateOrInsert(
                ['email' => $email],
                [
                    'name' => $technician['name'],
                    'phone' => $technician['phone'],
                    'specialization' => $technician['specialization'],
                    'status' => 'Active',
                    'commission_rate' => 10,
                    'notes' => 'Demo repair technician.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            $ids[$email] = (int) DB::table('repair_technicians')->where('email', $email)->value('id');
        }

        return $ids;
    }

    private function repairProducts(?int $userId, int $storeId): array
    {
        $products = DB::table('products')->whereNull('deleted_at')->where('is_active', true)->orderBy('id')->limit(4)->get();

        if ($products->count() < 4) {
            $demoProducts = [
                ['iPhone Charging Port Flex', 'REP-PART-001', 1500, 3000],
                ['Samsung A52 Display Assembly', 'REP-PART-002', 12000, 16000],
                ['Redmi Note Camera Lens', 'REP-PART-003', 2200, 5000],
                ['Oppo A78 Microphone Board', 'REP-PART-004', 1500, 3000],
            ];

            foreach ($demoProducts as [$name, $sku, $cost, $price]) {
                DB::table('products')->updateOrInsert(
                    ['sku' => $sku],
                    [
                        'name' => $name,
                        'barcode' => $sku,
                        'description' => 'Repair module demo part.',
                        'cost_price' => $cost,
                        'retail_price' => $price,
                        'wholesale_price' => $price,
                        'stock_alert_quantity' => 5,
                        'is_active' => true,
                        'created_by' => $userId,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            $products = DB::table('products')->whereNull('deleted_at')->where('is_active', true)->orderBy('id')->limit(4)->get();
        }

        foreach ($products as $product) {
            DB::table('product_store_stock')->updateOrInsert(
                ['product_id' => $product->id, 'store_id' => $storeId],
                ['quantity' => 25, 'minimum_stock' => 5, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        return $products->values()->all();
    }

    private function seedJob(array $demo, array $customers, array $technicians, array $products, int $storeId, ?int $userId): void
    {
        $technicianId = $demo['technician'] ? ($technicians[$demo['technician']] ?? null) : null;
        $jobId = DB::table('repair_jobs')->updateOrInsert(
            ['job_no' => $demo['job_no']],
            [
                'barcode' => $demo['job_no'],
                'qr_code' => route('repairs.public.track', ['job' => $demo['job_no']], false),
                'customer_id' => $customers[$demo['customer']],
                'store_id' => $storeId,
                'created_by' => $userId,
                'primary_technician_id' => $technicianId,
                'status' => $demo['status'],
                'problem_description' => $demo['problem'],
                'repair_notes' => 'Seeded testing data for the repair management module.',
                'customer_request' => $demo['request'],
                'estimated_cost' => $demo['estimate'],
                'labor_charge' => $demo['labor'],
                'urgent' => $demo['urgent'],
                'is_warranty' => $demo['warranty'],
                'expected_completion_date' => now()->addDays($demo['days'])->toDateString(),
                'completed_at' => in_array($demo['status'], ['Ready for Pickup', 'Completed', 'Delivered'], true) ? now()->subDay() : null,
                'delivered_at' => $demo['status'] === 'Delivered' ? now()->subDay() : null,
                'created_at' => now()->subDays(max(0, 5 - abs($demo['days']))),
                'updated_at' => now(),
            ]
        );
        $jobId = (int) DB::table('repair_jobs')->where('job_no', $demo['job_no'])->value('id');

        [$deviceType, $brand, $model, $imei, $serial, $color, $storage, $ram] = $demo['device'];
        DB::table('repair_devices')->updateOrInsert(
            ['repair_job_id' => $jobId],
            [
                'device_type' => $deviceType,
                'brand' => $brand,
                'model' => $model,
                'imei_1' => $imei,
                'serial_number' => $serial,
                'color' => $color,
                'storage' => $storage,
                'ram' => $ram,
                'battery_percent' => 64,
                'accessories_received' => 'Device only, SIM tray, clear case.',
                'physical_condition' => json_encode(['Scratches', 'Frame Damage']),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('repair_checklists')->updateOrInsert(
            ['repair_job_id' => $jobId],
            [
                'items' => json_encode(['Battery', 'SIM Tray', 'Touch Working', 'Speaker Working', 'Camera Lens']),
                'custom_items' => 'Demo intake checklist verified at front desk.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('repair_assignments')->where('repair_job_id', $jobId)->delete();
        if ($technicianId) {
            DB::table('repair_assignments')->insert([
                'repair_job_id' => $jobId,
                'technician_id' => $technicianId,
                'assigned_by' => $userId,
                'assigned_at' => now()->subDays(1),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('repair_parts')->where('repair_job_id', $jobId)->delete();
        foreach ($demo['parts'] as $part) {
            $product = $products[$part['index'] % count($products)];
            DB::table('repair_parts')->insert([
                'repair_job_id' => $jobId,
                'product_id' => $product->id,
                'store_id' => $storeId,
                'quantity' => $part['qty'],
                'cost_price' => $product->cost_price ?? 0,
                'selling_price' => $part['price'],
                'line_total' => $part['qty'] * $part['price'],
                'status' => $part['status'],
                'issued_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('repair_payments')->where('repair_job_id', $jobId)->delete();
        foreach ($demo['payments'] as $payment) {
            DB::table('repair_payments')->insert([
                'repair_job_id' => $jobId,
                'payment_date' => now()->toDateString(),
                'amount' => $payment['amount'],
                'payment_method' => $payment['method'],
                'reference_no' => $payment['ref'],
                'notes' => 'Demo repair payment.',
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->recalculateTotals($jobId);

        DB::table('repair_status_logs')->where('repair_job_id', $jobId)->delete();
        DB::table('repair_status_logs')->insert([
            'repair_job_id' => $jobId,
            'from_status' => null,
            'to_status' => 'Checked In',
            'note' => 'Demo job checked in.',
            'user_id' => $userId,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);
        if ($demo['status'] !== 'Checked In') {
            DB::table('repair_status_logs')->insert([
                'repair_job_id' => $jobId,
                'from_status' => 'Checked In',
                'to_status' => $demo['status'],
                'note' => 'Demo job moved through the repair workflow.',
                'user_id' => $userId,
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ]);
        }

        DB::table('repair_notes')->updateOrInsert(
            ['repair_job_id' => $jobId, 'type' => 'Internal'],
            [
                'user_id' => $userId,
                'note' => 'Demo internal note: customer approved testing and diagnosis.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('repair_notifications')->updateOrInsert(
            ['repair_job_id' => $jobId, 'event' => $demo['status']],
            [
                'channel' => 'WhatsApp',
                'recipient' => DB::table('customers')->where('id', $customers[$demo['customer']])->value('phone'),
                'message' => "Demo repair {$demo['job_no']} status is {$demo['status']}.",
                'status' => 'Pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        if ($demo['warranty']) {
            DB::table('repair_warranties')->updateOrInsert(
                ['repair_job_id' => $jobId],
                [
                    'warranty_type' => 'Days',
                    'duration' => 30,
                    'starts_at' => now()->subDay()->toDateString(),
                    'expires_at' => now()->addDays(29)->toDateString(),
                    'terms' => 'Demo warranty applies to the repaired microphone fault only.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        DB::table('repair_activity_logs')->updateOrInsert(
            ['repair_job_id' => $jobId, 'action' => 'Seed Demo'],
            [
                'user_id' => $userId,
                'description' => "Seeded {$demo['job_no']} for repair module testing.",
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function recalculateTotals(int $jobId): void
    {
        $repair = DB::table('repair_jobs')->where('id', $jobId)->first();
        $partsTotal = DB::table('repair_parts')->where('repair_job_id', $jobId)->where('status', 'Issued')->sum('line_total');
        $labor = (float) ($repair->labor_charge ?: $repair->estimated_cost ?: 0);
        $paid = DB::table('repair_payments')->where('repair_job_id', $jobId)->sum('amount');
        $total = $labor + (float) $partsTotal;

        DB::table('repair_jobs')->where('id', $jobId)->update([
            'parts_total' => $partsTotal,
            'total_amount' => $total,
            'paid_amount' => $paid,
            'balance_amount' => max(0, $total - $paid),
            'updated_at' => now(),
        ]);
    }
}
