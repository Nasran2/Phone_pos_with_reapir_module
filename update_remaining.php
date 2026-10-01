<?php
$dir = new RecursiveDirectoryIterator('resources/views');
$iterator = new RecursiveIteratorIterator($dir);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getPathname();
        $content = file_get_contents($path);
        $original = $content;

        // Global replace for any remaining grey text to black
        $content = preg_replace('/\btext-slate-[456789]00\b/', 'text-black', $content);
        $content = preg_replace('/\btext-gray-[456789]00\b/', 'text-black', $content);
        
        // Make placeholders black too
        $content = preg_replace('/\bplaceholder-slate-[345]00\b/', 'placeholder-black', $content);
        $content = preg_replace('/\bplaceholder:text-slate-[345]00\b/', 'placeholder:text-black', $content);

        // Ensure border-slate-900 is used on all inputs by replacing border-slate-200 with border-slate-900 
        // strictly for inputs (doing it globally for class="..." containing form elements just to be safe)
        $content = preg_replace_callback('/class=(["\'])(.*?)\1/s', function($matches) {
            $quote = $matches[1];
            $classes = $matches[2];
            
            if (preg_match('/\b(focus:ring|focus:border|w-full|px-3|form-input|form-select)\b/', $classes)) {
                $classes = preg_replace('/\bborder-slate-[234]00\b/', 'border-slate-900', $classes);
                $classes = preg_replace('/\bborder-gray-[234]00\b/', 'border-slate-900', $classes);
                $classes = preg_replace('/\bborder-slate-200\/70\b/', 'border-slate-900', $classes); // Sometimes they use opacity
            }
            return 'class=' . $quote . $classes . $quote;
        }, $content);

        if ($content !== $original) {
            file_put_contents($path, $content);
            echo "Updated: $path\n";
        }
    }
}
echo "Done.\n";
