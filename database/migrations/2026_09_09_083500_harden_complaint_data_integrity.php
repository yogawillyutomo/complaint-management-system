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
            MODIFY status VARCHAR(32) NOT NULL DEFAULT 'submitted'
        ");

        $this->replaceForeignKey('complaints', 'complaints_reporter_id_foreign', 'reporter_id', 'users', 'id', 'RESTRICT');
        $this->replaceForeignKey('complaints', 'complaints_assigned_to_foreign', 'assigned_to', 'users', 'id', 'RESTRICT');
        $this->replaceForeignKey('complaints', 'complaints_category_id_foreign', 'category_id', 'categories', 'id', 'RESTRICT');
        $this->replaceForeignKey('complaints', 'complaints_priority_id_foreign', 'priority_id', 'priorities', 'id', 'RESTRICT');
        $this->replaceForeignKey('complaint_histories', 'complaint_histories_user_id_foreign', 'user_id', 'users', 'id', 'RESTRICT');
        $this->replaceForeignKey('comments', 'comments_user_id_foreign', 'user_id', 'users', 'id', 'RESTRICT');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $this->replaceForeignKey('complaints', 'complaints_reporter_id_foreign', 'reporter_id', 'users', 'id', 'CASCADE');
        $this->replaceForeignKey('complaints', 'complaints_assigned_to_foreign', 'assigned_to', 'users', 'id', 'SET NULL');
        $this->replaceForeignKey('complaints', 'complaints_category_id_foreign', 'category_id', 'categories', 'id', 'CASCADE');
        $this->replaceForeignKey('complaints', 'complaints_priority_id_foreign', 'priority_id', 'priorities', 'id', 'CASCADE');
        $this->replaceForeignKey('complaint_histories', 'complaint_histories_user_id_foreign', 'user_id', 'users', 'id', 'SET NULL');
        $this->replaceForeignKey('comments', 'comments_user_id_foreign', 'user_id', 'users', 'id', 'CASCADE');

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

    private function replaceForeignKey(
        string $table,
        string $constraint,
        string $column,
        string $referencedTable,
        string $referencedColumn,
        string $onDelete
    ): void {
        DB::statement(sprintf(
            'ALTER TABLE %s DROP FOREIGN KEY %s',
            $table,
            $constraint
        ));

        DB::statement(sprintf(
            'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s) ON DELETE %s',
            $table,
            $constraint,
            $column,
            $referencedTable,
            $referencedColumn,
            $onDelete
        ));
    }
};
