<?php

namespace Database\Seeders;

use App\Models\Priority;
use Illuminate\Database\Seeder;

class PrioritySeeder extends Seeder
{
    public function run(): void
    {
        $priorities = [
            ['name' => 'Low', 'level' => 1, 'response_minutes' => 480, 'resolution_minutes' => 4320],
            ['name' => 'Medium', 'level' => 2, 'response_minutes' => 240, 'resolution_minutes' => 2880],
            ['name' => 'High', 'level' => 3, 'response_minutes' => 120, 'resolution_minutes' => 1440],
            ['name' => 'Critical', 'level' => 4, 'response_minutes' => 30, 'resolution_minutes' => 480],
        ];

        foreach ($priorities as $priority) {
            Priority::updateOrCreate(
                ['name' => $priority['name']],
                $priority
            );
        }
    }
}
