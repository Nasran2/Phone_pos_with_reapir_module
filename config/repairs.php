<?php

return [
    'accessories' => ['SIM card', 'Memory card', 'Battery', 'Back cover', 'Case', 'Charger', 'Cable', 'Earphones', 'Box', 'Stylus', 'SIM tray', 'Screen protector'],
    'condition' => ['Screen', 'Back glass', 'Frame', 'Camera lens', 'Housing', 'Charging port', 'Buttons'],
    'tests' => ['Display', 'Touch', 'Power button', 'Volume buttons', 'Charging', 'Battery', 'Front camera', 'Rear camera', 'Flash', 'Speaker', 'Earpiece', 'Microphone', 'Wi-Fi', 'Bluetooth', 'Mobile signal', 'SIM detection', 'Vibration', 'Fingerprint', 'Face unlock', 'Proximity sensor', 'Headphone jack', 'GPS'],
    'settings' => [
        'job_prefix' => ['Job number prefix', 'text', 'REP'],
        'warranty_days' => ['Default warranty days', 'number', 30],
        'require_full_payment' => ['Require full payment before delivery', 'checkbox', 1],
        'enable_tax' => ['Use global sales tax on new jobs', 'checkbox', 0],
        'require_signature' => ['Print customer signature line', 'checkbox', 1],
        'show_technician' => ['Print technician name', 'checkbox', 0],
        'auto_print' => ['Open job card after intake', 'checkbox', 0],
        'expected_days' => ['Default completion in days', 'number', 3],
        'step_by_step_intake' => ['Use step-by-step wizard for intake form', 'checkbox', 1],
        'image_limit' => ['Maximum photos per job', 'number', 12],
        'image_size_kb' => ['Maximum photo size (KB)', 'number', 4096],
        'default_store_id' => ['Default store', 'store', ''],
        'enable_sms' => ['Send SMS through configured notification gateway', 'checkbox', 0],
        'sms_template' => ['SMS template', 'textarea', 'Hello {customer}, job {job_no} ({device}) is {status}. Balance: {balance}. {business}'],
        'terms' => ['Job card terms', 'textarea', 'Please retain this job card when collecting your device. Warranty covers the repair performed and excludes physical or liquid damage.'],
    ],
];
