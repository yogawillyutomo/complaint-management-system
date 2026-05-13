<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Priority;

class PrioritySeeder extends Seeder
{
    public function run(): void
    {
        Priority::firstOrCreate([
            'name' => 'Low',
            'level' => 1,
        ]);

        Priority::firstOrCreate([
            'name' => 'Medium',
            'level' => 2,
        ]);

        Priority::firstOrCreate([
            'name' => 'High',
            'level' => 3,
        ]);

        Priority::firstOrCreate([
            'name' => 'Critical',
            'level' => 4,
        ]);
    }
}
