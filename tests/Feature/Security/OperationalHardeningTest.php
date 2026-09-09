<?php

namespace Tests\Feature\Security;

use App\Models\Category;
use App\Models\Complaint;
use App\Models\ComplaintHistory;
use App\Models\Priority;
use App\Models\User;
use App\Services\MutationRateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use LogicException;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Tests\TestCase;

class OperationalHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_browser_security_headers_are_applied(): void
    {
        $response = $this->get('/login');

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'same-origin')
            ->assertHeader(
                'Permissions-Policy',
                'camera=(), microphone=(), geolocation=(), payment=()'
            );
    }

    public function test_complaint_history_is_append_only_through_application_model(): void
    {
        $history = $this->historyFixture();

        try {
            $history->update([
                'description' => 'Rewritten audit record',
            ]);

            $this->fail('Updating complaint history should be rejected.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString(
                'append-only',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas('complaint_histories', [
            'id' => $history->id,
            'description' => 'Original audit record',
        ]);

        $this->expectException(LogicException::class);

        $history->fresh()->delete();
    }

    public function test_notification_pruning_removes_only_old_read_notifications(): void
    {
        $user = User::factory()->create();

        $oldRead = $this->notificationRow(
            $user,
            now()->subDays(120),
            now()->subDays(100)
        );

        $oldUnread = $this->notificationRow(
            $user,
            now()->subDays(150),
            null
        );

        $recentRead = $this->notificationRow(
            $user,
            now()->subDays(20),
            now()->subDays(10)
        );

        Artisan::call('notifications:prune', [
            '--days' => 90,
        ]);

        $this->assertDatabaseMissing('notifications', [
            'id' => $oldRead,
        ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $oldUnread,
        ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $recentRead,
        ]);
    }

    public function test_mutation_rate_limiter_rejects_requests_above_limit(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $limiter = app(MutationRateLimiter::class);

        $limiter->consume('hardening-test', 2, 60);
        $limiter->consume('hardening-test', 2, 60);

        $this->expectException(TooManyRequestsHttpException::class);

        $limiter->consume('hardening-test', 2, 60);
    }

    public function test_operational_indexes_exist_for_action_center_and_retention_queries(): void
    {
        $complaintIndexes = collect(Schema::getIndexes('complaints'))
            ->pluck('name');

        $this->assertContains(
            'complaints_assigned_status_idx',
            $complaintIndexes
        );

        $this->assertContains(
            'complaints_reporter_status_idx',
            $complaintIndexes
        );

        $this->assertContains(
            'complaints_status_resolution_due_idx',
            $complaintIndexes
        );

        $this->assertContains(
            'complaints_follow_up_queue_idx',
            $complaintIndexes
        );

        $notificationIndexes = collect(Schema::getIndexes('notifications'))
            ->pluck('name');

        $this->assertContains(
            'notifications_retention_idx',
            $notificationIndexes
        );
    }

    private function historyFixture(): ComplaintHistory
    {
        Role::firstOrCreate([
            'name' => 'karyawan',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create();
        $user->assignRole('karyawan');

        $category = Category::create([
            'name' => 'Audit Security',
            'description' => 'Audit hardening category',
        ]);

        $priority = Priority::create([
            'name' => 'Audit Priority',
            'level' => 91,
            'response_minutes' => 60,
            'resolution_minutes' => 240,
        ]);

        $complaint = Complaint::create([
            'ticket_number' => 'CMP-AUDIT-' . Str::upper((string) Str::ulid()),
            'reporter_id' => $user->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'title' => 'Audit history hardening complaint',
            'description' => 'Complaint fixture for append-only audit verification.',
            'status' => 'submitted',
        ]);

        return ComplaintHistory::create([
            'complaint_id' => $complaint->id,
            'user_id' => $user->id,
            'type' => 'created',
            'description' => 'Original audit record',
        ]);
    }

    private function notificationRow(
        User $user,
        $createdAt,
        $readAt
    ): string {
        $id = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $id,
            'type' => 'App\\Notifications\\ComplaintActivityNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'title' => 'Retention fixture',
            ]),
            'read_at' => $readAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        return $id;
    }
}
