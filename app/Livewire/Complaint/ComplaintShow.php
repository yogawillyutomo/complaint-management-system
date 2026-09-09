<?php

namespace App\Livewire\Complaint;

use App\Enums\RootCauseCategory;
use App\Models\Category;
use App\Models\Complaint;
use App\Models\ComplaintHistory;
use App\Models\Priority;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ComplaintShow extends Component
{
    public Complaint $complaint;

    public $showEditModal = false;
    public $title = '';
    public $description = '';
    public $category_id = '';
    public $priority_id = '';
    public $assigned_to = '';
    public $showAssignModal = false;
    public $message = '';

    public $root_cause_category = '';
    public $root_cause = '';
    public $resolution_summary = '';
    public $resolution_evidence = '';
    public $follow_up_required = false;
    public $follow_up_due_at = '';
    public $follow_up_note = '';

    public function mount(Complaint $complaint): void
    {
        $this->complaint = $complaint;

        Gate::authorize('view', $this->complaint);

        $this->reloadComplaint();
        $this->syncResolutionForm();
    }

    public function openAssignModal(): void
    {
        $this->authorizeFresh('assignHandler');

        $this->showAssignModal = true;
    }

    public function assignHandler(): void
    {
        $this->authorizeFresh('assignHandler');

        $this->validate([
            'assigned_to' => 'required|integer|exists:users,id',
        ]);

        $handler = User::find($this->assigned_to);

        if (! $handler?->hasRole('hrd') || ! $handler->is_active) {
            $this->addError('assigned_to', 'Selected user must have the HRD role.');

            return;
        }

        DB::transaction(function (): void {
            $this->complaint->update([
                'assigned_to' => $this->assigned_to,
                'status' => 'assigned',
            ]);

            $this->recordHistory(
                'assigned',
                'Complaint assigned to HRD handler'
            );
        });

        $this->showAssignModal = false;
        $this->reloadComplaint();

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Handler assigned successfully.'
        );
    }

    public function openEditModal(): void
    {
        $this->authorizeFresh('update');

        $this->title = $this->complaint->title;
        $this->description = $this->complaint->description;
        $this->category_id = $this->complaint->category_id;
        $this->priority_id = $this->complaint->priority_id;
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->resetValidation();
        $this->showEditModal = false;
    }

    public function updateComplaint(): void
    {
        $this->authorizeFresh('update');

        $this->validate([
            'title' => 'required|min:5',
            'description' => 'required|min:10',
            'category_id' => 'required|exists:categories,id',
            'priority_id' => 'required|exists:priorities,id',
        ]);

        DB::transaction(function (): void {
            $this->complaint->update([
                'title' => $this->title,
                'description' => $this->description,
                'category_id' => $this->category_id,
                'priority_id' => $this->priority_id,
            ]);

            $this->recordHistory(
                'updated',
                'Complaint details updated'
            );
        });

        $this->reloadComplaint();
        $this->closeEditModal();

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Complaint updated successfully.'
        );
    }

    public function validateComplaint(): void
    {
        $this->authorizeFresh('validateComplaint');

        DB::transaction(function (): void {
            $this->complaint->update([
                'status' => 'validated',
            ]);

            $this->recordHistory(
                'validated',
                'Complaint validated by supervisor'
            );
        });

        $this->reloadComplaint();

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Complaint validated successfully.'
        );
    }

    public function rejectComplaint(): void
    {
        $this->authorizeFresh('rejectComplaint');

        DB::transaction(function (): void {
            $this->complaint->update([
                'status' => 'rejected',
            ]);

            $this->recordHistory(
                'rejected',
                'Complaint rejected by supervisor'
            );
        });

        $this->reloadComplaint();

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Complaint rejected.'
        );
    }

    public function startProgress(): void
    {
        $this->authorizeFresh('startProgress');

        DB::transaction(function (): void {
            $this->complaint->update([
                'status' => 'in_progress',
            ]);

            $this->recordHistory(
                'in_progress',
                'Complaint processing started'
            );
        });

        $this->reloadComplaint();
        $this->syncResolutionForm();

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Complaint is now in progress.'
        );
    }

    public function waitingInput(): void
    {
        $this->authorizeFresh('requestInput');

        DB::transaction(function (): void {
            $this->complaint->update([
                'status' => 'waiting_input',
            ]);

            $this->recordHistory(
                'waiting_input',
                'Waiting additional input from employee'
            );
        });

        $this->reloadComplaint();

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Complaint waiting for employee input.'
        );
    }

    public function saveResolutionAnalysis(): void
    {
        $this->authorizeFresh('updateResolution');
        $this->validateResolutionForm();

        DB::transaction(function (): void {
            $this->complaint->update($this->resolutionPayload());

            $this->recordHistory(
                'resolution_analysis_updated',
                'Root cause, resolution, and follow-up plan updated by assigned HRD'
            );
        });

        $this->reloadComplaint();
        $this->syncResolutionForm();

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Resolution analysis saved.'
        );
    }

    public function resolveComplaint(): void
    {
        $this->authorizeFresh('resolveComplaint');
        $this->validateResolutionForm();

        DB::transaction(function (): void {
            $this->complaint->update(array_merge(
                $this->resolutionPayload(),
                [
                    'status' => 'resolved',
                    'resolved_at' => now(),
                    'follow_up_completed_at' => null,
                    'follow_up_note' => null,
                ]
            ));

            $this->recordHistory(
                'resolved',
                $this->follow_up_required
                    ? 'Complaint resolved; follow-up monitoring required before closure'
                    : 'Complaint resolved; no additional follow-up required'
            );
        });

        $this->reloadComplaint();
        $this->syncResolutionForm();

        $this->dispatch(
            'toast',
            type: 'success',
            message: $this->follow_up_required
                ? 'Complaint resolved. Follow-up must be completed before closure.'
                : 'Complaint resolved.'
        );
    }

    public function completeFollowUp(): void
    {
        $this->authorizeFresh('completeFollowUp');

        $this->validate([
            'follow_up_note' => 'required|string|min:5|max:5000',
        ]);

        DB::transaction(function (): void {
            $this->complaint->update([
                'follow_up_note' => $this->follow_up_note,
                'follow_up_completed_at' => now(),
            ]);

            $this->recordHistory(
                'follow_up_completed',
                'Post-resolution follow-up and evaluation completed by assigned HRD'
            );
        });

        $this->reloadComplaint();
        $this->syncResolutionForm();

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Follow-up completed. Complaint is ready to close.'
        );
    }

    public function closeComplaint(): void
    {
        $this->authorizeFresh('closeComplaint');

        DB::transaction(function (): void {
            $this->complaint->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);

            $this->recordHistory(
                'closed',
                $this->complaint->follow_up_required
                    ? 'Complaint closed after follow-up completion'
                    : 'Complaint closed'
            );
        });

        $this->reloadComplaint();

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Complaint closed.'
        );
    }

    public function sendComment(): void
    {
        $this->authorizeFresh('comment');

        $this->validate([
            'message' => 'required|min:2',
        ]);

        DB::transaction(function (): void {
            $this->complaint->comments()->create([
                'user_id' => auth()->id(),
                'message' => $this->message,
            ]);

            if (
                $this->complaint->status === 'waiting_input'
                && (int) $this->complaint->reporter_id === (int) auth()->id()
            ) {
                $this->complaint->update([
                    'status' => 'in_progress',
                ]);

                $this->recordHistory(
                    'response',
                    'Employee provided additional information'
                );
            }
        });

        $this->message = '';
        $this->reloadComplaint();

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Comment sent successfully.'
        );
    }

    public function render()
    {
        Gate::authorize('view', $this->complaint);

        return view('livewire.complaint.complaint-show', [
            'categories' => Category::all(),
            'priorities' => Priority::all(),
            'handlers' => User::role('hrd')
                ->where('is_active', true)
                ->get(),
            'rootCauseCategories' => RootCauseCategory::cases(),
        ])->layout('layouts.app');
    }

    private function validateResolutionForm(): void
    {
        $this->validate([
            'root_cause_category' => 'required|in:' . implode(',', RootCauseCategory::values()),
            'root_cause' => 'required|string|min:10|max:5000',
            'resolution_summary' => 'required|string|min:10|max:5000',
            'resolution_evidence' => 'nullable|string|max:5000',
            'follow_up_required' => 'boolean',
            'follow_up_due_at' => 'nullable|date|after_or_equal:today',
        ]);

        if ($this->follow_up_required && blank($this->follow_up_due_at)) {
            throw ValidationException::withMessages([
                'follow_up_due_at' => 'A follow-up due date is required when follow-up is enabled.',
            ]);
        }
    }

    private function resolutionPayload(): array
    {
        return [
            'root_cause_category' => $this->root_cause_category,
            'root_cause' => $this->root_cause,
            'resolution_summary' => $this->resolution_summary,
            'resolution_evidence' => filled($this->resolution_evidence)
                ? $this->resolution_evidence
                : null,
            'follow_up_required' => (bool) $this->follow_up_required,
            'follow_up_due_at' => $this->follow_up_required && filled($this->follow_up_due_at)
                ? $this->follow_up_due_at
                : null,
        ];
    }

    private function syncResolutionForm(): void
    {
        $this->root_cause_category = $this->complaint->root_cause_category ?? '';
        $this->root_cause = $this->complaint->root_cause ?? '';
        $this->resolution_summary = $this->complaint->resolution_summary ?? '';
        $this->resolution_evidence = $this->complaint->resolution_evidence ?? '';
        $this->follow_up_required = (bool) $this->complaint->follow_up_required;
        $this->follow_up_due_at = $this->complaint->follow_up_due_at?->format('Y-m-d\TH:i') ?? '';
        $this->follow_up_note = $this->complaint->follow_up_note ?? '';
    }

    private function authorizeFresh(string $ability): void
    {
        $this->complaint->refresh();

        Gate::authorize($ability, $this->complaint);
    }

    private function recordHistory(string $type, string $description): void
    {
        ComplaintHistory::create([
            'complaint_id' => $this->complaint->id,
            'user_id' => auth()->id(),
            'type' => $type,
            'description' => $description,
        ]);
    }

    private function reloadComplaint(): void
    {
        $this->complaint->refresh()->load([
            'reporter',
            'category',
            'priority',
            'histories.user',
            'handler',
            'comments.user',
            'attachments',
        ]);
    }
}
