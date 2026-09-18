<?php

namespace Modules\Security\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate([
            'email' => 'admin@gmail.com',
        ], [
            'name'     => 'Super Admin',
            'password' => Hash::make(1234),
            'status'   => 'active',
        ]);
        

        $admin->assignRole('Super Admin');
    }
}
