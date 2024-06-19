<?php

namespace Database\Seeders;

use App\Models\Presentation;
use App\Models\User;
use App\Models\SlideType;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\AdminSeeder;
use Database\Seeders\SlideThemeSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            SlideTypeSeeder::class,
            SlideThemeSeeder::class,
            UserSeeder::class,
            AdminSeeder::class,
            PresentationSeeder::class
        ]);
    }
}
