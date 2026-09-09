<?php

namespace Tests\Feature\Database;

use App\Enums\ComplaintStatus;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Complaint;
use App\Models\ComplaintHistory;
use App\Models\Priority;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class ComplaintDatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_canonical_statuses_are_accepted_by_the_model_and_database(): void
    {
        $reporter = User::factory()->create();
        $category = Category::create([
            'name' => 'Status Contract',
            'description' => 'Status parity test',
        ]);
        $priority = Priority::create([
            'name' => 'Status Priority',
            'level' => 99,
        ]);

        foreach (ComplaintStatus::cases() as $index => $status) {
            $complaint = Complaint::create([
                'ticket_number' => 'CMP-STATUS-' . $index,
                'reporter_id' => $reporter->id,
                'category_id' => $category->id,
                'priority_id' => $priority->id,
                'title' => 'Status contract test',
                'description' => 'Verifies the canonical status contract across database drivers.',
                'status' => $status->value,
            ]);

            $this->assertSame($status->value, $complaint->fresh()->status);
        }
    }

    public function test_unknown_status_is_rejected_before_persistence(): void
    {
        $reporter = User::factory()->create();
        $category = Category::create([
            'name' => 'Invalid Status',
            'description' => 'Invalid status test',
        ]);
        $priority = Priority::create([
            'name' => 'Invalid Status Priority',
            'level' => 98,
        ]);

        $this->expectException(InvalidArgumentException::class);

        Complaint::create([
            'ticket_number' => 'CMP-INVALID-STATUS',
            'reporter_id' => $reporter->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'title' => 'Invalid status attempt',
            'description' => 'An invalid complaint status must never be persisted.',
            'status' => 'escalated',
        ]);
    }

    public function test_mysql_restricts_deletion_of_records_referenced_by_complaint_evidence(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('MySQL foreign-key integrity contract.');
        }

        $reporter = User::factory()->create();
        $handler = User::factory()->create();
        $historyActor = User::factory()->create();
        $commentActor = User::factory()->create();

        $category = Category::create([
            'name' => 'Retention Category',
            'description' => 'Retention FK test',
        ]);

        $priority = Priority::create([
            'name' => 'Retention Priority',
            'level' => 97,
        ]);

        $complaint = Complaint::create([
            'ticket_number' => 'CMP-RETENTION-FK',
            'reporter_id' => $reporter->id,
            'assigned_to' => $handler->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'title' => 'Retention constraint test',
            'description' => 'Referenced records must not be deletable in the production database.',
            'status' => ComplaintStatus::Assigned->value,
        ]);

        ComplaintHistory::create([
            'complaint_id' => $complaint->id,
            'user_id' => $historyActor->id,
            'type' => 'assigned',
            'description' => 'Retention history',
        ]);

        Comment::create([
            'complaint_id' => $complaint->id,
            'user_id' => $commentActor->id,
            'message' => 'Retention comment',
        ]);

        $this->assertRestrictedDelete('users', $reporter->id);
        $this->assertRestrictedDelete('users', $handler->id);
        $this->assertRestrictedDelete('users', $historyActor->id);
        $this->assertRestrictedDelete('users', $commentActor->id);
        $this->assertRestrictedDelete('categories', $category->id);
        $this->assertRestrictedDelete('priorities', $priority->id);

        $this->assertDatabaseHas('complaints', ['id' => $complaint->id]);
        $this->assertDatabaseHas('complaint_histories', ['complaint_id' => $complaint->id]);
        $this->assertDatabaseHas('comments', ['complaint_id' => $complaint->id]);
    }

    private function assertRestrictedDelete(string $table, int $id): void
    {
        try {
            DB::table($table)->where('id', $id)->delete();
            $this->fail("Expected delete on {$table} to be restricted.");
        } catch (QueryException) {
            $this->assertDatabaseHas($table, ['id' => $id]);
        }
    }
}
