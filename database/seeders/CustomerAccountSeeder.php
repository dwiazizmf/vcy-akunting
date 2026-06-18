<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Incomes\Customer;
use App\Models\Accounting\Accounting\Account;
use App\Models\Accounting\Accounting\AccountType;
use Illuminate\Support\Facades\DB;

class CustomerAccountSeeder extends Seeder
{
    public function run(): void
    {
        // Get all companies
        $companies = DB::table('companies')->where('enabled', 1)->get();

        foreach ($companies as $company) {
            // Find the "Piutang Usaha" parent account for this company
            $parentAccount = Account::where('company_id', $company->id)
                ->where('enabled', 1)
                ->whereHas('type', fn($q) => $q->where('name', 'Aset Lancar'))
                ->where(function ($q) {
                    $q->where('name', 'like', '%Piutang Usaha%')
                      ->orWhere('name', 'like', '%Piutang%');
                })
                ->whereNull('parent_id')
                ->first();

            if (!$parentAccount) {
                $this->command->warn("Akun Piutang Usaha tidak ditemukan untuk company_id: {$company->id}");
                continue;
            }

            // e.g. "120000" → base = "12"
            $baseCode = substr($parentAccount->code, 0, 2); // e.g. "12"

            // Get customers without account_id for this company
            $customers = Customer::where('company_id', $company->id)
                ->whereNull('account_id')
                ->get();

            if ($customers->isEmpty()) {
                $this->command->info("Semua customer di company_id {$company->id} sudah punya COA.");
                continue;
            }

            // Find current max sequence under this parent
            $existingCodes = Account::where('company_id', $company->id)
                ->where('parent_id', $parentAccount->id)
                ->pluck('code')
                ->toArray();

            // Determine next sequence number
            $maxSeq = 0;
            foreach ($existingCodes as $code) {
                // Extract last numeric part (e.g. from 121382 extract 1382)
                if (preg_match('/^12(\d+)$/', $code, $matches)) {
                    $maxSeq = max($maxSeq, (int)$matches[1]);
                }
            }

            $typeId = $parentAccount->type_id;

            foreach ($customers as $customer) {
                $maxSeq++;
                $newCode = $baseCode . str_pad($maxSeq, 4, '0', STR_PAD_LEFT);

                // Create sub-account COA for this customer
                $account = Account::create([
                    'company_id'  => $company->id,
                    'type_id'     => $typeId,
                    'parent_id'   => $parentAccount->id,
                    'code'        => $newCode,
                    'name'        => $customer->name,
                    'description' => 'Piutang - ' . $customer->name,
                    'system'      => false,
                    'enabled'     => true,
                ]);

                // Link customer to this account
                $customer->update(['account_id' => $account->id]);

                $this->command->line("  ✓ [{$newCode}] {$customer->name}");
            }

            $this->command->info("Company {$company->id}: {$customers->count()} customer berhasil di-assign COA.");
        }
    }
}
