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

                @can('validateComplaint', $complaint)
                <button
                    wire:click="validateComplaint"
                    class="px-5 py-3 rounded-2xl bg-emerald-500 text-white hover:bg-emerald-600 transition">
                    Validate
                </button>
                @endcan

                @can('rejectComplaint', $complaint)
                <button
                    wire:click="rejectComplaint"
                    class="px-5 py-3 rounded-2xl bg-red-500 text-white hover:bg-red-600 transition">
                    Reject
                </button>
                @endcan

                @can('assignHandler', $complaint)
                <button
                    wire:click="openAssignModal"
                    class="px-5 py-3 rounded-2xl bg-indigo-600 text-white hover:bg-indigo-700 transition">
                    Assign Handler
                </button>
                @endcan

                @can('startProgress', $complaint)
                <button
                    wire:click="startProgress"
                    class="px-5 py-3 rounded-2xl bg-blue-600 text-white hover:bg-blue-700 transition">
                    Start Progress
                </button>
                @endcan

                @can('requestInput', $complaint)
                <button
                    wire:click="waitingInput"
                    class="px-5 py-3 rounded-2xl bg-orange-500 text-white hover:bg-orange-600 transition">
                    Waiting Input
                </button>
                @endcan

                @can('resolveComplaint', $complaint)
                <button
                    wire:click="resolveComplaint"
                    class="px-5 py-3 rounded-2xl bg-green-600 text-white hover:bg-green-700 transition">
                    Resolve
                </button>
                @endcan

                @can('closeComplaint', $complaint)
                <button
                    wire:click="closeComplaint"
                    class="px-5 py-3 rounded-2xl bg-slate-700 text-white hover:bg-slate-800 transition">
                    Close Complaint
                </button>
                @endcan

                @can('escalateComplaint', $complaint)
                <button
                    wire:click="openEscalationModal"
                    class="px-5 py-3 rounded-2xl bg-purple-600 text-white hover:bg-purple-700 transition">
                    Escalate
                </button>
                @endcan

                @can('update', $complaint)
                <button
                    type="button"
                    wire:click="openEditModal"
                    class="px-5 py-3 rounded-2xl bg-brand-600 text-white hover:bg-brand-700 transition">
                    Edit Complaint
                </button>
                @endcan



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

                {{-- Resolution & Follow-up --}}
                <div class="bg-white rounded-3xl p-8 shadow-sm border border-surface-100">

                    <div class="flex items-start justify-between gap-4 mb-6">
                        <div>
                            <h2 class="text-xl font-bold text-slate2-900">
                                Resolution & Follow-up
                            </h2>
                            <p class="text-sm text-slate2-500 mt-1">
                                Root cause, solution, evidence, and post-resolution monitoring
                            </p>
                        </div>

                        @if($complaint->follow_up_required)
                            <span class="px-3 py-1 rounded-xl text-sm font-medium {{ $complaint->follow_up_completed_at ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ $complaint->follow_up_completed_at ? 'Follow-up Complete' : 'Follow-up Required' }}
                            </span>
                        @elseif($complaint->resolution_summary)
                            <span class="px-3 py-1 rounded-xl text-sm font-medium bg-slate-100 text-slate-700">
                                No Follow-up Required
                            </span>
                        @endif
                    </div>

                    @can('updateResolution', $complaint)
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-semibold text-slate2-700 mb-2">
                                    Root Cause Category
                                </label>
                                <select wire:model="root_cause_category" class="input">
                                    <option value="">Select root cause category</option>
                                    @foreach($rootCauseCategories as $rootCauseCategory)
                                        <option value="{{ $rootCauseCategory->value }}">
                                            {{ $rootCauseCategory->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('root_cause_category')
                                    <p class="text-sm text-red-500 mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate2-700 mb-2">
                                    Root Cause Analysis
                                </label>
                                <textarea
                                    wire:model="root_cause"
                                    rows="4"
                                    class="input resize-none"
                                    placeholder="Describe the verified root cause of the complaint..."></textarea>
                                @error('root_cause')
                                    <p class="text-sm text-red-500 mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate2-700 mb-2">
                                    Resolution Summary
                                </label>
                                <textarea
                                    wire:model="resolution_summary"
                                    rows="4"
                                    class="input resize-none"
                                    placeholder="Describe the solution or corrective action taken..."></textarea>
                                @error('resolution_summary')
                                    <p class="text-sm text-red-500 mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate2-700 mb-2">
                                    Evidence / Reference
                                </label>
                                <textarea
                                    wire:model="resolution_evidence"
                                    rows="3"
                                    class="input resize-none"
                                    placeholder="Optional reference, verification note, document number, or evidence description..."></textarea>
                                @error('resolution_evidence')
                                    <p class="text-sm text-red-500 mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="rounded-2xl border border-surface-200 p-5 space-y-4">
                                <label class="flex items-center gap-3">
                                    <input
                                        type="checkbox"
                                        wire:model.live="follow_up_required"
                                        class="rounded border-surface-300 text-brand-600 focus:ring-brand-500">
                                    <span class="font-semibold text-slate2-800">
                                        Follow-up monitoring is required
                                    </span>
                                </label>

                                @if($follow_up_required)
                                    <div>
                                        <label class="block text-sm font-medium text-slate2-700 mb-2">
                                            Follow-up Due
                                        </label>
                                        <input
                                            type="datetime-local"
                                            wire:model="follow_up_due_at"
                                            class="input">
                                        @error('follow_up_due_at')
                                            <p class="text-sm text-red-500 mt-2">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endif
                            </div>

                            <div class="flex justify-end">
                                <button
                                    wire:click="saveResolutionAnalysis"
                                    wire:loading.attr="disabled"
                                    wire:target="saveResolutionAnalysis"
                                    class="px-5 py-3 rounded-2xl bg-brand-600 text-white hover:bg-brand-700 transition">
                                    Save Resolution Analysis
                                </button>
                            </div>
                        </div>
                    @else
                        @if($complaint->root_cause || $complaint->resolution_summary)
                            <div class="space-y-5">
                                <div>
                                    <p class="text-xs uppercase tracking-wide font-semibold text-slate2-400 mb-2">
                                        Root Cause Category
                                    </p>
                                    <p class="font-semibold text-slate2-800">
                                        {{ \App\Enums\RootCauseCategory::tryFrom((string) $complaint->root_cause_category)?->label() ?? 'Not recorded.' }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs uppercase tracking-wide font-semibold text-slate2-400 mb-2">
                                        Root Cause Analysis
                                    </p>
                                    <p class="text-slate2-700 leading-7">
                                        {{ $complaint->root_cause ?: 'Not recorded.' }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs uppercase tracking-wide font-semibold text-slate2-400 mb-2">
                                        Resolution
                                    </p>
                                    <p class="text-slate2-700 leading-7">
                                        {{ $complaint->resolution_summary ?: 'Not recorded.' }}
                                    </p>
                                </div>

                                @if($complaint->resolution_evidence)
                                    <div>
                                        <p class="text-xs uppercase tracking-wide font-semibold text-slate2-400 mb-2">
                                            Evidence / Reference
                                        </p>
                                        <p class="text-slate2-700 leading-7">
                                            {{ $complaint->resolution_evidence }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="rounded-2xl border border-dashed border-surface-200 p-6 text-sm text-slate2-500">
                                Resolution analysis has not been recorded yet.
                            </div>
                        @endif
                    @endcan

                    @if($complaint->follow_up_required && $complaint->status === 'resolved')
                        <div class="mt-6 pt-6 border-t border-surface-100">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                                <div>
                                    <p class="text-xs uppercase tracking-wide font-semibold text-slate2-400 mb-1">
                                        Follow-up Due
                                    </p>
                                    <p class="font-semibold text-slate2-800">
                                        {{ $complaint->follow_up_due_at?->format('d M Y H:i') ?? '-' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide font-semibold text-slate2-400 mb-1">
                                        Completed At
                                    </p>
                                    <p class="font-semibold text-slate2-800">
                                        {{ $complaint->follow_up_completed_at?->format('d M Y H:i') ?? 'Pending' }}
                                    </p>
                                </div>
                            </div>

                            @can('completeFollowUp', $complaint)
                                <div>
                                    <label class="block text-sm font-semibold text-slate2-700 mb-2">
                                        Follow-up Evaluation Note
                                    </label>
                                    <textarea
                                        wire:model="follow_up_note"
                                        rows="3"
                                        class="input resize-none"
                                        placeholder="Record monitoring result, recurrence check, and evaluation..."></textarea>
                                    @error('follow_up_note')
                                        <p class="text-sm text-red-500 mt-2">{{ $message }}</p>
                                    @enderror

                                    <div class="flex justify-end mt-4">
                                        <button
                                            wire:click="completeFollowUp"
                                            class="px-5 py-3 rounded-2xl bg-emerald-600 text-white hover:bg-emerald-700 transition">
                                            Complete Follow-up
                                        </button>
                                    </div>
                                </div>
                            @else
                                @if($complaint->follow_up_note)
                                    <div>
                                        <p class="text-xs uppercase tracking-wide font-semibold text-slate2-400 mb-2">
                                            Follow-up Evaluation
                                        </p>
                                        <p class="text-slate2-700 leading-7">
                                            {{ $complaint->follow_up_note }}
                                        </p>
                                    </div>
                                @endif
                            @endcan
                        </div>
                    @endif

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
                                PIC / HRD Handler
                            </p>

                            <p class="font-semibold text-slate2-900">
                                {{ $complaint->handler?->name ?? 'Not assigned' }}
                            </p>
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

                {{-- SLA Monitoring --}}
                <div class="card p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="font-semibold text-slate2-900">
                            SLA Monitoring
                        </h3>

                        @if($complaint->isResponseOverdue() || $complaint->isResolutionOverdue())
                            <span class="px-3 py-1 rounded-xl bg-red-100 text-red-700 text-xs font-semibold">
                                SLA Breach
                            </span>
                        @else
                            <span class="px-3 py-1 rounded-xl bg-emerald-100 text-emerald-700 text-xs font-semibold">
                                On Track
                            </span>
                        @endif
                    </div>

                    <div class="space-y-5">
                        <div class="rounded-2xl border border-surface-100 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-slate2-800">
                                    First Response
                                </p>
                                <span class="text-xs font-semibold {{ $complaint->isResponseOverdue() ? 'text-red-600' : 'text-emerald-600' }}">
                                    {{ $complaint->isResponseOverdue() ? 'Breached' : ($complaint->first_response_at ? 'Responded' : 'Pending') }}
                                </span>
                            </div>

                            <p class="text-xs text-slate2-500 mt-2">
                                Due {{ $complaint->response_due_at?->format('d M Y H:i') ?? '-' }}
                            </p>

                            @if($complaint->first_response_at)
                                <p class="text-xs text-slate2-500 mt-1">
                                    Responded {{ $complaint->first_response_at->format('d M Y H:i') }}
                                </p>
                            @endif
                        </div>

                        <div class="rounded-2xl border border-surface-100 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-slate2-800">
                                    Resolution
                                </p>
                                <span class="text-xs font-semibold {{ $complaint->isResolutionOverdue() ? 'text-red-600' : 'text-emerald-600' }}">
                                    {{ $complaint->isResolutionOverdue() ? 'Breached' : ($complaint->resolved_at ? 'Resolved' : 'Pending') }}
                                </span>
                            </div>

                            <p class="text-xs text-slate2-500 mt-2">
                                Due {{ $complaint->resolution_due_at?->format('d M Y H:i') ?? '-' }}
                            </p>

                            @if($complaint->resolved_at)
                                <p class="text-xs text-slate2-500 mt-1">
                                    Resolved {{ $complaint->resolved_at->format('d M Y H:i') }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Escalation --}}
                <div class="card p-6">
                    <div class="flex items-center justify-between gap-4 mb-5">
                        <h3 class="font-semibold text-slate2-900">
                            Escalation
                        </h3>

                        @if($complaint->activeEscalation)
                            <span class="px-3 py-1 rounded-xl bg-purple-100 text-purple-700 text-xs font-semibold">
                                Active
                            </span>
                        @else
                            <span class="px-3 py-1 rounded-xl bg-slate-100 text-slate2-500 text-xs font-semibold">
                                None Active
                            </span>
                        @endif
                    </div>

                    @if($complaint->activeEscalation)
                        @php($activeEscalation = $complaint->activeEscalation)
                        <div class="rounded-2xl border border-purple-100 bg-purple-50/60 p-4">
                            <p class="font-semibold text-slate2-900">
                                {{ \App\Enums\EscalationReason::tryFrom($activeEscalation->reason_code)?->label() ?? $activeEscalation->reason_code }}
                            </p>
                            <p class="text-sm text-slate2-600 mt-2 leading-6">
                                {{ $activeEscalation->note }}
                            </p>
                            <div class="mt-3 text-xs text-slate2-500 space-y-1">
                                <p>
                                    Level: {{ \App\Enums\EscalationLevel::tryFrom($activeEscalation->escalation_level)?->label() ?? $activeEscalation->escalation_level }}
                                </p>
                                <p>
                                    Escalated by {{ $activeEscalation->escalator?->name ?? 'System' }}
                                </p>
                                @if($activeEscalation->target)
                                    <p>
                                        Target: {{ $activeEscalation->target->name }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        @can('resolveEscalation', $complaint)
                            <div class="mt-4">
                                <label class="block text-sm font-semibold text-slate2-700 mb-2">
                                    Escalation Resolution Note
                                </label>
                                <textarea
                                    wire:model="escalation_resolution_note"
                                    rows="3"
                                    class="input resize-none"
                                    placeholder="Record the decision, support, or higher-level resolution..."></textarea>
                                @error('escalation_resolution_note')
                                    <p class="text-sm text-red-500 mt-2">{{ $message }}</p>
                                @enderror

                                <button
                                    wire:click="resolveEscalation"
                                    class="w-full mt-3 px-4 py-3 rounded-2xl bg-purple-600 text-white hover:bg-purple-700 transition">
                                    Resolve Escalation
                                </button>
                            </div>
                        @endcan
                    @else
                        <p class="text-sm text-slate2-500">
                            No active escalation for this complaint.
                        </p>
                    @endif

                    @if($complaint->escalations->isNotEmpty())
                        <div class="mt-5 pt-5 border-t border-surface-100 space-y-3">
                            <p class="text-xs uppercase tracking-wide font-semibold text-slate2-400">
                                Escalation History
                            </p>

                            @foreach($complaint->escalations->take(3) as $escalation)
                                <div class="text-sm">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="font-medium text-slate2-800">
                                            {{ \App\Enums\EscalationReason::tryFrom($escalation->reason_code)?->label() ?? $escalation->reason_code }}
                                        </span>
                                        <span class="text-xs {{ $escalation->status === 'active' ? 'text-purple-600' : 'text-emerald-600' }}">
                                            {{ ucfirst($escalation->status) }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate2-500 mt-1">
                                        {{ $escalation->created_at->format('d M Y H:i') }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif
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

    @if($showEscalationModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="w-full max-w-xl bg-white rounded-3xl shadow-2xl overflow-hidden">
            <div class="flex items-center justify-between px-8 py-6 border-b border-surface-100">
                <div>
                    <h2 class="text-2xl font-bold text-slate2-900">
                        Escalate Complaint
                    </h2>
                    <p class="text-sm text-slate2-500 mt-1">
                        Escalate when authority, SLA, resource, policy, or system constraints require higher-level attention.
                    </p>
                </div>

                <button
                    wire:click="$set('showEscalationModal', false)"
                    class="w-10 h-10 rounded-2xl hover:bg-surface-100 flex items-center justify-center transition">
                    <x-heroicon-o-x-mark class="w-5 h-5 text-slate2-500" />
                </button>
            </div>

            <div class="p-8 space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-slate2-700 mb-2">
                        Reason
                    </label>
                    <select wire:model="escalation_reason_code" class="input">
                        <option value="">Select escalation reason</option>
                        @foreach($escalationReasons as $reason)
                            <option value="{{ $reason->value }}">{{ $reason->label() }}</option>
                        @endforeach
                    </select>
                    @error('escalation_reason_code')
                        <p class="text-sm text-red-500 mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate2-700 mb-2">
                        Escalation Level
                    </label>
                    <select wire:model.live="escalation_level" class="input">
                        <option value="">Select escalation level</option>
                        @foreach($escalationLevels as $level)
                            <option value="{{ $level->value }}">{{ $level->label() }}</option>
                        @endforeach
                    </select>
                    @error('escalation_level')
                        <p class="text-sm text-red-500 mt-2">{{ $message }}</p>
                    @enderror
                </div>

                @if($escalation_level === \App\Enums\EscalationLevel::Leadership->value)
                    <div>
                        <label class="block text-sm font-semibold text-slate2-700 mb-2">
                            Target Pimpinan
                        </label>
                        <select wire:model="escalated_to" class="input">
                            <option value="">Select active pimpinan</option>
                            @foreach($leadershipTargets as $target)
                                <option value="{{ $target->id }}">{{ $target->name }}</option>
                            @endforeach
                        </select>
                        @error('escalated_to')
                            <p class="text-sm text-red-500 mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-semibold text-slate2-700 mb-2">
                        Escalation Note
                    </label>
                    <textarea
                        wire:model="escalation_note"
                        rows="4"
                        class="input resize-none"
                        placeholder="Explain why the complaint requires escalation and what support or decision is needed..."></textarea>
                    @error('escalation_note')
                        <p class="text-sm text-red-500 mt-2">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 px-8 py-6 border-t border-surface-100 bg-surface-50">
                <button
                    wire:click="$set('showEscalationModal', false)"
                    class="px-5 py-3 rounded-2xl border border-surface-200 bg-white hover:bg-surface-100 transition">
                    Cancel
                </button>

                <button
                    wire:click="escalateComplaint"
                    class="px-5 py-3 rounded-2xl bg-purple-600 text-white hover:bg-purple-700 transition">
                    Escalate Complaint
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
