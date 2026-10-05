<x-layouts::app :title="$job ? 'Edit Repair Intake' : 'New Repair Job'"><section class="repair-shell" data-repair-intake>@include('repairs.partials.header',['heading'=>$job?'Edit intake · '.$job->job_no:'Receive a device','subtitle'=>'Record its condition now so every repair starts with a clear handover.'])
@php($isWizard = \App\Support\RepairSettings::bool('step_by_step_intake'))
@if($isWizard)
<div class="repair-stepper" data-wizard-nav style="display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid var(--r-border); padding-bottom: 16px; overflow-x: auto;">
  <button type="button" class="active" data-step-target="1" style="display: flex; align-items: center; gap: 8px; background: none; border: none; font-size: 14px; font-weight: 600; color: var(--r-indigo); cursor: pointer; padding: 8px 16px; border-radius: 8px; white-space: nowrap;"><span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: var(--r-indigo); color: white; font-size: 12px; font-weight: 700;">1</span> Customer</button>
  <button type="button" data-step-target="2" style="display: flex; align-items: center; gap: 8px; background: none; border: none; font-size: 14px; font-weight: 500; color: var(--r-text-muted); cursor: pointer; padding: 8px 16px; border-radius: 8px; white-space: nowrap;"><span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: #e2e8f0; color: #475569; font-size: 12px; font-weight: 700;">2</span> Device & Accessories</button>
  <button type="button" data-step-target="3" style="display: flex; align-items: center; gap: 8px; background: none; border: none; font-size: 14px; font-weight: 500; color: var(--r-text-muted); cursor: pointer; padding: 8px 16px; border-radius: 8px; white-space: nowrap;"><span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: #e2e8f0; color: #475569; font-size: 12px; font-weight: 700;">3</span> Problems & Condition</button>
  <button type="button" data-step-target="4" style="display: flex; align-items: center; gap: 8px; background: none; border: none; font-size: 14px; font-weight: 500; color: var(--r-text-muted); cursor: pointer; padding: 8px 16px; border-radius: 8px; white-space: nowrap;"><span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: #e2e8f0; color: #475569; font-size: 12px; font-weight: 700;">4</span> Tests & Photos</button>
