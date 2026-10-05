<form class="repair-filter" method="GET">
<x-repair-field name="search" label="Search" :value="request('search')" placeholder="Job, customer, phone, IMEI…"/>
<x-repair-field name="customer_id" label="Customer number" type="number" min="1" :value="request('customer_id')" placeholder="All customers"/>
<x-repair-field name="status_id" label="Status" :value="request('status_id')" :options="[''=>'All statuses']+$statuses->pluck('name','id')->all()"/>
<x-repair-field name="technician_id" label="Technician" :value="request('technician_id')" :options="[''=>'All technicians']+$technicians->pluck('name','id')->all()"/>
<x-repair-field name="store_id" label="Store" :value="request('store_id')" :options="[''=>'All stores']+$stores->pluck('name','id')->all()"/>
<x-repair-field name="from" label="From" type="date" :value="request('from')"/>
<x-repair-field name="to" label="To" type="date" :value="request('to')"/>
<x-repair-field name="payment" label="Payment" :value="request('payment')" :options="[''=>'All payments','paid'=>'Paid','due'=>'Outstanding','hold'=>'Cheque hold']"/>
<x-repair-field name="urgent" label="Priority" :value="request('urgent')" :options="[''=>'All priorities','1'=>'Urgent','0'=>'Normal']"/>
<x-repair-field name="is_warranty" label="Warranty visit" :value="request('is_warranty')" :options="[''=>'All visits','1'=>'Warranty','0'=>'Standard']"/>
<x-repair-field name="overdue" label="Completion" :value="request('overdue')" :options="[''=>'Any date','1'=>'Overdue']"/>
<div class="repair-actions"><button class="repair-button primary">Apply filters</button><a class="repair-button" href="{{ url()->current() }}">Reset</a></div>
</form>
