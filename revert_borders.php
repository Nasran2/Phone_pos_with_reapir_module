<?php
$dir = new RecursiveDirectoryIterator('resources/views');
$iterator = new RecursiveIteratorIterator($dir);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getPathname();
        $content = file_get_contents($path);
        $original = $content;

        // We want to revert border-slate-900 to border-slate-200 for anything that is NOT an input/select/textarea
        // A simple way is to match all tags with their classes.
        $content = preg_replace_callback('/<([a-zA-Z0-9\-]+)([^>]*)class=(["\'])(.*?)\3([^>]*)>/is', function($matches) {
            $tag = strtolower($matches[1]);
            $beforeClass = $matches[2];
            $quote = $matches[3];
            $classes = $matches[4];
            $afterClass = $matches[5];
            
            // If the tag is NOT an input element, AND it doesn't have focus: classes, revert border-slate-900 to border-slate-200
            if (!in_array($tag, ['input', 'select', 'textarea']) && strpos($classes, 'focus:') === false) {
                $classes = preg_replace('/\bborder-slate-900\b/', 'border-slate-200', $classes);
            }
            
            return "<{$tag}{$beforeClass}class={$quote}{$classes}{$quote}{$afterClass}>";
        }, $content);

        // Just to be sure we also catch cases where class might be multiline or something, 
        // wait, the above regex captures multi-line because of `is` modifier!

        if ($content !== $original) {
            file_put_contents($path, $content);
            echo "Updated: $path\n";
        }
    }
}
echo "Done.\n";