</div>
@endif
<form method="POST" action="{{ $job?route('repairs.update',$job):route('repairs.store') }}" class="repair-stack {{ $isWizard ? 'repair-wizard' : '' }}" enctype="multipart/form-data">@csrf @if($job) @method('PUT') @else<input type="hidden" name="intake_key" value="{{ old('intake_key', (string)\Illuminate\Support\Str::uuid()) }}">@endif
<div class="repair-grid">
<section class="repair-card" data-wizard-step="1"><h2>01 · Customer & handover</h2>
@php($selectedCustomer=old('customer_id')?\App\Models\Customer::find(old('customer_id')):$job?->customer)
<div data-repair-customer data-search-url="{{ route('repairs.customers') }}" data-create-url="{{ route('repairs.customers.store') }}">
<label class="repair-field" for="customer-search"><span>Customer name, phone or number *</span><input id="customer-search" type="search" autocomplete="off" placeholder="Search existing customers…" value="{{ $selectedCustomer?->name }}" data-customer-search></label>
<input type="hidden" name="customer_id" value="{{ old('customer_id',$job?->customer_id) }}" data-customer-id>
<p class="repair-muted" data-customer-selected>{{ $selectedCustomer?->phone }}</p><div class="repair-search-results" data-customer-results hidden></div>
<button type="button" class="repair-button" data-customer-add style="margin:12px 0">＋ Quick add customer</button>
</div>
<div class="repair-fields"><x-repair-field name="store_id" label="Receiving store" :required="true" :value="old('store_id',$job?->store_id??\App\Support\RepairSettings::get('default_store_id')??$stores->first()?->id)" :options="$stores->pluck('name','id')->all()"/>
<x-repair-field name="expected_completion_date" label="Expected completion" type="date" :value="old('expected_completion_date',$job?->expected_completion_date?->format('Y-m-d')??today()->addDays((int)\App\Support\RepairSettings::get('expected_days',3))->toDateString())"/>
<x-repair-field name="estimated_cost" label="Estimate" type="number" min="0" step="0.01" :value="old('estimated_cost',$job?->estimated_cost??0)"/>
@if(!$job && auth()->user()->hasPermission('repair.payments'))<x-repair-field name="advance_amount" label="Intake advance" type="number" min="0" step="0.01" :value="old('advance_amount',0)"/><x-repair-field name="advance_method" label="Advance payment method" :value="old('advance_method','Cash')" :options="collect(\App\Services\MixedPaymentService::paymentMethods())->reject(fn($m)=>$m==='Cheque')->mapWithKeys(fn($m)=>[$m=>$m])->all()"/>@endif
</div>
<div class="repair-checks"><label><input type="checkbox" name="urgent" value="1" @checked(old('urgent',$job?->urgent))>Urgent repair</label><label><input type="checkbox" name="is_warranty" value="1" @checked(old('is_warranty',$job?->is_warranty))>Warranty visit</label></div>
<x-repair-field name="customer_request" label="Customer request" type="textarea" :value="old('customer_request',$job?->customer_request)" style="margin-top:14px"/>
</section>
<section class="repair-card" data-wizard-step="2"><h2>02 · Device identity</h2><div class="repair-fields">
@foreach(['device_type'=>'Device type','brand'=>'Brand','model'=>'Model','imei_1'=>'IMEI 1','imei_2'=>'IMEI 2','serial_number'=>'Serial number','color'=>'Color','storage'=>'Storage','ram'=>'RAM','carrier'=>'Carrier','battery_percent'=>'Battery %'] as $field=>$label)
<x-repair-field :name="'device['.$field.']'" :label="$label" :type="$field==='battery_percent'?'number':'text'" :value="old('device.'.$field,$job?->device?->$field)" :required="$field==='model'" maxlength="150"/>
@endforeach</div><div class="repair-checks"><label><input type="checkbox" name="device[google_lock]" value="1" @checked(old('device.google_lock',$job?->device?->google_lock))>Google account lock</label><label><input type="checkbox" name="device[apple_id_lock]" value="1" @checked(old('device.apple_id_lock',$job?->device?->apple_id_lock))>Apple ID lock</label></div></section>
<section class="repair-card" data-wizard-step="3"><h2>03 · Problems reported</h2>@if($job && $job->problem_snapshot)<p class="repair-muted">Original intake problems are preserved: {{ implode(', ',$job->problem_snapshot) }}. Select additional problems below.</p>@endif<label class="repair-field" for="problem-search"><span>Find a problem</span><input id="problem-search" type="search" data-problem-search placeholder="Filter the problem catalog…"></label>
<div class="repair-checks" data-problem-list>@foreach($problems as $problem)<label><input type="checkbox" name="problem_ids[]" value="{{ $problem->id }}" @checked(in_array($problem->id,old('problem_ids', $job ? $problems->whereIn('name',$job->problem_snapshot??[])->pluck('id')->all():[])))>{{ $problem->name }}</label>@endforeach</div>
<x-repair-field name="problem_description" label="Other problem / diagnosis details" type="textarea" :value="old('problem_description',$job?->problem_description)" style="margin-top:14px"/>
</section>
<section class="repair-card" data-repair-unlock data-wizard-step="2"><h2>04 · Device unlock</h2><p class="repair-muted">Saved credentials are encrypted and visible only to authorized staff.</p>
<x-repair-field name="device[unlock_type]" label="Unlock type" :value="old('device.unlock_type',$job?->device?->unlock_type??'None')" :options="['None'=>'No credential','PIN'=>'PIN','Password'=>'Password','Pattern'=>'Android pattern']" data-unlock-type/>
<label class="repair-field" data-unlock-text hidden style="margin-top:14px"><span>New PIN or password</span><input type="password" autocomplete="new-password" data-unlock-input maxlength="256"></label>
<input type="hidden" name="device[unlock_secret]" value="" data-unlock-secret>
<div data-unlock-pattern hidden><p class="repair-muted" style="margin-top:12px">Tap dots or drag a pattern. Intermediate dots are included automatically.</p><div class="repair-pattern" data-pattern-pad aria-label="Android unlock pattern"><svg viewBox="0 0 250 250" aria-hidden="true"><polyline data-pattern-line fill="none" stroke="#3b82f6" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/></svg>
@for($dot=1;$dot<=9;$dot++)<button type="button" data-dot="{{ $dot }}" aria-label="Pattern dot {{ $dot }}" style="left:{{ 20+(($dot-1)%3)*30 }}%;top:{{ 20+intdiv($dot-1,3)*30 }}%">{{ $dot }}</button>@endfor</div><div class="repair-actions"><button type="button" class="repair-button" data-pattern-clear>Clear / Redraw</button><button type="button" class="repair-button primary" data-pattern-save>Save pattern</button></div><p class="repair-muted" role="status" data-pattern-status>No pattern selected</p></div>
@if($job)<p class="repair-muted" style="margin-top:12px">Leave the new credential blank to keep the saved credential.</p><label class="repair-flex repair-muted"><input type="checkbox" name="clear_credential" value="1">Clear saved credential</label>@endif
</section>
<section class="repair-card" data-wizard-step="2"><h2>05 · Accessories received</h2><div class="repair-checks">@foreach(config('repairs.accessories') as $accessory)<label><input type="checkbox" name="device[accessories_received][]" value="{{ $accessory }}" @checked(in_array($accessory,old('device.accessories_received',$job?->device?->accessories_received??[])))>{{ $accessory }}</label>@endforeach</div>
<x-repair-field name="accessory_other" label="Other accessory" :value="old('accessory_other',collect($job?->device?->accessories_received??[])->diff(config('repairs.accessories'))->implode(', '))" style="margin-top:14px"/></section>
<section class="repair-card" data-wizard-step="3"><h2>06 · Physical condition</h2><div class="repair-fields">@foreach(config('repairs.condition') as $condition)<x-repair-field :name="'device[physical_condition]['.$condition.']'" :label="$condition" :value="old('device.physical_condition.'.$condition,$job?->device?->physical_condition[$condition]??'Not inspected')" :options="array_combine(['Not inspected','Good','Scratched','Cracked','Broken'],['Not inspected','Good','Scratched','Cracked','Broken'])"/>@endforeach</div>
<div class="repair-checks">@foreach(['Water damage','Missing screws','Missing parts','Previous repair'] as $condition)<label><input type="checkbox" name="device[physical_condition][{{ $condition }}]" value="Yes" @checked(old('device.physical_condition.'.$condition,$job?->device?->physical_condition[$condition]??null)==='Yes')>{{ $condition }}</label>@endforeach</div>
<x-repair-field name="device[physical_condition][Other]" label="Other condition" type="textarea" :value="old('device.physical_condition.Other',$job?->device?->physical_condition['Other']??null)" style="margin-top:14px"/></section>
</div>
<section class="repair-card" data-wizard-step="4"><h2>07 · Before-repair function tests</h2><div class="repair-grid three">@foreach(array_unique(array_merge(config('repairs.tests'),array_keys(old('checklist',$job?->checklist?->items['before']??[])))) as $test)<x-repair-field :name="'checklist['.$test.']'" :label="$test" :value="old('checklist.'.$test,$job?->checklist?->items['before'][$test]??'Not tested')" :options="['Not tested'=>'Not tested','Working'=>'Working','Not working'=>'Not working']"/>@endforeach</div><div data-custom-tests style="margin-top:16px"><div class="repair-fields"><x-repair-field name="custom_test_name" label="Custom function test" placeholder="e.g. Wireless charging" data-custom-test-name/><x-repair-field name="custom_test_state" label="Test result" :options="['Not tested'=>'Not tested','Working'=>'Working','Not working'=>'Not working']" data-custom-test-state/></div><button type="button" class="repair-button" data-custom-test-add style="margin-top:12px">＋ Add custom test</button><div class="repair-grid three" data-custom-test-rows style="margin-top:12px"></div></div><x-repair-field name="custom_tests" label="Additional tests / observations" type="textarea" :value="old('custom_tests',$job?->checklist?->custom_items)" style="margin-top:14px"/></section>
@if(!$job)<section class="repair-card" data-wizard-step="4"><h2>08 · Intake photos</h2><p class="repair-muted">Multiple front, back and damage photos. Private storage; maximum {{ \App\Support\RepairSettings::get('image_limit',12) }} images.</p><x-repair-field name="images[]" label="Before / damage photos" type="file" multiple accept="image/jpeg,image/png,image/webp" data-photo-preview-input/><div class="repair-photos" data-photo-previews></div></section>@endif
<div class="repair-actions" style="justify-content:end">
  <a class="repair-button" href="{{ $job?route('repairs.show',$job):route('repairs.index') }}">Cancel</a>
  @if($isWizard)
    <button type="button" class="repair-button" data-wizard-prev style="display:none;">← Back</button>
    <button type="button" class="repair-button primary" data-wizard-next>Next step →</button>
    <button class="repair-button primary" type="submit" data-wizard-submit style="display:none;">{{ $job?'Save intake changes':'Check in device' }}</button>
  @else
    <button class="repair-button primary" type="submit">{{ $job?'Save intake changes':'Check in device' }}</button>
  @endif
