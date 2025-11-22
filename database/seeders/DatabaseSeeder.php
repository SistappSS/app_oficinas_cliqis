<?php

namespace Database\Seeders;

use Database\Seeders\Users\RolePerGroupSeeder;
use Database\Seeders\Users\UserSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            RolePerGroupSeeder::class
        ]);
    }
}
