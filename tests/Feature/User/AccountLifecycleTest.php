<?php

namespace Tests\Feature\User;

use App\Livewire\User\UserIndex;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Complaint;
use App\Models\ComplaintHistory;
use App\Models\Priority;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'karyawan', 'supervisor', 'hrd', 'pimpinan'] as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
        ]);

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasErrors(['form.email']);

        $this->assertGuest();
    }

    public function test_inactive_authenticated_session_is_terminated_on_protected_route(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
        ]);
        $user->assignRole('karyawan');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_admin_can_deactivate_user_without_deleting_complaint_history(): void
    {
        $admin = $this->userWithRole('admin');
        $employee = $this->userWithRole('karyawan');
        $complaint = $this->makeComplaint($employee);

        ComplaintHistory::create([
            'complaint_id' => $complaint->id,
            'user_id' => $employee->id,
            'type' => 'created',
            'description' => 'Complaint created',
        ]);

        Comment::create([
            'complaint_id' => $complaint->id,
            'user_id' => $employee->id,
            'message' => 'Additional employee context',
        ]);

        Livewire::actingAs($admin)
            ->test(UserIndex::class)
            ->call('toggleUserStatus', $employee->id)
            ->assertHasNoErrors();

        $this->assertFalse($employee->fresh()->is_active);
        $this->assertDatabaseHas('users', ['id' => $employee->id]);
        $this->assertDatabaseHas('complaints', ['id' => $complaint->id]);
        $this->assertDatabaseHas('complaint_histories', [
            'complaint_id' => $complaint->id,
            'user_id' => $employee->id,
        ]);
        $this->assertDatabaseHas('comments', [
            'complaint_id' => $complaint->id,
            'user_id' => $employee->id,
        ]);
        $this->assertSame($employee->id, $complaint->fresh()->reporter->id);
    }

    public function test_admin_cannot_deactivate_their_own_account(): void
    {
        $admin = $this->userWithRole('admin');

        Livewire::actingAs($admin)
            ->test(UserIndex::class)
            ->call('toggleUserStatus', $admin->id)
            ->assertHasErrors(['status']);

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_last_active_admin_cannot_remove_their_admin_role(): void
    {
        $admin = $this->userWithRole('admin');

        Livewire::actingAs($admin)
            ->test(UserIndex::class)
            ->call('openEditModal', $admin->id)
            ->set('name', $admin->name)
            ->set('email', $admin->email)
            ->set('role', 'karyawan')
            ->call('updateUser')
            ->assertHasErrors(['role']);

        $this->assertTrue($admin->fresh()->hasRole('admin'));
    }

    public function test_complaints_cannot_be_deleted_through_application_policy(): void
    {
        $admin = $this->userWithRole('admin');
        $employee = $this->userWithRole('karyawan');
        $complaint = $this->makeComplaint($employee);

        $this->assertFalse(
            Gate::forUser($admin)->allows('delete', $complaint)
        );

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
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

    private function makeComplaint(User $reporter): Complaint
    {
        $category = Category::firstOrCreate([
            'name' => 'Lingkungan Kerja',
        ], [
            'description' => 'Test category',
        ]);

        $priority = Priority::firstOrCreate([
            'name' => 'High',
        ], [
            'level' => 3,
        ]);

        return Complaint::create([
            'ticket_number' => 'CMP-LIFECYCLE-' . uniqid(),
            'reporter_id' => $reporter->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'title' => 'Retained complaint record',
            'description' => 'This complaint must remain after account deactivation.',
            'status' => 'submitted',
        ]);
    }
}
