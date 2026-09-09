<?php

namespace App\Policies;

use App\Models\Complaint;
use App\Models\User;

class ComplaintPolicy
{
    private const STAFF_ROLES = ['admin', 'supervisor', 'hrd', 'pimpinan'];

    public function viewAny(User $user): bool
    {
        return $user->hasRole('karyawan') || $user->hasAnyRole(self::STAFF_ROLES);
    }

    public function view(User $user, Complaint $complaint): bool
    {
        if ($user->hasRole('karyawan')) {
            return (int) $complaint->reporter_id === (int) $user->id;
        }

        return $user->hasAnyRole(self::STAFF_ROLES);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('karyawan');
    }

    public function update(User $user, Complaint $complaint): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('karyawan')
            && (int) $complaint->reporter_id === (int) $user->id
            && $complaint->status === 'submitted';
    }

    public function delete(User $user, Complaint $complaint): bool
    {
        return false;
    }

    public function validateComplaint(User $user, Complaint $complaint): bool
    {
        return $user->hasRole('supervisor')
            && $complaint->status === 'submitted';
    }

    public function rejectComplaint(User $user, Complaint $complaint): bool
    {
        return $this->validateComplaint($user, $complaint);
    }

    public function assignHandler(User $user, Complaint $complaint): bool
    {
        return $user->hasRole('supervisor')
            && $complaint->status === 'validated';
    }

    public function startProgress(User $user, Complaint $complaint): bool
    {
        return $this->isAssignedHrd($user, $complaint)
            && $complaint->status === 'assigned';
    }

    public function requestInput(User $user, Complaint $complaint): bool
    {
        return $this->isAssignedHrd($user, $complaint)
            && $complaint->status === 'in_progress';
    }

    public function updateResolution(User $user, Complaint $complaint): bool
    {
        return $this->isAssignedHrd($user, $complaint)
            && in_array($complaint->status, ['in_progress', 'waiting_input'], true);
    }

    public function resolveComplaint(User $user, Complaint $complaint): bool
    {
        return $this->isAssignedHrd($user, $complaint)
            && $complaint->status === 'in_progress'
            && ! $complaint->activeEscalation()->exists();
    }

    public function completeFollowUp(User $user, Complaint $complaint): bool
    {
        return $this->isAssignedHrd($user, $complaint)
            && $complaint->status === 'resolved'
            && $complaint->follow_up_required
            && is_null($complaint->follow_up_completed_at);
    }

    public function submitFeedback(User $user, Complaint $complaint): bool
    {
        return $user->hasRole('karyawan')
            && (int) $complaint->reporter_id === (int) $user->id
            && $complaint->status === 'resolved'
            && ! $complaint->feedback()->exists();
    }

    public function addressFeedback(User $user, Complaint $complaint): bool
    {
        $feedback = $complaint->feedback()->first();

        return $this->isAssignedHrd($user, $complaint)
            && $complaint->status === 'resolved'
            && $feedback
            && $feedback->needsFollowUp()
            && ! $feedback->isAddressed();
    }

    public function closeComplaint(User $user, Complaint $complaint): bool
    {
        if (
            ! $this->isAssignedHrd($user, $complaint)
            || $complaint->status !== 'resolved'
            || $complaint->activeEscalation()->exists()
        ) {
            return false;
        }

        if (
            $complaint->follow_up_required
            && is_null($complaint->follow_up_completed_at)
        ) {
            return false;
        }

        $feedback = $complaint->feedback()->first();

        if (
            $feedback
            && $feedback->needsFollowUp()
            && ! $feedback->isAddressed()
        ) {
            return false;
        }

        return true;
    }

    public function escalateComplaint(User $user, Complaint $complaint): bool
    {
        if (in_array($complaint->status, ['closed', 'rejected'], true)) {
            return false;
        }

        if ($complaint->activeEscalation()->exists()) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'supervisor'])
            || $this->isAssignedHrd($user, $complaint);
    }

    public function resolveEscalation(User $user, Complaint $complaint): bool
    {
        $activeEscalation = $complaint->activeEscalation()->first();

        if (! $activeEscalation) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'pimpinan'])
            || (int) $activeEscalation->escalated_to === (int) $user->id;
    }

    public function comment(User $user, Complaint $complaint): bool
    {
        return $this->view($user, $complaint);
    }

    private function isAssignedHrd(User $user, Complaint $complaint): bool
    {
        return $user->hasRole('hrd')
            && (int) $complaint->assigned_to === (int) $user->id;
    }
}
