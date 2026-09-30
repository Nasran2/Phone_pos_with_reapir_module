import re
import sys

filename = 'resources/views/products/index.blade.php'

try:
    with open(filename, 'r') as f:
        content = f.read()

    # 1. Make all font colors black
    # Replacing all text-slate-* with text-black
    content = re.sub(r'text-slate-[1-9]00', 'text-black', content)
    content = re.sub(r'text-slate-850', 'text-black', content)
    content = re.sub(r'text-slate-350', 'text-black', content)
    content = re.sub(r'text-slate-650', 'text-black', content)
    content = re.sub(r'text-slate-150', 'text-black', content)
    
    # Also replacing text-[color]-400 etc to text-black if they are labels/texts but keeping some status colors is okay.
    # The user said "make the all font colors black", so let's stick to replacing slate colors for now since they are the primary UI colors.
    
    # 2. Make the input field border dark
    # Finding inputs, selects, textareas and replacing their border classes
    # e.g. class="... border border-slate-200 ..."
    
    # A generic approach for inputs:
    # Replace `border border-slate-200` with `border-2 border-black` 
    # However, let's only do it for form elements.
    
    # Find all inputs, selects, textareas
    def replace_border(match):
        tag_content = match.group(0)
        # Check if it has border border-slate-200 or border-slate-200
        tag_content = re.sub(r'border border-slate-200', 'border-2 border-black', tag_content)
        tag_content = re.sub(r'border-slate-200', 'border-black', tag_content)
        # Make sure inputs are well rounded and visible
        return tag_content

    # Regex to find form elements
    content = re.sub(r'<(input|select|textarea)[^>]+>', replace_border, content)
    
    # Let's also do it for the custom select boxes (divs with class acting like select)
    # They have classes like: w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white flex justify-between items-center cursor-pointer
    # So it's easier to just do it for classes containing "border border-slate-200" in elements that are interactive.
    # Actually, if I just replace `border border-slate-200` with `border-2 border-black` everywhere inside the modal container, it might look messy.
    # Let's do it globally for form elements as done above, but also for those custom divs.
    def replace_custom_div_border(match):
        return re.sub(r'border border-slate-200', 'border-2 border-black', match.group(0))
        
    content = re.sub(r'<div[^>]*w-full[^>]*px-[^>]*py-[^>]*border border-slate-200[^>]*>', replace_custom_div_border, content)

    # 3. Make sure it is easy to understand for non-technical users
    # Change tiny uppercase labels to normal text-sm font-bold
    content = re.sub(r'text-\[10px\](\s+)font-bold(\s+)text-black(\s+)uppercase(\s+)tracking-wide', r'text-sm\1font-bold\2text-black', content)
    content = re.sub(r'text-\[9px\]', 'text-sm', content)
    content = re.sub(r'text-xs', 'text-sm', content) # Up-size all xs text
    
    with open(filename, 'w') as f:
        f.write(content)

    print("Success")
except Exception as e:
    print(f"Error: {e}")