</div>
</form></section>

@if($isWizard)
<script>
document.addEventListener('DOMContentLoaded', () => {
    let currentStep = 1;
    const totalSteps = 4;
    
    function updateWizard() {
        document.querySelectorAll('[data-step-target]').forEach(btn => {
            const stepNum = parseInt(btn.dataset.stepTarget);
            btn.style.fontWeight = stepNum === currentStep ? '600' : '500';
            btn.style.color = stepNum === currentStep ? 'var(--r-indigo)' : 'var(--r-text-muted)';
            const span = btn.querySelector('span');
            span.style.background = stepNum === currentStep ? 'var(--r-indigo)' : '#e2e8f0';
            span.style.color = stepNum === currentStep ? 'white' : '#475569';
        });
        
        document.querySelectorAll('[data-wizard-step]').forEach(sec => {
            if (parseInt(sec.dataset.wizardStep) === currentStep) {
                sec.style.display = '';
            } else {
                sec.style.display = 'none';
            }
        });
        
        const prevBtn = document.querySelector('[data-wizard-prev]');
        const nextBtn = document.querySelector('[data-wizard-next]');
        const submitBtn = document.querySelector('[data-wizard-submit]');
        
        prevBtn.style.display = currentStep > 1 ? '' : 'none';
        
        if (currentStep === totalSteps) {
            nextBtn.style.display = 'none';
            submitBtn.style.display = '';
        } else {
            nextBtn.style.display = '';
            submitBtn.style.display = 'none';
        }
    }
    
    function validateCurrentStep() {
        const fields = Array.from(document.querySelectorAll(`[data-wizard-step="${currentStep}"] input, [data-wizard-step="${currentStep}"] select, [data-wizard-step="${currentStep}"] textarea`));
        for (const field of fields) {
            if (field.hasAttribute('required') && !field.checkValidity()) {
                field.reportValidity();
                return false;
            }
        }
        return true;
    }

    document.querySelector('[data-wizard-next]').addEventListener('click', () => {
        if (!validateCurrentStep()) return;
        currentStep++;
        updateWizard();
        window.scrollTo(0, 0);
    });
    
    document.querySelector('[data-wizard-prev]').addEventListener('click', () => {
        currentStep--;
        updateWizard();
        window.scrollTo(0, 0);
    });
    
    document.querySelectorAll('[data-step-target]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const targetStep = parseInt(e.currentTarget.dataset.stepTarget);
            if (targetStep > currentStep && !validateCurrentStep()) return;
            currentStep = targetStep;
            updateWizard();
            window.scrollTo(0, 0);
        });
    });
    
    updateWizard();
});
</script>
<style>
.repair-wizard .repair-grid { display: block; }
.repair-wizard [data-wizard-step] { margin-bottom: 24px; }
</style>
@endif

</x-layouts::app>
