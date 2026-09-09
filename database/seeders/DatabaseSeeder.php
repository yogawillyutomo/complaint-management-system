<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Complaint;
use App\Models\ComplaintHistory;
use App\Models\Priority;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(PrioritySeeder::class);

        // Demo accounts use known credentials and must never be provisioned in production.
        if (app()->environment('production')) {
            return;
        }

        $users = [
            ['name' => 'Admin', 'email' => 'admin@gmail.com', 'role' => 'admin'],
            ['name' => 'Karyawan Demo', 'email' => 'karyawan@gmail.com', 'role' => 'karyawan'],
            ['name' => 'Supervisor Demo', 'email' => 'supervisor@gmail.com', 'role' => 'supervisor'],
            ['name' => 'HRD Demo', 'email' => 'hrd@gmail.com', 'role' => 'hrd'],
            ['name' => 'Pimpinan Demo', 'email' => 'pimpinan@gmail.com', 'role' => 'pimpinan'],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$userData['role']]);
        }

        $karyawan = User::where('email', 'karyawan@gmail.com')->first();
        $supervisor = User::where('email', 'supervisor@gmail.com')->first();
        $hrd = User::where('email', 'hrd@gmail.com')->first();

        $lingkunganKerja = Category::where('name', 'Lingkungan Kerja')->first();
        $kompensasi = Category::where('name', 'Kompensasi & Benefit')->first();
        $high = Priority::where('name', 'High')->first();
        $medium = Priority::where('name', 'Medium')->first();

        $complaint = Complaint::firstOrCreate(
            ['ticket_number' => 'CMP-DEMO-001'],
            [
                'reporter_id' => $karyawan->id,
                'assigned_to' => $hrd->id,
                'category_id' => $lingkunganKerja->id,
                'priority_id' => $high->id,
                'title' => 'Fasilitas ruang kerja kurang memadai',
                'description' => 'Karyawan membutuhkan tindak lanjut atas kondisi ruang kerja yang kurang nyaman dan menghambat produktivitas.',
                'status' => 'in_progress',
            ]
        );

        ComplaintHistory::firstOrCreate(
            [
                'complaint_id' => $complaint->id,
                'type' => 'created',
            ],
            [
                'user_id' => $karyawan->id,
                'description' => 'Complaint created',
            ]
        );

        ComplaintHistory::firstOrCreate(
            [
                'complaint_id' => $complaint->id,
                'type' => 'validated',
            ],
            [
                'user_id' => $supervisor->id,
                'description' => 'Complaint validated by supervisor',
            ]
        );

        Complaint::firstOrCreate(
            ['ticket_number' => 'CMP-DEMO-002'],
            [
                'reporter_id' => $karyawan->id,
                'category_id' => $kompensasi->id,
                'priority_id' => $medium->id,
                'title' => 'Pertanyaan terkait benefit lembur',
                'description' => 'Karyawan membutuhkan penjelasan terkait mekanisme benefit lembur yang berlaku di unit kerja.',
                'status' => 'submitted',
            ]
        );
    }
}
