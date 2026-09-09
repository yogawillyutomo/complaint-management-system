<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Support\Collection;

class ActionCenterService
{
    public function forUser(User $user): Collection
    {
        $items = collect();

        if ($user->hasRole('karyawan')) {
            $this->employeeItems($items, $user);
        }

        if ($user->hasRole('supervisor')) {
            $this->supervisorItems($items);
        }

        if ($user->hasRole('hrd')) {
            $this->hrdItems($items, $user);
        }

        if ($user->hasRole('pimpinan')) {
            $this->leadershipItems($items, $user);
        }

        if ($user->hasRole('admin')) {
            $this->adminItems($items);
        }

        return $items
            ->unique('key')
            ->sort(function (array $a, array $b): int {
                $urgency = $this->urgencyWeight($b['urgency'])
                    <=> $this->urgencyWeight($a['urgency']);

                if ($urgency !== 0) {
                    return $urgency;
                }

                return strcmp($b['sort_at'], $a['sort_at']);
            })
            ->values();
    }

    private function employeeItems(Collection $items, User $user): void
    {
        Complaint::query()
            ->where('reporter_id', $user->id)
            ->where('status', 'waiting_input')
            ->get()
            ->each(fn (Complaint $complaint) => $items->push(
                $this->item(
                    'employee-input',
                    $complaint,
                    'warning',
                    'Additional input required',
                    'HRD is waiting for additional information from you.'
                )
            ));

        Complaint::query()
            ->where('reporter_id', $user->id)
            ->where('status', 'resolved')
            ->whereDoesntHave('feedback')
            ->get()
            ->each(fn (Complaint $complaint) => $items->push(
                $this->item(
                    'employee-feedback',
                    $complaint,
                    'info',
                    'Review resolution and give feedback',
                    'The complaint is resolved and is waiting for your confirmation.'
                )
            ));

        Complaint::query()
            ->where('reporter_id', $user->id)
            ->where('status', 'resolved')
            ->whereHas('feedback', fn ($q) => $q
                ->where('outcome', 'needs_follow_up')
                ->whereNotNull('addressed_at'))
            ->get()
            ->each(fn (Complaint $complaint) => $items->push(
                $this->item(
                    'employee-review-followup',
                    $complaint,
                    'info',
                    'Review HRD follow-up response',
                    'HRD has addressed your follow-up feedback.'
                )
            ));
    }

    private function supervisorItems(Collection $items): void
    {
        Complaint::query()
            ->where('status', 'submitted')
            ->get()
            ->each(fn (Complaint $complaint) => $items->push(
                $this->item(
                    'supervisor-validation',
                    $complaint,
                    $complaint->isResponseOverdue() ? 'critical' : 'warning',
                    $complaint->isResponseOverdue()
                        ? 'Response SLA breached — validate complaint'
                        : 'Complaint requires validation',
                    'Validate or reject this newly submitted complaint.'
                )
            ));

        Complaint::query()
            ->where('status', 'validated')
            ->whereNull('assigned_to')
            ->get()
            ->each(fn (Complaint $complaint) => $items->push(
                $this->item(
                    'supervisor-assign',
                    $complaint,
                    'warning',
                    'Assign an HRD PIC',
                    'This validated complaint does not yet have an HRD handler.'
                )
            ));
    }

    private function hrdItems(Collection $items, User $user): void
    {
        Complaint::query()
            ->where('assigned_to', $user->id)
            ->where('status', 'assigned')
            ->get()
            ->each(fn (Complaint $complaint) => $items->push(
                $this->item(
                    'hrd-start',
                    $complaint,
                    'warning',
                    'Start complaint handling',
                    'This complaint is assigned to you and has not been started.'
                )
            ));

        Complaint::query()
            ->where('assigned_to', $user->id)
            ->whereIn('status', ['in_progress', 'waiting_input'])
            ->whereNotNull('resolution_due_at')
            ->where('resolution_due_at', '<', now())
            ->get()
            ->each(fn (Complaint $complaint) => $items->push(
                $this->item(
                    'hrd-resolution-overdue',
                    $complaint,
                    'critical',
                    'Resolution SLA overdue',
                    'This complaint is still open past its resolution deadline.'
                )
            ));

        Complaint::query()
            ->where('assigned_to', $user->id)
            ->where('status', 'resolved')
            ->where('follow_up_required', true)
            ->whereNull('follow_up_completed_at')
            ->get()
            ->each(fn (Complaint $complaint) => $items->push(
                $this->item(
                    'hrd-follow-up',
                    $complaint,
                    $complaint->follow_up_due_at && now()->gt($complaint->follow_up_due_at)
                        ? 'critical'
                        : 'warning',
                    $complaint->follow_up_due_at && now()->gt($complaint->follow_up_due_at)
                        ? 'Follow-up overdue'
                        : 'Post-resolution follow-up required',
                    'Complete monitoring/evaluation before this complaint can be closed.'
                )
            ));

        Complaint::query()
            ->where('assigned_to', $user->id)
            ->where('status', 'resolved')
            ->whereHas('feedback', fn ($q) => $q
                ->where('outcome', 'needs_follow_up')
                ->whereNull('addressed_at'))
            ->get()
            ->each(fn (Complaint $complaint) => $items->push(
                $this->item(
                    'hrd-feedback',
                    $complaint,
                    'critical',
                    'Employee feedback needs follow-up',
                    'The reporter says the resolution still needs additional attention.'
                )
            ));

        Complaint::query()
            ->where('assigned_to', $user->id)
            ->where('status', 'in_progress')
            ->whereDoesntHave('escalations', fn ($q) => $q->where('status', 'active'))
            ->whereHas('escalations', fn ($q) => $q->where('status', 'resolved'))
            ->get()
            ->each(fn (Complaint $complaint) => $items->push(
                $this->item(
                    'hrd-escalation-resolved',
                    $complaint,
                    'info',
                    'Escalation resolved — continue handling',
                    'Higher-level escalation is complete and the complaint workflow can continue.'
                )
            ));
    }

