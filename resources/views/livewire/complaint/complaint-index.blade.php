<div>
    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">

            <div>

                <h1 class="text-3xl font-bold text-slate2-900">
                    Complaints
                </h1>

                <p class="text-slate2-600 mt-1">
                    Manage employee complaints
                </p>

            </div>

            <button
                wire:click="$set('showModal', true)"
                class="btn-primary flex items-center gap-2">

                <x-heroicon-o-plus class="w-5 h-5" />

                Create Complaint

            </button>

        </div>

        {{-- Filters --}}
        <div class="card p-5">

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                {{-- Search --}}
                <div>

                    <input
                        type="text"
                        wire:model.live="search"
                        placeholder="Search complaints..."
                        class="input">

                </div>

                {{-- Status --}}
                <div>

                    <select
                        wire:model.live="status"
                        class="input">

                        <option value="">
                            All Status
                        </option>

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

                        <option value="waiting_feedback">
                            Waiting Feedback
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

                </div>

                {{-- Priority --}}
                <div>

                    <select
                        wire:model.live="priority"
                        class="input">

                        <option value="">
                            All Priority
                        </option>

                        <option value="Low">
                            Low
                        </option>

                        <option value="Medium">
                            Medium
                        </option>

                        <option value="High">
                            High
                        </option>

                        <option value="Critical">
                            Critical
                        </option>

                    </select>

                </div>

                {{-- Sort --}}
                <div>

                    <select
                        wire:model.live="sort"
                        class="input">

                        <option value="latest">
                            Latest
                        </option>

                        <option value="oldest">
                            Oldest
                        </option>

                    </select>

                </div>

            </div>

        </div>

        {{-- Table --}}
        <div class="card overflow-hidden">

            <div class="overflow-x-auto">

                <table class="w-full">

                    <thead class="bg-surface-100">

                        <tr>

                            <th class="text-left px-6 py-4 text-sm font-semibold text-brand-600">
                                Ticket
                            </th>

                            <th class="text-left px-6 py-4 text-sm font-semibold">
                                Title
                            </th>

                            <th class="text-left px-6 py-4 text-sm font-semibold">
                                Category
                            </th>

                            <th class="text-left px-6 py-4 text-sm font-semibold">
                                Priority
                            </th>

                            <th class="text-left px-6 py-4 text-sm font-semibold">
                                Status
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($complaints as $complaint)

                        <tr
                            onclick="window.location='{{ route('complaints.show', $complaint) }}'"
                            class="border-t border-surface-100 hover:bg-surface-50 transition cursor-pointer">

                            <td class="px-6 py-4 font-semibold text-brand-600">
                                {{ $complaint->ticket_number }}
                            </td>

                            <td class="px-6 py-4 font-medium">
                                {{ $complaint->title }}
                            </td>

                            <td class="px-6 py-4">
                                {{ $complaint->category->name }}
                            </td>

                            <td class="px-6 py-4">

                                <span class="px-3 py-1 rounded-full text-xs font-medium

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

                            </td>

                            <td class="px-6 py-4">

                                <span class="
                                    px-3 py-1 rounded-full text-xs font-semibold

                                    @if($complaint->status === 'submitted')
                                        bg-yellow-100 text-yellow-700

                                    @elseif($complaint->status === 'reviewed')
                                        bg-indigo-100 text-indigo-700

                                    @elseif($complaint->status === 'in_progress')
                                        bg-blue-100 text-blue-700

                                    @elseif($complaint->status === 'resolved')
                                        bg-green-100 text-green-700

                                    @elseif($complaint->status === 'rejected')
                                        bg-red-100 text-red-700
                                    @endif
                                ">

                                    {{ ucwords(str_replace('_', ' ', $complaint->status)) }}

                                </span>

                            </td>

                        </tr>

                        @empty

                        <tr>

                            <td colspan="5" class="px-6 py-10 text-center text-slate2-500">

                                No complaints found

                            </td>

                        </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    @if($showModal)

    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">

        {{-- Backdrop --}}
        <div
            wire:click="closeModal"
            class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

        {{-- Modal --}}
        <div class="relative w-full max-w-2xl card p-8 transition-all duration-200">

            {{-- Header --}}
            <div class="flex items-start justify-between mb-6">

                <div>

                    <h2 class="text-2xl font-bold text-slate2-900">
                        Create Complaint
                    </h2>

                    <p class="text-slate2-600 mt-1">
                        Submit a new employee complaint
                    </p>

                </div>

                <button
                    wire:click="closeModal"
                    class="w-10 h-10 rounded-xl hover:bg-surface-100 flex items-center justify-center transition">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>

            </div>

            {{-- Form --}}
            <form wire:submit="save" class="space-y-5">

                {{-- Title --}}
                <div>

                    <label class="block text-sm font-medium mb-2">
                        Complaint Title
                    </label>

                    <input
                        type="text"
                        wire:model.live="title"
                        class="input"
                        placeholder="Enter complaint title">

                    @error('title')

                    <p class="text-sm text-red-500 mt-1">
                        {{ $message }}
                    </p>

                    @enderror

                </div>

                {{-- Category --}}
                <div>

                    <label class="block text-sm font-medium mb-2">
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

                    <p class="text-sm text-red-500 mt-1">
                        {{ $message }}
                    </p>

                    @enderror

                </div>

                {{-- Priority --}}
                <div>

                    <label class="block text-sm font-medium mb-2">
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

                    <p class="text-sm text-red-500 mt-1">
                        {{ $message }}
                    </p>

                    @enderror

                </div>

                {{-- Description --}}
                <div>

                    <label class="block text-sm font-medium mb-2">
                        Description
                    </label>

                    <textarea
                        wire:model.live="description"
                        rows="5"
                        class="input"
                        placeholder="Describe the complaint..."></textarea>

                    @error('description')

                    <p class="text-sm text-red-500 mt-1">
                        {{ $message }}
                    </p>

                    @enderror

                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 pt-4">

                    <button
                        type="button"
                        wire:click="closeModal"
                        class="btn-secondary">
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="btn-primary"
                        wire:loading.attr="disabled"
                        wire:target="save">

                        <span wire:loading.remove wire:target="save">
                            Submit Complaint
                        </span>

                        <span wire:loading wire:target="save">
                            Saving...
                        </span>

                    </button>
                </div>

            </form>

        </div>

    </div>

    @endif

</div>