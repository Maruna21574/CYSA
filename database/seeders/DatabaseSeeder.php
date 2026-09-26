<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Demo data must not be seeded in production. Use "php artisan app:create-super-admin" instead.');
        }

        $this->call([DemoSeeder::class, DemoCourseSeeder::class]);
    }
}
