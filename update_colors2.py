import os
import re

def process_file(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    original = content
    content = re.sub(r'text-slate-900', 'text-black', content)
    
    if content != original:
        with open(filepath, 'w') as f:
            f.write(content)
        print(f"Updated {filepath}")

for root, dirs, files in os.walk('resources/views'):
    for file in files:
        if file.endswith('.php'): # .blade.php
            process_file(os.path.join(root, file))

print("Done")
