<?php
$path = 'resources/views/layouts/app/sidebar.blade.php';
$content = file_get_contents($path);

// Restore text-black to text-slate-400 in the sidebar because the sidebar has a dark background
$content = str_replace('text-black', 'text-slate-400', $content);

// Ensure the profile badge or something is not accidentally text-slate-400 where it shouldn't be? 
// No, the bottom user section has: `<div class="font-bold text-white">`
// Wait, is there any place where it was `text-black` initially?
// E.g. body tag: `<body class="... text-slate-400 dark:text-slate-400 ...">` 
// Ah, the body tag in sidebar.blade.php has `text-black dark:text-black`!
// Let's fix that back to `text-slate-800 dark:text-slate-200` just in case, though this body tag seems to wrap the whole app.
$content = str_replace('text-slate-400 dark:text-slate-400', 'text-slate-800 dark:text-slate-200', $content);

file_put_contents($path, $content);
echo "Sidebar fixed.\n";
