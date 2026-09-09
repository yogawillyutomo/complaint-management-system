<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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

        $slaDefaults = [
            'Low' => [480, 4320],
            'Medium' => [240, 2880],
            'High' => [120, 1440],
            'Critical' => [30, 480],
        ];

        foreach ($slaDefaults as $name => [$responseMinutes, $resolutionMinutes]) {
            DB::table('priorities')
                ->where('name', $name)
                ->update([
                    'response_minutes' => $responseMinutes,
                    'resolution_minutes' => $resolutionMinutes,
                ]);
        }

        $priorities = DB::table('priorities')
            ->get()
            ->keyBy('id');

        DB::table('complaints')
            ->orderBy('id')
            ->chunkById(100, function ($complaints) use ($priorities): void {
                foreach ($complaints as $complaint) {
                    $priority = $priorities->get($complaint->priority_id);

                    if (! $priority || ! $complaint->created_at) {
                        continue;
                    }

                    $createdAt = Carbon::parse($complaint->created_at);

                    $firstResponseAt = DB::table('complaint_histories')
                        ->where('complaint_id', $complaint->id)
                        ->whereIn('type', ['validated', 'rejected'])
                        ->orderBy('created_at')
                        ->value('created_at');

                    DB::table('complaints')
                        ->where('id', $complaint->id)
                        ->update([
                            'first_response_at' => $firstResponseAt,
                            'response_due_at' => $createdAt
                                ->copy()
                                ->addMinutes((int) $priority->response_minutes),
                            'resolution_due_at' => $createdAt
                                ->copy()
                                ->addMinutes((int) $priority->resolution_minutes),
                        ]);
                }
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
