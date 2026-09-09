<div class="max-w-7xl mx-auto space-y-8">

    {{-- Header --}}
    <div>

        <h1 class="text-4xl font-bold tracking-tight text-slate2-900">
            Dashboard
        </h1>

        <p class="text-slate2-500 mt-2 text-base">
            Monitor complaint statistics and system activity.
        </p>

    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

        {{-- Total --}}
        <div class="bg-white rounded-3xl p-8 border border-surface-100 shadow-[0_4px_20px_rgba(15,23,42,0.04)] hover:-translate-y-1 transition-all duration-300">

            <div class="flex items-center justify-between">

                <div>

                    <p class="text-sm font-medium text-slate2-500">
                        Total Complaints
                    </p>

                    <h2 class="text-5xl font-bold text-slate2-900 mt-4">
                        {{ $totalComplaints }}
                    </h2>

                </div>

                <div class="w-16 h-16 rounded-3xl bg-brand-100 flex items-center justify-center">

                    <x-heroicon-o-chat-bubble-left-right class="w-8 h-8 text-brand-600" />

                </div>

            </div>

        </div>

        {{-- In Progress --}}
        <div class="bg-white rounded-3xl p-8 border border-surface-100 shadow-[0_4px_20px_rgba(15,23,42,0.04)] hover:-translate-y-1 transition-all duration-300">

            <div class="flex items-center justify-between">

                <div>

                    <p class="text-sm font-medium text-slate2-500">
                        In Progress
                    </p>

                    <h2 class="text-5xl font-bold text-amber-500 mt-4">
                        {{ $inProgress }}
                    </h2>

                </div>

                <div class="w-16 h-16 rounded-3xl bg-amber-100 flex items-center justify-center">

                    <x-heroicon-o-clock class="w-8 h-8 text-amber-500" />

                </div>

            </div>

        </div>

        {{-- Resolved --}}
        <div class="bg-white rounded-3xl p-8 border border-surface-100 shadow-[0_4px_20px_rgba(15,23,42,0.04)] hover:-translate-y-1 transition-all duration-300">

            <div class="flex items-center justify-between">

                <div>

                    <p class="text-sm font-medium text-slate2-500">
                        Resolved
                    </p>

                    <h2 class="text-5xl font-bold text-emerald-500 mt-4">
                        {{ $resolved }}
                    </h2>

                </div>

                <div class="w-16 h-16 rounded-3xl bg-emerald-100 flex items-center justify-center">

                    <x-heroicon-o-check-circle class="w-8 h-8 text-emerald-500" />

                </div>

            </div>

        </div>

        {{-- Critical --}}
        <div class="bg-white rounded-3xl p-8 border border-surface-100 shadow-[0_4px_20px_rgba(15,23,42,0.04)] hover:-translate-y-1 transition-all duration-300">

            <div class="flex items-center justify-between">

                <div>

                    <p class="text-sm font-medium text-slate2-500">
                        Critical
                    </p>

                    <h2 class="text-5xl font-bold text-red-500 mt-4">
                        {{ $critical }}
                    </h2>

                </div>

                <div class="w-16 h-16 rounded-3xl bg-red-100 flex items-center justify-center">

                    <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-red-500" />

                </div>

            </div>

        </div>

    </div>



    {{-- Recent Complaints --}}
    <div class="bg-white mt-6 rounded-3xl border border-surface-100 overflow-hidden shadow-[0_4px_20px_rgba(15,23,42,0.04)]">

        {{-- Header --}}
        <div class="flex items-center justify-between px-8 py-6 border-b border-surface-100">

            <div>

                <h2 class="text-2xl font-bold text-slate2-900">
                    Recent Complaints
                </h2>

                <p class="text-sm text-slate2-500 mt-1">
                    Latest submitted complaints
                </p>

            </div>

            <a
                href="{{ route('complaints.index') }}"
                class="text-sm font-semibold text-brand-600 hover:text-brand-700 transition">

                View All
            </a>

        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">

            <table class="w-full">

                <thead class="bg-slate-50/80 border-b border-surface-100">

                    <tr>

                        <th class="text-left px-8 py-5 text-xs font-bold uppercase tracking-wider text-slate2-500">
                            Ticket
                        </th>

                        <th class="text-left px-8 py-5 text-xs font-bold uppercase tracking-wider text-slate2-500">
                            Title
                        </th>

                        <th class="text-left px-8 py-5 text-xs font-bold uppercase tracking-wider text-slate2-500">
                            Status
                        </th>

                        <th class="text-left px-8 py-5 text-xs font-bold uppercase tracking-wider text-slate2-500">
                            Priority
                        </th>

                        <th class="text-left px-8 py-5 text-xs font-bold uppercase tracking-wider text-slate2-500">
                            Created
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($recentComplaints as $complaint)

                    <tr class="border-t border-surface-100 hover:bg-slate-50/70 transition-all duration-200">

                        {{-- Ticket --}}
                        <td class="px-8 py-6">

                            <a
                                href="{{ route('complaints.show', $complaint->ticket_number) }}"
                                class="font-semibold text-brand-600 hover:text-brand-700 transition">

                                {{ $complaint->ticket_number }}

                            </a>

                        </td>

                        {{-- Title --}}
                        <td class="px-8 py-6">

                            <p class="font-semibold text-slate2-900">
                                {{ $complaint->title }}
                            </p>

                        </td>

                        {{-- Status --}}
                        <td class="px-8 py-6">

                            <span class="px-3 py-1.5 rounded-xl text-xs font-semibold

                                @if($complaint->status === 'submitted')
                                    bg-yellow-100 text-yellow-700

                                @elseif($complaint->status === 'in_progress')
                                    bg-blue-100 text-blue-700

                                @elseif($complaint->status === 'resolved')
                                    bg-green-100 text-green-700

                                @elseif($complaint->status === 'closed')
                                    bg-slate-100 text-slate-700
                                @endif
                            ">

                                {{ ucwords(str_replace('_', ' ', $complaint->status)) }}

                            </span>

                        </td>

                        {{-- Priority --}}
                        <td class="px-8 py-6">

                            <span class="px-3 py-1.5 rounded-xl text-xs font-semibold

                                @if($complaint->priority?->name === 'Low')
                                    bg-slate-100 text-slate-700

                                @elseif($complaint->priority?->name === 'Medium')
                                    bg-blue-100 text-blue-700

                                @elseif($complaint->priority?->name === 'High')
                                    bg-orange-100 text-orange-700

                                @elseif($complaint->priority?->name === 'Critical')
                                    bg-red-100 text-red-700
                                @endif
                            ">

                                {{ $complaint->priority?->name }}

                            </span>

                        </td>

                        {{-- Created --}}
                        <td class="px-8 py-6 text-sm text-slate2-500">

                            {{ $complaint->created_at->diffForHumans() }}

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="5" class="px-8 py-16 text-center text-slate2-500">

                            No complaints found.

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>


    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">

        @can('create', \App\Models\Complaint::class)
        {{-- Create Complaint --}}
        <button
            wire:click="openCreateModal"
            class="group bg-white min-h-[190px] rounded-3xl p-8 border border-surface-100 shadow-[0_4px_20px_rgba(15,23,42,0.04)] hover:-translate-y-1 hover:border-brand-200 transition-all duration-300 text-left">

            <div class="flex items-center justify-between gap-6 h-full">

                <div class="flex-1">

                    <p class="text-xl font-bold text-slate2-900">
                        Create Complaint
                    </p>

                    <p class="text-sm text-slate2-500 mt-3 leading-7">
                        Submit a new employee complaint quickly and efficiently.
                    </p>

                </div>

                <div class="w-16 h-16 shrink-0 rounded-3xl bg-brand-100 flex items-center justify-center group-hover:bg-brand-600 transition-all duration-300">

                    <x-heroicon-o-plus class="w-8 h-8 text-brand-600 group-hover:text-white transition" />

                </div>

            </div>

        </button>
        @endcan

        {{-- View Complaints --}}
        <a
            href="{{ route('complaints.index') }}"
            class="group bg-white min-h-[190px] rounded-3xl p-8 border border-surface-100 shadow-[0_4px_20px_rgba(15,23,42,0.04)] hover:-translate-y-1 hover:border-blue-200 transition-all duration-300">

            <div class="flex items-center justify-between gap-6 h-full">

                <div class="flex-1">

                    <p class="text-xl font-bold text-slate2-900">
                        View Complaints
                    </p>

                    <p class="text-sm text-slate2-500 mt-3 leading-7">
                        Manage and monitor complaint workflow and ticket activity.
                    </p>

                </div>

                <div class="w-16 h-16 shrink-0 rounded-3xl bg-blue-100 flex items-center justify-center group-hover:bg-blue-600 transition-all duration-300">

                    <x-heroicon-o-folder-open class="w-8 h-8 text-blue-600 group-hover:text-white transition" />

                </div>

            </div>

        </a>

        {{-- Reports --}}
        <div
            class="group bg-white min-h-[190px] rounded-3xl p-8 border border-dashed border-surface-200 shadow-[0_4px_20px_rgba(15,23,42,0.04)] opacity-80">

            <div class="flex items-center justify-between gap-6 h-full">

                <div class="flex-1">

                    <p class="text-xl font-bold text-slate2-900">
                        Reports
                    </p>

                    <p class="text-sm text-slate2-500 mt-3 leading-7">
                        Export and analyze complaint reports and statistics.
                    </p>

                    <span class="inline-flex mt-5 px-3 py-1.5 rounded-xl bg-slate-100 text-xs font-semibold text-slate2-600">

                        Coming Soon

                    </span>

                </div>

                <div class="w-16 h-16 shrink-0 rounded-3xl bg-slate-100 flex items-center justify-center">

                    <x-heroicon-o-chart-bar class="w-8 h-8 text-slate2-500" />

                </div>

            </div>

        </div>

    </div>

    {{-- Create Modal --}}
    @if($showCreateModal)

    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">

        {{-- Backdrop --}}
        <div
            wire:click="closeCreateModal"
            class="absolute inset-0 bg-black/40 backdrop-blur-sm">
        </div>

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
                    wire:click="closeCreateModal"
                    class="w-10 h-10 rounded-xl hover:bg-surface-100 flex items-center justify-center transition">

                    <x-heroicon-o-x-mark class="w-5 h-5" />

                </button>

            </div>

            {{-- Form --}}
            <form wire:submit="saveComplaint" class="space-y-5">

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

                {{-- Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

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
                        wire:click="closeCreateModal"
                        class="btn-secondary">

                        Cancel

                    </button>

                    <button
                        type="submit"
                        class="btn-primary"
                        wire:loading.attr="disabled"
                        wire:target="saveComplaint">

                        <span
                            wire:loading.remove
                            wire:target="saveComplaint">

                            Submit Complaint

                        </span>

                        <span
                            wire:loading
                            wire:target="saveComplaint">

                            Saving...

                        </span>

                    </button>

                </div>

            </form>

        </div>

    </div>

    @endif
</div>