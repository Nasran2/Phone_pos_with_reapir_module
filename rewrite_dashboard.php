<?php
$path = 'resources/views/dashboard.blade.php';
$content = file_get_contents($path);

// Find the bottom section and remove it
$bottomPattern = '/\s*<!-- Very Bottom Widgets Bar \(5 Blocks\) -->.*?<\/div>\s*<\/div>\s*<!-- Charts Loading Configurations -->/is';
if (preg_match($bottomPattern, $content, $matches)) {
    // The match includes the closing </div> of the main dashboard-content! We must preserve that.
}

// Let's use string manipulation which is safer.
$startStr = '        <!-- Very Bottom Widgets Bar (5 Blocks) -->';
$endStr = '    <!-- Charts Loading Configurations -->';
$posStart = strpos($content, $startStr);
$posEnd = strpos($content, $endStr);

if ($posStart !== false && $posEnd !== false) {
    // Get everything before the bottom widgets bar
    $beforeWidgets = substr($content, 0, $posStart);
    
    // The bottom widgets bar has an enclosing </div> for dashboard-content.
    // We should just keep the closing </div> from it.
    
    // Let's create the new top cards section.
    $newTopCards = <<<HTML
        <!-- Secondary Widgets Bar (5 Blocks) -->
        <div class="dashboard-bottom-stats grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3 mt-3 mb-6">
            <!-- Total Products -->
            <a href="{{ route('products.index') }}" class="group bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 hover:border-blue-400 hover:shadow-sm transition duration-300 dark:border-slate-700/50 shadow-xs flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-blue-100/70 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 shrink-0 group-hover:bg-blue-200 transition">
                    <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] text-black font-bold tracking-wide uppercase leading-none">Total Products</p>
                    <h4 class="text-sm font-black text-black dark:text-white mt-1">{{ number_format(\$totalProducts) }}</h4>
                </div>
            </a>

            <!-- Total Stock Value -->
            <a href="{{ route('stock.index') }}" class="group bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 hover:border-emerald-400 hover:shadow-sm transition duration-300 dark:border-slate-700/50 shadow-xs flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-emerald-100/70 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400 shrink-0 group-hover:bg-emerald-200 transition">
                    <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] text-black font-bold tracking-wide uppercase leading-none">Total Stock Value</p>
                    <h4 class="text-sm font-black text-black dark:text-white mt-1">{{ \App\Support\SystemSettings::money((float) \$totalStockValue) }}</h4>
                </div>
            </a>

            <!-- Orders -->
            <a href="{{ route('sales.index') }}" class="group bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 hover:border-amber-400 hover:shadow-sm transition duration-300 dark:border-slate-700/50 shadow-xs flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-amber-100/70 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400 shrink-0 group-hover:bg-amber-200 transition">
                    <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] text-black font-bold tracking-wide uppercase leading-none">Orders · {{ \$dateLabel }}</p>
                    <h4 class="text-sm font-black text-black dark:text-white mt-1">{{ \$todayOrders }}</h4>
                </div>
            </a>

            <!-- Due Invoices -->
            <a href="{{ route('sales.index', ['payment_status' => 'due']) }}" class="group bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 hover:border-red-400 hover:shadow-sm transition duration-300 dark:border-slate-700/50 shadow-xs flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-red-100/70 text-red-600 dark:bg-red-900/20 dark:text-red-400 shrink-0 group-hover:bg-red-200 transition">
                    <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] text-black font-bold tracking-wide uppercase leading-none">Due Invoices</p>
                    <h4 class="text-sm font-black text-black dark:text-white mt-1">{{ \$dueInvoices }}</h4>
                </div>
            </a>

            <!-- Current Cheque Alerts -->
            <a href="{{ route('cheques.dashboard') }}" class="group bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 hover:border-indigo-400 hover:shadow-sm transition duration-300 dark:border-slate-700/50 shadow-xs flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-indigo-100/70 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400 shrink-0 group-hover:bg-indigo-200 transition">
                    <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] text-black font-bold tracking-wide uppercase leading-none">Current Cheque Alerts</p>
                    <h4 class="text-sm font-black text-black dark:text-white mt-1">{{ \$unreadNotifications }}</h4>
                </div>
            </a>
        </div>
HTML;

    // Remove the bottom section by cutting it out, but keep the closing </div> for the main dashboard content
    // wait, what is exactly at the end?
    //     </div>
    // 
    //     <!-- Charts Loading Configurations -->
    
    // We just remove between $startStr and $endStr, and add a closing div if needed.
    // Let's actually just replace it.
    
    // Find where the top cards end. 
    //         </div>
    // 
    //         <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    $insertPos = strpos($content, '        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">');
    
    if ($insertPos !== false) {
        $part1 = substr($content, 0, $insertPos);
        $part2 = substr($content, $insertPos, $posStart - $insertPos);
        $part3 = substr($content, $posEnd);
        
        // Wait, $posStart has:
        //        <!-- Very Bottom Widgets Bar (5 Blocks) -->
        //        <div class="...">
        //        ...
        //        </div>
        //    </div>
        //    <!-- Charts ... -->
        
        // So I must keep the final </div> that closes the dashboard-content!
        // Let's make sure:
        $newContent = $part1 . $newTopCards . "\n" . $part2 . "    </div>\n\n" . $part3;
        
        file_put_contents($path, $newContent);
        echo "Successfully moved and linked cards!\n";
    } else {
        echo "Could not find insertion point.\n";
    }
} else {
    echo "Could not find start/end points.\n";
}
