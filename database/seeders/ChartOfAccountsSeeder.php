<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Accounting\AccountType;
use App\Models\Accounting\Account;
use App\Models\Settings\Company;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Aset Lancar', 'category' => 'Asset'],
            ['name' => 'Aset Tetap', 'category' => 'Asset'],
            ['name' => 'Kewajiban Jangka Pendek', 'category' => 'Liability'],
            ['name' => 'Kewajiban Jangka Panjang', 'category' => 'Liability'],
            ['name' => 'Ekuitas', 'category' => 'Equity'],
            ['name' => 'Pendapatan', 'category' => 'Revenue'],
            ['name' => 'Harga Pokok Penjualan', 'category' => 'Expense'],
            ['name' => 'Beban Operasional', 'category' => 'Expense'],
            ['name' => 'Pendapatan Lain-Lain', 'category' => 'Revenue'],
            ['name' => 'Beban Lain-Lain', 'category' => 'Expense'],
        ];

        foreach ($types as $type) {
            AccountType::firstOrCreate(['name' => $type['name']], $type);
        }

        $companies = Company::all();
        if ($companies->isEmpty()) {
            return;
        }

        $assetId = AccountType::where('name', 'Aset Lancar')->first()->id;
        $liabilityId = AccountType::where('name', 'Kewajiban Jangka Pendek')->first()->id;
        $equityId = AccountType::where('name', 'Ekuitas')->first()->id;
        $revenueId = AccountType::where('name', 'Pendapatan')->first()->id;

        foreach ($companies as $company) {
            Account::firstOrCreate([
                'company_id' => $company->id,
                'code' => '110000'
            ], [
                'type_id' => $assetId,
                'name' => 'Kas & Bank',
                'system' => true,
                'enabled' => true,
            ]);

            Account::firstOrCreate([
                'company_id' => $company->id,
                'code' => '120000'
            ], [
                'type_id' => $assetId,
                'name' => 'Piutang Usaha',
                'system' => true,
                'enabled' => true,
            ]);

            Account::firstOrCreate([
                'company_id' => $company->id,
                'code' => '210000'
            ], [
                'type_id' => $liabilityId,
                'name' => 'Hutang Usaha',
                'system' => true,
                'enabled' => true,
            ]);

            Account::firstOrCreate([
                'company_id' => $company->id,
                'code' => '310000'
            ], [
                'type_id' => $equityId,
                'name' => 'Laba Ditahan',
                'system' => true,
                'enabled' => true,
            ]);

            Account::firstOrCreate([
                'company_id' => $company->id,
                'code' => '410000'
            ], [
                'type_id' => $revenueId,
                'name' => 'Pendapatan Penjualan',
                'system' => true,
                'enabled' => true,
            ]);
        }
    }
}
