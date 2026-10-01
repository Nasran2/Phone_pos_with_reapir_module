<?php
$dir = new RecursiveDirectoryIterator('resources/views');
$iterator = new RecursiveIteratorIterator($dir);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getPathname();
        $content = file_get_contents($path);
        $original = $content;

        // Make all text black and borders dark for inputs
        $content = preg_replace_callback('/class=(["\'])(.*?)\1/s', function($matches) {
            $quote = $matches[1];
            $classes = $matches[2];
            
            // Text colors to black
            $classes = preg_replace('/\btext-slate-[456789]00\b/', 'text-black', $classes);
            $classes = preg_replace('/\btext-gray-[456789]00\b/', 'text-black', $classes);
            $classes = preg_replace('/\btext-slate-[0-9]+0\b/', 'text-black', $classes); // e.g. text-slate-850

            // If it has input-like classes, make border dark
            if (preg_match('/\b(focus:ring|focus:border|w-full|px-3)\b/', $classes)) {
                $classes = preg_replace('/\bborder-slate-[1234]00\b/', 'border-slate-900', $classes);
                $classes = preg_replace('/\bborder-gray-[1234]00\b/', 'border-slate-900', $classes);
            }
            
            return 'class=' . $quote . $classes . $quote;
        }, $content);

        // Also process raw inputs if they missed the class block? No, class is always there.

        if ($content !== $original) {
            file_put_contents($path, $content);
            echo "Updated: $path\n";
        }
    }
}
echo "Done.\n";
