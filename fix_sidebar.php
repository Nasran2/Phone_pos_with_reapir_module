<?php

$file = __DIR__ . '/resources/views/layouts/app/sidebar.blade.php';
$content = file_get_contents($file);

// 1. Fix Expenses Link
$content = str_replace(
    "route('reports.expenses')",
    "route('accounting.expenses.index')",
    $content
);
$content = str_replace(
    "request()->routeIs('reports.expenses')",
    "request()->routeIs('accounting.expenses.*')",
    $content
);

// 2. Fix Accounting Dropdown logic
$content = str_replace(
    "request()->routeIs('reports.profit_loss') || request()->routeIs('reports.cash_bank')",
    "request()->routeIs('accounting.*') && !request()->routeIs('accounting.expenses.*')",
    $content
);

// 3. Fix Chart of Accounts Link
$content = str_replace(
    '<a href="{{ route(\'reports.profit_loss\') }}" class="block py-2 text-xs font-medium {{ request()->routeIs(\'reports.profit_loss\') ? \'text-blue-400 font-semibold\' : \'text-slate-400 hover:text-white\' }}">Chart of Accounts</a>',
    '<a href="{{ route(\'accounting.accounts.index\') }}" class="block py-2 text-xs font-medium {{ request()->routeIs(\'accounting.accounts.*\') ? \'text-blue-400 font-semibold\' : \'text-slate-400 hover:text-white\' }}">Chart of Accounts</a>',
    $content
);

// 4. Fix Journal Entries Link
$content = str_replace(
    '<a href="{{ route(\'reports.cash_bank\') }}" class="block py-2 text-xs font-medium {{ request()->routeIs(\'reports.cash_bank\') ? \'text-blue-400 font-semibold\' : \'text-slate-400 hover:text-white\' }}">Journal Entries</a>',
    '<a href="{{ route(\'accounting.journal-entries.index\') }}" class="block py-2 text-xs font-medium {{ request()->routeIs(\'accounting.journal-entries.*\') ? \'text-blue-400 font-semibold\' : \'text-slate-400 hover:text-white\' }}">Journal Entries</a>',
    $content
);

// 5. Fix Ledger Book Link
$content = preg_replace(
    '/<a href="\{\{ route\(\'reports\.cash_bank\'\) \}\}" class="block py-2 text-xs font-medium \{\{ request\(\)->routeIs\(\'reports\.cash_bank\'\) \? \'text-blue-400 font-semibold\' : \'text-slate-400 hover:text-white\' \}\}">Ledger Book<\/a>/',
    '<a href="{{ route(\'accounting.ledger.index\') }}" class="block py-2 text-xs font-medium {{ request()->routeIs(\'accounting.ledger.*\') ? \'text-blue-400 font-semibold\' : \'text-slate-400 hover:text-white\' }}">Ledger Book</a>',
    $content
);

file_put_contents($file, $content);
echo "Sidebar updated.\n";
