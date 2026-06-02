<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE complaints
            MODIFY status ENUM(
                'submitted',
                'validated',
                'assigned',
                'in_progress',
                'waiting_input',
                'resolved',
                'closed',
                'rejected',
                'escalated'
            ) NOT NULL DEFAULT 'submitted'
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE complaints
            MODIFY status ENUM(
                'submitted',
                'validated',
                'assigned',
                'in_progress',
                'resolved',
                'closed',
                'rejected',
                'escalated'
            ) NOT NULL DEFAULT 'submitted'
        ");
    }
};
