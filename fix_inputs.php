<?php
$dir = new RecursiveDirectoryIterator('resources/views');
$iterator = new RecursiveIteratorIterator($dir);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getPathname();
        
        // Skip POS views because they need small dense inputs
        if (strpos($path, '/pos/') !== false) continue;

        $content = file_get_contents($path);
        $original = $content;

        // Use regex to find <input, <select, <textarea tags
        $content = preg_replace_callback('/<(input|select|textarea)([^>]+)>/i', function($matches) {
            $tag = $matches[1];
            $attrs = $matches[2];

            // In attributes, if there is a class attribute, let's modify it
            if (preg_match('/class=([\'"])(.*?)\1/i', $attrs, $classMatch)) {
                $classes = $classMatch[2];
                
                // Replace text-xs with text-sm
                $classes = preg_replace('/\btext-xs\b/', 'text-sm', $classes);
                
                // Replace py-1.5 or py-2 with py-2.5
                $classes = preg_replace('/\bpy-1\.5\b/', 'py-2.5', $classes);
                $classes = preg_replace('/\bpy-2\b/', 'py-2.5', $classes);
                
                // Also bump text-[10px] or text-[11px] or text-[12px] to text-sm if they exist inside form inputs
                $classes = preg_replace('/\btext-\[\d+px\]\b/', 'text-sm', $classes);

                $newAttrs = str_replace($classMatch[0], 'class="' . $classes . '"', $attrs);
                return '<' . $tag . $newAttrs . '>';
            }
            return $matches[0];
        }, $content);

        if ($content !== $original) {
            file_put_contents($path, $content);
            echo "Updated: $path\n";
        }
    }
}
echo "Done.\n";
