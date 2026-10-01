<?php
$path = 'resources/views/layouts/app/sidebar.blade.php';
$content = file_get_contents($path);

// Set body tag text to black again
$content = str_replace('text-slate-800 dark:text-slate-200', 'text-black dark:text-black', $content);

file_put_contents($path, $content);
echo "Sidebar body fixed.\n";
