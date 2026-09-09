<?php

namespace Tests\Feature\Uat;

use App\Enums\EscalationLevel;
use App\Enums\EscalationReason;
use App\Enums\FeedbackOutcome;
use App\Enums\RootCauseCategory;
use App\Livewire\Complaint\ComplaintIndex;
use App\Livewire\Complaint\ComplaintShow;
use App\Models\Category;
use App\Models\Complaint;
use App\Models\Priority;
use App\Models\User;
use App\Services\ActionCenterService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UatScenarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EndToEndComplaintUatTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Priority $priority;
    private User $employee;
    private User $otherEmployee;
    private User $supervisor;
    private User $hrd;
    private User $otherHrd;
    private User $pimpinan;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-09-09 14:00:00'));

        foreach (['admin', 'karyawan', 'supervisor', 'hrd', 'pimpinan'] as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }

        $this->category = Category::create([
            'name' => 'UAT Process',
            'description' => 'End-to-end UAT category',
        ]);

        $this->priority = Priority::create([
            'name' => 'High',
            'level' => 3,
            'response_minutes' => 120,
            'resolution_minutes' => 1440,
        ]);

        $this->employee = $this->userWithRole('karyawan');
        $this->otherEmployee = $this->userWithRole('karyawan');
        $this->supervisor = $this->userWithRole('supervisor');
        $this->hrd = $this->userWithRole('hrd');
        $this->otherHrd = $this->userWithRole('hrd');
        $this->pimpinan = $this->userWithRole('pimpinan');
        $this->admin = $this->userWithRole('admin');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_full_role_based_complaint_lifecycle_matches_uat_acceptance_flow(): void
    {
        Livewire::actingAs($this->employee)
            ->test(ComplaintIndex::class)
            ->set('title', 'Cross-unit approval delays operational work')
            ->set(
                'description',
                'A cross-unit approval handoff repeatedly delays operational completion and needs formal follow-up.'
            )
            ->set('category_id', $this->category->id)
            ->set('priority_id', $this->priority->id)
            ->call('save')
            ->assertHasNoErrors();

        $complaint = Complaint::latest('id')->firstOrFail();

        $this->assertSame('submitted', $complaint->status);
        $this->assertSame($this->employee->id, $complaint->reporter_id);
        $this->assertTrue(
            $this->supervisor->unreadNotifications->contains(
                fn ($notification) => $notification->data['event_type'] === 'complaint_created'
            )
        );

        $this->actingAs($this->otherEmployee)
            ->get(route('complaints.show', $complaint))
            ->assertForbidden();

        Livewire::actingAs($this->supervisor)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->call('validateComplaint')
            ->assertHasNoErrors();

        $complaint->refresh();
        $this->assertSame('validated', $complaint->status);
        $this->assertNotNull($complaint->first_response_at);

        Livewire::actingAs($this->supervisor)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->set('assigned_to', $this->hrd->id)
            ->call('assignHandler')
            ->assertHasNoErrors();

        $complaint->refresh();
        $this->assertSame('assigned', $complaint->status);
        $this->assertSame($this->hrd->id, $complaint->assigned_to);

        Livewire::actingAs($this->otherHrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->call('startProgress')
            ->assertForbidden();

        Livewire::actingAs($this->hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->call('startProgress')
            ->assertHasNoErrors();

        Livewire::actingAs($this->hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint->fresh()])
            ->call('waitingInput')
            ->assertHasNoErrors();

        $complaint->refresh();
        $this->assertSame('waiting_input', $complaint->status);

        $employeeActions = app(ActionCenterService::class)
            ->forUser($this->employee);

        $this->assertTrue(
            $employeeActions->contains(
                fn (array $item) => $item['key'] === "employee-input:{$complaint->id}"
            )
        );

        Livewire::actingAs($this->employee)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->set(
                'message',
                'The delayed approval occurs during the handoff from the operational unit to the supporting unit.'
            )
            ->call('sendComment')
            ->assertHasNoErrors();

        $complaint->refresh();
        $this->assertSame('in_progress', $complaint->status);

        Livewire::actingAs($this->employee)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->set('escalation_reason_code', EscalationReason::AuthorityLimit->value)
            ->set('escalation_level', EscalationLevel::Leadership->value)
            ->set('escalated_to', $this->pimpinan->id)
            ->set(
                'escalation_note',
                'Employee should not be able to create a leadership escalation.'
            )
            ->call('escalateComplaint')
            ->assertForbidden();

        Livewire::actingAs($this->hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint->fresh()])
            ->set('escalation_reason_code', EscalationReason::AuthorityLimit->value)
            ->set('escalation_level', EscalationLevel::Leadership->value)
            ->set('escalated_to', $this->pimpinan->id)
            ->set(
                'escalation_note',
                'A leadership decision is required because the assigned HRD cannot alter the cross-unit approval authority.'
            )
            ->call('escalateComplaint')
            ->assertHasNoErrors();

        $complaint->refresh();
        $this->assertTrue($complaint->activeEscalation()->exists());

        Livewire::actingAs($this->hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->set('root_cause_category', RootCauseCategory::AdministrativeProcess->value)
            ->set(
                'root_cause',
                'The cross-unit approval chain contains an unclear handoff and unnecessary approval delay.'
            )
            ->set(
                'resolution_summary',
                'The approval path is simplified and the responsible PIC for each handoff is documented.'
            )
            ->set('follow_up_required', true)
            ->set('follow_up_due_at', now()->addDay()->format('Y-m-d\TH:i'))
            ->call('resolveComplaint')
            ->assertForbidden();

        Livewire::actingAs($this->pimpinan)
            ->test(ComplaintShow::class, ['complaint' => $complaint->fresh()])
            ->set(
                'escalation_resolution_note',
                'Leadership approved the simplified handoff and confirmed the responsible unit for final approval.'
            )
            ->call('resolveEscalation')
            ->assertHasNoErrors();

        $complaint->refresh();
        $this->assertFalse($complaint->activeEscalation()->exists());

        Livewire::actingAs($this->hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->set('root_cause_category', RootCauseCategory::AdministrativeProcess->value)
            ->set(
                'root_cause',
                'The cross-unit approval chain contains an unclear handoff and unnecessary approval delay.'
            )
            ->set(
                'resolution_summary',
                'The approval path is simplified and the responsible PIC for each handoff is documented.'
            )
            ->set(
                'resolution_evidence',
                'UAT leadership approval note and revised handoff record.'
            )
            ->set('follow_up_required', true)
            ->set('follow_up_due_at', now()->addDay()->format('Y-m-d\TH:i'))
            ->call('resolveComplaint')
            ->assertHasNoErrors();

        $complaint->refresh();
        $this->assertSame('resolved', $complaint->status);
        $this->assertTrue($complaint->follow_up_required);
        $this->assertNull($complaint->follow_up_completed_at);

        Livewire::actingAs($this->employee)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->set('feedback_outcome', FeedbackOutcome::NeedsFollowUp->value)
            ->set('feedback_rating', 2)
            ->set(
                'feedback_comment',
                'The resolution is documented, but I still need confirmation that the new handoff is consistently applied.'
            )
            ->call('submitFeedback')
            ->assertHasNoErrors();

        Livewire::actingAs($this->hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint->fresh()])
            ->call('closeComplaint')
            ->assertForbidden();

        Livewire::actingAs($this->hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint->fresh()])
            ->set(
                'follow_up_note',
                'Monitoring confirms the revised approval handoff was used successfully without recurrence during the UAT observation.'
            )
            ->call('completeFollowUp')
            ->assertHasNoErrors();

        Livewire::actingAs($this->hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint->fresh()])
            ->set(
                'feedback_address_note',
                'HRD confirmed the revised handoff was applied and shared the verification result with the employee.'
            )
            ->call('addressFeedback')
            ->assertHasNoErrors();

        Livewire::actingAs($this->hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint->fresh()])
            ->call('closeComplaint')
            ->assertHasNoErrors();

        $closed = $complaint->fresh();

        $this->assertSame('closed', $closed->status);
        $this->assertNotNull($closed->resolved_at);
        $this->assertNotNull($closed->closed_at);
        $this->assertNotNull($closed->follow_up_completed_at);
        $this->assertSame(
            FeedbackOutcome::NeedsFollowUp->value,
            $closed->feedback->outcome
        );
        $this->assertNotNull($closed->feedback->addressed_at);

        foreach ([
            'created',
            'validated',
            'assigned',
            'in_progress',
            'waiting_input',
            'response',
            'escalated',
            'escalation_resolved',
            'resolved',
            'employee_feedback_submitted',
            'follow_up_completed',
            'employee_feedback_addressed',
            'closed',
        ] as $historyType) {
            $this->assertDatabaseHas('complaint_histories', [
                'complaint_id' => $closed->id,
                'type' => $historyType,
            ]);
        }
    }

    public function test_normal_database_seeding_creates_reference_data_without_login_accounts(): void
    {
        $usersBeforeSeed = User::count();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(
            $usersBeforeSeed,
            User::count(),
            'Normal database seeding must not provision login accounts.'
        );

        foreach (['admin', 'karyawan', 'supervisor', 'hrd', 'pimpinan'] as $role) {
            $this->assertDatabaseHas('roles', [
                'name' => $role,
            ]);
        }

        foreach (['Low', 'Medium', 'High', 'Critical'] as $priority) {
            $this->assertDatabaseHas('priorities', [
                'name' => $priority,
            ]);
        }
    }

    public function test_uat_seeder_is_fail_closed_without_dedicated_password(): void
    {
        config()->set('uat.demo_password', null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('UAT_DEMO_PASSWORD');

        $this->seed(UatScenarioSeeder::class);
    }

    public function test_uat_seeder_creates_controlled_role_and_state_scenarios(): void
    {
        config()->set(
            'uat.demo_password',
            'Local-UAT-Only-Password-2026'
        );

        $this->seed(UatScenarioSeeder::class);

        foreach ([
            'uat.admin@example.test' => 'admin',
            'uat.employee@example.test' => 'karyawan',
            'uat.supervisor@example.test' => 'supervisor',
            'uat.hrd@example.test' => 'hrd',
            'uat.pimpinan@example.test' => 'pimpinan',
        ] as $email => $role) {
            $user = User::where('email', $email)->firstOrFail();

            $this->assertTrue($user->hasRole($role));
            $this->assertTrue($user->is_active);
        }

        foreach ([
            'UAT-SUBMITTED-001' => 'submitted',
            'UAT-ASSIGNED-001' => 'assigned',
            'UAT-WAITING-INPUT-001' => 'waiting_input',
            'UAT-RESOLVED-FEEDBACK-001' => 'resolved',
            'UAT-ESCALATED-001' => 'in_progress',
        ] as $ticket => $status) {
            $this->assertDatabaseHas('complaints', [
                'ticket_number' => $ticket,
                'status' => $status,
            ]);
        }

        $this->assertDatabaseHas('complaint_feedback', [
            'complaint_id' => Complaint::where(
                'ticket_number',
                'UAT-RESOLVED-FEEDBACK-001'
            )->value('id'),
            'outcome' => FeedbackOutcome::NeedsFollowUp->value,
        ]);

        $this->assertTrue(
            Complaint::where('ticket_number', 'UAT-ESCALATED-001')
                ->firstOrFail()
                ->activeEscalation()
                ->exists()
        );
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
