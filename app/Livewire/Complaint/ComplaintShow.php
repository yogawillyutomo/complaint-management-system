<?php

namespace App\Livewire\Complaint;

use App\Models\Complaint;
use Livewire\Component;
use App\Models\Category;
use App\Models\Priority;
use App\Models\ComplaintHistory;

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

    public function mount(Complaint $complaint)
    {
        $this->complaint = $complaint->load([
            'reporter',
            'category',
            'priority',
            'histories.user',
        ]);

        if (
            auth()->user()->hasRole('Employee')
            && auth()->id() !== $this->complaint->reporter_id
        ) {
            abort(403);
        }

        $this->status = $this->complaint->status;
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

    public function render()
    {
        return view('livewire.complaint.complaint-show', [

            'categories' => Category::all(),

            'priorities' => Priority::all(),

        ])->layout('layouts.app');
    }
}
