import os

replacements = {
    # Controllers
    r'App\Http\Controllers\DocumentController': r'App\Http\Controllers\Incomes\DocumentController',
    r'App\Http\Controllers\DiscountController': r'App\Http\Controllers\Settings\DiscountController',

    # Models ending with ;
    r'App\Models\Account;': r'App\Models\Accounting\Account;',
    r'App\Models\AccountType;': r'App\Models\Accounting\AccountType;',
    r'App\Models\Journal;': r'App\Models\Accounting\Journal;',
    r'App\Models\Ledger;': r'App\Models\Accounting\Ledger;',
    r'App\Models\Payment;': r'App\Models\Expenses\Payment;',
    r'App\Models\PaymentInvoice;': r'App\Models\Expenses\PaymentInvoice;',
    r'App\Models\Vendor;': r'App\Models\Expenses\Vendor;',
    r'App\Models\Customer;': r'App\Models\Incomes\Customer;',
    r'App\Models\Document;': r'App\Models\Incomes\Document;',
    r'App\Models\BankAccount;': r'App\Models\Settings\BankAccount;',
    r'App\Models\User;': r'App\Models\Settings\User;',
    
    # Models ending with ::
    r'App\Models\Account::': r'App\Models\Accounting\Account::',
    r'App\Models\AccountType::': r'App\Models\Accounting\AccountType::',
    r'App\Models\Journal::': r'App\Models\Accounting\Journal::',
    r'App\Models\Ledger::': r'App\Models\Accounting\Ledger::',
    r'App\Models\Payment::': r'App\Models\Expenses\Payment::',
    r'App\Models\PaymentInvoice::': r'App\Models\Expenses\PaymentInvoice::',
    r'App\Models\Vendor::': r'App\Models\Expenses\Vendor::',
    r'App\Models\Customer::': r'App\Models\Incomes\Customer::',
    r'App\Models\Document::': r'App\Models\Incomes\Document::',
    r'App\Models\BankAccount::': r'App\Models\Settings\BankAccount::',
    r'App\Models\User::': r'App\Models\Settings\User::',
}

namespaces = {
    'app/Http/Controllers/Incomes/DocumentController.php': r'namespace App\Http\Controllers\Incomes;',
    'app/Http/Controllers/Settings/DiscountController.php': r'namespace App\Http\Controllers\Settings;',
    'app/Models/Accounting/Account.php': r'namespace App\Models\Accounting;',
    'app/Models/Accounting/AccountType.php': r'namespace App\Models\Accounting;',
    'app/Models/Accounting/Journal.php': r'namespace App\Models\Accounting;',
    'app/Models/Accounting/Ledger.php': r'namespace App\Models\Accounting;',
    'app/Models/Expenses/Payment.php': r'namespace App\Models\Expenses;',
    'app/Models/Expenses/PaymentInvoice.php': r'namespace App\Models\Expenses;',
    'app/Models/Expenses/Vendor.php': r'namespace App\Models\Expenses;',
    'app/Models/Incomes/Customer.php': r'namespace App\Models\Incomes;',
    'app/Models/Incomes/Document.php': r'namespace App\Models\Incomes;',
    'app/Models/Settings/BankAccount.php': r'namespace App\Models\Settings;',
    'app/Models/Settings/User.php': r'namespace App\Models\Settings;',
}

for root, _, files in os.walk('.'):
    if 'vendor' in root or 'node_modules' in root or '.git' in root:
        continue
    for f in files:
        if not f.endswith('.php'):
            continue
        path = os.path.join(root, f)
        with open(path, 'r', encoding='utf-8') as file:
            content = file.read()
            
        original_content = content
        
        # Replace use statements
        for old, new in replacements.items():
            content = content.replace(old, new)
            
        # Replace namespace if it's one of the moved files
        normalized_path = path[2:] # Remove ./
        if normalized_path in namespaces:
            import re
            content = re.sub(r'namespace\s+App(?:\\\w+)*;', namespaces[normalized_path], content)
            
        if content != original_content:
            with open(path, 'w', encoding='utf-8') as file:
                file.write(content)
            print(f"Updated: {path}")

