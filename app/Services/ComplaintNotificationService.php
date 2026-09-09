<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\User;
use App\Notifications\ComplaintActivityNotification;
use Illuminate\Support\Collection;

class ComplaintNotificationService
{
    public function notifySupervisorsNewComplaint(Complaint $complaint): void
    {
        $this->notifyUsers(
            User::role('supervisor')->where('is_active', true)->get(),
            $complaint,
            'complaint_created',
            'New complaint requires validation',
            "{$complaint->ticket_number} was submitted and is waiting for supervisor validation."
        );
    }

    public function notifyAssignment(Complaint $complaint): void
    {
        if (! $complaint->handler?->is_active) {
            return;
        }

        $this->notifyUser(
            $complaint->handler,
            $complaint,
            'complaint_assigned',
            'Complaint assigned to you',
            "{$complaint->ticket_number} has been assigned to you for handling."
        );
    }

    public function notifyReporterWaitingInput(Complaint $complaint): void
    {
        if (! $complaint->reporter?->is_active) {
            return;
        }

        $this->notifyUser(
            $complaint->reporter,
            $complaint,
            'waiting_input',
            'Additional input required',
            "{$complaint->ticket_number} needs additional information from you."
        );
    }

    public function notifyReporterResolved(Complaint $complaint): void
    {
        if (! $complaint->reporter?->is_active) {
            return;
        }

        $this->notifyUser(
            $complaint->reporter,
            $complaint,
            'complaint_resolved',
            'Complaint resolved — feedback requested',
            "{$complaint->ticket_number} has been resolved. Please review the result and submit your feedback."
        );
    }

    public function notifyHrdFeedbackNeedsFollowUp(Complaint $complaint): void
    {
        if (! $complaint->handler?->is_active) {
            return;
        }

        $this->notifyUser(
            $complaint->handler,
            $complaint,
            'feedback_needs_follow_up',
            'Employee requested additional follow-up',
            "{$complaint->ticket_number} received feedback that still needs HRD follow-up."
        );
    }

    public function notifyReporterFeedbackAddressed(Complaint $complaint): void
    {
        if (! $complaint->reporter?->is_active) {
            return;
        }

        $this->notifyUser(
            $complaint->reporter,
            $complaint,
            'feedback_addressed',
            'Your follow-up feedback was addressed',
            "HRD recorded a follow-up response for {$complaint->ticket_number}."
        );
    }

    public function notifyEscalation(Complaint $complaint): void
    {
        $active = $complaint->activeEscalation()->first();

        if (! $active) {
            return;
        }

        if ($active->target?->is_active) {
            $this->notifyUser(
                $active->target,
                $complaint,
                'complaint_escalated',
                'Complaint escalation requires attention',
                "{$complaint->ticket_number} has been escalated to you."
            );

            return;
        }

        $this->notifyUsers(
            User::role('pimpinan')->where('is_active', true)->get(),
            $complaint,
            'complaint_escalated',
            'Complaint escalation requires leadership attention',
            "{$complaint->ticket_number} has been escalated to a higher authority/external unit."
        );
    }

    public function notifyHrdEscalationResolved(Complaint $complaint): void
    {
        if (! $complaint->handler?->is_active) {
            return;
        }

        $this->notifyUser(
            $complaint->handler,
            $complaint,
            'escalation_resolved',
            'Escalation resolved — workflow can continue',
            "The active escalation on {$complaint->ticket_number} has been resolved."
        );
    }

    private function notifyUsers(
        Collection $users,
        Complaint $complaint,
        string $eventType,
        string $title,
        string $message
    ): void {
        $users->each(
            fn (User $user) => $this->notifyUser(
                $user,
                $complaint,
                $eventType,
                $title,
                $message
            )
        );
    }

    private function notifyUser(
        User $user,
        Complaint $complaint,
        string $eventType,
        string $title,
        string $message
    ): void {
        $user->notify(
            new ComplaintActivityNotification(
                $complaint,
                $eventType,
                $title,
                $message
            )
        );
    }
}
