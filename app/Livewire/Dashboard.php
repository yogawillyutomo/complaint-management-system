<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Complaint;
use App\Models\Category;
use App\Models\Priority;
use App\Models\ComplaintHistory;

class Dashboard extends Component
{

    public $showCreateModal = false;

    public $title = '';

    public $description = '';

    public $category_id = '';

    public $priority_id = '';

    public function openCreateModal()
    {
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
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

    public function saveComplaint()
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

        $this->dispatch('toast', [
            'message' => 'Complaint created successfully.'
        ]);

        $this->closeCreateModal();
    }

    public function render()
    {
        $query = Complaint::query();

        // Employee hanya lihat complaint sendiri
        if (

            auth()->user()->roles
            ->pluck('name')
            ->contains(
                fn($role) =>
                trim(strtolower($role)) === 'employee'
            )

        ) {

            $query->where('reporter_id', auth()->id());
        }

        return view('livewire.dashboard', [

            'totalComplaints' => (clone $query)->count(),

            'inProgress' => (clone $query)
                ->where('status', 'in_progress')
                ->count(),

            'resolved' => (clone $query)
                ->where('status', 'resolved')
                ->count(),

            'critical' => (clone $query)
                ->whereHas('priority', function ($query) {

                    $query->where('name', 'Critical');
                })
                ->count(),

            'recentComplaints' => (clone $query)
                ->latest()
                ->take(5)
                ->get(),

            'categories' => Category::all(),

            'priorities' => Priority::all(),

        ])->layout('layouts.app');
    }
}
