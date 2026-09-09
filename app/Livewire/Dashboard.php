<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Complaint;
use App\Models\ComplaintHistory;
use App\Models\Priority;
use App\Services\ActionCenterService;
use App\Services\ComplaintNotificationService;
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

        $terminalStatuses = ['resolved', 'closed', 'rejected'];

        $responseSamples = (clone $query)
            ->whereNotNull('first_response_at')
            ->get(['created_at', 'first_response_at']);

        $resolutionSamples = (clone $query)
            ->whereNotNull('resolved_at')
            ->get(['created_at', 'resolved_at']);

        $avgFirstResponseMinutes = $responseSamples->isEmpty()
            ? null
            : round($responseSamples->avg(
                fn (Complaint $complaint) => $complaint->created_at
                    ->diffInMinutes($complaint->first_response_at)
            ), 1);

        $avgResolutionMinutes = $resolutionSamples->isEmpty()
            ? null
            : round($resolutionSamples->avg(
                fn (Complaint $complaint) => $complaint->created_at
                    ->diffInMinutes($complaint->resolved_at)
            ), 1);

        $actionItems = app(ActionCenterService::class)
            ->forUser(auth()->user());

        return view('livewire.dashboard', [
            'actionCenterItems' => $actionItems->take(5),
            'actionCenterCount' => $actionItems->count(),
            'totalComplaints' => (clone $query)->count(),
            'inProgress' => (clone $query)->where('status', 'in_progress')->count(),
            'resolved' => (clone $query)->where('status', 'resolved')->count(),
            'critical' => (clone $query)
                ->whereHas('priority', function ($query) {
                    $query->where('name', 'Critical');
                })
                ->count(),
            'openBacklog' => (clone $query)
                ->whereNotIn('status', $terminalStatuses)
                ->count(),
            'overdueResponse' => (clone $query)
                ->whereNull('first_response_at')
                ->whereNotNull('response_due_at')
                ->where('response_due_at', '<', now())
                ->count(),
            'overdueResolution' => (clone $query)
                ->whereNotIn('status', $terminalStatuses)
                ->whereNotNull('resolution_due_at')
                ->where('resolution_due_at', '<', now())
                ->count(),
            'activeEscalations' => (clone $query)
                ->whereHas('escalations', fn ($q) => $q->where('status', 'active'))
                ->count(),
            'avgFirstResponseMinutes' => $avgFirstResponseMinutes,
            'avgResolutionMinutes' => $avgResolutionMinutes,
            'recentComplaints' => (clone $query)
                ->with(['priority', 'activeEscalation'])
                ->latest()
                ->take(5)
                ->get(),
            'categories' => Category::all(),
            'priorities' => Priority::all(),
        ])->layout('layouts.app');
    }
}
