import os
import re

def process_file(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    original = content
    
    # Text colors to black or slate-900
    # Let's make all text-slate-[5678]00 to text-black
    content = re.sub(r'text-slate-[5678]00', 'text-black', content)
    content = re.sub(r'text-slate-750', 'text-black', content)
    
    # Input borders to dark (slate-900 or black)
    # Most borders are border-slate-400, border-slate-300, border-slate-200
    # We want to target inputs. Let's do it via regex matching inputs/selects/textareas
    
    def input_border_replacer(match):
        tag = match.group(1)
        attrs = match.group(2)
        
        # Replace border-slate-X with border-slate-900
        attrs = re.sub(r'border-slate-[234]00', 'border-slate-900', attrs)
        return f"<{tag}{attrs}>"
        
    content = re.sub(r'<(input|select|textarea)([^>]+)>', input_border_replacer, content)

    # Some labels might be grey, let's just make sure all text is black if it's slate
    
    if content != original:
        with open(filepath, 'w') as f:
            f.write(content)
        print(f"Updated {filepath}")

for root, dirs, files in os.walk('resources/views'):
    for file in files:
        if file.endswith('.php'): # .blade.php
            process_file(os.path.join(root, file))

print("Done")
