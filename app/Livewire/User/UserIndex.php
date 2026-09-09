<?php

namespace App\Livewire\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class UserIndex extends Component
{
    public $showCreateModal = false;
    public $name = '';
    public $email = '';
    public $password = '';
    public $role = '';
    public $showEditModal = false;
    public $editingUserId = null;

    public function mount(): void
    {
        $this->authorizeAdmin();
    }

    public function openCreateModal(): void
    {
        $this->authorizeAdmin();

        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->reset([
            'name',
            'email',
            'password',
            'role',
        ]);

        $this->resetValidation();

        $this->showCreateModal = false;
    }

    public function createUser(): void
    {
        $this->authorizeAdmin();

        $this->validate([
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => [
                'required',
                Rule::exists('roles', 'name')->where('guard_name', 'web'),
            ],
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $user->assignRole($this->role);

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'User created successfully.'
        );

        $this->closeCreateModal();
    }

    public function openEditModal(int $id): void
    {
        $this->authorizeAdmin();

        $user = User::findOrFail($id);

        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->roles->first()?->name;
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->reset([
            'editingUserId',
            'name',
            'email',
            'password',
            'role',
        ]);

        $this->resetValidation();

        $this->showEditModal = false;
    }

    public function updateUser(): void
    {
        $this->authorizeAdmin();

        $this->validate([
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users,email,' . $this->editingUserId,
            'role' => [
                'required',
                Rule::exists('roles', 'name')->where('guard_name', 'web'),
            ],
        ]);

        $user = User::findOrFail($this->editingUserId);

        if (
            $user->is_active
            && $user->hasRole('admin')
            && $this->role !== 'admin'
            && $this->activeAdminCount() <= 1
        ) {
            throw ValidationException::withMessages([
                'role' => 'At least one active administrator must remain.',
            ]);
        }

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        $user->syncRoles([$this->role]);

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'User updated successfully.'
        );

        $this->closeEditModal();
    }

    public function toggleUserStatus(int $id): void
    {
        $this->authorizeAdmin();

        $user = User::findOrFail($id);

        if ((int) $user->id === (int) auth()->id() && $user->is_active) {
            throw ValidationException::withMessages([
                'status' => 'You cannot deactivate your own account.',
            ]);
        }

        if (
            $user->is_active
            && $user->hasRole('admin')
            && $this->activeAdminCount() <= 1
        ) {
            throw ValidationException::withMessages([
                'status' => 'At least one active administrator must remain.',
            ]);
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        $this->dispatch(
            'toast',
            type: 'success',
            message: $user->is_active
                ? 'User account activated.'
                : 'User account deactivated.'
        );
    }

    public function render()
    {
        $this->authorizeAdmin();

        return view('livewire.user.user-index', [
            'users' => User::with('roles')
                ->latest()
                ->get(),
            'roles' => Role::query()
                ->where('guard_name', 'web')
                ->orderBy('name')
                ->get(),
        ])->layout('layouts.app');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(
            auth()->check() && auth()->user()->hasRole('admin'),
            403
        );
    }

    private function activeAdminCount(): int
    {
        return User::role('admin')
            ->where('is_active', true)
            ->count();
    }
}
