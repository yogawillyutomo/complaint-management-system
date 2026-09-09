<?php

namespace Tests\Feature\Complaint;

use App\Enums\EscalationLevel;
use App\Enums\EscalationReason;
use App\Enums\RootCauseCategory;
use App\Livewire\Complaint\ComplaintShow;
use App\Livewire\Dashboard;
use App\Models\Category;
use App\Models\Complaint;
use App\Models\Priority;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ComplaintSlaEscalationTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

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
            'name' => 'SLA Test',
            'description' => 'SLA and escalation test category',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_sla_deadlines_are_snapshotted_when_complaint_is_created(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-09 08:00:00'));

        $priority = $this->priority(
            'Critical Test',
            30,
            480
        );

        $employee = $this->userWithRole('karyawan');

        $complaint = $this->makeComplaint(
            $employee,
            $priority
        );

        $this->assertSame(
            '2026-09-09 08:30:00',
            $complaint->response_due_at->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            '2026-09-09 16:00:00',
            $complaint->resolution_due_at->format('Y-m-d H:i:s')
        );

        $priority->update([
            'response_minutes' => 5,
            'resolution_minutes' => 10,
        ]);

        $complaint->refresh();

        $this->assertSame(
            '2026-09-09 08:30:00',
            $complaint->response_due_at->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            '2026-09-09 16:00:00',
            $complaint->resolution_due_at->format('Y-m-d H:i:s')
        );
    }

    public function test_supervisor_validation_records_first_official_response(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-09 09:00:00'));

        $priority = $this->priority('Response Test', 120, 480);
        $employee = $this->userWithRole('karyawan');
        $supervisor = $this->userWithRole('supervisor');
        $complaint = $this->makeComplaint($employee, $priority);

        Carbon::setTestNow(Carbon::parse('2026-09-09 09:45:00'));

        Livewire::actingAs($supervisor)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->call('validateComplaint')
            ->assertHasNoErrors();

        $this->assertSame(
            '2026-09-09 09:45:00',
            $complaint->fresh()->first_response_at->format('Y-m-d H:i:s')
        );
    }

    public function test_sla_helpers_detect_response_and_resolution_breaches(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-09 10:00:00'));

        $priority = $this->priority('Breach Test', 30, 60);
        $employee = $this->userWithRole('karyawan');
        $complaint = $this->makeComplaint($employee, $priority);

        Carbon::setTestNow(Carbon::parse('2026-09-09 11:15:00'));

        $complaint->refresh();

        $this->assertTrue($complaint->isResponseOverdue());
        $this->assertTrue($complaint->isResolutionOverdue());

        $complaint->update([
            'first_response_at' => Carbon::parse('2026-09-09 10:20:00'),
            'resolved_at' => Carbon::parse('2026-09-09 10:50:00'),
            'status' => 'resolved',
        ]);

        $complaint->refresh();

        $this->assertFalse($complaint->isResponseOverdue());
        $this->assertFalse($complaint->isResolutionOverdue());
    }

    public function test_employee_cannot_escalate_complaint(): void
    {
        $priority = $this->priority('Employee Escalation', 60, 240);
        $employee = $this->userWithRole('karyawan');
        $complaint = $this->makeComplaint($employee, $priority);

        Livewire::actingAs($employee)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->call('openEscalationModal')
            ->assertForbidden();

        $this->assertDatabaseCount('complaint_escalations', 0);
    }

    public function test_leadership_escalation_requires_active_pimpinan_target(): void
    {
        $priority = $this->priority('Leadership Escalation', 60, 240);
        $employee = $this->userWithRole('karyawan');
        $supervisor = $this->userWithRole('supervisor');
        $inactivePimpinan = $this->userWithRole('pimpinan', false);
        $complaint = $this->makeComplaint($employee, $priority);

        Livewire::actingAs($supervisor)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->set('escalation_reason_code', EscalationReason::AuthorityLimit->value)
            ->set('escalation_level', EscalationLevel::Leadership->value)
            ->set('escalated_to', $inactivePimpinan->id)
            ->set('escalation_note', 'The issue requires an authority decision outside the current operational level.')
            ->call('escalateComplaint')
            ->assertHasErrors(['escalated_to']);

        $this->assertDatabaseCount('complaint_escalations', 0);
    }

    public function test_only_one_active_escalation_can_exist_per_complaint(): void
    {
        $priority = $this->priority('Single Active Escalation', 60, 240);
        $employee = $this->userWithRole('karyawan');
        $supervisor = $this->userWithRole('supervisor');
        $pimpinan = $this->userWithRole('pimpinan');
        $complaint = $this->makeComplaint($employee, $priority);

        $component = Livewire::actingAs($supervisor)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->set('escalation_reason_code', EscalationReason::SlaRisk->value)
            ->set('escalation_level', EscalationLevel::Leadership->value)
            ->set('escalated_to', $pimpinan->id)
            ->set('escalation_note', 'The response window is at risk and leadership support is required.')
            ->call('escalateComplaint')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('complaint_escalations', [
            'complaint_id' => $complaint->id,
            'status' => 'active',
            'escalated_to' => $pimpinan->id,
        ]);

        $component
            ->set('escalation_reason_code', EscalationReason::Other->value)
            ->set('escalation_level', EscalationLevel::HigherAuthority->value)
            ->set('escalation_note', 'A second active escalation must not be accepted.')
            ->call('escalateComplaint')
            ->assertForbidden();

        $this->assertDatabaseCount('complaint_escalations', 1);
    }

    public function test_active_escalation_blocks_resolution_until_leadership_resolves_it(): void
    {
        $priority = $this->priority('Resolution Escalation', 60, 240);
        $employee = $this->userWithRole('karyawan');
        $hrd = $this->userWithRole('hrd');
        $pimpinan = $this->userWithRole('pimpinan');

        $complaint = $this->makeComplaint(
            $employee,
            $priority,
            'in_progress',
            $hrd
        );

        Livewire::actingAs($hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->set('escalation_reason_code', EscalationReason::PolicySystem->value)
            ->set('escalation_level', EscalationLevel::Leadership->value)
            ->set('escalated_to', $pimpinan->id)
            ->set('escalation_note', 'A policy-level decision is required before the assigned HRD can finalize the resolution.')
            ->call('escalateComplaint')
            ->assertHasNoErrors();

        Livewire::actingAs($hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint->fresh()])
            ->set('root_cause_category', RootCauseCategory::AdministrativeProcess->value)
            ->set('root_cause', 'The existing policy approval path prevents completion at the current authority level.')
            ->set('resolution_summary', 'The proposed resolution depends on a leadership policy decision.')
            ->set('follow_up_required', false)
            ->call('resolveComplaint')
            ->assertForbidden();

        Livewire::actingAs($pimpinan)
            ->test(ComplaintShow::class, ['complaint' => $complaint->fresh()])
            ->set('escalation_resolution_note', 'Leadership approved the revised operational handling and delegated execution to HRD.')
            ->call('resolveEscalation')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('complaint_escalations', [
            'complaint_id' => $complaint->id,
            'status' => 'resolved',
            'resolved_by' => $pimpinan->id,
        ]);

        Livewire::actingAs($hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint->fresh()])
            ->set('root_cause_category', RootCauseCategory::AdministrativeProcess->value)
            ->set('root_cause', 'The existing policy approval path prevented completion at the current authority level.')
            ->set('resolution_summary', 'Leadership approval was obtained and the revised handling was executed by HRD.')
            ->set('follow_up_required', false)
            ->call('resolveComplaint')
            ->assertHasNoErrors();

        $this->assertSame('resolved', $complaint->fresh()->status);
    }

    public function test_dashboard_exposes_backlog_overdue_escalation_and_time_metrics(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-09 12:00:00'));

        $priority = $this->priority('Dashboard SLA', 30, 60);
        $admin = $this->userWithRole('admin');
        $employee = $this->userWithRole('karyawan');
        $supervisor = $this->userWithRole('supervisor');
        $pimpinan = $this->userWithRole('pimpinan');

        $overdue = $this->makeComplaint($employee, $priority);
        $overdue->update([
            'response_due_at' => now()->subMinutes(10),
            'resolution_due_at' => now()->subMinutes(5),
        ]);

        $completed = $this->makeComplaint(
            $employee,
            $priority,
            'resolved'
        );

        $completed->forceFill([
            'created_at' => now()->subMinutes(120),
            'first_response_at' => now()->subMinutes(90),
            'resolved_at' => now()->subMinutes(30),
        ])->save();

        Livewire::actingAs($supervisor)
            ->test(ComplaintShow::class, ['complaint' => $overdue])
            ->set('escalation_reason_code', EscalationReason::SlaBreach->value)
            ->set('escalation_level', EscalationLevel::Leadership->value)
            ->set('escalated_to', $pimpinan->id)
            ->set('escalation_note', 'The ticket has crossed its SLA target and requires leadership attention.')
            ->call('escalateComplaint')
            ->assertHasNoErrors();

        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->assertViewHas('openBacklog', 1)
            ->assertViewHas('overdueResponse', 1)
            ->assertViewHas('overdueResolution', 1)
            ->assertViewHas('activeEscalations', 1)
            ->assertViewHas('avgFirstResponseMinutes', 30.0)
            ->assertViewHas('avgResolutionMinutes', 90.0);
    }

    private function userWithRole(string $role, bool $isActive = true): User
    {
        $user = User::factory()->create([
            'is_active' => $isActive,
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function priority(
        string $name,
        int $responseMinutes,
        int $resolutionMinutes
    ): Priority {
        return Priority::create([
            'name' => $name,
            'level' => random_int(10, 1000),
            'response_minutes' => $responseMinutes,
            'resolution_minutes' => $resolutionMinutes,
        ]);
    }

    private function makeComplaint(
        User $reporter,
        Priority $priority,
        string $status = 'submitted',
        ?User $handler = null
    ): Complaint {
        return Complaint::create([
            'ticket_number' => 'CMP-SLA-' . uniqid(),
            'reporter_id' => $reporter->id,
            'assigned_to' => $handler?->id,
            'category_id' => $this->category->id,
            'priority_id' => $priority->id,
            'title' => 'SLA escalation workflow complaint',
            'description' => 'Complaint used to verify SLA monitoring, escalation, and operational metrics.',
            'status' => $status,
        ]);
    }
}
