<div>
    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex items-start justify-between mb-8">

            <div>

                <p class="text-sm font-medium text-brand-600 mb-2">
                    {{ $complaint->ticket_number }}
                </p>

                <h1 class="text-4xl font-bold text-slate2-900 mb-4">
                    {{ $complaint->title }}
                </h1>

                <div class="flex items-center gap-3">

                    <span class="px-4 py-1.5 rounded-full text-sm font-medium

                        @if($complaint->status === 'submitted')
                            bg-yellow-100 text-yellow-700

                        @elseif($complaint->status === 'validated')
                            bg-sky-100 text-sky-700

                        @elseif($complaint->status === 'assigned')
                            bg-indigo-100 text-indigo-700

                        @elseif($complaint->status === 'in_progress')
                            bg-blue-100 text-blue-700

                        @elseif($complaint->status === 'waiting_input')
                            bg-orange-100 text-orange-700

                        @elseif($complaint->status === 'resolved')
                            bg-green-100 text-green-700

                        @elseif($complaint->status === 'closed')
                            bg-slate-200 text-slate-700

                        @elseif($complaint->status === 'rejected')
                            bg-red-100 text-red-700

                        @elseif($complaint->status === 'escalated')
                            bg-purple-100 text-purple-700

                        @endif
                    ">
                        {{ ucwords(str_replace('_', ' ', $complaint->status)) }}
                    </span>

                    <span class="text-sm text-slate2-500">
                        Created {{ $complaint->created_at->diffForHumans() }}
                    </span>

                </div>

            </div>

            <div class="flex items-center gap-3">

                <a href="{{ route('complaints.index') }}"
                    class="px-5 py-3 rounded-2xl border border-surface-200 bg-white hover:bg-surface-50 transition">

                    Back
                </a>

                {{-- Supervisor Validate --}}
                @if(

                auth()->user()->roles
                ->pluck('name')
                ->contains(
                fn($role) =>
                trim(strtolower($role)) === 'supervisor'
                )

                && $complaint->status === 'submitted'

                )

                <button
                    wire:click="validateComplaint"
                    class="px-5 py-3 rounded-2xl bg-emerald-500 text-white hover:bg-emerald-600 transition">

                    Validate

                </button>

                <button
                    wire:click="rejectComplaint"
                    class="px-5 py-3 rounded-2xl bg-red-500 text-white hover:bg-red-600 transition">

                    Reject

                </button>

                @endif



                @if(

                auth()->user()->roles
                ->pluck('name')
                ->contains(
                fn($role) =>
                trim(strtolower($role)) === 'supervisor'
                )

                && $complaint->status === 'validated'

                )

                <button
                    wire:click="openAssignModal"
                    class="px-5 py-3 rounded-2xl bg-indigo-600 text-white hover:bg-indigo-700 transition">

                    Assign Handler

                </button>

                @endif

                {{-- HR Workflow --}}
                @if(

                auth()->user()->roles
                ->pluck('name')
                ->contains(
                fn($role) =>
                trim(strtolower($role)) === 'hr'
                )

                && $complaint->assigned_to === auth()->id()

                )

                {{-- Start Progress --}}
                @if($complaint->status === 'assigned')

                <button
                    wire:click="startProgress"
                    class="px-5 py-3 rounded-2xl bg-blue-600 text-white hover:bg-blue-700 transition">

                    Start Progress

                </button>

                @endif

                {{-- Waiting Input --}}
                @if($complaint->status === 'in_progress')

                <button
                    wire:click="waitingInput"
                    class="px-5 py-3 rounded-2xl bg-orange-500 text-white hover:bg-orange-600 transition">

                    Waiting Input

                </button>

                <button
                    wire:click="resolveComplaint"
                    class="px-5 py-3 rounded-2xl bg-green-600 text-white hover:bg-green-700 transition">

                    Resolve

                </button>

                @endif

                {{-- Close --}}
                @if($complaint->status === 'resolved')

                <button
                    wire:click="closeComplaint"
                    class="px-5 py-3 rounded-2xl bg-slate-700 text-white hover:bg-slate-800 transition">

                    Close Complaint

                </button>

                @endif

                @if(

                auth()->user()->hasRole('admin')

                || auth()->user()->hasRole('hrd')

                || auth()->id() === $complaint->reporter_id

                )

                <button
                    type="button"
                    wire:click="openEditModal"
                    class="px-5 py-3 rounded-2xl bg-brand-600 text-white hover:bg-brand-700 transition">

                    Edit Complaint
                </button>

                @endif

                @endif

                @if(auth()->user()->hasRole('admin'))
                <button
                    wire:click="openDeleteModal"
                    class="px-5 py-3 rounded-2xl bg-red-500 text-white hover:bg-red-600 transition">

                    Delete
                </button>
                @endif

            </div>

        </div>

        {{-- Content --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Left --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Detail --}}
                <div class="bg-white rounded-3xl p-8 shadow-sm border border-surface-100">

                    <div class="flex items-center justify-between mb-6">

                        <h2 class="text-xl font-bold text-slate2-900">
                            Complaint Detail
                        </h2>

                        <span class="px-3 py-1 rounded-xl text-sm font-medium

                        @if($complaint->priority->name === 'Low')
                            bg-slate-100 text-slate-700

                        @elseif($complaint->priority->name === 'Medium')
                            bg-blue-100 text-blue-700

                        @elseif($complaint->priority->name === 'High')
                            bg-orange-100 text-orange-700

                        @elseif($complaint->priority->name === 'Critical')
                            bg-red-100 text-red-700
                        @endif
                        ">
                            {{ $complaint->priority->name }}
                        </span>

                    </div>

                    <div class="prose prose-slate max-w-none">

                        <p class="text-slate2-700 leading-8 text-[15px]">
                            {{ $complaint->description }}
                        </p>

                    </div>

                </div>

                {{-- Activity Timeline --}}
                <div class="bg-white rounded-3xl p-8 shadow-sm border border-surface-100">

                    <div class="flex items-center justify-between mb-8">

                        <div>

                            <h2 class="text-2xl font-bold text-slate2-900">
                                Activity Timeline
                            </h2>

                            <p class="text-sm text-slate2-500 mt-1">
                                Complaint activity history and updates
                            </p>

                        </div>

                    </div>

                    <div class="relative">

                        {{-- Timeline Line --}}
                        <div class="absolute left-5 top-0 bottom-0 w-px bg-surface-200"></div>

                        <div class="space-y-8">

                            @forelse($complaint->histories as $history)

                            <div class="relative flex gap-5">

                                {{-- Dot --}}
                                <div class="relative z-10 flex items-center justify-center w-10 h-10 rounded-2xl bg-brand-100 border border-brand-200">

                                    <x-heroicon-o-clock class="w-5 h-5 text-brand-600" />

                                </div>

                                {{-- Content --}}
                                <div class="flex-1 pb-2">

                                    <div class="flex items-center justify-between gap-4">

                                        <h3 class="font-semibold text-slate2-900">
                                            {{ $history->description }}
                                        </h3>

                                        <span class="text-xs text-slate2-500 whitespace-nowrap">

                                            {{ $history->created_at->diffForHumans() }}

                                        </span>

                                    </div>

                                    <div class="flex items-center gap-2 mt-2">

                                        <div class="w-7 h-7 rounded-full bg-brand-600 text-white flex items-center justify-center text-xs font-bold">

                                            {{ strtoupper(substr($history->user?->name ?? 'S', 0, 1)) }}

                                        </div>

                                        <p class="text-sm text-slate2-600">

                                            {{ $history->user?->name ?? 'System' }}

                                        </p>

                                    </div>

                                </div>

                            </div>

                            @empty

                            <div class="text-center py-12">

                                <p class="text-slate2-500">
                                    No activity history available.
                                </p>

                            </div>

                            @endforelse

                        </div>

                    </div>

                </div>

                {{-- Comments --}}
                <div class="bg-white rounded-3xl border border-surface-100 p-8 mt-8">

                    <div class="flex items-center justify-between mb-6">

                        <div>

                            <h3 class="text-2xl font-bold text-slate2-900">
                                Discussion
                            </h3>

                            <p class="text-sm text-slate2-500 mt-1">
                                Complaint discussion and responses
                            </p>

                        </div>

                    </div>

                    {{-- Comment List --}}
                    <div class="space-y-5">

                        @forelse($complaint->comments as $comment)

                        <div class="flex gap-4">

                            {{-- Avatar --}}
                            <div
                                class="w-11 h-11 rounded-2xl bg-brand-600 text-white flex items-center justify-center font-semibold shrink-0">

                                {{ strtoupper(substr($comment->user->name, 0, 1)) }}

                            </div>

                            {{-- Content --}}
                            <div class="flex-1">

                                <div class="bg-surface-50 rounded-2xl p-5">

                                    <div class="flex items-center justify-between mb-2">

                                        <div>

                                            <p class="font-semibold text-slate2-900">
                                            <div class="flex items-center gap-2">

                                                <p class="font-semibold text-slate2-900">
                                                    {{ $comment->user->name }}
                                                </p>

                                                <span class="px-2 py-1 rounded-lg bg-surface-100 text-xs text-slate2-600">

                                                    {{ $comment->user->roles->first()?->name }}

                                                </span>

                                            </div>
                                            </p>

                                            <p class="text-xs text-slate2-500">
                                                {{ $comment->created_at->diffForHumans() }}
                                            </p>

                                        </div>

                                    </div>

                                    <p class="text-slate2-700 leading-relaxed">
                                        {{ $comment->message }}
                                    </p>

                                </div>

                            </div>

                        </div>

                        @empty

                        <div
                            class="border border-dashed border-surface-200 rounded-3xl p-10 text-center">

                            <p class="text-slate2-500">
                                No discussion yet.
                            </p>

                        </div>

                        @endforelse

                    </div>

                    {{-- Comment Form --}}
                    <div class="mt-8">

                        <textarea
                            wire:model="message"
                            rows="4"
                            placeholder="Write a comment..."
                            class="input resize-none"></textarea>

                        @error('message')

                        <p class="text-sm text-red-500 mt-2">
                            {{ $message }}
                        </p>

                        @enderror

                        <div class="flex justify-end mt-4">

                            <button
                                wire:click="sendComment"
                                class="px-5 py-3 rounded-2xl bg-brand-600 text-white hover:bg-brand-700 transition">

                                Send Comment

                            </button>

                        </div>

                    </div>

                </div>

            </div>

            {{-- Right --}}
            <div class="space-y-6">

                {{-- Status --}}
                @if(
                auth()->user()->hasRole('admin') ||
                auth()->user()->hasRole('hrd')
                )
                <div class="card p-6">

                    <div class="flex items-center justify-between mb-4">

                        <h3 class="font-semibold text-slate2-900">
                            Status
                        </h3>

                        <span class="
                        px-3 py-1 rounded-full text-xs font-semibold

                            @if($complaint->status === 'submitted')
                                bg-yellow-100 text-yellow-700

                            @elseif($complaint->status === 'validated')
                                bg-sky-100 text-sky-700

                            @elseif($complaint->status === 'assigned')
                                bg-indigo-100 text-indigo-700

                            @elseif($complaint->status === 'in_progress')
                                bg-blue-100 text-blue-700

                            @elseif($complaint->status === 'waiting_input')
                                bg-orange-100 text-orange-700

                            @elseif($complaint->status === 'resolved')
                                bg-green-100 text-green-700

                            @elseif($complaint->status === 'closed')
                                bg-slate-200 text-slate-700

                            @elseif($complaint->status === 'rejected')
                                bg-red-100 text-red-700

                            @elseif($complaint->status === 'escalated')
                                bg-purple-100 text-purple-700

                            @endif
                        ">
                            {{ ucwords(str_replace('_', ' ', $complaint->status)) }}
                        </span>

                    </div>

                    <div class="space-y-4">

                        <div class="relative">

                            <select
                                wire:model.live="status"
                                style="background-image: none;"
                                class="
                                w-full
                                appearance-none
                                rounded-2xl
                                border
                                border-surface-200
                                bg-white
                                px-4
                                py-3
                                pr-12
                                text-sm
                                font-medium
                                text-slate2-800
                                shadow-sm
                                transition
                                cursor-pointer
                                focus:outline-none
                                focus:border-brand-500
                                focus:ring-4
                                focus:ring-brand-100
                            ">

                                <option value="submitted">
                                    Submitted
                                </option>

                                <option value="validated">
                                    Validated
                                </option>

                                <option value="assigned">
                                    Assigned
                                </option>

                                <option value="in_progress">
                                    In Progress
                                </option>

                                <option value="waiting_input">
                                    Waiting Input
                                </option>

                                <option value="resolved">
                                    Resolved
                                </option>

                                <option value="closed">
                                    Closed
                                </option>

                                <option value="rejected">
                                    Rejected
                                </option>

                                <option value="escalated">
                                    Escalated
                                </option>
                            </select>

                            <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none">

                                <x-heroicon-o-chevron-down class="w-5 h-5 text-slate2-400" />

                            </div>

                        </div>

                        <button
                            wire:click="updateStatus"
                            wire:loading.attr="disabled"
                            wire:target="updateStatus"
                            class="w-full px-4 py-3 rounded-2xl bg-brand-600 text-white font-medium hover:bg-brand-700 transition">

                            <span
                                wire:loading.remove
                                wire:target="updateStatus">

                                Update Status
                            </span>

                            <span
                                wire:loading
                                wire:target="updateStatus">

                                Updating...
                            </span>

                        </button>

                    </div>

                </div>
                @endif

                {{-- Information --}}
                <div class="card p-6">

                    <h3 class="font-semibold text-slate2-900 mb-6">
                        Information
                    </h3>

                    <div class="space-y-6">

                        <div>
                            <p class="text-sm text-slate2-500 mb-1">
                                Reporter
                            </p>

                            <p class="font-semibold text-slate2-900">
                                {{ $complaint->reporter->name }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-slate2-500 mb-1">
                                Category
                            </p>

                            <p class="font-semibold text-slate2-900">
                                {{ $complaint->category->name }}
                            </p>
                        </div>

                        <div>

                            <p class="text-sm text-slate2-500 mb-2">
                                Priority
                            </p>

                            <span class="inline-flex items-center px-3 py-1 rounded-xl text-sm font-medium

                            @if($complaint->priority->name === 'Low')
                                bg-slate-100 text-slate-700

                            @elseif($complaint->priority->name === 'Medium')
                                bg-blue-100 text-blue-700

                            @elseif($complaint->priority->name === 'High')
                                bg-orange-100 text-orange-700

                            @elseif($complaint->priority->name === 'Critical')
                                bg-red-100 text-red-700
                            @endif
                        ">

                                {{ $complaint->priority->name }}

                            </span>

                        </div>

                        <div>
                            <p class="text-sm text-slate2-500 mb-1">
                                Created At
                            </p>

                            <p class="font-semibold text-slate2-900">
                                {{ $complaint->created_at->format('d M Y H:i') }}
                            </p>
                        </div>

                    </div>

                </div>
            </div>

        </div>

    </div>

    {{-- Edit Modal --}}
    @if($showEditModal)

    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">

        <div
            class="w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-hidden">

            {{-- Header --}}
            <div class="flex items-center justify-between px-8 py-6 border-b border-surface-100">

                <div>

                    <h2 class="text-2xl font-bold text-slate2-900">
                        Edit Complaint
                    </h2>

                    <p class="text-sm text-slate2-500 mt-1">
                        Update complaint information
                    </p>

                </div>

                <button
                    wire:click="closeEditModal"
                    class="w-10 h-10 rounded-2xl hover:bg-surface-100 flex items-center justify-center transition">

                    <x-heroicon-o-x-mark class="w-5 h-5 text-slate2-500" />

                </button>

            </div>

            {{-- Body --}}
            <div class="p-8 space-y-6">

                {{-- Title --}}
                <div>

                    <label class="block text-sm font-medium text-slate2-700 mb-2">
                        Complaint Title
                    </label>

                    <input
                        type="text"
                        wire:model.live="title"
                        class="input"
                        placeholder="Enter complaint title">

                    @error('title')
                    <p class="text-sm text-red-500 mt-2">
                        {{ $message }}
                    </p>
                    @enderror

                </div>

                {{-- Category & Priority --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>

                        <label class="block text-sm font-medium text-slate2-700 mb-2">
                            Category
                        </label>

                        <select
                            wire:model.live="category_id"
                            class="input">

                            <option value="">
                                Select Category
                            </option>

                            @foreach($categories as $category)

                            <option value="{{ $category->id }}">
                                {{ $category->name }}
                            </option>

                            @endforeach

                        </select>

                        @error('category_id')
                        <p class="text-sm text-red-500 mt-2">
                            {{ $message }}
                        </p>
                        @enderror

                    </div>

                    <div>

                        <label class="block text-sm font-medium text-slate2-700 mb-2">
                            Priority
                        </label>

                        <select
                            wire:model.live="priority_id"
                            class="input">

                            <option value="">
                                Select Priority
                            </option>

                            @foreach($priorities as $priority)

                            <option value="{{ $priority->id }}">
                                {{ $priority->name }}
                            </option>

                            @endforeach

                        </select>

                        @error('priority_id')
                        <p class="text-sm text-red-500 mt-2">
                            {{ $message }}
                        </p>
                        @enderror

                    </div>

                </div>

                {{-- Description --}}
                <div>

                    <label class="block text-sm font-medium text-slate2-700 mb-2">
                        Description
                    </label>

                    <textarea
                        rows="6"
                        wire:model.live="description"
                        class="input resize-none"
                        placeholder="Describe your complaint..."></textarea>

                    @error('description')
                    <p class="text-sm text-red-500 mt-2">
                        {{ $message }}
                    </p>
                    @enderror

                </div>

            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-4 px-8 py-6 border-t border-surface-100 bg-surface-50">

                <button
                    wire:click="closeEditModal"
                    class="px-5 py-3 rounded-2xl border border-surface-200 bg-white hover:bg-surface-100 transition">

                    Cancel
                </button>

                <button
                    wire:click="updateComplaint"
                    wire:loading.attr="disabled"
                    wire:target="updateComplaint"
                    class="px-5 py-3 rounded-2xl bg-brand-600 text-white font-medium hover:bg-brand-700 transition">

                    <span
                        wire:loading.remove
                        wire:target="updateComplaint">

                        Save Changes
                    </span>

                    <span
                        wire:loading
                        wire:target="updateComplaint">

                        Saving...
                    </span>

                </button>

            </div>

        </div>

    </div>

    @endif


    @if($showDeleteModal)

    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">

        <div
            class="w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden">

            {{-- Header --}}
            <div class="p-8 text-center">

                <div class="w-20 h-20 mx-auto rounded-full bg-red-100 flex items-center justify-center mb-5">

                    <x-heroicon-o-trash class="w-10 h-10 text-red-500" />

                </div>

                <h2 class="text-2xl font-bold text-slate2-900">
                    Delete Complaint
                </h2>

                <p class="text-slate2-500 mt-3 leading-relaxed">
                    Are you sure you want to delete this complaint?
                    This action cannot be undone.
                </p>

            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-center gap-4 px-8 py-6 border-t border-surface-100 bg-surface-50">

                <button
                    wire:click="closeDeleteModal"
                    class="px-5 py-3 rounded-2xl border border-surface-200 bg-white hover:bg-surface-100 transition">

                    Cancel
                </button>


                <button
                    wire:click="deleteComplaint"
                    wire:loading.attr="disabled"
                    wire:target="deleteComplaint"
                    class="px-5 py-3 rounded-2xl bg-red-500 text-white hover:bg-red-600 transition">

                    <span
                        wire:loading.remove
                        wire:target="deleteComplaint">

                        Delete
                    </span>

                    <span
                        wire:loading
                        wire:target="deleteComplaint">

                        Deleting...
                    </span>

                </button>

            </div>

        </div>

    </div>

    @endif

    @if($showAssignModal)

    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">

        <div
            class="w-full max-w-lg bg-white rounded-3xl shadow-2xl overflow-hidden">

            {{-- Header --}}
            <div class="flex items-center justify-between px-8 py-6 border-b border-surface-100">

                <div>

                    <h2 class="text-2xl font-bold text-slate2-900">
                        Assign Handler
                    </h2>

                    <p class="text-sm text-slate2-500 mt-1">
                        Assign complaint to HR handler
                    </p>

                </div>

                <button
                    wire:click="$set('showAssignModal', false)"
                    class="w-10 h-10 rounded-2xl hover:bg-surface-100 flex items-center justify-center transition">

                    <x-heroicon-o-x-mark class="w-5 h-5 text-slate2-500" />

                </button>

            </div>

            {{-- Body --}}
            <div class="p-8">

                <label class="block text-sm font-medium text-slate2-700 mb-3">

                    Select Handler

                </label>

                <select
                    wire:model.live="assigned_to"
                    class="input">

                    <option value="">
                        Select HR Handler
                    </option>

                    @foreach($handlers as $handler)

                    <option value="{{ $handler->id }}">
                        {{ $handler->name }}
                    </option>

                    @endforeach

                </select>

                @error('assigned_to')

                <p class="text-sm text-red-500 mt-2">
                    {{ $message }}
                </p>

                @enderror

            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-4 px-8 py-6 border-t border-surface-100 bg-surface-50">

                <button
                    wire:click="$set('showAssignModal', false)"
                    class="px-5 py-3 rounded-2xl border border-surface-200 bg-white hover:bg-surface-100 transition">

                    Cancel

                </button>

                <button
                    wire:click="assignHandler"
                    class="px-5 py-3 rounded-2xl bg-indigo-600 text-white hover:bg-indigo-700 transition">

                    Assign Handler

                </button>

            </div>

        </div>

    </div>

    @endif
</div>
