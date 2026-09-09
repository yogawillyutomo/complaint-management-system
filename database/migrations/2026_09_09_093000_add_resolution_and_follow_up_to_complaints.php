<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->string('root_cause_category', 64)->nullable()->index()->after('description');
            $table->text('root_cause')->nullable()->after('root_cause_category');
            $table->text('resolution_summary')->nullable()->after('root_cause');
            $table->text('resolution_evidence')->nullable()->after('resolution_summary');
            $table->boolean('follow_up_required')->default(false)->after('resolution_evidence');
            $table->timestamp('follow_up_due_at')->nullable()->after('follow_up_required');
            $table->timestamp('follow_up_completed_at')->nullable()->after('follow_up_due_at');
            $table->text('follow_up_note')->nullable()->after('follow_up_completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropColumn([
                'root_cause_category',
                'root_cause',
                'resolution_summary',
                'resolution_evidence',
                'follow_up_required',
                'follow_up_due_at',
                'follow_up_completed_at',
                'follow_up_note',
            ]);
        });
    }
};
