import re

with open('resources/views/products/index.blade.php', 'r') as f:
    content = f.read()

# Make all text colors black
# Specifically targeting text-slate-[100-900] and text-slate-850
content = re.sub(r'text-slate-[1-9]00', 'text-black', content)
content = re.sub(r'text-slate-850', 'text-black', content)
content = re.sub(r'text-slate-350', 'text-black', content)
content = re.sub(r'text-slate-650', 'text-black', content)

# Make labels larger and more readable
content = re.sub(r'text-\[10px\]\s+font-bold\s+text-black\s+uppercase\s+tracking-wide', 'text-sm font-bold text-black', content)

# Make input borders dark and slightly thicker for better visibility
# We'll replace 'border border-slate-200' with 'border-2 border-black' in the whole file
content = re.sub(r'border border-slate-200', 'border-2 border-black', content)

# But wait, border-slate-200 is also used in non-inputs (e.g. containers). 
# We should target input/select/textarea classes directly.
# Let's revert that and just do it for inputs/selects/textareas.

