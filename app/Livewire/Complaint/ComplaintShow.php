<?php

namespace App\Livewire\Complaint;

use App\Models\Category;
use App\Models\Complaint;
use App\Models\ComplaintHistory;
use App\Models\Priority;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ComplaintShow extends Component
{
    public Complaint $complaint;

    public $showEditModal = false;
    public $showDeleteModal = false;
    public $title = '';
    public $description = '';
    public $category_id = '';
    public $priority_id = '';
    public $assigned_to = '';
    public $showAssignModal = false;
    public $message = '';

    public function mount(Complaint $complaint): void
    {
        $this->complaint = $complaint;

        Gate::authorize('view', $this->complaint);

        $this->reloadComplaint();
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

        if (! $handler?->hasRole('hrd')) {
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

    public function openDeleteModal(): void
    {
        $this->authorizeFresh('delete');

        $this->showDeleteModal = true;
    }

    public function deleteComplaint()
    {
        $this->authorizeFresh('delete');

        $this->complaint->delete();

        return redirect()
            ->route('complaints.index')
            ->with('success', 'Complaint deleted successfully.');
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
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

    public function resolveComplaint(): void
    {
        $this->authorizeFresh('resolveComplaint');

        DB::transaction(function (): void {
            $this->complaint->update([
                'status' => 'resolved',
                'resolved_at' => now(),
            ]);

            $this->recordHistory(
                'resolved',
                'Complaint resolved successfully'
            );
        });

        $this->reloadComplaint();

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Complaint resolved.'
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
                'Complaint closed'
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
            'handlers' => User::role('hrd')->get(),
        ])->layout('layouts.app');
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
        ]);
    }
}
