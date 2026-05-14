<?php

namespace App\Livewire\Complaint;

use App\Models\Complaint;
use Livewire\Component;
use App\Models\Category;
use App\Models\Priority;
use App\Models\ComplaintHistory;
use App\Models\User;

class ComplaintShow extends Component
{
    public Complaint $complaint;

    public $status = '';

    public $showEditModal = false;

    public $showDeleteModal = false;

    public $title = '';

    public $description = '';

    public $category_id = '';

    public $priority_id = '';

    public $assigned_to = '';

    public $showAssignModal = false;

    public $message = '';

    public function mount(Complaint $complaint)
    {
        $this->complaint = $complaint->load([
            'reporter',
            'category',
            'priority',
            'histories.user',
            'handler',
            'comments.user',
        ]);

        if (
            auth()->user()->hasRole('Employee')
            && auth()->id() !== $this->complaint->reporter_id
        ) {
            abort(403);
        }

        $this->status = $this->complaint->status;
    }

    public function openAssignModal()
    {
        $this->showAssignModal = true;
    }

    public function assignHandler()
    {
        $this->validate([

            'assigned_to' => 'required|exists:users,id',

        ]);

        $this->complaint->update([

            'assigned_to' => $this->assigned_to,

            'status' => 'assigned',

        ]);

        ComplaintHistory::create([

            'complaint_id' => $this->complaint->id,

            'user_id' => auth()->id(),

            'type' => 'assigned',

            'description' => 'Complaint assigned to handler',

        ]);

        $this->complaint->refresh();

        $this->showAssignModal = false;

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Handler assigned successfully.'
        );
    }

    public function updateStatus()
    {

        abort_unless(

            auth()->user()->hasRole('Admin') ||
                auth()->user()->hasRole('HR'),

            403

        );

        $this->complaint->update([
            'status' => $this->status,
        ]);


        ComplaintHistory::create([

            'complaint_id' => $this->complaint->id,

            'user_id' => auth()->id(),

            'type' => 'status_updated',

            'description' => 'Status changed to ' . ucwords(str_replace('_', ' ', $this->status)),

        ]);

        $this->complaint->refresh();

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Complaint status updated successfully.'
        );
    }

    public function openEditModal()
    {
        $this->title = $this->complaint->title;

        $this->description = $this->complaint->description;

        $this->category_id = $this->complaint->category_id;

        $this->priority_id = $this->complaint->priority_id;

        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->resetValidation();

        $this->showEditModal = false;
    }

    public function updateComplaint()
    {

        abort_unless(

            auth()->user()->hasRole('Admin')

                || auth()->user()->hasRole('HR')

                || auth()->id() === $this->complaint->reporter_id,

            403

        );

        $this->validate([
            'title' => 'required|min:5',
            'description' => 'required|min:10',
            'category_id' => 'required',
            'priority_id' => 'required',
        ]);

        $this->complaint->update([

            'title' => $this->title,

            'description' => $this->description,

            'category_id' => $this->category_id,

            'priority_id' => $this->priority_id,

        ]);

        $this->complaint->refresh();

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Complaint updated successfully.'
        );

        $this->closeEditModal();
    }

    public function openDeleteModal()
    {
        $this->showDeleteModal = true;
    }

    public function deleteComplaint()
    {
        abort_unless(
            auth()->user()->hasRole('Admin'),
            403
        );

        $this->complaint->delete();

        return redirect()
            ->route('complaints.index')
            ->with('success', 'Complaint deleted successfully.');
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
    }

    public function validateComplaint()
    {
        abort_unless(
            auth()->user()->roles
                ->pluck('name')
                ->contains(
                    fn($role) =>
                    trim(strtolower($role)) === 'supervisor'
                ),
            403
        );

        $this->complaint->update([

            'status' => 'validated',

        ]);

        ComplaintHistory::create([

            'complaint_id' => $this->complaint->id,

            'user_id' => auth()->id(),

            'type' => 'validated',

            'description' => 'Complaint validated by supervisor',

        ]);

        $this->complaint->refresh();

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Complaint validated successfully.'
        );
    }

    public function rejectComplaint()
    {
        $this->complaint->update([

            'status' => 'rejected',

        ]);

        ComplaintHistory::create([

            'complaint_id' => $this->complaint->id,

            'user_id' => auth()->id(),

            'type' => 'rejected',

            'description' => 'Complaint rejected by supervisor',

        ]);

        $this->complaint->refresh();

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Complaint rejected.'
        );
    }

    public function startProgress()
    {
        $this->complaint->update([

            'status' => 'in_progress',

        ]);

        ComplaintHistory::create([

            'complaint_id' => $this->complaint->id,

            'user_id' => auth()->id(),

            'type' => 'in_progress',

            'description' => 'Complaint processing started',

        ]);

        $this->complaint->refresh();

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Complaint is now in progress.'
        );
    }

    public function waitingInput()
    {
        $this->complaint->update([

            'status' => 'waiting_input',

        ]);

        ComplaintHistory::create([

            'complaint_id' => $this->complaint->id,

            'user_id' => auth()->id(),

            'type' => 'waiting_input',

            'description' => 'Waiting additional input from employee',

        ]);

        $this->complaint->refresh();

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Complaint waiting for employee input.'
        );
    }

    public function resolveComplaint()
    {
        $this->complaint->update([

            'status' => 'resolved',

            'resolved_at' => now(),

        ]);

        ComplaintHistory::create([

            'complaint_id' => $this->complaint->id,

            'user_id' => auth()->id(),

            'type' => 'resolved',

            'description' => 'Complaint resolved successfully',

        ]);

        $this->complaint->refresh();

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Complaint resolved.'
        );
    }

    public function closeComplaint()
    {
        $this->complaint->update([

            'status' => 'closed',

            'closed_at' => now(),

        ]);

        ComplaintHistory::create([

            'complaint_id' => $this->complaint->id,

            'user_id' => auth()->id(),

            'type' => 'closed',

            'description' => 'Complaint closed',

        ]);

        $this->complaint->refresh();

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Complaint closed.'
        );
    }

    public function sendComment()
    {
        $this->validate([

            'message' => 'required|min:2',

        ]);

        $this->complaint->comments()->create([

            'user_id' => auth()->id(),

            'message' => $this->message,

        ]);

        // Jika employee reply saat waiting input
        if (

            $this->complaint->status === 'waiting_input'

            && $this->complaint->reporter_id === auth()->id()

        ) {

            $this->complaint->update([

                'status' => 'in_progress',

            ]);

            ComplaintHistory::create([

                'complaint_id' => $this->complaint->id,

                'user_id' => auth()->id(),

                'type' => 'response',

                'description' => 'Employee provided additional information',

            ]);
        }

        $this->message = '';

        $this->complaint->refresh();

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Comment sent successfully.'
        );
    }

    public function render()
    {
        return view('livewire.complaint.complaint-show', [

            'categories' => Category::all(),

            'priorities' => Priority::all(),

            'handlers' => User::role('HR')->get(),

        ])->layout('layouts.app');
    }
}
