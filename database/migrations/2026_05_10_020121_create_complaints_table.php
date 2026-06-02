<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();

            $table->string('ticket_number')->unique();

            $table->foreignId('reporter_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('priority_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title');

            $table->longText('description');

            $table->enum('status', [
                'submitted',
                'validated',
                'assigned',
                'in_progress',
                'waiting_input',
                'resolved',
                'closed',
                'rejected',
            ])->default('submitted');

            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
