import os

replacements = {
    r"'Accounts/Index'": r"'Accounting/Accounts/Index'",
    r"'Journals/Create'": r"'Accounting/Journals/Create'",
    r"'Journals/Index'": r"'Accounting/Journals/Index'",
    r"'Journals/Show'": r"'Accounting/Journals/Show'",
    r"'Ledger/Index'": r"'Accounting/Ledger/Index'",
    r"'Expenses/Form'": r"'Expenses/Bills/Form'",
    r"'Expenses/Index'": r"'Expenses/Bills/Index'",
    r"'ExpensePayments/Create'": r"'Expenses/BillsPayments/Create'",
    r"'ExpensePayments/Index'": r"'Expenses/BillsPayments/Index'",
    r"'Payments/Create'": r"'Expenses/Payments/Create'",
    r"'Payments/Index'": r"'Expenses/Payments/Index'",
    r"'Payments/Show'": r"'Expenses/Payments/Show'",
    r"'Vendors/Form'": r"'Expenses/Vendors/Form'",
    r"'Vendors/Index'": r"'Expenses/Vendors/Index'",
    r"'Documents/Create'": r"'Incomes/Documents/Create'",
    r"'Invoices/Create'": r"'Incomes/Invoices/Create'",
    r"'Invoices/Edit'": r"'Incomes/Invoices/Edit'",
    r"'Invoices/Index'": r"'Incomes/Invoices/Index'",
    r"'Kwitansi/Index'": r"'Incomes/Kwitansi/Index'",
    r"'ListKirimTagihan/Index'": r"'Incomes/ListKirimTagihan/Index'",
    r"'ReportMayora/Index'": r"'Incomes/ReportMayora/Index'",
    r"'ScheduleTukarFaktur/Index'": r"'Incomes/ScheduleTukarFaktur/Index'",
    r"'SuratTagihan/Index'": r"'Incomes/SuratTagihan/Index'",
    r"'TandaTerima/Index'": r"'Incomes/TandaTerima/Index'",
    r"'TandaTerima/New'": r"'Incomes/TandaTerima/New'",
    r"'TitipInternal/Index'": r"'Incomes/TitipInternal/Index'",
    r"'UploadNoFaktur/Index'": r"'Incomes/UploadNoFaktur/Index'",
}

for root, _, files in os.walk('.'):
    if 'vendor' in root or 'node_modules' in root or '.git' in root or 'storage' in root:
        continue
    for f in files:
        if not f.endswith('.php'):
            continue
        path = os.path.join(root, f)
        try:
            with open(path, 'r', encoding='utf-8') as file:
                content = file.read()
                
            original_content = content
            
            # Replace Inertia::render arguments
            for old, new in replacements.items():
                content = content.replace(old, new)
                
            if content != original_content:
                with open(path, 'w', encoding='utf-8') as file:
                    file.write(content)
                print(f"Updated Inertia paths in: {path}")
        except Exception as e:
            print(f"Error reading {path}: {e}")
