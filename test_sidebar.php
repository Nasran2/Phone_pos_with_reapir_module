<?php
$content = file_get_contents('resources/views/layouts/app/sidebar.blade.php');
echo substr_count($content, 'text-slate-400') . " instances of text-slate-400\n";
echo substr_count($content, 'text-black') . " instances of text-black\n";
