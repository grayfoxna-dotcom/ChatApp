<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::updateOrCreate(
            ['email' => 'bluefoxna@gmail.com'],
            [
                'name' => 'Duc Tran',
                'password' => \Illuminate\Support\Facades\Hash::make('Anhduc56348@'),
                'avatar' => '/default_avatar.jpg',
                'email_verified_at' => now(),
                'isActive' => 1,
            ]
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'grayfoxna@gmail.com'],
            [
                'name' => 'Duck Tran',
                'password' => \Illuminate\Support\Facades\Hash::make('Anhduc56348@'),
                'avatar' => '/default_avatar.jpg',
                'email_verified_at' => now(),
                'isActive' => 1,
            ]
        );

        // Tạo thêm 10 người dùng mẫu
        $names = [
            'Minh Anh', 'Thanh Hà', 'Quang Vinh', 'Hoàng Yến', 'Bá Thịnh',
            'Linh Chi', 'Nam Khánh', 'Thùy Dương', 'Trần Đức', 'Phóng Dev'
        ];

        foreach ($names as $index => $name) {
            $email = \Illuminate\Support\Str::slug($name, '') . '@gmail.com';
            \App\Models\User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => \Illuminate\Support\Facades\Hash::make('Anhduc56348@'),
                    'avatar' => '/default_avatar.jpg',
                    'email_verified_at' => now(),
                    'isActive' => 1,
                ]
            );
        }
    }
}
