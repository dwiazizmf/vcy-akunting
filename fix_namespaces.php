<?php

function updateNamespace($file, $newNamespace) {
    if (!file_exists($file)) return;
    $content = file_get_contents($file);
    // Replace the namespace declaration
    $content = preg_replace('/namespace\s+App(?:\\\\[a-zA-Z0-9_]+)*;/', "namespace $newNamespace;", $content);
    file_put_contents($file, $content);
    echo "Updated namespace in $file\n";
}

// Controllers
updateNamespace('app/Http/Controllers/Incomes/DocumentController.php', 'App\Http\Controllers\Incomes');
updateNamespace('app/Http/Controllers/Settings/DiscountController.php', 'App\Http\Controllers\Settings');

// Models
updateNamespace('app/Models/Accounting/Account.php', 'App\Models\Accounting\Accounting');
updateNamespace('app/Models/Accounting/AccountType.php', 'App\Models\Accounting\Accounting');
updateNamespace('app/Models/Accounting/Journal.php', 'App\Models\Accounting\Accounting');
updateNamespace('app/Models/Accounting/Ledger.php', 'App\Models\Accounting\Accounting');
updateNamespace('app/Models/Expenses/Payment.php', 'App\Models\Expenses');
updateNamespace('app/Models/Expenses/PaymentInvoice.php', 'App\Models\Expenses');
updateNamespace('app/Models/Expenses/Vendor.php', 'App\Models\Expenses');
updateNamespace('app/Models/Incomes/Customer.php', 'App\Models\Incomes');
updateNamespace('app/Models/Incomes/Document.php', 'App\Models\Incomes');
updateNamespace('app/Models/Settings/BankAccount.php', 'App\Models\Settings');
updateNamespace('app/Models/Settings/User.php', 'App\Models\Settings');

