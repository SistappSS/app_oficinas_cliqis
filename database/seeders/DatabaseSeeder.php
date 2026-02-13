<?php

namespace Database\Seeders;

use Database\Seeders\Modules\ModuleSeeder;
use Database\Seeders\Stock\StockMovementReasonSeeder;
use Database\Seeders\Users\UserSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            StockMovementReasonSeeder::class,
            ModuleSeeder::class
        ]);
    }
}
