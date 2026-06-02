<?php

namespace App\Livewire\Complaint;

use App\Models\Complaint;
use App\Models\ComplaintHistory;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Category;
use App\Models\Priority;

class ComplaintIndex extends Component
{
    use WithPagination;

    public $search = '';

    public $status = '';

    public $showModal = false;

    public $title = '';

    public $description = '';

    public $category_id = '';

    public $priority_id = '';

    public $priority = '';

    public $sort = 'latest';

    public function save()
    {
        $this->validate([
            'title' => 'required|min:5',
            'description' => 'required|min:10',
            'category_id' => 'required',
            'priority_id' => 'required',
        ]);

        $complaint = Complaint::create([

            'ticket_number' => 'CMP-' . now()->format('YmdHis'),

            'reporter_id' => auth()->id(),

            'category_id' => $this->category_id,

            'priority_id' => $this->priority_id,

            'title' => $this->title,

            'description' => $this->description,

            'status' => 'submitted',

        ]);

        ComplaintHistory::create([
            'complaint_id' => $complaint->id,
            'user_id' => auth()->id(),
            'type' => 'created',
            'description' => 'Complaint created',
        ]);

        $this->reset([
            'title',
            'description',
            'category_id',
            'priority_id',
        ]);

        $this->closeModal();
    }

    public function closeModal()
    {
        $this->resetValidation();

        $this->reset([
            'title',
            'description',
            'category_id',
            'priority_id',
        ]);

        $this->showModal = false;
    }

    public function render()
    {
        $query = Complaint::with([
            'reporter',
            'category',
            'priority',
        ]);

        // Karyawan hanya melihat complaint miliknya sendiri.
        if (

            auth()->user()->roles
            ->pluck('name')
            ->contains(
                fn($role) =>
                trim(strtolower($role)) === 'karyawan'
            )

        ) {

            $query->where('reporter_id', auth()->id());
        }

        // Search
        $query->when($this->search, function ($query) {

            $query->where(function ($q) {

                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('ticket_number', 'like', '%' . $this->search . '%');
            });
        });

        // Status filter
        $query->when($this->status, function ($query) {

            $query->where('status', $this->status);
        });

        // Priority filter
        $query->when($this->priority, function ($query) {

            $query->whereHas('priority', function ($q) {

                $q->where('name', $this->priority);
            });
        });

        // Sorting
        if ($this->sort === 'latest') {

            $query->latest();
        } elseif ($this->sort === 'oldest') {

            $query->oldest();
        }


        return view('livewire.complaint.complaint-index', [

            'complaints' => $query->paginate(10),

            'categories' => Category::all(),

            'priorities' => Priority::all(),

        ])->layout('layouts.app');
    }
}