    private function leadershipItems(Collection $items, User $user): void
    {
        Complaint::query()
            ->whereHas('escalations', fn ($q) => $q
                ->where('status', 'active')
                ->where(function ($q) use ($user) {
                    $q->where('escalated_to', $user->id)
                        ->orWhereNull('escalated_to');
                }))
            ->get()
            ->each(fn (Complaint $complaint) => $items->push(
                $this->item(
                    'leadership-escalation',
                    $complaint,
                    'critical',
                    'Active escalation requires leadership attention',
                    'Review the escalation and record the higher-level decision or support.'
                )
            ));

        Complaint::query()
            ->whereNotIn('status', ['resolved', 'closed', 'rejected'])
            ->whereHas('priority', fn ($q) => $q->where('name', 'Critical'))
            ->where(function ($q) {
                $q->where(function ($q) {
                    $q->whereNull('first_response_at')
                        ->whereNotNull('response_due_at')
                        ->where('response_due_at', '<', now());
                })->orWhere(function ($q) {
                    $q->whereNotNull('resolution_due_at')
                        ->where('resolution_due_at', '<', now());
                });
            })
            ->get()
            ->each(fn (Complaint $complaint) => $items->push(
                $this->item(
                    'leadership-critical-sla',
                    $complaint,
                    'critical',
                    'Critical complaint breached SLA',
                    'A critical-priority complaint requires management attention.'
                )
            ));
    }

    private function adminItems(Collection $items): void
    {
        Complaint::query()
            ->whereNotIn('status', ['resolved', 'closed', 'rejected'])
            ->where(function ($q) {
                $q->where(function ($q) {
                    $q->whereNull('first_response_at')
                        ->whereNotNull('response_due_at')
                        ->where('response_due_at', '<', now());
                })->orWhere(function ($q) {
                    $q->whereNotNull('resolution_due_at')
                        ->where('resolution_due_at', '<', now());
                });
            })
            ->get()
            ->each(fn (Complaint $complaint) => $items->push(
                $this->item(
                    'admin-sla',
                    $complaint,
                    'critical',
                    'SLA breach needs oversight',
                    'This open complaint has breached a response or resolution target.'
                )
            ));

        Complaint::query()
            ->whereHas('escalations', fn ($q) => $q->where('status', 'active'))
            ->get()
            ->each(fn (Complaint $complaint) => $items->push(
                $this->item(
                    'admin-escalation',
                    $complaint,
                    'critical',
                    'Active escalation needs oversight',
                    'An escalation remains active on this complaint.'
                )
            ));

        Complaint::query()
            ->where('status', 'resolved')
            ->whereHas('feedback', fn ($q) => $q
                ->where('outcome', 'needs_follow_up')
                ->whereNull('addressed_at'))
            ->get()
            ->each(fn (Complaint $complaint) => $items->push(
                $this->item(
                    'admin-feedback',
                    $complaint,
                    'warning',
                    'Employee follow-up feedback is pending',
                    'The assigned HRD has not yet addressed employee follow-up feedback.'
                )
            ));
    }

    private function item(
        string $type,
        Complaint $complaint,
        string $urgency,
        string $title,
        string $description
    ): array {
        return [
            'key' => "{$type}:{$complaint->id}",
            'type' => $type,
            'urgency' => $urgency,
            'title' => $title,
            'description' => $description,
            'ticket_number' => $complaint->ticket_number,
            'complaint_title' => $complaint->title,
            'sort_at' => ($complaint->updated_at ?? $complaint->created_at)->toIso8601String(),
        ];
    }

    private function urgencyWeight(string $urgency): int
    {
        return match ($urgency) {
            'critical' => 3,
            'warning' => 2,
            default => 1,
        };
    }
}
