<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Category::firstOrCreate([
            'name' => 'Kompensasi & Benefit',
        ]);

        Category::firstOrCreate([
            'name' => 'Lingkungan Kerja',
        ]);

        Category::firstOrCreate([
            'name' => 'Hubungan Antar Karyawan',
        ]);

        Category::firstOrCreate([
            'name' => 'Kebijakan Perusahaan',
        ]);

        Category::firstOrCreate([
            'name' => 'Pengembangan Karir',
        ]);
    }
}
