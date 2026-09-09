<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('priorities', function (Blueprint $table) {
            $table->unsignedInteger('response_minutes')->default(480)->after('level');
            $table->unsignedInteger('resolution_minutes')->default(4320)->after('response_minutes');
        });

        Schema::table('complaints', function (Blueprint $table) {
            $table->timestamp('first_response_at')->nullable()->after('status');
            $table->timestamp('response_due_at')->nullable()->index()->after('first_response_at');
            $table->timestamp('resolution_due_at')->nullable()->index()->after('response_due_at');
        });

        Schema::create('complaint_escalations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('complaint_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('escalated_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('escalated_to')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('reason_code', 64);
            $table->string('escalation_level', 64);
            $table->text('note');
            $table->string('status', 32)->default('active')->index();

            $table->foreignId('resolved_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();

            $table->timestamps();

            $table->index(['complaint_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_escalations');

        Schema::table('complaints', function (Blueprint $table) {
            $table->dropIndex(['response_due_at']);
            $table->dropIndex(['resolution_due_at']);
            $table->dropColumn([
                'first_response_at',
                'response_due_at',
                'resolution_due_at',
            ]);
        });

        Schema::table('priorities', function (Blueprint $table) {
            $table->dropColumn([
                'response_minutes',
                'resolution_minutes',
            ]);
        });
    }
};
