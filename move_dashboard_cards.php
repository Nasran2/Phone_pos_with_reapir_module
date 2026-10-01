<?php
$path = 'resources/views/dashboard.blade.php';
$content = file_get_contents($path);

// Extract the bottom widgets bar
preg_match('/<!-- Very Bottom Widgets Bar \(5 Blocks\) -->(.*?)<\/div>\s*<\/div>\s*<!-- Charts Loading Configurations -->/is', $content, $matches);
if (!$matches) {
    echo "Could not find bottom widgets bar!\n";
    exit(1);
}
$bottom_widgets = $matches[0];
// Wait, the bottom_widgets includes the closing </div> of the main content?
// Let's be more precise:
preg_match('/<!-- Very Bottom Widgets Bar \(5 Blocks\) -->\s*<div class="dashboard-bottom-stats.*?>\s*<!-- Total Products -->.*?<\/div>\s*<\/div>\s*<\/div>\s*/is', $content, $m2);
// Let's just find the start and carefully get it.
