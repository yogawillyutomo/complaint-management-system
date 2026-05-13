<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE complaints
            MODIFY status ENUM(
                'submitted',
                'reviewed',
                'in_progress',
                'resolved',
                'rejected'
            ) NOT NULL DEFAULT 'submitted'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE complaints
            MODIFY status ENUM(
                'submitted',
                'in_progress',
                'resolved',
                'closed'
            ) NOT NULL DEFAULT 'submitted'
        ");
    }
};
