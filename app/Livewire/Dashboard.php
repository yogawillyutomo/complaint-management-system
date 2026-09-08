<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Complaint;
use App\Models\ComplaintHistory;
use App\Models\Priority;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;

class Dashboard extends Component
{
    public $showCreateModal = false;
    public $title = '';
    public $description = '';
    public $category_id = '';
    public $priority_id = '';

    public function openCreateModal(): void
    {
        Gate::authorize('create', Complaint::class);

        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->resetValidation();

        $this->reset([
            'title',
            'description',
            'category_id',
            'priority_id',
        ]);

        $this->showCreateModal = false;
    }

    public function saveComplaint(): void
    {
        Gate::authorize('create', Complaint::class);

        $this->validate([
            'title' => 'required|min:5',
            'description' => 'required|min:10',
            'category_id' => 'required|exists:categories,id',
            'priority_id' => 'required|exists:priorities,id',
        ]);

        DB::transaction(function (): void {
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
        });

        $this->dispatch('toast', [
            'message' => 'Complaint created successfully.',
        ]);

        $this->closeCreateModal();
    }

    public function render()
    {
        Gate::authorize('viewAny', Complaint::class);

        $query = Complaint::query();

        if (auth()->user()->hasRole('karyawan')) {
            $query->where('reporter_id', auth()->id());
        }

        return view('livewire.dashboard', [
            'totalComplaints' => (clone $query)->count(),
            'inProgress' => (clone $query)->where('status', 'in_progress')->count(),
            'resolved' => (clone $query)->where('status', 'resolved')->count(),
            'critical' => (clone $query)
                ->whereHas('priority', function ($query) {
                    $query->where('name', 'Critical');
                })
                ->count(),
            'recentComplaints' => (clone $query)->latest()->take(5)->get(),
            'categories' => Category::all(),
            'priorities' => Priority::all(),
        ])->layout('layouts.app');
    }
}
