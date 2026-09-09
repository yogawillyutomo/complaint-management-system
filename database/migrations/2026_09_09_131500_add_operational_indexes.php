<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->index(
                ['assigned_to', 'status'],
                'complaints_assigned_status_idx'
            );

            $table->index(
                ['reporter_id', 'status'],
                'complaints_reporter_status_idx'
            );

            $table->index(
                ['status', 'response_due_at'],
                'complaints_status_response_due_idx'
            );

            $table->index(
                ['status', 'resolution_due_at'],
                'complaints_status_resolution_due_idx'
            );

            $table->index(
                ['status', 'follow_up_required', 'follow_up_due_at'],
                'complaints_follow_up_queue_idx'
            );
        });

        Schema::table('complaint_histories', function (Blueprint $table) {
            $table->index(
                ['complaint_id', 'created_at'],
                'complaint_histories_timeline_idx'
            );
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->index(
                ['complaint_id', 'created_at'],
                'comments_timeline_idx'
            );
        });

        Schema::table('complaint_escalations', function (Blueprint $table) {
            $table->index(
                ['escalated_to', 'status'],
                'complaint_escalations_target_status_idx'
            );
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(
                ['read_at', 'created_at'],
                'notifications_retention_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_retention_idx');
        });

        Schema::table('complaint_escalations', function (Blueprint $table) {
            $table->dropIndex('complaint_escalations_target_status_idx');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_timeline_idx');
        });

        Schema::table('complaint_histories', function (Blueprint $table) {
            $table->dropIndex('complaint_histories_timeline_idx');
        });

        Schema::table('complaints', function (Blueprint $table) {
            $table->dropIndex('complaints_assigned_status_idx');
            $table->dropIndex('complaints_reporter_status_idx');
            $table->dropIndex('complaints_status_response_due_idx');
            $table->dropIndex('complaints_status_resolution_due_idx');
            $table->dropIndex('complaints_follow_up_queue_idx');
        });
    }
};
