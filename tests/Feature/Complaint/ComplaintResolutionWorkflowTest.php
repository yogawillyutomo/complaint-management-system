<?php

namespace Tests\Feature\Complaint;

use App\Enums\RootCauseCategory;
use App\Livewire\Complaint\ComplaintShow;
use App\Models\Category;
use App\Models\Complaint;
use App\Models\Priority;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ComplaintResolutionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Priority $priority;

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
            'name' => 'Resolution Test',
            'description' => 'Resolution workflow test category',
        ]);

        $this->priority = Priority::create([
            'name' => 'Resolution Priority',
            'level' => 20,
        ]);
    }

    public function test_only_assigned_hrd_can_record_resolution_analysis(): void
    {
        $employee = $this->userWithRole('karyawan');
        $assignedHrd = $this->userWithRole('hrd');
        $otherHrd = $this->userWithRole('hrd');
        $complaint = $this->makeComplaint($employee, 'in_progress', $assignedHrd);

        Livewire::actingAs($otherHrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->set('root_cause_category', RootCauseCategory::WorkloadTarget->value)
            ->set('root_cause', 'Workload exceeds available operating capacity.')
            ->set('resolution_summary', 'Work allocation and daily priorities were adjusted.')
            ->call('saveResolutionAnalysis')
            ->assertForbidden();

        Livewire::actingAs($assignedHrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint->fresh()])
            ->set('root_cause_category', RootCauseCategory::WorkloadTarget->value)
            ->set('root_cause', 'Workload exceeds available operating capacity.')
            ->set('resolution_summary', 'Work allocation and daily priorities were adjusted.')
            ->set('resolution_evidence', 'Supervisor review note REF-001.')
            ->call('saveResolutionAnalysis')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'root_cause_category' => RootCauseCategory::WorkloadTarget->value,
            'resolution_evidence' => 'Supervisor review note REF-001.',
        ]);

        $this->assertDatabaseHas('complaint_histories', [
            'complaint_id' => $complaint->id,
            'type' => 'resolution_analysis_updated',
        ]);
    }

    public function test_resolve_requires_structured_root_cause_and_resolution_summary(): void
    {
        $employee = $this->userWithRole('karyawan');
        $hrd = $this->userWithRole('hrd');
        $complaint = $this->makeComplaint($employee, 'in_progress', $hrd);

        Livewire::actingAs($hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->call('resolveComplaint')
            ->assertHasErrors([
                'root_cause_category',
                'root_cause',
                'resolution_summary',
            ]);

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_unknown_root_cause_category_is_rejected(): void
    {
        $employee = $this->userWithRole('karyawan');
        $hrd = $this->userWithRole('hrd');
        $complaint = $this->makeComplaint($employee, 'in_progress', $hrd);

        Livewire::actingAs($hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->set('root_cause_category', 'invented_category')
            ->set('root_cause', 'A sufficiently detailed root cause explanation.')
            ->set('resolution_summary', 'A sufficiently detailed resolution summary.')
            ->call('saveResolutionAnalysis')
            ->assertHasErrors(['root_cause_category']);
    }

    public function test_complaint_can_resolve_and_close_without_follow_up_when_not_required(): void
    {
        $employee = $this->userWithRole('karyawan');
        $hrd = $this->userWithRole('hrd');
        $complaint = $this->makeComplaint($employee, 'in_progress', $hrd);

        Livewire::actingAs($hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->set('root_cause_category', RootCauseCategory::CrossFunctionCoordination->value)
            ->set('root_cause', 'Cross-unit handoff was unclear and caused repeated delays.')
            ->set('resolution_summary', 'The handoff flow and responsible PIC were clarified.')
            ->set('follow_up_required', false)
            ->call('resolveComplaint')
            ->assertHasNoErrors();

        $resolved = $complaint->fresh();

        $this->assertSame('resolved', $resolved->status);
        $this->assertNotNull($resolved->resolved_at);
        $this->assertFalse($resolved->follow_up_required);

        Livewire::actingAs($hrd)
            ->test(ComplaintShow::class, ['complaint' => $resolved])
            ->set(
                'closed_without_feedback_reason',
                'The resolution workflow fixture has no employee feedback and records an explicit closure reason.'
            )
            ->call('closeComplaint')
            ->assertHasNoErrors();

        $this->assertSame('closed', $complaint->fresh()->status);
    }

    public function test_follow_up_due_date_is_required_when_monitoring_is_enabled(): void
    {
        $employee = $this->userWithRole('karyawan');
        $hrd = $this->userWithRole('hrd');
        $complaint = $this->makeComplaint($employee, 'in_progress', $hrd);

        Livewire::actingAs($hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->set('root_cause_category', RootCauseCategory::ResourceLimitation->value)
            ->set('root_cause', 'The unit has insufficient operating capacity for the current workload.')
            ->set('resolution_summary', 'Temporary capacity support was arranged while workload is rebalanced.')
            ->set('follow_up_required', true)
            ->call('resolveComplaint')
            ->assertHasErrors(['follow_up_due_at']);

        $this->assertSame('in_progress', $complaint->fresh()->status);
    }

    public function test_required_follow_up_blocks_closure_until_evaluation_is_completed(): void
    {
        $employee = $this->userWithRole('karyawan');
        $hrd = $this->userWithRole('hrd');
        $complaint = $this->makeComplaint($employee, 'in_progress', $hrd);
        $dueAt = now()->addDay()->format('Y-m-d\TH:i');

        Livewire::actingAs($hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->set('root_cause_category', RootCauseCategory::AdministrativeProcess->value)
            ->set('root_cause', 'A multi-step approval flow repeatedly delayed operational completion.')
            ->set('resolution_summary', 'The approval flow was simplified and the responsible PIC was clarified.')
            ->set('resolution_evidence', 'Approved process note AP-2026-09.')
            ->set('follow_up_required', true)
            ->set('follow_up_due_at', $dueAt)
            ->call('resolveComplaint')
            ->assertHasNoErrors();

        $resolved = $complaint->fresh();

        $this->assertTrue($resolved->follow_up_required);
        $this->assertNull($resolved->follow_up_completed_at);

        Livewire::actingAs($hrd)
            ->test(ComplaintShow::class, ['complaint' => $resolved])
            ->call('closeComplaint')
            ->assertForbidden();

        Livewire::actingAs($hrd)
            ->test(ComplaintShow::class, ['complaint' => $resolved->fresh()])
            ->set('follow_up_note', 'Monitoring confirms the issue has not recurred after the process change.')
            ->call('completeFollowUp')
            ->assertHasNoErrors();

        $followedUp = $complaint->fresh();

        $this->assertNotNull($followedUp->follow_up_completed_at);
        $this->assertSame(
            'Monitoring confirms the issue has not recurred after the process change.',
            $followedUp->follow_up_note
        );

        Livewire::actingAs($hrd)
            ->test(ComplaintShow::class, ['complaint' => $followedUp])
            ->set(
                'closed_without_feedback_reason',
                'The follow-up workflow fixture has no employee feedback and records an explicit closure reason.'
            )
            ->call('closeComplaint')
            ->assertHasNoErrors();

        $this->assertSame('closed', $complaint->fresh()->status);
        $this->assertDatabaseHas('complaint_histories', [
            'complaint_id' => $complaint->id,
            'type' => 'follow_up_completed',
        ]);
    }

    public function test_inactive_hrd_cannot_be_assigned_even_with_tampered_request(): void
    {
        $employee = $this->userWithRole('karyawan');
        $supervisor = $this->userWithRole('supervisor');
        $inactiveHrd = $this->userWithRole('hrd', false);
        $complaint = $this->makeComplaint($employee, 'validated');

        Livewire::actingAs($supervisor)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->set('assigned_to', $inactiveHrd->id)
            ->call('assignHandler')
            ->assertHasErrors(['assigned_to']);

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'validated',
            'assigned_to' => null,
        ]);
    }

    private function userWithRole(string $role, bool $isActive = true): User
    {
        $user = User::factory()->create([
            'is_active' => $isActive,
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function makeComplaint(
        User $reporter,
        string $status,
        ?User $handler = null
    ): Complaint {
        return Complaint::create([
            'ticket_number' => 'CMP-RES-' . uniqid(),
            'reporter_id' => $reporter->id,
            'assigned_to' => $handler?->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'title' => 'Resolution workflow complaint',
            'description' => 'Complaint used to verify resolution and follow-up controls.',
            'status' => $status,
        ]);
    }
}
