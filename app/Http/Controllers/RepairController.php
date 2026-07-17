<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Support\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepairController extends Controller
{
    private array $statuses = [
        'Checked In',
        'Diagnosis',
        'Waiting Approval',
        'Waiting Parts',
        'Assigned',
        'Under Repair',
        'Quality Check',
        'Ready for Pickup',
        'Completed',
        'Delivered',
        'Cancelled',
        'Rejected',
    ];

    private array $deviceTypes = ['Phone', 'Tablet', 'Laptop', 'Smart Watch', 'Camera', 'Gaming Console', 'Printer', 'Other'];
    private array $brands = ['Samsung', 'Apple', 'Xiaomi', 'Oppo', 'Vivo', 'Realme', 'Huawei', 'Google', 'Nokia', 'OnePlus', 'Motorola', 'Sony', 'Custom'];

    public function dashboard()
    {
        $today = now()->toDateString();
        $stats = [
            'today_checkins' => DB::table('repair_jobs')->whereDate('created_at', $today)->count(),
            'pending_repairs' => DB::table('repair_jobs')->whereNotIn('status', ['Delivered', 'Cancelled', 'Rejected'])->count(),
            'waiting_customer' => DB::table('repair_jobs')->where('status', 'Waiting Approval')->count(),
            'waiting_parts' => DB::table('repair_jobs')->where('status', 'Waiting Parts')->count(),
            'under_repair' => DB::table('repair_jobs')->where('status', 'Under Repair')->count(),
            'completed_today' => DB::table('repair_jobs')->whereDate('completed_at', $today)->count(),
            'delivered_today' => DB::table('repair_jobs')->whereDate('delivered_at', $today)->count(),
            'warranty_repairs' => DB::table('repair_jobs')->where('is_warranty', true)->count(),
            'repair_income' => DB::table('repair_payments')->whereDate('payment_date', $today)->sum('amount'),
            'parts_used_today' => DB::table('repair_parts')->whereDate('created_at', $today)->sum('line_total'),
        ];

        $statusChart = DB::table('repair_jobs')->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status');
        $technicianPerformance = DB::table('repair_technicians')
            ->leftJoin('repair_jobs', 'repair_jobs.primary_technician_id', '=', 'repair_technicians.id')
            ->select('repair_technicians.name', DB::raw('COUNT(repair_jobs.id) as total'))
            ->groupBy('repair_technicians.id', 'repair_technicians.name')
            ->orderByDesc('total')
            ->limit(6)
            ->get();
        $recentJobs = $this->jobsQuery()->latest('repair_jobs.id')->limit(8)->get();

        return view('repairs.dashboard', compact('stats', 'statusChart', 'technicianPerformance', 'recentJobs'));
    }

    public function jobs(Request $request)
    {
        $jobs = $this->jobsQuery()
            ->when($request->filled('status'), fn ($q) => $q->where('repair_jobs.status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = '%' . $request->search . '%';
                $q->where(function ($sub) use ($search) {
                    $sub->where('repair_jobs.job_no', 'like', $search)
                        ->orWhere('customers.name', 'like', $search)
                        ->orWhere('customers.phone', 'like', $search)
                        ->orWhere('repair_devices.imei_1', 'like', $search)
                        ->orWhere('repair_devices.serial_number', 'like', $search);
                });
            })
            ->latest('repair_jobs.id')
            ->paginate(15)
            ->withQueryString();

        return view('repairs.jobs.index', ['jobs' => $jobs, 'statuses' => $this->statuses]);
    }

    public function create()
    {
        return view('repairs.jobs.create', $this->formData());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['required_without:customer_id', 'nullable', 'string', 'max:255'],
            'customer_phone' => ['required_without:customer_id', 'nullable', 'string', 'max:50'],
            'customer_whatsapp' => ['nullable', 'string', 'max:50'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_address' => ['nullable', 'string', 'max:1000'],
            'customer_nic' => ['nullable', 'string', 'max:80'],
            'store_id' => ['nullable', 'exists:stores,id'],
            'device_type' => ['required', 'string', 'max:80'],
            'brand' => ['required', 'string', 'max:80'],
            'model' => ['required', 'string', 'max:255'],
            'imei_1' => ['nullable', 'string', 'max:80'],
            'imei_2' => ['nullable', 'string', 'max:80'],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'color' => ['nullable', 'string', 'max:80'],
            'storage' => ['nullable', 'string', 'max:80'],
            'ram' => ['nullable', 'string', 'max:80'],
            'carrier' => ['nullable', 'string', 'max:120'],
            'pattern_lock' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'pin' => ['nullable', 'string', 'max:80'],
            'google_lock' => ['nullable', 'boolean'],
            'apple_id_lock' => ['nullable', 'boolean'],
            'battery_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'accessories_received' => ['nullable', 'string', 'max:2000'],
            'condition_checklist' => ['nullable', 'array'],
            'physical_condition' => ['nullable', 'array'],
            'custom_checklist' => ['nullable', 'string', 'max:1000'],
            'problem_description' => ['required', 'string', 'max:5000'],
            'repair_notes' => ['nullable', 'string', 'max:5000'],
            'customer_request' => ['nullable', 'string', 'max:2000'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'urgent' => ['nullable', 'boolean'],
            'is_warranty' => ['nullable', 'boolean'],
            'expected_completion_date' => ['nullable', 'date'],
            'technician_id' => ['nullable', 'exists:repair_technicians,id'],
            'photos.*' => ['nullable', 'image', 'max:4096'],
        ]);

        DB::beginTransaction();

        try {
            $customerId = $validated['customer_id'] ?? null;
            if (!$customerId) {
                $customer = Customer::create([
                    'name' => $validated['customer_name'],
                    'phone' => $validated['customer_phone'],
                    'email' => $validated['customer_email'] ?? null,
                    'address' => $validated['customer_address'] ?? null,
                    'notes' => trim('WhatsApp: ' . ($validated['customer_whatsapp'] ?? '-') . ' NIC: ' . ($validated['customer_nic'] ?? '-')),
                    'status' => true,
                    'created_by' => auth()->id(),
                ]);
                $customerId = $customer->id;
            }

            $jobNo = $this->generateJobNo();
            $storeId = $validated['store_id'] ?? SystemSettings::number('default_store_id', 1, 'business_settings');
            $technicianId = $validated['technician_id'] ?? null;
            $status = $technicianId ? 'Assigned' : 'Checked In';

            $jobId = DB::table('repair_jobs')->insertGetId([
                'job_no' => $jobNo,
                'barcode' => $jobNo,
                'qr_code' => route('repairs.public.track', ['job' => $jobNo], false),
                'customer_id' => $customerId,
                'store_id' => $storeId,
                'created_by' => auth()->id(),
                'primary_technician_id' => $technicianId,
                'status' => $status,
                'problem_description' => $validated['problem_description'],
                'repair_notes' => $validated['repair_notes'] ?? null,
                'customer_request' => $validated['customer_request'] ?? null,
                'estimated_cost' => $validated['estimated_cost'] ?? 0,
                'urgent' => $request->boolean('urgent'),
                'is_warranty' => $request->boolean('is_warranty'),
                'expected_completion_date' => $validated['expected_completion_date'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('repair_devices')->insert([
                'repair_job_id' => $jobId,
                'device_type' => $validated['device_type'],
                'brand' => $validated['brand'],
                'model' => $validated['model'],
                'imei_1' => $validated['imei_1'] ?? null,
                'imei_2' => $validated['imei_2'] ?? null,
                'serial_number' => $validated['serial_number'] ?? null,
                'color' => $validated['color'] ?? null,
                'storage' => $validated['storage'] ?? null,
                'ram' => $validated['ram'] ?? null,
                'carrier' => $validated['carrier'] ?? null,
                'pattern_lock' => $validated['pattern_lock'] ?? null,
                'password' => $validated['password'] ?? null,
                'pin' => $validated['pin'] ?? null,
                'google_lock' => $request->boolean('google_lock'),
                'apple_id_lock' => $request->boolean('apple_id_lock'),
                'battery_percent' => $validated['battery_percent'] ?? null,
                'accessories_received' => $validated['accessories_received'] ?? null,
                'physical_condition' => json_encode($validated['physical_condition'] ?? []),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('repair_checklists')->insert([
                'repair_job_id' => $jobId,
                'items' => json_encode($validated['condition_checklist'] ?? []),
                'custom_items' => $validated['custom_checklist'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($technicianId) {
                DB::table('repair_assignments')->insert([
                    'repair_job_id' => $jobId,
                    'technician_id' => $technicianId,
                    'assigned_by' => auth()->id(),
                    'assigned_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($request->file('photos', []) as $type => $photo) {
                if ($photo) {
                    DB::table('repair_images')->insert([
                        'repair_job_id' => $jobId,
                        'type' => $type,
                        'path' => $photo->store('repair-photos', 'public'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $this->logStatus($jobId, null, $status, 'Repair job checked in.');
            $this->repairLog($jobId, 'Create', "Created repair job {$jobNo}");
            ActivityLog::log('Create', 'Repair Management', "Created repair job {$jobNo}");

            DB::commit();

            return redirect()->route('repairs.jobs.show', $jobId)->with('success', 'Repair job checked in successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()->withInput()->withErrors(['repair' => 'Unable to create repair job. ' . $e->getMessage()]);
        }
    }

    public function saveWorkspace(Request $request, int $job)
    {
        $parts = collect($request->input('parts', []))->map(function (array $part): array {
            $status = $part['status'] ?? 'Issued';
            $part['status'] = match ($status) {
                'Used' => 'Issued',
                'Cancelled' => 'Returned',
                default => $status,
            };

            return $part;
        })->values()->all();

        $request->merge(['parts' => $parts]);

        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', $this->statuses)],
            'status_note' => ['nullable', 'string', 'max:1000'],
            'technician_id' => ['nullable', 'exists:repair_technicians,id'],
            'notify_customer' => ['nullable', 'boolean'],
            'parts' => ['nullable', 'array'],
            'parts.*.id' => ['nullable', 'integer'],
            'parts.*.product_id' => ['required_without:parts.*.id', 'exists:products,id'],
            'parts.*.quantity' => ['required_with:parts.*.product_id', 'numeric', 'min:0.01'],
            'parts.*.selling_price' => ['required_with:parts.*.product_id', 'numeric', 'min:0'],
            'parts.*.store_id' => ['nullable', 'exists:stores,id'],
            'parts.*.status' => ['required_with:parts.*.product_id', 'in:Reserved,Issued,Returned'],
            'charges' => ['nullable', 'array'],
            'charges.labor' => ['nullable', 'numeric', 'min:0'],
            'charges.diagnosis' => ['nullable', 'numeric', 'min:0'],
            'charges.service' => ['nullable', 'numeric', 'min:0'],
            'charges.software' => ['nullable', 'numeric', 'min:0'],
            'charges.other' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'array'],
            'discount.type' => ['nullable', 'in:fixed,percentage'],
            'discount.value' => ['nullable', 'numeric', 'min:0'],
            'service_items' => ['nullable', 'array'],
            'service_items.*.id' => ['nullable', 'integer'],
            'service_items.*.name' => ['nullable', 'string', 'max:120'],
            'service_items.*.amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $currentJob = DB::table('repair_jobs')->where('id', $job)->first();
        abort_unless($currentJob, 404);

        DB::beginTransaction();
        try {
            $technicianId = $validated['technician_id'] ?? null;

            if ((string) ($currentJob->primary_technician_id ?? '') !== (string) ($technicianId ?? '')) {
                DB::table('repair_jobs')->where('id', $job)->update([
                    'primary_technician_id' => $technicianId,
                    'updated_at' => now(),
                ]);

                if ($technicianId) {
                    DB::table('repair_assignments')->insert([
                        'repair_job_id' => $job,
                        'technician_id' => $technicianId,
                        'assigned_by' => auth()->id(),
                        'assigned_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $this->repairLog($job, 'Assigned', 'Technician assigned via workspace.');
                } else {
                    $this->repairLog($job, 'Assigned', 'Technician removed via workspace.');
                }
            }

            if (isset($validated['parts']) && is_array($validated['parts'])) {
                foreach ($validated['parts'] as $partData) {
                    if (isset($partData['id']) && $partData['id']) {
                        $existingPart = DB::table('repair_parts')
                            ->where('id', $partData['id'])
                            ->where('repair_job_id', $job)
                            ->first();

                        if (!$existingPart) {
                            continue;
                        }

                        $existingStoreId = $existingPart->store_id ?? SystemSettings::number('default_store_id', 1, 'business_settings');
                        $storeId = $partData['store_id'] ?? $existingStoreId;
                        $quantity = (float) $partData['quantity'];
                        $sellingPrice = (float) $partData['selling_price'];
                        $lineTotal = $quantity * $sellingPrice;

                        $stockChanges = [];
                        if ($existingPart->status === 'Issued') {
                            $stockChanges[(int) $existingStoreId] = ($stockChanges[(int) $existingStoreId] ?? 0) + (float) $existingPart->quantity;
                        }
                        if ($partData['status'] === 'Issued') {
                            $stockChanges[(int) $storeId] = ($stockChanges[(int) $storeId] ?? 0) - $quantity;
                        }

                        foreach ($stockChanges as $stockStoreId => $quantityChange) {
                            if (abs($quantityChange) > 0.0001) {
                                $this->adjustRepairPartStock($existingPart->product_id, $stockStoreId, $quantityChange, $currentJob, $quantityChange > 0 ? 'repair_part_return' : 'repair_part');
                            }
                        }

                        DB::table('repair_parts')->where('id', $existingPart->id)->update([
                            'store_id' => $storeId,
                            'quantity' => $quantity,
                            'selling_price' => $sellingPrice,
                            'line_total' => $lineTotal,
                            'status' => $partData['status'],
                            'updated_at' => now(),
                        ]);
                    } elseif (isset($partData['product_id'])) {
                        $product = DB::table('products')->where('id', $partData['product_id'])->first();
                        abort_unless($product, 404);

                        $storeId = $partData['store_id'] ?? SystemSettings::number('default_store_id', 1, 'business_settings');
                        $quantity = (float) $partData['quantity'];
                        $sellingPrice = (float) $partData['selling_price'];
                        $lineTotal = $quantity * $sellingPrice;

                        DB::table('repair_parts')->insert([
                            'repair_job_id' => $job,
                            'product_id' => $partData['product_id'],
                            'store_id' => $storeId,
                            'quantity' => $quantity,
                            'cost_price' => $product->cost_price ?? 0,
                            'selling_price' => $sellingPrice,
                            'line_total' => $lineTotal,
                            'status' => $partData['status'],
                            'issued_by' => auth()->id(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        if ($partData['status'] === 'Issued') {
                            $this->adjustRepairPartStock($product->id, $storeId, -$quantity, $currentJob, 'repair_part');
                        }
                    }
                }
            }

            $chargePayload = $validated['charges'] ?? [];
            $chargeValues = [
                'labor_charge' => (float) ($chargePayload['labor'] ?? $currentJob->labor_charge ?? $currentJob->estimated_cost ?? 0),
                'diagnosis_fee' => (float) ($chargePayload['diagnosis'] ?? $currentJob->diagnosis_fee ?? 0),
                'service_charge' => (float) ($chargePayload['service'] ?? $currentJob->service_charge ?? 0),
                'software_charge' => (float) ($chargePayload['software'] ?? $currentJob->software_charge ?? 0),
                'other_charge' => (float) ($chargePayload['other'] ?? $currentJob->other_charge ?? 0),
            ];
            $partsTotal = (float) DB::table('repair_parts')
                ->where('repair_job_id', $job)
                ->where('status', 'Issued')
                ->sum('line_total');
            $serviceItems = collect($validated['service_items'] ?? [])
                ->filter(fn ($item) => trim((string) ($item['name'] ?? '')) !== '')
                ->values();
            DB::table('repair_charge_items')->where('repair_job_id', $job)->delete();
            foreach ($serviceItems as $index => $item) {
                DB::table('repair_charge_items')->insert([
                    'repair_job_id' => $job,
                    'name' => trim((string) $item['name']),
                    'amount' => (float) $item['amount'],
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $serviceItemsTotal = (float) $serviceItems->sum(fn ($item) => (float) $item['amount']);
            $discountType = $validated['discount']['type'] ?? $currentJob->discount_type ?? 'fixed';
            $discountValue = (float) ($validated['discount']['value'] ?? $currentJob->discount_value ?? $currentJob->discount_amount ?? 0);
            $discountBase = array_sum($chargeValues) + $partsTotal + $serviceItemsTotal;
            $discountAmount = $discountType === 'percentage'
                ? round($discountBase * min($discountValue, 100) / 100, 2)
                : min($discountValue, $discountBase);

            DB::table('repair_jobs')->where('id', $job)->update(array_merge($chargeValues, [
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $discountAmount,
                'updated_at' => now(),
            ]));

            if ($validated['status'] && $currentJob->status !== $validated['status']) {
                $updates = ['status' => $validated['status'], 'updated_at' => now()];
                if ($validated['status'] === 'Completed') {
                    $updates['completed_at'] = now();
                }
                if ($validated['status'] === 'Delivered') {
                    $updates['delivered_at'] = now();
                }
                DB::table('repair_jobs')->where('id', $job)->update($updates);
                $this->logStatus($job, $currentJob->status, $validated['status'], $validated['status_note'] ?? null);
                if (!empty($validated['notify_customer'])) {
                    $this->queueNotification($job, $validated['status']);
                }
            } elseif (!empty($validated['status_note'])) {
                 $this->logStatus($job, $currentJob->status, $currentJob->status, $validated['status_note']);
            }

            $this->recalculateTotals($job);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Workspace saved successfully.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show(int $job)
    {
        $repair = $this->jobDetails($job);

        return view('repairs.jobs.show', array_merge($this->formData($repair->store_id), ['job' => $repair, 'statuses' => $this->statuses]));
    }

    public function updateStatus(Request $request, int $job)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', $this->statuses)],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $current = DB::table('repair_jobs')->where('id', $job)->first();
        abort_unless($current, 404);

        $updates = ['status' => $validated['status'], 'updated_at' => now()];
        if ($validated['status'] === 'Completed') {
            $updates['completed_at'] = now();
        }
        if ($validated['status'] === 'Delivered') {
            $updates['delivered_at'] = now();
        }

        DB::table('repair_jobs')->where('id', $job)->update($updates);
        $this->logStatus($job, $current->status, $validated['status'], $validated['note'] ?? null);
        $this->queueNotification($job, $validated['status']);
        ActivityLog::log('Update', 'Repair Management', "Updated {$current->job_no} status to {$validated['status']}");

        return back()->with('success', 'Repair status updated.');
    }

    public function assign(Request $request, int $job)
    {
        $validated = $request->validate(['technician_id' => ['required', 'exists:repair_technicians,id']]);
        $current = DB::table('repair_jobs')->where('id', $job)->first();
        abort_unless($current, 404);

        DB::table('repair_jobs')->where('id', $job)->update([
            'primary_technician_id' => $validated['technician_id'],
            'status' => 'Assigned',
            'updated_at' => now(),
        ]);
        DB::table('repair_assignments')->insert([
            'repair_job_id' => $job,
            'technician_id' => $validated['technician_id'],
            'assigned_by' => auth()->id(),
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->logStatus($job, $current->status, 'Assigned', 'Technician assigned.');

        return back()->with('success', 'Technician assigned.');
    }

    public function issuePart(Request $request, int $job)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'store_id' => ['nullable', 'exists:stores,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:Reserved,Issued,Returned'],
        ]);

        $storeId = $validated['store_id'] ?? SystemSettings::number('default_store_id', 1, 'business_settings');
        $product = DB::table('products')->where('id', $validated['product_id'])->first();
        abort_unless($product, 404);

        DB::beginTransaction();
        try {
            $lineTotal = (float) $validated['quantity'] * (float) $validated['selling_price'];
            DB::table('repair_parts')->insert([
                'repair_job_id' => $job,
                'product_id' => $validated['product_id'],
                'store_id' => $storeId,
                'quantity' => $validated['quantity'],
                'cost_price' => $product->cost_price ?? 0,
                'selling_price' => $validated['selling_price'],
                'line_total' => $lineTotal,
                'status' => $validated['status'],
                'issued_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($validated['status'] === 'Issued') {
                $stock = DB::table('product_store_stock')->where('product_id', $product->id)->where('store_id', $storeId)->first();
                $newQuantity = ($stock->quantity ?? 0) - $validated['quantity'];
                DB::table('product_store_stock')->updateOrInsert(
                    ['product_id' => $product->id, 'store_id' => $storeId],
                    ['quantity' => $newQuantity, 'updated_at' => now(), 'created_at' => now()]
                );
                DB::table('stock_histories')->insert([
                    'product_id' => $product->id,
                    'store_id' => $storeId,
                    'quantity_before' => $stock->quantity ?? 0,
                    'quantity_change' => -$validated['quantity'],
                    'new_quantity' => $newQuantity,
                    'type' => 'repair_part',
                    'reference_no' => DB::table('repair_jobs')->where('id', $job)->value('job_no'),
                    'note' => 'Repair parts issued',
                    'reference_type' => 'repair_job',
                    'reference_id' => $job,
                    'description' => 'Repair parts issued for service job.',
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                ]);
            }

            $this->recalculateTotals($job);
            $this->repairLog($job, 'Parts', "Added {$product->name} to repair job.");
            DB::commit();

            return back()->with('success', 'Repair part added and stock updated.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()->withErrors(['part' => 'Unable to issue repair part. ' . $e->getMessage()]);
        }
    }

    public function payment(Request $request, int $job)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:80'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::table('repair_payments')->insert([
            'repair_job_id' => $job,
            'payment_date' => now()->toDateString(),
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'reference_no' => $validated['reference_no'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->recalculateTotals($job);
        $this->repairLog($job, 'Payment', 'Repair payment received: ' . SystemSettings::money((float) $validated['amount']));

        return back()->with('success', 'Payment recorded.');
    }

    public function technicians()
    {
        $technicians = DB::table('repair_technicians')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get()
            ->map(function ($technician) {
                $technician->current_jobs = DB::table('repair_jobs')->where('primary_technician_id', $technician->id)->whereNotIn('status', ['Delivered', 'Cancelled', 'Rejected'])->count();
                $technician->completed_jobs = DB::table('repair_jobs')->where('primary_technician_id', $technician->id)->whereIn('status', ['Completed', 'Delivered'])->count();
                return $technician;
            });

        return view('repairs.technicians', compact('technicians'));
    }

    public function storeTechnician(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:Active,Inactive,On Leave'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::table('repair_technicians')->insert(array_merge($validated, ['created_at' => now(), 'updated_at' => now()]));

        return back()->with('success', 'Technician saved.');
    }

    public function updateTechnician(Request $request, int $technician)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:Active,Inactive,On Leave'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::table('repair_technicians')->where('id', $technician)->update(array_merge($validated, ['updated_at' => now()]));

        return back()->with('success', 'Technician updated.');
    }

    public function queue()
    {
        $columns = ['Checked In', 'Assigned', 'Waiting Parts', 'Under Repair', 'Quality Check', 'Ready for Pickup', 'Completed'];
        $jobs = $this->jobsQuery()->whereIn('repair_jobs.status', $columns)->get()->groupBy('status');

        return view('repairs.queue', compact('columns', 'jobs'));
    }

    public function statusBoard()
    {
        $statuses = $this->statuses;
        $counts = DB::table('repair_jobs')->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status');
        $latest = $this->jobsQuery()->latest('repair_jobs.updated_at')->limit(20)->get();

        return view('repairs.status-board', compact('statuses', 'counts', 'latest'));
    }

    public function parts()
    {
        $parts = DB::table('repair_parts')
            ->join('repair_jobs', 'repair_jobs.id', '=', 'repair_parts.repair_job_id')
            ->join('products', 'products.id', '=', 'repair_parts.product_id')
            ->leftJoin('stores', 'stores.id', '=', 'repair_parts.store_id')
            ->select('repair_parts.*', 'repair_jobs.job_no', 'products.name as product_name', 'stores.name as store_name')
            ->latest('repair_parts.id')
            ->paginate(20);

        return view('repairs.parts', compact('parts'));
    }

    public function quotations()
    {
        $jobs = $this->jobsQuery()->whereIn('repair_jobs.status', ['Diagnosis', 'Waiting Approval'])->latest('repair_jobs.id')->paginate(20);
        return view('repairs.simple-list', ['title' => 'Repair Quotations', 'description' => 'Review jobs waiting for diagnosis quotations and customer approval.', 'jobs' => $jobs]);
    }

    public function invoices()
    {
        $jobs = $this->jobsQuery()->where('repair_jobs.total_amount', '>', 0)->latest('repair_jobs.id')->paginate(20);
        return view('repairs.simple-list', ['title' => 'Repair Invoices', 'description' => 'Repair invoices, balances, advances, and customer payment status.', 'jobs' => $jobs]);
    }

    public function warranty()
    {
        $jobs = $this->jobsQuery()->where('repair_jobs.is_warranty', true)->latest('repair_jobs.id')->paginate(20);
        return view('repairs.simple-list', ['title' => 'Warranty Repairs', 'description' => 'Track repair warranty jobs, warranty claims, and expiry follow-up.', 'jobs' => $jobs]);
    }

    public function reports()
    {
        $summary = [
            'income' => DB::table('repair_payments')->sum('amount'),
            'parts' => DB::table('repair_parts')->sum('line_total'),
            'pending' => DB::table('repair_jobs')->whereNotIn('status', ['Delivered', 'Cancelled', 'Rejected'])->count(),
            'delayed' => DB::table('repair_jobs')->whereDate('expected_completion_date', '<', now()->toDateString())->whereNotIn('status', ['Delivered', 'Cancelled', 'Rejected'])->count(),
        ];
        $technicians = DB::table('repair_technicians')
            ->leftJoin('repair_jobs', 'repair_jobs.primary_technician_id', '=', 'repair_technicians.id')
            ->select('repair_technicians.name', DB::raw('COUNT(repair_jobs.id) as jobs'), DB::raw('SUM(repair_jobs.total_amount) as revenue'))
            ->groupBy('repair_technicians.id', 'repair_technicians.name')
            ->get();

        return view('repairs.reports', compact('summary', 'technicians'));
    }

    public function settings(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'default_warranty_days' => ['nullable', 'integer', 'min:0'],
                'default_labor_charge' => ['nullable', 'numeric', 'min:0'],
                'repair_terms' => ['nullable', 'string', 'max:5000'],
                'notify_checkin' => ['nullable', 'boolean'],
                'notify_ready' => ['nullable', 'boolean'],
                'notify_delivered' => ['nullable', 'boolean'],
            ]);
            foreach (['notify_checkin', 'notify_ready', 'notify_delivered'] as $toggle) {
                $data[$toggle] = $request->boolean($toggle);
            }
            foreach ($data as $key => $value) {
                DB::table('business_settings')->updateOrInsert(['key' => 'repair_' . $key], ['value' => is_bool($value) ? ($value ? '1' : '0') : $value, 'created_at' => now(), 'updated_at' => now()]);
            }
            return back()->with('success', 'Repair settings saved.');
        }

        $settings = DB::table('business_settings')->where('key', 'like', 'repair_%')->pluck('value', 'key');
        return view('repairs.settings', compact('settings'));
    }

    public function print(int $job)
    {
        return view('repairs.print', ['job' => $this->jobDetails($job)]);
    }

    public function printInvoice(int $job)
    {
        return view('repairs.invoice', ['job' => $this->jobDetails($job)]);
    }

    public function publicTrack(Request $request)
    {
        $query = $request->input('job') ?: $request->input('phone');
        $job = null;
        if ($query) {
            $job = $this->jobsQuery()
                ->where(function ($q) use ($query) {
                    $q->where('repair_jobs.job_no', $query)->orWhere('customers.phone', $query);
                })
                ->latest('repair_jobs.id')
                ->first();
        }

        return view('repairs.track', compact('job', 'query'));
    }

    private function jobsQuery()
    {
        return DB::table('repair_jobs')
            ->join('customers', 'customers.id', '=', 'repair_jobs.customer_id')
            ->leftJoin('repair_devices', 'repair_devices.repair_job_id', '=', 'repair_jobs.id')
            ->leftJoin('repair_technicians', 'repair_technicians.id', '=', 'repair_jobs.primary_technician_id')
            ->leftJoin('stores', 'stores.id', '=', 'repair_jobs.store_id')
            ->whereNull('repair_jobs.deleted_at')
            ->select('repair_jobs.*', 'customers.name as customer_name', 'customers.phone as customer_phone', 'repair_devices.device_type', 'repair_devices.brand', 'repair_devices.model', 'repair_devices.imei_1', 'repair_devices.serial_number', 'repair_technicians.name as technician_name', 'stores.name as store_name');
    }

    private function jobDetails(int $id)
    {
        $job = $this->jobsQuery()->where('repair_jobs.id', $id)->first();
        abort_unless($job, 404);
        $job->device = DB::table('repair_devices')->where('repair_job_id', $id)->first();
        $job->checklist = DB::table('repair_checklists')->where('repair_job_id', $id)->first();
        $job->images = DB::table('repair_images')->where('repair_job_id', $id)->get();
        $job->parts = DB::table('repair_parts')->join('products', 'products.id', '=', 'repair_parts.product_id')->select('repair_parts.*', 'products.name as product_name')->where('repair_job_id', $id)->get();
        $job->charge_items = DB::table('repair_charge_items')->where('repair_job_id', $id)->orderBy('sort_order')->orderBy('id')->get();
        $job->payments = DB::table('repair_payments')->where('repair_job_id', $id)->get();
        $job->logs = DB::table('repair_status_logs')->where('repair_job_id', $id)->latest()->get();
        $job->notes = DB::table('repair_notes')->where('repair_job_id', $id)->latest()->get();
        return $job;
    }

    private function formData(?int $storeId = null): array
    {
        $defaultStoreId = $storeId ?? SystemSettings::number('default_store_id', 1, 'business_settings');

        return [
            'customers' => DB::table('customers')->whereNull('deleted_at')->orderBy('name')->get(),
            'technicians' => DB::table('repair_technicians')->whereNull('deleted_at')->where('status', 'Active')->orderBy('name')->get(),
            'stores' => DB::table('stores')->where('status', true)->orderBy('name')->get(),
            'products' => DB::table('products')
                ->leftJoin('product_store_stock', function ($join) use ($defaultStoreId) {
                    $join->on('product_store_stock.product_id', '=', 'products.id')
                         ->where('product_store_stock.store_id', '=', $defaultStoreId);
                })
                ->whereNull('products.deleted_at')
                ->where('products.is_active', true)
                ->select('products.*', DB::raw('COALESCE(product_store_stock.quantity, 0) as stock'))
                ->orderBy('products.name')
                ->get(),
            'productStocks' => DB::table('product_store_stock')
                ->select('product_id', 'store_id', 'quantity')
                ->get(),
            'deviceTypes' => $this->deviceTypes,
            'brands' => $this->brands,
            'checklistItems' => $this->checklistItems(),
            'physicalConditions' => $this->physicalConditions(),
        ];
    }

    private function adjustRepairPartStock(int $productId, int $storeId, float $quantityChange, object $job, string $type): void
    {
        $stock = DB::table('product_store_stock')
            ->where('product_id', $productId)
            ->where('store_id', $storeId)
            ->first();

        $quantityBefore = (float) ($stock->quantity ?? 0);
        $newQuantity = $quantityBefore + $quantityChange;

        if ($newQuantity < -0.0001) {
            throw new \RuntimeException('Not enough available stock for the selected repair part.');
        }

        DB::table('product_store_stock')->updateOrInsert(
            ['product_id' => $productId, 'store_id' => $storeId],
            [
                'quantity' => $newQuantity,
                'updated_at' => now(),
                'created_at' => $stock->created_at ?? now(),
            ]
        );

        DB::table('stock_histories')->insert([
            'product_id' => $productId,
            'store_id' => $storeId,
            'quantity_before' => $quantityBefore,
            'quantity_change' => $quantityChange,
            'new_quantity' => $newQuantity,
            'type' => $type,
            'reference_no' => $job->job_no,
            'note' => $quantityChange < 0 ? 'Repair parts issued' : 'Repair part returned',
            'reference_type' => 'repair_job',
            'reference_id' => $job->id,
            'description' => $quantityChange < 0 ? 'Repair parts issued for service job.' : 'Repair part returned to stock.',
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);
    }

    private function recalculateTotals(int $job): void
    {
        $partsTotal = DB::table('repair_parts')->where('repair_job_id', $job)->where('status', 'Issued')->sum('line_total');
        $repair = DB::table('repair_jobs')->where('id', $job)->first();
        $labor = (float) ($repair->labor_charge ?: $repair->estimated_cost ?: 0);
        $extraCharges = (float) ($repair->diagnosis_fee ?? 0)
            + (float) ($repair->service_charge ?? 0)
            + (float) ($repair->software_charge ?? 0)
            + (float) ($repair->other_charge ?? 0);
        $serviceItemsTotal = (float) DB::table('repair_charge_items')->where('repair_job_id', $job)->sum('amount');
        $discount = (float) $repair->discount_amount;
        $taxRate = (float) SystemSettings::number('default_sale_tax', 0, 'tax_settings');
        $subtotal = max(0, $labor + $extraCharges + $serviceItemsTotal + $partsTotal - $discount);
        $tax = SystemSettings::bool('enable_tax_system', true, 'tax_settings') ? round($subtotal * $taxRate / 100, 2) : 0;
        $total = $subtotal + $tax;
        $paid = DB::table('repair_payments')->where('repair_job_id', $job)->sum('amount');
        DB::table('repair_jobs')->where('id', $job)->update([
            'parts_total' => $partsTotal,
            'tax_amount' => $tax,
            'paid_amount' => $paid,
            'total_amount' => $total,
            'balance_amount' => max(0, $total - $paid),
            'updated_at' => now(),
        ]);
    }

    private function generateJobNo(): string
    {
        $prefix = 'REP-' . now()->format('Ymd') . '-';
        $next = DB::table('repair_jobs')->where('job_no', 'like', $prefix . '%')->count() + 1;
        do {
            $number = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (DB::table('repair_jobs')->where('job_no', $number)->exists());
        return $number;
    }

    private function logStatus(int $job, ?string $from, string $to, ?string $note = null): void
    {
        DB::table('repair_status_logs')->insert([
            'repair_job_id' => $job,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'user_id' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function repairLog(int $job, string $action, ?string $description = null): void
    {
        DB::table('repair_activity_logs')->insert([
            'repair_job_id' => $job,
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function queueNotification(int $job, string $event): void
    {
        $repair = $this->jobsQuery()->where('repair_jobs.id', $job)->first();
        DB::table('repair_notifications')->insert([
            'repair_job_id' => $job,
            'channel' => 'WhatsApp',
            'event' => $event,
            'recipient' => $repair->customer_phone ?? null,
            'message' => "Repair {$repair->job_no} status updated to {$event}.",
            'status' => 'Pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function checklistItems(): array
    {
        return ['Battery', 'SIM 1', 'SIM 2', 'Memory Card', 'Back Cover', 'Phone Case', 'Screen Protector', 'Stylus', 'Charger', 'USB Cable', 'Earphones', 'Box', 'Warranty Card', 'Receipt', 'Camera Lens', 'Fingerprint Working', 'Face ID Working', 'Buttons Working', 'Speaker Working', 'Microphone Working', 'Charging Port Working', 'WiFi Working', 'Bluetooth Working', 'NFC Working', 'Flash Working', 'Touch Working', 'LCD Working', 'Vibrator Working', 'Front Camera Working', 'Rear Camera Working', 'SIM Tray'];
    }

    private function physicalConditions(): array
    {
        return ['Good', 'Scratches', 'Dent', 'Bent', 'Water Damage', 'Cracked Screen', 'Broken Back Glass', 'LCD Damage', 'Frame Damage', 'Camera Glass Broken', 'Dust Inside Camera', 'Charging Port Loose', 'Speaker Fault', 'Microphone Fault', 'No Power', 'Boot Loop', 'Dead Device'];
    }
}
