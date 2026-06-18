import os

directories = [
    'resources/js/Pages/Accounting',
    'resources/js/Pages/Expenses',
    'resources/js/Pages/Incomes',
]

for dir_path in directories:
    for root, _, files in os.walk(dir_path):
        for f in files:
            if not f.endswith('.svelte'):
                continue
            path = os.path.join(root, f)
            with open(path, 'r', encoding='utf-8') as file:
                content = file.read()
                
            original_content = content
            
            # Since the files moved 1 level deeper, replace '../../' with '../../../'
            content = content.replace("'../../Layouts", "'../../../Layouts")
            content = content.replace("'../../Components", "'../../../Components")
            content = content.replace("'../../Stores", "'../../../Stores")
            
            # Double quotes
            content = content.replace('"../../Layouts', '"../../../Layouts')
            content = content.replace('"../../Components', '"../../../Components')
            content = content.replace('"../../Stores', '"../../../Stores')
            
            if content != original_content:
                with open(path, 'w', encoding='utf-8') as file:
                    file.write(content)
                print(f"Updated imports in: {path}")
