<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->text('closed_without_feedback_reason')
                ->nullable()
                ->after('closed_at');
        });

        Schema::create('complaint_feedback', function (Blueprint $table) {
            $table->id();

            $table->foreignId('complaint_id')
                ->unique()
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('submitted_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('outcome', 32);
            $table->unsignedTinyInteger('satisfaction_rating');
            $table->text('comment')->nullable();

            $table->foreignId('addressed_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('addressed_at')->nullable();
            $table->text('address_note')->nullable();

            $table->timestamps();

            $table->index(['outcome', 'addressed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_feedback');

        Schema::table('complaints', function (Blueprint $table) {
            $table->dropColumn('closed_without_feedback_reason');
        });
    }
};
