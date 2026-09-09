<?php

namespace Tests\Feature\Complaint;

use App\Enums\FeedbackOutcome;
use App\Livewire\Complaint\ComplaintShow;
use App\Models\Category;
use App\Models\Complaint;
use App\Models\Priority;
use App\Models\User;
use App\Services\ComplaintAnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ComplaintFeedbackWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Priority $priority;
    private User $employee;
    private User $hrd;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'karyawan', 'supervisor', 'hrd', 'pimpinan'] as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }

        $this->category = Category::create([
            'name' => 'Feedback Test',
            'description' => 'Feedback workflow category',
        ]);

        $this->priority = Priority::create([
            'name' => 'Feedback Priority',
            'level' => 70,
            'response_minutes' => 60,
            'resolution_minutes' => 240,
        ]);

        $this->employee = $this->userWithRole('karyawan');
        $this->hrd = $this->userWithRole('hrd');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_reporter_can_submit_one_feedback_for_resolved_complaint(): void
    {
        $complaint = $this->resolvedComplaint();

        Livewire::actingAs($this->employee)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->set('feedback_outcome', FeedbackOutcome::Accepted->value)
            ->set('feedback_rating', 5)
            ->set('feedback_comment', 'The recorded resolution answers my complaint.')
            ->call('submitFeedback')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('complaint_feedback', [
            'complaint_id' => $complaint->id,
            'submitted_by' => $this->employee->id,
            'outcome' => FeedbackOutcome::Accepted->value,
            'satisfaction_rating' => 5,
        ]);

        $this->assertDatabaseHas('complaint_histories', [
            'complaint_id' => $complaint->id,
            'type' => 'employee_feedback_submitted',
        ]);

        Livewire::actingAs($this->employee)
            ->test(ComplaintShow::class, ['complaint' => $complaint->fresh()])
            ->set('feedback_outcome', FeedbackOutcome::Accepted->value)
            ->set('feedback_rating', 4)
            ->call('submitFeedback')
            ->assertForbidden();

        $this->assertDatabaseCount('complaint_feedback', 1);
    }

    public function test_non_reporter_management_user_cannot_submit_employee_feedback(): void
    {
        $complaint = $this->resolvedComplaint();
        $admin = $this->userWithRole('admin');

        Livewire::actingAs($admin)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->set('feedback_outcome', FeedbackOutcome::Accepted->value)
            ->set('feedback_rating', 5)
            ->call('submitFeedback')
            ->assertForbidden();

        $this->assertDatabaseCount('complaint_feedback', 0);
    }

    public function test_needs_follow_up_feedback_requires_explanation(): void
    {
        $complaint = $this->resolvedComplaint();

        Livewire::actingAs($this->employee)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->set('feedback_outcome', FeedbackOutcome::NeedsFollowUp->value)
            ->set('feedback_rating', 2)
            ->call('submitFeedback')
            ->assertHasErrors(['feedback_comment']);

        $this->assertDatabaseCount('complaint_feedback', 0);
    }

    public function test_feedback_rating_must_be_between_one_and_five(): void
    {
        $complaint = $this->resolvedComplaint();

        Livewire::actingAs($this->employee)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->set('feedback_outcome', FeedbackOutcome::Accepted->value)
            ->set('feedback_rating', 6)
            ->call('submitFeedback')
            ->assertHasErrors(['feedback_rating']);

        $this->assertDatabaseCount('complaint_feedback', 0);
    }

    public function test_needs_follow_up_feedback_blocks_closure_until_assigned_hrd_addresses_it(): void
    {
        $complaint = $this->resolvedComplaint();

        Livewire::actingAs($this->employee)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->set('feedback_outcome', FeedbackOutcome::NeedsFollowUp->value)
            ->set('feedback_rating', 2)
            ->set('feedback_comment', 'The coordination issue is still affecting the daily process.')
            ->call('submitFeedback')
            ->assertHasNoErrors();

        Livewire::actingAs($this->hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint->fresh()])
            ->call('closeComplaint')
            ->assertForbidden();

        Livewire::actingAs($this->hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint->fresh()])
            ->set(
                'feedback_address_note',
                'The assigned HRD coordinated the remaining handoff issue and confirmed the corrected flow.'
            )
            ->call('addressFeedback')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('complaint_feedback', [
            'complaint_id' => $complaint->id,
            'addressed_by' => $this->hrd->id,
        ]);

        $feedback = $complaint->fresh()->feedback;

        $this->assertNotNull($feedback->addressed_at);

        Livewire::actingAs($this->hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint->fresh()])
            ->call('closeComplaint')
            ->assertHasNoErrors();

        $this->assertSame('closed', $complaint->fresh()->status);
        $this->assertDatabaseHas('complaint_histories', [
            'complaint_id' => $complaint->id,
            'type' => 'employee_feedback_addressed',
        ]);
    }

    public function test_closing_without_feedback_requires_persistent_reason(): void
    {
        $complaint = $this->resolvedComplaint();

        Livewire::actingAs($this->hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->call('closeComplaint')
            ->assertHasErrors(['closed_without_feedback_reason']);

        $this->assertSame('resolved', $complaint->fresh()->status);

        $reason = 'The reporter has not submitted feedback, while the verified resolution and required follow-up are complete.';

        Livewire::actingAs($this->hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint->fresh()])
            ->set('closed_without_feedback_reason', $reason)
            ->call('closeComplaint')
            ->assertHasNoErrors();

        $closed = $complaint->fresh();

        $this->assertSame('closed', $closed->status);
        $this->assertSame($reason, $closed->closed_without_feedback_reason);

        $this->assertDatabaseHas('complaint_histories', [
            'complaint_id' => $complaint->id,
            'type' => 'closed',
            'description' => 'Complaint closed without employee feedback; closure reason recorded',
        ]);
    }

    public function test_accepted_feedback_allows_closure_without_override_reason(): void
    {
        $complaint = $this->resolvedComplaint();

        Livewire::actingAs($this->employee)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->set('feedback_outcome', FeedbackOutcome::Accepted->value)
            ->set('feedback_rating', 4)
            ->set('feedback_comment', 'The resolution is clear and acceptable.')
            ->call('submitFeedback')
            ->assertHasNoErrors();

        Livewire::actingAs($this->hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint->fresh()])
            ->call('closeComplaint')
            ->assertHasNoErrors();

        $closed = $complaint->fresh();

        $this->assertSame('closed', $closed->status);
        $this->assertNull($closed->closed_without_feedback_reason);
    }

    public function test_feedback_metrics_measure_coverage_satisfaction_and_pending_follow_up(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-09 12:00:00'));

        $accepted = $this->resolvedComplaint('CMP-FEEDBACK-A', Carbon::parse('2026-09-03 08:00:00'));
        $needsFollowUp = $this->resolvedComplaint('CMP-FEEDBACK-B', Carbon::parse('2026-09-04 08:00:00'));
        $closedNoFeedback = $this->resolvedComplaint('CMP-FEEDBACK-C', Carbon::parse('2026-09-05 08:00:00'));

        $accepted->feedback()->create([
            'submitted_by' => $this->employee->id,
            'outcome' => FeedbackOutcome::Accepted->value,
            'satisfaction_rating' => 5,
            'comment' => 'Accepted.',
        ]);

        $needsFollowUp->feedback()->create([
            'submitted_by' => $this->employee->id,
            'outcome' => FeedbackOutcome::NeedsFollowUp->value,
            'satisfaction_rating' => 2,
            'comment' => 'Further coordination is required.',
        ]);

        $closedNoFeedback->update([
            'status' => 'closed',
            'closed_at' => Carbon::parse('2026-09-05 10:00:00'),
            'closed_without_feedback_reason' => 'The resolution was verified but no employee feedback was submitted.',
        ]);

        $report = app(ComplaintAnalyticsService::class)->build(
            Carbon::parse('2026-09-01'),
            Carbon::parse('2026-09-30')
        );

        $this->assertSame(3, $report['feedback']['eligibleComplaints']);
        $this->assertSame(2, $report['feedback']['submitted']);
        $this->assertSame(66.7, $report['feedback']['coverageRate']);
        $this->assertSame(3.5, $report['feedback']['averageSatisfaction']);
        $this->assertSame(1, $report['feedback']['accepted']);
        $this->assertSame(1, $report['feedback']['needsFollowUp']);
        $this->assertSame(0, $report['feedback']['addressed']);
        $this->assertSame(1, $report['feedback']['pending']);
        $this->assertSame(1, $report['feedback']['closedWithoutFeedback']);
    }

    private function resolvedComplaint(
        string $ticket = 'CMP-FEEDBACK-TEST',
        ?Carbon $createdAt = null
    ): Complaint {
        $createdAt ??= now()->copy()->subHours(2);

        $complaint = Complaint::create([
            'ticket_number' => $ticket,
            'reporter_id' => $this->employee->id,
            'assigned_to' => $this->hrd->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'title' => 'Resolution feedback complaint',
            'description' => 'Complaint used to verify employee feedback and resolution confirmation governance.',
            'root_cause_category' => 'cross_function_coordination',
            'root_cause' => 'The cross-functional handoff was unclear and caused repeated operational delays.',
            'resolution_summary' => 'The handoff flow and assigned responsibilities were clarified and implemented.',
            'follow_up_required' => false,
            'status' => 'resolved',
            'first_response_at' => $createdAt->copy()->addMinutes(30),
            'resolved_at' => $createdAt->copy()->addMinutes(90),
        ]);

        $complaint->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
            'response_due_at' => $createdAt->copy()->addMinutes(60),
            'resolution_due_at' => $createdAt->copy()->addMinutes(240),
        ])->save();

        return $complaint->fresh();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }
}
