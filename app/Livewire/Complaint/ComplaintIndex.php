<?php

namespace App\Livewire\Complaint;

use App\Models\Category;
use App\Models\Complaint;
use App\Models\ComplaintHistory;
use App\Models\Priority;
use App\Services\ComplaintNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

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

    public function openCreateModal(): void
    {
        Gate::authorize('create', Complaint::class);

        $this->showModal = true;
    }

    public function save(): void
    {
        Gate::authorize('create', Complaint::class);

        $this->validate([
            'title' => 'required|min:5',
            'description' => 'required|min:10',
            'category_id' => 'required|exists:categories,id',
            'priority_id' => 'required|exists:priorities,id',
        ]);

        $complaint = DB::transaction(function (): Complaint {
            $complaint = Complaint::create([
                'ticket_number' => 'CMP-' . Str::upper((string) Str::ulid()),
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

            return $complaint;
        });

        app(ComplaintNotificationService::class)
            ->notifySupervisorsNewComplaint($complaint);

        $this->reset([
            'title',
            'description',
            'category_id',
            'priority_id',
        ]);

        $this->closeModal();
    }

    public function closeModal(): void
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
        Gate::authorize('viewAny', Complaint::class);

        $query = Complaint::with([
            'reporter',
            'category',
            'priority',
        ]);

        if (auth()->user()->hasRole('karyawan')) {
            $query->where('reporter_id', auth()->id());
        }

        $query->when($this->search, function ($query) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('ticket_number', 'like', '%' . $this->search . '%');
            });
        });

        $query->when($this->status, function ($query) {
            $query->where('status', $this->status);
        });

        $query->when($this->priority, function ($query) {
            $query->whereHas('priority', function ($q) {
                $q->where('name', $this->priority);
            });
        });

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
