<?php

namespace Tests\Feature\Report;

use App\Enums\EscalationLevel;
use App\Enums\EscalationReason;
use App\Enums\RootCauseCategory;
use App\Livewire\Report\ManagementReport;
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

class ManagementReportTest extends TestCase
{
    use RefreshDatabase;

    private Category $categoryA;
    private Category $categoryB;
    private Priority $priority;
    private User $employee;
    private User $admin;
    private User $supervisor;
    private User $hrdA;
    private User $hrdB;
    private User $pimpinan;

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

        $this->categoryA = Category::create([
            'name' => 'Work Process',
            'description' => 'Work process complaints',
        ]);

        $this->categoryB = Category::create([
            'name' => 'Resources',
            'description' => 'Resource complaints',
        ]);

        $this->priority = Priority::create([
            'name' => 'Report Priority',
            'level' => 51,
            'response_minutes' => 60,
            'resolution_minutes' => 240,
        ]);

        $this->employee = $this->userWithRole('karyawan');
        $this->admin = $this->userWithRole('admin');
        $this->supervisor = $this->userWithRole('supervisor');
        $this->hrdA = $this->userWithRole('hrd');
        $this->hrdB = $this->userWithRole('hrd');
        $this->pimpinan = $this->userWithRole('pimpinan');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_employee_cannot_access_management_reports(): void
    {
        $this->actingAs($this->employee)
            ->get('/reports')
            ->assertForbidden();
    }

    public function test_management_roles_can_access_reports(): void
    {
        foreach ([$this->admin, $this->supervisor, $this->hrdA, $this->pimpinan] as $user) {
            $this->actingAs($user)
                ->get('/reports')
                ->assertOk();
        }
    }

    public function test_analytics_calculate_distribution_recurrence_sla_escalation_and_handler_metrics(): void
    {
        [$a, $b, $c, $d] = $this->seedReportingCohort();

        $report = app(ComplaintAnalyticsService::class)->build(
            Carbon::parse('2026-09-01'),
            Carbon::parse('2026-09-30')
        );

        $this->assertSame(4, $report['totalComplaints']);
        $this->assertSame(2, $report['categoryDistribution']['Work Process']);
        $this->assertSame(2, $report['categoryDistribution']['Resources']);

        $this->assertSame(
            2,
            $report['rootCauseDistribution']['Workload & Target']
        );

        $this->assertSame(
            1,
            $report['rootCauseDistribution']['Resource Limitation']
        );

        $this->assertSame(3, $report['recurrence']['categorizedComplaints']);
        $this->assertSame(1, $report['recurrence']['repeatedGroups']);
        $this->assertSame(2, $report['recurrence']['complaintsInRepeatedGroups']);
        $this->assertSame(66.7, $report['recurrence']['proxyRate']);

        $this->assertSame(3, $report['sla']['responseSamples']);
        $this->assertSame(2, $report['sla']['responseCompliant']);
        $this->assertSame(66.7, $report['sla']['responseComplianceRate']);
        $this->assertSame(1, $report['sla']['responseOverdueOpen']);

        $this->assertSame(2, $report['sla']['resolutionSamples']);
        $this->assertSame(1, $report['sla']['resolutionCompliant']);
        $this->assertSame(50.0, $report['sla']['resolutionComplianceRate']);
        $this->assertSame(2, $report['sla']['resolutionOverdueOpen']);

        $this->assertSame(50.0, $report['sla']['avgFirstResponseMinutes']);
        $this->assertSame(210.0, $report['sla']['avgResolutionMinutes']);

        $this->assertSame(2, $report['escalations']['total']);
        $this->assertSame(1, $report['escalations']['active']);
        $this->assertSame(1, $report['escalations']['resolved']);
        $this->assertSame(
            1,
            $report['escalations']['reasonDistribution']['SLA Breach']
        );
        $this->assertSame(
            1,
            $report['escalations']['reasonDistribution']['Resource Constraint']
        );

        $handlerA = collect($report['handlerPerformance'])
            ->firstWhere('handlerId', $this->hrdA->id);

        $this->assertSame(2, $handlerA['assigned']);
        $this->assertSame(1, $handlerA['resolved']);
        $this->assertSame(1, $handlerA['openBacklog']);
        $this->assertSame(100.0, $handlerA['resolutionComplianceRate']);
        $this->assertSame(120.0, $handlerA['avgResolutionMinutes']);

        $handlerB = collect($report['handlerPerformance'])
            ->firstWhere('handlerId', $this->hrdB->id);

        $this->assertSame(1, $handlerB['assigned']);
        $this->assertSame(1, $handlerB['resolved']);
        $this->assertSame(0, $handlerB['openBacklog']);
        $this->assertSame(0.0, $handlerB['resolutionComplianceRate']);
        $this->assertSame(300.0, $handlerB['avgResolutionMinutes']);

        $trend = collect($report['trend'])->keyBy('date');

        $this->assertSame(1, $trend['2026-09-01']['count']);
        $this->assertSame(1, $trend['2026-09-02']['count']);
        $this->assertSame(1, $trend['2026-09-03']['count']);
        $this->assertSame(1, $trend['2026-09-04']['count']);
        $this->assertSame(0, $trend['2026-09-05']['count']);

        $this->assertSame($a->id, $a->fresh()->id);
        $this->assertSame($b->id, $b->fresh()->id);
        $this->assertSame($c->id, $c->fresh()->id);
        $this->assertSame($d->id, $d->fresh()->id);
    }

