<?php
$content = file_get_contents('resources/views/layouts/app/sidebar.blade.php');
preg_match_all("/href=\"\{\{ route\('([^']+)'[^}]*\}\}/", $content, $matches);
print_r(array_unique($matches[1]));
