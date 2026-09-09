<div>
    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">

            <div>

                <h1 class="text-3xl font-bold text-slate2-900">
                    User Management
                </h1>

                <p class="text-slate2-600 mt-1">
                    Manage system users and roles
                </p>

            </div>

            <button
                wire:click="openCreateModal"
                class="btn-primary">

                Create User

            </button>

        </div>

        @error('status')
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-700">
            {{ $message }}
        </div>
        @enderror

        {{-- Table --}}
        <div class="bg-white rounded-3xl border border-surface-100 overflow-hidden shadow-sm">

            <table class="w-full">

                <thead class="bg-surface-50 border-b border-surface-100">

                    <tr class="text-left">

                        <th class="px-6 py-4 text-sm font-semibold text-slate2-700">
                            Name
                        </th>

                        <th class="px-6 py-4 text-sm font-semibold text-slate2-700">
                            Email
                        </th>

                        <th class="px-6 py-4 text-sm font-semibold text-slate2-700">
                            Role
                        </th>

                        <th class="px-6 py-4 text-sm font-semibold text-slate2-700">
                            Status
                        </th>

                        <th class="px-6 py-4 text-sm font-semibold text-slate2-700">
                            Created
                        </th>
                        <th class="px-6 py-4 text-sm font-semibold text-slate2-700 text-right">
                            Action
                        </th>


                    </tr>

                </thead>

                <tbody class="divide-y divide-surface-100">

                    @forelse($users as $user)

                    <tr class="hover:bg-surface-50 transition">

                        <td class="px-6 py-5">

                            <div class="flex items-center gap-3">

                                <div class="w-10 h-10 rounded-2xl bg-brand-600 text-white flex items-center justify-center font-semibold">

                                    {{ strtoupper(substr($user->name, 0, 1)) }}

                                </div>

                                <div>

                                    <p class="font-semibold text-slate2-900">
                                        {{ $user->name }}
                                    </p>

                                </div>

                            </div>

                        </td>

                        <td class="px-6 py-5 text-slate2-600">

                            {{ $user->email }}

                        </td>

                        <td class="px-6 py-5">

                            <span class="px-3 py-1 rounded-xl bg-brand-100 text-brand-700 text-sm font-medium">

                                {{ $user->roles->first()?->name ?? 'User' }}

                            </span>

                        </td>

                        <td class="px-6 py-5">

                            <span class="px-3 py-1 rounded-xl text-sm font-medium {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate2-500' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>

                        </td>

                        <td class="px-6 py-5 text-slate2-600 text-sm">

                            {{ $user->created_at->format('d M Y') }}

                        </td>
                        <td class="px-6 py-5">

                            <div class="flex items-center justify-end gap-2">

                                <button
                                    wire:click="openEditModal({{ $user->id }})"
                                    class="w-10 h-10 rounded-xl border border-surface-200 flex items-center justify-center hover:bg-brand-50 hover:border-brand-200 transition">

                                    <x-heroicon-o-pencil-square class="w-5 h-5 text-brand-600" />

                                </button>

                                @if((int) $user->id !== (int) auth()->id())
                                <button
                                    wire:click="toggleUserStatus({{ $user->id }})"
                                    wire:confirm="{{ $user->is_active ? 'Deactivate this user account?' : 'Activate this user account?' }}"
                                    class="px-3 h-10 rounded-xl border border-surface-200 text-sm font-semibold {{ $user->is_active ? 'text-red-600 hover:bg-red-50 hover:border-red-200' : 'text-emerald-600 hover:bg-emerald-50 hover:border-emerald-200' }} transition">

                                    {{ $user->is_active ? 'Deactivate' : 'Activate' }}

                                </button>
                                @endif

                            </div>

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="6" class="px-6 py-10 text-center text-slate2-500">

                            No users found.

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    @if($showCreateModal)

    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">

        {{-- Backdrop --}}
        <div
            wire:click="closeCreateModal"
            class="absolute inset-0 bg-black/40 backdrop-blur-sm">
        </div>

        {{-- Modal --}}
        <div class="relative w-full max-w-2xl card p-8">

            {{-- Header --}}
            <div class="flex items-start justify-between mb-6">

                <div>

                    <h2 class="text-2xl font-bold text-slate2-900">
                        Create User
                    </h2>

                    <p class="text-slate2-600 mt-1">
                        Add a new system user
                    </p>

                </div>

                <button
                    wire:click="closeCreateModal"
                    class="w-10 h-10 rounded-xl hover:bg-surface-100 flex items-center justify-center transition">

                    <x-heroicon-o-x-mark class="w-5 h-5" />

                </button>

            </div>

            {{-- Form --}}
            <form wire:submit="createUser" class="space-y-5">

                {{-- Name --}}
                <div>

                    <label class="block text-sm font-medium mb-2">
                        Full Name
                    </label>

                    <input
                        type="text"
                        wire:model.live="name"
                        class="input"
                        placeholder="Enter full name">

                    @error('name')
                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                    @enderror

                </div>

                {{-- Email --}}
                <div>

                    <label class="block text-sm font-medium mb-2">
                        Email
                    </label>

                    <input
                        type="email"
                        wire:model.live="email"
                        class="input"
                        placeholder="Enter email">

                    @error('email')
                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                    @enderror

                </div>

                {{-- Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    {{-- Password --}}
                    <div>

                        <label class="block text-sm font-medium mb-2">
                            Password
                        </label>

                        <input
                            type="password"
                            wire:model.live="password"
                            class="input"
                            placeholder="Enter password">

                        @error('password')
                        <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror

                    </div>

                    {{-- Role --}}
                    <div>

                        <label class="block text-sm font-medium mb-2">
                            Role
                        </label>

                        <select
                            wire:model.live="role"
                            class="input">

                            <option value="">
                                Select Role
                            </option>

                            @foreach($roles as $role)

                            <option value="{{ $role->name }}">
                                {{ $role->name }}
                            </option>

                            @endforeach

                        </select>

                        @error('role')
                        <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror

                    </div>

                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 pt-4">

                    <button
                        type="button"
                        wire:click="closeCreateModal"
                        class="btn-secondary">

                        Cancel

                    </button>

                    <button
                        type="submit"
                        class="btn-primary">

                        Create User

                    </button>

                </div>

            </form>

        </div>

    </div>

    @endif


    @if($showEditModal)

    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">

        {{-- Backdrop --}}
        <div
            wire:click="closeEditModal"
            class="absolute inset-0 bg-black/40 backdrop-blur-sm">
        </div>

        {{-- Modal --}}
        <div class="relative w-full max-w-2xl card p-8">

            {{-- Header --}}
            <div class="flex items-start justify-between mb-6">

                <div>

                    <h2 class="text-2xl font-bold text-slate2-900">
                        Edit User
                    </h2>

                    <p class="text-slate2-600 mt-1">
                        Update user information and role
                    </p>

                </div>

                <button
                    wire:click="closeEditModal"
                    class="w-10 h-10 rounded-xl hover:bg-surface-100 flex items-center justify-center transition">

                    <x-heroicon-o-x-mark class="w-5 h-5" />

                </button>

            </div>

            {{-- Form --}}
            <form wire:submit="updateUser" class="space-y-5">

                {{-- Name --}}
                <div>

                    <label class="block text-sm font-medium mb-2">
                        Full Name
                    </label>

                    <input
                        type="text"
                        wire:model.live="name"
                        class="input">

                </div>

                {{-- Email --}}
                <div>

                    <label class="block text-sm font-medium mb-2">
                        Email
                    </label>

                    <input
                        type="email"
                        wire:model.live="email"
                        class="input">

                </div>

                {{-- Role --}}
                <div>

                    <label class="block text-sm font-medium mb-2">
                        Role
                    </label>

                    <select
                        wire:model.live="role"
                        class="input">

                        @foreach($roles as $roleOption)

                        <option value="{{ $roleOption->name }}">
                            {{ $roleOption->name }}
                        </option>

                        @endforeach

                    </select>

                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 pt-4">

                    <button
                        type="button"
                        wire:click="closeEditModal"
                        class="btn-secondary">

                        Cancel

                    </button>

                    <button
                        type="submit"
                        class="btn-primary">

                        Save Changes

                    </button>

                </div>

            </form>

        </div>

    </div>

    @endif
</div>