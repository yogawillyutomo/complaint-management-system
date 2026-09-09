<?php

namespace Tests\Feature\Complaint;

use App\Livewire\Complaint\ComplaintIndex;
use App\Livewire\Complaint\ComplaintShow;
use App\Models\Category;
use App\Models\Complaint;
use App\Models\Priority;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ComplaintAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Priority $priority;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'karyawan', 'supervisor', 'hrd', 'pimpinan'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->category = Category::create([
            'name' => 'Lingkungan Kerja',
            'description' => 'Test category',
        ]);

        $this->priority = Priority::create([
            'name' => 'High',
            'level' => 3,
        ]);
    }

    public function test_roleless_user_cannot_access_complaint_surfaces(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertForbidden();

        $this->actingAs($user)
            ->get('/complaints')
            ->assertForbidden();
    }

    public function test_employee_can_only_view_their_own_complaint(): void
    {
        $employee = $this->userWithRole('karyawan');
        $otherEmployee = $this->userWithRole('karyawan');
        $ownComplaint = $this->makeComplaint($employee);
        $otherComplaint = $this->makeComplaint($otherEmployee);

        $this->actingAs($employee)
            ->get(route('complaints.show', $ownComplaint))
            ->assertOk();

        $this->actingAs($employee)
            ->get(route('complaints.show', $otherComplaint))
            ->assertForbidden();
    }

    public function test_only_employee_can_create_a_complaint(): void
    {
        $admin = $this->userWithRole('admin');

        Livewire::actingAs($admin)
            ->test(ComplaintIndex::class)
            ->set('title', 'Unauthorized complaint')
            ->set('description', 'This complaint must not be accepted by the system.')
            ->set('category_id', $this->category->id)
            ->set('priority_id', $this->priority->id)
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseCount('complaints', 0);
    }

    public function test_employee_created_tickets_are_unique(): void
    {
        $employee = $this->userWithRole('karyawan');

        foreach (['First valid complaint', 'Second valid complaint'] as $title) {
            Livewire::actingAs($employee)
                ->test(ComplaintIndex::class)
                ->set('title', $title)
                ->set('description', 'A sufficiently long complaint description for automated testing.')
                ->set('category_id', $this->category->id)
                ->set('priority_id', $this->priority->id)
                ->call('save')
                ->assertHasNoErrors();
        }

        $tickets = Complaint::pluck('ticket_number');

        $this->assertCount(2, $tickets);
        $this->assertCount(2, $tickets->unique());
    }

    public function test_supervisor_can_only_validate_or_reject_submitted_complaints(): void
    {
        $employee = $this->userWithRole('karyawan');
        $supervisor = $this->userWithRole('supervisor');
        $complaint = $this->makeComplaint($employee, 'submitted');

        Livewire::actingAs($supervisor)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->call('validateComplaint')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'validated',
        ]);

        Livewire::actingAs($supervisor)
            ->test(ComplaintShow::class, ['complaint' => $complaint->fresh()])
            ->call('rejectComplaint')
            ->assertForbidden();

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'validated',
        ]);
    }

    public function test_supervisor_can_only_assign_hrd_users(): void
    {
        $employee = $this->userWithRole('karyawan');
        $supervisor = $this->userWithRole('supervisor');
        $admin = $this->userWithRole('admin');
        $hrd = $this->userWithRole('hrd');
        $complaint = $this->makeComplaint($employee, 'validated');

        Livewire::actingAs($supervisor)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->set('assigned_to', $admin->id)
            ->call('assignHandler')
            ->assertHasErrors(['assigned_to']);

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'validated',
            'assigned_to' => null,
        ]);

        Livewire::actingAs($supervisor)
            ->test(ComplaintShow::class, ['complaint' => $complaint->fresh()])
            ->set('assigned_to', $hrd->id)
            ->call('assignHandler')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'assigned',
            'assigned_to' => $hrd->id,
        ]);
    }

    public function test_only_assigned_hrd_can_process_complaint(): void
    {
        $employee = $this->userWithRole('karyawan');
        $assignedHrd = $this->userWithRole('hrd');
        $otherHrd = $this->userWithRole('hrd');
        $complaint = $this->makeComplaint($employee, 'assigned', $assignedHrd);

        Livewire::actingAs($otherHrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->call('startProgress')
            ->assertForbidden();

        Livewire::actingAs($assignedHrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint->fresh()])
            ->call('startProgress')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_hrd_cannot_skip_workflow_states(): void
    {
        $employee = $this->userWithRole('karyawan');
        $hrd = $this->userWithRole('hrd');
        $complaint = $this->makeComplaint($employee, 'assigned', $hrd);

        Livewire::actingAs($hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->call('resolveComplaint')
            ->assertForbidden();

        Livewire::actingAs($hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint->fresh()])
            ->call('startProgress')
            ->assertHasNoErrors();

        Livewire::actingAs($hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint->fresh()])
            ->call('closeComplaint')
            ->assertForbidden();

        Livewire::actingAs($hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint->fresh()])
            ->set('root_cause_category', 'workload_target')
            ->set('root_cause', 'The workload exceeded the available operating capacity.')
            ->set('resolution_summary', 'Work allocation and priorities were adjusted by the assigned PIC.')
            ->set('follow_up_required', false)
            ->call('resolveComplaint')
            ->assertHasNoErrors();

        Livewire::actingAs($hrd)
            ->test(ComplaintShow::class, ['complaint' => $complaint->fresh()])
            ->call('closeComplaint')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'closed',
        ]);

        $this->assertNotNull($complaint->fresh()->resolved_at);
        $this->assertNotNull($complaint->fresh()->closed_at);
    }

    public function test_employee_cannot_edit_complaint_after_validation(): void
    {
        $employee = $this->userWithRole('karyawan');
        $complaint = $this->makeComplaint($employee, 'validated');

        Livewire::actingAs($employee)
            ->test(ComplaintShow::class, ['complaint' => $complaint])
            ->set('title', 'Changed after validation')
            ->set('description', 'This change should be rejected after supervisor validation.')
            ->set('category_id', $this->category->id)
            ->set('priority_id', $this->priority->id)
            ->call('updateComplaint')
            ->assertForbidden();

        $this->assertDatabaseMissing('complaints', [
            'id' => $complaint->id,
            'title' => 'Changed after validation',
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function makeComplaint(
        User $reporter,
        string $status = 'submitted',
        ?User $handler = null
    ): Complaint {
        return Complaint::create([
            'ticket_number' => 'CMP-TEST-' . uniqid(),
            'reporter_id' => $reporter->id,
            'assigned_to' => $handler?->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'title' => 'Test complaint title',
            'description' => 'A complaint description long enough for the test suite.',
            'status' => $status,
        ]);
    }
}
