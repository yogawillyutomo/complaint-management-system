<?php

namespace Tests\Feature\ActionCenter;

use App\Enums\EscalationLevel;
use App\Enums\EscalationReason;
use App\Enums\FeedbackOutcome;
use App\Livewire\Complaint\ComplaintShow;
use App\Livewire\Notification\NotificationBell;
use App\Models\Category;
use App\Models\Complaint;
use App\Models\Priority;
use App\Models\User;
use App\Services\ActionCenterService;
use App\Services\ComplaintNotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActionCenterNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Priority $priority;
    private User $employee;
    private User $supervisor;
    private User $hrd;
    private User $pimpinan;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-09-09 12:00:00'));

        foreach (['admin', 'karyawan', 'supervisor', 'hrd', 'pimpinan'] as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }

        $this->category = Category::create([
            'name' => 'Action Center Test',
            'description' => 'Action center and notification test category',
        ]);

        $this->priority = Priority::create([
            'name' => 'Critical',
            'level' => 4,
            'response_minutes' => 30,
            'resolution_minutes' => 120,
        ]);

        $this->employee = $this->userWithRole('karyawan');
        $this->supervisor = $this->userWithRole('supervisor');
        $this->hrd = $this->userWithRole('hrd');
        $this->pimpinan = $this->userWithRole('pimpinan');
        $this->admin = $this->userWithRole('admin');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_action_center_route_is_available_to_authenticated_roles(): void
    {
        foreach ([
            $this->employee,
            $this->supervisor,
            $this->hrd,
            $this->pimpinan,
            $this->admin,
        ] as $user) {
            $this->actingAs($user)
                ->get('/action-center')
                ->assertOk();
        }
    }

    public function test_employee_actions_are_derived_from_current_complaint_state(): void
    {
        $waiting = $this->complaint('CMP-ACTION-EMP-1', 'waiting_input', $this->hrd);
        $resolved = $this->complaint('CMP-ACTION-EMP-2', 'resolved', $this->hrd);
        $accepted = $this->complaint('CMP-ACTION-EMP-3', 'resolved', $this->hrd);

        $accepted->feedback()->create([
            'submitted_by' => $this->employee->id,
            'outcome' => FeedbackOutcome::Accepted->value,
            'satisfaction_rating' => 5,
            'comment' => 'Accepted.',
        ]);

        $items = app(ActionCenterService::class)->forUser($this->employee);

        $this->assertTrue($items->contains(
            fn (array $item) => $item['key'] === "employee-input:{$waiting->id}"
        ));

        $this->assertTrue($items->contains(
            fn (array $item) => $item['key'] === "employee-feedback:{$resolved->id}"
        ));

        $this->assertFalse($items->contains(
            fn (array $item) => $item['ticket_number'] === $accepted->ticket_number
        ));
    }

    public function test_supervisor_actions_cover_validation_assignment_and_response_breach(): void
    {
        $submitted = $this->complaint('CMP-ACTION-SUP-1', 'submitted');
        $submitted->update([
            'response_due_at' => now()->subMinute(),
        ]);

        $validated = $this->complaint('CMP-ACTION-SUP-2', 'validated');

        $items = app(ActionCenterService::class)->forUser($this->supervisor);

        $validation = $items->firstWhere(
            'key',
            "supervisor-validation:{$submitted->id}"
        );

        $this->assertSame('critical', $validation['urgency']);

        $this->assertTrue($items->contains(
            fn (array $item) => $item['key'] === "supervisor-assign:{$validated->id}"
        ));
    }

    public function test_hrd_actions_cover_assignment_overdue_follow_up_feedback_and_resolved_escalation(): void
    {
        $assigned = $this->complaint('CMP-ACTION-HRD-1', 'assigned', $this->hrd);

        $overdue = $this->complaint('CMP-ACTION-HRD-2', 'in_progress', $this->hrd);
        $overdue->update([
            'resolution_due_at' => now()->subMinute(),
        ]);

        $followUp = $this->complaint('CMP-ACTION-HRD-3', 'resolved', $this->hrd);
        $followUp->update([
            'follow_up_required' => true,
            'follow_up_due_at' => now()->subMinute(),
            'follow_up_completed_at' => null,
        ]);

        $feedback = $this->complaint('CMP-ACTION-HRD-4', 'resolved', $this->hrd);
        $feedback->feedback()->create([
            'submitted_by' => $this->employee->id,
            'outcome' => FeedbackOutcome::NeedsFollowUp->value,
            'satisfaction_rating' => 2,
            'comment' => 'The issue still needs attention.',
        ]);

        $escalated = $this->complaint('CMP-ACTION-HRD-5', 'in_progress', $this->hrd);
        $escalated->escalations()->create([
            'escalated_by' => $this->hrd->id,
            'escalated_to' => $this->pimpinan->id,
            'reason_code' => EscalationReason::AuthorityLimit->value,
            'escalation_level' => EscalationLevel::Leadership->value,
            'note' => 'Leadership decision was required.',
            'status' => 'resolved',
            'resolved_by' => $this->pimpinan->id,
            'resolved_at' => now()->subMinute(),
            'resolution_note' => 'Leadership decision recorded.',
        ]);

        $items = app(ActionCenterService::class)->forUser($this->hrd);
        $keys = $items->pluck('key');

        $this->assertContains("hrd-start:{$assigned->id}", $keys);
        $this->assertContains("hrd-resolution-overdue:{$overdue->id}", $keys);
        $this->assertContains("hrd-follow-up:{$followUp->id}", $keys);
        $this->assertContains("hrd-feedback:{$feedback->id}", $keys);
        $this->assertContains("hrd-escalation-resolved:{$escalated->id}", $keys);
    }

    public function test_leadership_actions_cover_targeted_escalation_and_critical_sla_breach(): void
    {
        $escalated = $this->complaint('CMP-ACTION-LEAD-1', 'in_progress', $this->hrd);
        $escalated->escalations()->create([
            'escalated_by' => $this->hrd->id,
            'escalated_to' => $this->pimpinan->id,
            'reason_code' => EscalationReason::PolicySystem->value,
            'escalation_level' => EscalationLevel::Leadership->value,
            'note' => 'Leadership decision required.',
            'status' => 'active',
        ]);

        $breached = $this->complaint('CMP-ACTION-LEAD-2', 'submitted');
        $breached->update([
            'response_due_at' => now()->subMinute(),
        ]);

        $items = app(ActionCenterService::class)->forUser($this->pimpinan);
        $keys = $items->pluck('key');

        $this->assertContains("leadership-escalation:{$escalated->id}", $keys);
        $this->assertContains("leadership-critical-sla:{$breached->id}", $keys);
    }

    public function test_marking_notification_read_does_not_remove_source_action(): void
    {
        $complaint = $this->complaint(
            'CMP-NOTIFY-INVARIANT',
            'waiting_input',
            $this->hrd
        );

        app(ComplaintNotificationService::class)
            ->notifyReporterWaitingInput($complaint->fresh());

        $notification = $this->employee
            ->notifications()
            ->firstOrFail();

        $this->assertNull($notification->read_at);

        $before = app(ActionCenterService::class)
            ->forUser($this->employee);

        $this->assertTrue($before->contains(
            fn (array $item) => $item['key'] === "employee-input:{$complaint->id}"
        ));

        Livewire::actingAs($this->employee)
            ->test(NotificationBell::class)
            ->call('markRead', $notification->id)
            ->assertHasNoErrors();

        $this->assertNotNull(
            $this->employee->notifications()->findOrFail($notification->id)->read_at
        );

        $after = app(ActionCenterService::class)
            ->forUser($this->employee);

        $this->assertTrue($after->contains(
            fn (array $item) => $item['key'] === "employee-input:{$complaint->id}"
        ));
    }

    public function test_notification_bell_can_mark_all_notifications_read(): void
    {
        $complaint = $this->complaint('CMP-NOTIFY-ALL', 'resolved', $this->hrd);

        $service = app(ComplaintNotificationService::class);

        $service->notifyReporterResolved($complaint->fresh());
        $service->notifyReporterValidated($complaint->fresh());

        $this->assertSame(
            2,
            $this->employee->unreadNotifications()->count()
        );

        Livewire::actingAs($this->employee)
            ->test(NotificationBell::class)
            ->call('markAllRead')
            ->assertHasNoErrors();

        $this->assertSame(
            0,
            $this->employee->unreadNotifications()->count()
        );
    }

    public function test_assignment_workflow_creates_database_notification_for_hrd(): void
    {
        $complaint = $this->complaint('CMP-NOTIFY-ASSIGN', 'validated');

        Livewire::actingAs($this->supervisor)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->set('assigned_to', $this->hrd->id)
            ->call('assignHandler')
            ->assertHasNoErrors();

        $notification = $this->hrd
            ->unreadNotifications()
            ->firstOrFail();

        $this->assertSame(
            'complaint_assigned',
            $notification->data['event_type']
        );

        $this->assertSame(
            $complaint->ticket_number,
            $notification->data['ticket_number']
        );
    }

    public function test_waiting_input_resolution_feedback_and_escalation_events_notify_expected_users(): void
    {
        $inProgress = $this->complaint(
            'CMP-NOTIFY-FLOW',
            'in_progress',
            $this->hrd
        );

        Livewire::actingAs($this->hrd)
            ->test(ComplaintShow::class, ['complaint' => $inProgress])
            ->call('waitingInput')
            ->assertHasNoErrors();

        $this->assertTrue(
            $this->employee->unreadNotifications->contains(
                fn ($notification) => $notification->data['event_type'] === 'waiting_input'
            )
        );

        $inProgress->refresh()->update([
            'status' => 'in_progress',
        ]);

        Livewire::actingAs($this->hrd)
            ->test(ComplaintShow::class, ['complaint' => $inProgress->fresh()])
            ->set('root_cause_category', 'cross_function_coordination')
            ->set('root_cause', 'Cross-functional handoff was unclear and delayed operational handling.')
            ->set('resolution_summary', 'The handoff flow and assigned responsibilities were clarified.')
            ->set('follow_up_required', false)
            ->call('resolveComplaint')
            ->assertHasNoErrors();

        $this->assertTrue(
            $this->employee->fresh()->unreadNotifications->contains(
                fn ($notification) => $notification->data['event_type'] === 'complaint_resolved'
            )
        );

        Livewire::actingAs($this->employee)
            ->test(ComplaintShow::class, ['complaint' => $inProgress->fresh()])
            ->set('feedback_outcome', FeedbackOutcome::NeedsFollowUp->value)
            ->set('feedback_rating', 2)
            ->set('feedback_comment', 'One coordination issue still requires attention from HRD.')
            ->call('submitFeedback')
            ->assertHasNoErrors();

        $this->assertTrue(
            $this->hrd->fresh()->unreadNotifications->contains(
                fn ($notification) => $notification->data['event_type'] === 'feedback_needs_follow_up'
            )
        );

        $escalationComplaint = $this->complaint(
            'CMP-NOTIFY-ESC',
            'in_progress',
            $this->hrd
        );

        Livewire::actingAs($this->hrd)
            ->test(ComplaintShow::class, ['complaint' => $escalationComplaint])
            ->set('escalation_reason_code', EscalationReason::AuthorityLimit->value)
            ->set('escalation_level', EscalationLevel::Leadership->value)
            ->set('escalated_to', $this->pimpinan->id)
            ->set('escalation_note', 'This complaint needs a decision outside the current authority level.')
            ->call('escalateComplaint')
            ->assertHasNoErrors();

        $this->assertTrue(
            $this->pimpinan->fresh()->unreadNotifications->contains(
                fn ($notification) => $notification->data['event_type'] === 'complaint_escalated'
            )
        );
    }

    private function complaint(
        string $ticket,
        string $status,
        ?User $handler = null
    ): Complaint {
        return Complaint::create([
            'ticket_number' => $ticket,
            'reporter_id' => $this->employee->id,
            'assigned_to' => $handler?->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'title' => 'Action Center workflow complaint',
            'description' => 'Complaint fixture for action center and in-app notification verification.',
            'status' => $status,
        ]);
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
