<?php

namespace App\Livewire\User;

use App\Models\User;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserIndex extends Component
{

    public $showCreateModal = false;

    public $name = '';

    public $email = '';

    public $password = '';

    public $role = '';

    public $showEditModal = false;

    public $editingUserId = null;

    public function mount()
    {
        abort_unless(
            auth()->user()->hasRole('admin'),
            403
        );
    }

    public function openCreateModal()
    {
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
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

    public function createUser()
    {
        $this->validate([

            'name' => 'required|min:3',

            'email' => 'required|email|unique:users,email',

            'password' => 'required|min:6',

            'role' => 'required',

        ]);

        $user = User::create([

            'name' => $this->name,

            'email' => $this->email,

            'password' => Hash::make($this->password),

            'email_verified_at' => now(),

        ]);

        $user->assignRole($this->role);

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'User created successfully.'
        );

        $this->closeCreateModal();
    }

    public function openEditModal($id)
    {
        $user = User::findOrFail($id);

        $this->editingUserId = $user->id;

        $this->name = $user->name;

        $this->email = $user->email;

        $this->role = $user->roles->first()?->name;

        $this->showEditModal = true;
    }

    public function closeEditModal()
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

    public function updateUser()
    {
        $this->validate([

            'name' => 'required|min:3',

            'email' => 'required|email|unique:users,email,' . $this->editingUserId,

            'role' => 'required',

        ]);

        $user = User::findOrFail($this->editingUserId);

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


    public function render()
    {
        return view('livewire.user.user-index', [

            'users' => User::with('roles')
                ->latest()
                ->get(),

            'roles' => Role::all(),

        ])->layout('layouts.app');
    }
}
