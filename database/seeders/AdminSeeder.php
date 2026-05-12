<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admins = [
            [
                'name' => 'Duc Tran',
                'email' => 'bluefoxna@gmail.com',
                'password' => \Illuminate\Support\Facades\Hash::make('Anhduc56348@'),
                'avatar' => '/default_avatar.jpg',
                'email_verified_at' => now(),
                'status' => 1,
            ],
            [
                'name' => 'Duck Tran',
                'email' => 'grayfoxna@gmail.com',
                'password' => \Illuminate\Support\Facades\Hash::make('Anhduc56348@'),
                'avatar' => '/default_avatar.jpg',
                'email_verified_at' => now(),
                'status' => 1,
            ],
            [
                'name' => 'Tran Anh Duc',
                'email' => 'greenfoxna@gmail.com',
                'password' => \Illuminate\Support\Facades\Hash::make('Anhduc56348@'),
                'avatar' => '/default_avatar.jpg',
                'email_verified_at' => now(),
                'status' => 1,
            ],
        ];

        $superAdminRole = \App\Models\Role::where('name', 'super_admin')->first();

        foreach ($admins as $adminData) {
            $admin = \App\Models\Admin::updateOrCreate(
                ['email' => $adminData['email']],
                $adminData
            );

            if ($superAdminRole && $adminData['email'] === 'bluefoxna@gmail.com') {
                $admin->roles()->sync([$superAdminRole->id]);
            }
        }
    }
}