    public function test_reporting_filter_excludes_complaints_outside_applied_period(): void
    {
        $this->seedReportingCohort();

        $outside = $this->makeComplaint(
            'CMP-REPORT-OUTSIDE',
            Carbon::parse('2026-08-20 08:00:00'),
            $this->categoryA,
            'submitted'
        );

        Livewire::actingAs($this->admin)
            ->test(ManagementReport::class)
            ->set('date_from', '2026-09-01')
            ->set('date_to', '2026-09-30')
            ->call('applyFilters')
            ->assertHasNoErrors()
            ->assertViewHas(
                'report',
                fn (array $report) => $report['totalComplaints'] === 4
            );

        $this->assertDatabaseHas('complaints', [
            'id' => $outside->id,
        ]);
    }

    public function test_reporting_range_cannot_exceed_366_days(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ManagementReport::class)
            ->set('date_from', '2025-01-01')
            ->set('date_to', '2026-09-01')
            ->call('applyFilters')
            ->assertHasErrors(['date_to']);
    }

    public function test_csv_export_uses_applied_period_and_expected_columns(): void
    {
        $this->seedReportingCohort();

        $rows = app(ComplaintAnalyticsService::class)->exportRows(
            Carbon::parse('2026-09-01'),
            Carbon::parse('2026-09-30')
        );

        $this->assertCount(4, $rows);

        $first = $rows->first();

        $this->assertSame([
            'ticket_number',
            'created_at',
            'title',
            'status',
            'priority',
            'category',
            'root_cause_category',
            'reporter',
            'handler',
            'first_response_at',
            'response_due_at',
            'response_sla',
            'resolved_at',
            'resolution_due_at',
            'resolution_sla',
            'follow_up_required',
            'follow_up_completed_at',
            'escalation_count',
            'active_escalation',
            'feedback_outcome',
            'feedback_rating',
            'feedback_comment',
            'feedback_addressed_at',
            'closed_without_feedback_reason',
        ], array_keys($first));

        Livewire::actingAs($this->admin)
            ->test(ManagementReport::class)
            ->set('date_from', '2026-09-01')
            ->set('date_to', '2026-09-30')
            ->call('applyFilters')
            ->call('exportCsv')
            ->assertFileDownloaded(
                'complaint-management-report-2026-09-01-to-2026-09-30.csv'
            );
    }

    private function seedReportingCohort(): array
    {
        $a = $this->makeComplaint(
            'CMP-REPORT-A',
            Carbon::parse('2026-09-01 08:00:00'),
            $this->categoryA,
            'resolved',
            $this->hrdA,
            RootCauseCategory::WorkloadTarget,
            Carbon::parse('2026-09-01 08:20:00'),
            Carbon::parse('2026-09-01 09:00:00'),
            Carbon::parse('2026-09-01 10:00:00'),
            Carbon::parse('2026-09-01 12:00:00')
        );

        $b = $this->makeComplaint(
            'CMP-REPORT-B',
            Carbon::parse('2026-09-02 08:00:00'),
            $this->categoryA,
            'in_progress',
            $this->hrdA,
            RootCauseCategory::WorkloadTarget,
            Carbon::parse('2026-09-02 09:30:00'),
            Carbon::parse('2026-09-02 09:00:00'),
            null,
            Carbon::parse('2026-09-02 12:00:00')
        );

        $b->escalations()->create([
            'escalated_by' => $this->supervisor->id,
            'escalated_to' => $this->pimpinan->id,
            'reason_code' => EscalationReason::SlaBreach->value,
            'escalation_level' => EscalationLevel::Leadership->value,
            'note' => 'The complaint exceeded the operating SLA and requires leadership attention.',
            'status' => 'active',
        ]);

        $c = $this->makeComplaint(
            'CMP-REPORT-C',
            Carbon::parse('2026-09-03 08:00:00'),
            $this->categoryB,
            'closed',
            $this->hrdB,
            RootCauseCategory::ResourceLimitation,
            Carbon::parse('2026-09-03 08:40:00'),
            Carbon::parse('2026-09-03 09:00:00'),
            Carbon::parse('2026-09-03 13:00:00'),
            Carbon::parse('2026-09-03 12:00:00')
        );

        $c->escalations()->create([
            'escalated_by' => $this->hrdB->id,
            'escalated_to' => $this->pimpinan->id,
            'reason_code' => EscalationReason::ResourceConstraint->value,
            'escalation_level' => EscalationLevel::Leadership->value,
            'note' => 'Resource capacity required temporary leadership intervention for resolution.',
            'status' => 'resolved',
            'resolved_by' => $this->pimpinan->id,
            'resolved_at' => Carbon::parse('2026-09-03 10:00:00'),
            'resolution_note' => 'Temporary capacity support was approved.',
        ]);

        $d = $this->makeComplaint(
            'CMP-REPORT-D',
            Carbon::parse('2026-09-04 08:00:00'),
            $this->categoryB,
            'submitted',
            null,
            null,
            null,
            Carbon::parse('2026-09-04 09:00:00'),
            null,
            Carbon::parse('2026-09-04 12:00:00')
        );

        return [$a, $b, $c, $d];
    }

    private function makeComplaint(
        string $ticket,
        Carbon $createdAt,
        Category $category,
        string $status,
        ?User $handler = null,
        ?RootCauseCategory $rootCauseCategory = null,
        ?Carbon $firstResponseAt = null,
        ?Carbon $responseDueAt = null,
        ?Carbon $resolvedAt = null,
        ?Carbon $resolutionDueAt = null
    ): Complaint {
        $complaint = Complaint::create([
            'ticket_number' => $ticket,
            'reporter_id' => $this->employee->id,
            'assigned_to' => $handler?->id,
            'category_id' => $category->id,
            'priority_id' => $this->priority->id,
            'title' => 'Management report fixture',
            'description' => 'Complaint fixture for management analytics verification.',
            'root_cause_category' => $rootCauseCategory?->value,
            'root_cause' => $rootCauseCategory
                ? 'A verified root cause narrative for management reporting.'
                : null,
            'resolution_summary' => $resolvedAt
                ? 'A verified resolution summary for management reporting.'
                : null,
            'status' => $status,
            'first_response_at' => $firstResponseAt,
            'response_due_at' => $responseDueAt,
            'resolved_at' => $resolvedAt,
            'resolution_due_at' => $resolutionDueAt,
        ]);

        $complaint->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
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
