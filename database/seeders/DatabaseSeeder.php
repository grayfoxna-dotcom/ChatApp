<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Tự động dọn sạch các tệp tin và thư mục con cũ trong public/uploads khi chạy fresh --seed
        $uploadsDir = public_path('uploads');
        if (File::exists($uploadsDir)) {
            File::cleanDirectory($uploadsDir);
        }

        $this->call([
            RolePermissionSeeder::class,
            AdminSeeder::class,
            UserSeeder::class,
            ConversationSeeder::class,
        ]);
    }
}
