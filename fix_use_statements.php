<?php

$replacements = [
    // Controllers
    'App\Http\Controllers\Incomes\DocumentController' => 'App\Http\Controllers\Incomes\DocumentController',
    'App\Http\Controllers\Settings\DiscountController' => 'App\Http\Controllers\Settings\DiscountController',

    // Models
    'App\Models\Accounting\Accounting\Account;' => 'App\Models\Accounting\Accounting\Account;',
    'App\Models\Accounting\Accounting\AccountType;' => 'App\Models\Accounting\Accounting\AccountType;',
    'App\Models\Accounting\Accounting\Journal;' => 'App\Models\Accounting\Accounting\Journal;',
    'App\Models\Accounting\Accounting\Ledger;' => 'App\Models\Accounting\Accounting\Ledger;',
    'App\Models\Expenses\Payment;' => 'App\Models\Expenses\Payment;',
    'App\Models\Expenses\PaymentInvoice;' => 'App\Models\Expenses\PaymentInvoice;',
    'App\Models\Expenses\Vendor;' => 'App\Models\Expenses\Vendor;',
    'App\Models\Incomes\Customer;' => 'App\Models\Incomes\Customer;',
    'App\Models\Incomes\Document;' => 'App\Models\Incomes\Document;',
    'App\Models\Settings\BankAccount;' => 'App\Models\Settings\BankAccount;',
    'App\Models\Settings\User;' => 'App\Models\Settings\User;',
    
    // Sometimes it's inside docblocks or closures, e.g. \App\Models\User
    'App\Models\Accounting\Accounting\Account::' => 'App\Models\Accounting\Accounting\Account::',
    'App\Models\Accounting\Accounting\AccountType::' => 'App\Models\Accounting\Accounting\AccountType::',
    'App\Models\Accounting\Accounting\Journal::' => 'App\Models\Accounting\Accounting\Journal::',
    'App\Models\Accounting\Accounting\Ledger::' => 'App\Models\Accounting\Accounting\Ledger::',
    'App\Models\Expenses\Payment::' => 'App\Models\Expenses\Payment::',
    'App\Models\Expenses\PaymentInvoice::' => 'App\Models\Expenses\PaymentInvoice::',
    'App\Models\Expenses\Vendor::' => 'App\Models\Expenses\Vendor::',
    'App\Models\Incomes\Customer::' => 'App\Models\Incomes\Customer::',
    'App\Models\Incomes\Document::' => 'App\Models\Incomes\Document::',
    'App\Models\Settings\BankAccount::' => 'App\Models\Settings\BankAccount::',
    'App\Models\Settings\User::' => 'App\Models\Settings\User::',
];

$directories = [
    __DIR__ . '/app',
    __DIR__ . '/routes',
    __DIR__ . '/database',
    __DIR__ . '/tests',
];

foreach ($directories as $dir) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            $newContent = str_replace(array_keys($replacements), array_values($replacements), $content);
            if ($content !== $newContent) {
                file_put_contents($file->getPathname(), $newContent);
                echo "Updated uses in " . $file->getPathname() . "\n";
            }
        }
    }
}
