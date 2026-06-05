<?php
// update_coa.php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Account;
use Illuminate\Support\Facades\DB;

DB::transaction(function() {
    $accounts = Account::withoutGlobalScopes()->get();
    $count = 0;
    foreach($accounts as $acc) {
        $code = $acc->code;
        $newCode = $code;
        
        if (preg_match('/^(\d)-(\d{4})$/', $code, $matches)) {
            // e.g. 1-1000 -> 110000
            // $matches[1] is 1, $matches[2] is 1000
            // $matches[2][0] is 1, substr($matches[2], 1) is 000
            $newCode = $matches[1] . $matches[2][0] . '0' . substr($matches[2], 1);
        } elseif (preg_match('/^1-2000(\d+)$/', $code, $matches)) {
            // e.g. 1-20001382 -> 121382
            $seq = (int)$matches[1];
            $newCode = '12' . str_pad($seq, 4, '0', STR_PAD_LEFT);
        }

        if ($newCode !== $code) {
            $acc->code = $newCode;
            $acc->save();
            $count++;
            // echo "Updated $code -> $newCode\n"; // hide output for speed
        }
    }
    echo "Updated $count accounts format.\n";
});
