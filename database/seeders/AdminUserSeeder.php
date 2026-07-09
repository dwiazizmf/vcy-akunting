<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Settings\User;
use App\Models\Settings\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan role super-admin sudah ada
        $superAdminRole = Role::where('name', 'super-admin')->first();

        if (!$superAdminRole) {
            $this->command->error('Role super-admin belum ada. Jalankan RolePermissionSeeder terlebih dahulu.');
            return;
        }

        // Buat user admin default
        $admin = User::updateOrCreate(
            ['email' => 'admin@vcy.test'],
            [
                'name'     => 'Super Admin',
                'username' => 'admin',
                'password' => Hash::make('password123'),
            ]
        );

        $admin->syncRoles(['super-admin']);

        $this->command->info("✅ Admin user [{$admin->email}] ready with role [super-admin].");
        $this->command->warn('   Password: password123 — Ganti segera setelah login pertama!');
    }
}
