<?php

namespace App\Services;

use App\Enums\EscalationReason;
use App\Enums\RootCauseCategory;
use App\Models\Complaint;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ComplaintAnalyticsService
{
    public function build(CarbonInterface $from, CarbonInterface $to): array
    {
        $complaints = $this->complaints($from, $to);

        $statusCounts = $complaints
            ->countBy('status')
            ->sortKeys()
            ->all();

        $categoryDistribution = $complaints
            ->groupBy(fn (Complaint $complaint) => $complaint->category?->name ?? 'Uncategorized')
            ->map->count()
            ->sortDesc()
            ->all();

        $rootCauseDistribution = $complaints
            ->filter(fn (Complaint $complaint) => filled($complaint->root_cause_category))
            ->groupBy('root_cause_category')
            ->map->count()
            ->sortDesc()
            ->mapWithKeys(function (int $count, string $category): array {
                $label = RootCauseCategory::tryFrom($category)?->label() ?? $category;

                return [$label => $count];
            })
            ->all();

        $rootCauseCounts = $complaints
            ->filter(fn (Complaint $complaint) => filled($complaint->root_cause_category))
            ->countBy('root_cause_category');

        $categorizedRootCauseCount = $rootCauseCounts->sum();

        $repeatedRootCauseGroups = $rootCauseCounts
            ->filter(fn (int $count) => $count >= 2);

        $repeatedPatternComplaints = $repeatedRootCauseGroups->sum();

        $recurrenceProxyRate = $categorizedRootCauseCount > 0
            ? round(($repeatedPatternComplaints / $categorizedRootCauseCount) * 100, 1)
            : null;

        $responded = $complaints
            ->filter(fn (Complaint $complaint) => $complaint->first_response_at && $complaint->response_due_at);

        $responseCompliant = $responded
            ->filter(fn (Complaint $complaint) => $complaint->first_response_at->lte($complaint->response_due_at))
            ->count();

        $responseComplianceRate = $responded->isNotEmpty()
            ? round(($responseCompliant / $responded->count()) * 100, 1)
            : null;

        $resolved = $complaints
            ->filter(fn (Complaint $complaint) => $complaint->resolved_at && $complaint->resolution_due_at);

        $resolutionCompliant = $resolved
            ->filter(fn (Complaint $complaint) => $complaint->resolved_at->lte($complaint->resolution_due_at))
            ->count();

        $resolutionComplianceRate = $resolved->isNotEmpty()
            ? round(($resolutionCompliant / $resolved->count()) * 100, 1)
            : null;

        $responseOverdueOpen = $complaints
            ->filter(fn (Complaint $complaint) => is_null($complaint->first_response_at)
                && $complaint->response_due_at
                && now()->gt($complaint->response_due_at))
            ->count();

        $resolutionOverdueOpen = $complaints
            ->filter(fn (Complaint $complaint) => ! in_array($complaint->status, ['resolved', 'closed', 'rejected'], true)
                && $complaint->resolution_due_at
                && now()->gt($complaint->resolution_due_at))
            ->count();

        $feedbackEligible = $complaints
            ->filter(fn (Complaint $complaint) => ! is_null($complaint->resolved_at));

        $feedbacks = $feedbackEligible
            ->pluck('feedback')
            ->filter();

        $feedbackCoverageRate = $feedbackEligible->isNotEmpty()
            ? round(($feedbacks->count() / $feedbackEligible->count()) * 100, 1)
            : null;

        $averageSatisfaction = $feedbacks->isNotEmpty()
            ? round($feedbacks->avg('satisfaction_rating'), 2)
            : null;

        $acceptedFeedback = $feedbacks
            ->where('outcome', 'accepted')
            ->count();

        $needsFollowUpFeedback = $feedbacks
            ->where('outcome', 'needs_follow_up');

        $addressedFeedback = $needsFollowUpFeedback
            ->filter(fn ($feedback) => ! is_null($feedback->addressed_at))
            ->count();

        $pendingFeedback = $needsFollowUpFeedback
            ->filter(fn ($feedback) => is_null($feedback->addressed_at))
            ->count();

        $closedWithoutFeedback = $complaints
            ->filter(fn (Complaint $complaint) => $complaint->status === 'closed'
                && is_null($complaint->feedback)
                && filled($complaint->closed_without_feedback_reason))
            ->count();

        $escalations = $complaints
            ->flatMap(fn (Complaint $complaint) => $complaint->escalations);

        $escalationReasonDistribution = $escalations
            ->countBy('reason_code')
            ->sortDesc()
            ->mapWithKeys(function (int $count, string $reason): array {
                $label = EscalationReason::tryFrom($reason)?->label() ?? $reason;

                return [$label => $count];
            })
            ->all();

        $avgFirstResponseMinutes = $responded->isNotEmpty()
            ? round($responded->avg(
                fn (Complaint $complaint) => $complaint->created_at
                    ->diffInMinutes($complaint->first_response_at)
            ), 1)
            : null;

        $avgResolutionMinutes = $resolved->isNotEmpty()
            ? round($resolved->avg(
                fn (Complaint $complaint) => $complaint->created_at
                    ->diffInMinutes($complaint->resolved_at)
            ), 1)
            : null;

        return [
            'period' => [
                'from' => $from->copy()->startOfDay(),
                'to' => $to->copy()->endOfDay(),
            ],
            'totalComplaints' => $complaints->count(),
            'statusCounts' => $statusCounts,
            'categoryDistribution' => $categoryDistribution,
            'rootCauseDistribution' => $rootCauseDistribution,
            'trend' => $this->dailyTrend($complaints, $from, $to),
            'recurrence' => [
                'categorizedComplaints' => $categorizedRootCauseCount,
                'repeatedGroups' => $repeatedRootCauseGroups->count(),
                'complaintsInRepeatedGroups' => $repeatedPatternComplaints,
                'proxyRate' => $recurrenceProxyRate,
            ],
            'sla' => [
                'responseSamples' => $responded->count(),
                'responseCompliant' => $responseCompliant,
                'responseComplianceRate' => $responseComplianceRate,
                'responseOverdueOpen' => $responseOverdueOpen,
                'resolutionSamples' => $resolved->count(),
                'resolutionCompliant' => $resolutionCompliant,
                'resolutionComplianceRate' => $resolutionComplianceRate,
                'resolutionOverdueOpen' => $resolutionOverdueOpen,
                'avgFirstResponseMinutes' => $avgFirstResponseMinutes,
                'avgResolutionMinutes' => $avgResolutionMinutes,
            ],
            'feedback' => [
                'eligibleComplaints' => $feedbackEligible->count(),
                'submitted' => $feedbacks->count(),
                'coverageRate' => $feedbackCoverageRate,
                'averageSatisfaction' => $averageSatisfaction,
                'accepted' => $acceptedFeedback,
                'needsFollowUp' => $needsFollowUpFeedback->count(),
                'addressed' => $addressedFeedback,
                'pending' => $pendingFeedback,
                'closedWithoutFeedback' => $closedWithoutFeedback,
            ],
            'escalations' => [
                'total' => $escalations->count(),
                'active' => $escalations->where('status', 'active')->count(),
                'resolved' => $escalations->where('status', 'resolved')->count(),
                'reasonDistribution' => $escalationReasonDistribution,
            ],
            'handlerPerformance' => $this->handlerPerformance($complaints),
        ];
    }

    public function exportRows(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $this->complaints($from, $to)
            ->sortBy('created_at')
            ->values()
            ->map(function (Complaint $complaint): array {
                return [
                    'ticket_number' => $complaint->ticket_number,
                    'created_at' => $complaint->created_at?->format('Y-m-d H:i:s'),
                    'title' => $complaint->title,
                    'status' => $complaint->status,
                    'priority' => $complaint->priority?->name,
                    'category' => $complaint->category?->name,
                    'root_cause_category' => RootCauseCategory::tryFrom(
                        (string) $complaint->root_cause_category
                    )?->label(),
                    'reporter' => $complaint->reporter?->name,
                    'handler' => $complaint->handler?->name,
                    'first_response_at' => $complaint->first_response_at?->format('Y-m-d H:i:s'),
                    'response_due_at' => $complaint->response_due_at?->format('Y-m-d H:i:s'),
                    'response_sla' => $this->responseSlaLabel($complaint),
                    'resolved_at' => $complaint->resolved_at?->format('Y-m-d H:i:s'),
                    'resolution_due_at' => $complaint->resolution_due_at?->format('Y-m-d H:i:s'),
                    'resolution_sla' => $this->resolutionSlaLabel($complaint),
                    'follow_up_required' => $complaint->follow_up_required ? 'yes' : 'no',
                    'follow_up_completed_at' => $complaint->follow_up_completed_at?->format('Y-m-d H:i:s'),
                    'escalation_count' => $complaint->escalations->count(),
                    'active_escalation' => $complaint->escalations->contains(
                        fn ($escalation) => $escalation->status === 'active'
                    ) ? 'yes' : 'no',
                    'feedback_outcome' => $complaint->feedback?->outcome,
                    'feedback_rating' => $complaint->feedback?->satisfaction_rating,
                    'feedback_comment' => $complaint->feedback?->comment,
                    'feedback_addressed_at' => $complaint->feedback?->addressed_at?->format('Y-m-d H:i:s'),
                    'closed_without_feedback_reason' => $complaint->closed_without_feedback_reason,
                ];
            });
    }

    private function complaints(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return Complaint::query()
            ->with([
                'category',
                'priority',
                'reporter',
                'handler',
                'escalations',
                'feedback',
            ])
            ->whereBetween('created_at', [
                $from->copy()->startOfDay(),
                $to->copy()->endOfDay(),
            ])
            ->get();
    }

    private function dailyTrend(
        Collection $complaints,
        CarbonInterface $from,
        CarbonInterface $to
    ): array {
        $counts = $complaints
            ->countBy(fn (Complaint $complaint) => $complaint->created_at->format('Y-m-d'));

        $trend = [];
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $date = $cursor->format('Y-m-d');

            $trend[] = [
                'date' => $date,
                'label' => $cursor->format('d M'),
                'count' => (int) ($counts[$date] ?? 0),
            ];

            $cursor->addDay();
        }

        return $trend;
    }

    private function handlerPerformance(Collection $complaints): array
    {
        return $complaints
            ->filter(fn (Complaint $complaint) => $complaint->assigned_to && $complaint->handler)
            ->groupBy('assigned_to')
            ->map(function (Collection $assigned): array {
                /** @var Complaint $first */
                $first = $assigned->first();

                $resolved = $assigned->filter(
                    fn (Complaint $complaint) => $complaint->resolved_at && $complaint->resolution_due_at
                );

                $compliant = $resolved->filter(
                    fn (Complaint $complaint) => $complaint->resolved_at->lte($complaint->resolution_due_at)
                );

                $open = $assigned->filter(
                    fn (Complaint $complaint) => ! in_array($complaint->status, ['resolved', 'closed', 'rejected'], true)
                );

                return [
                    'handlerId' => $first->handler->id,
                    'handlerName' => $first->handler->name,
                    'assigned' => $assigned->count(),
                    'resolved' => $resolved->count(),
                    'openBacklog' => $open->count(),
                    'resolutionComplianceRate' => $resolved->isNotEmpty()
                        ? round(($compliant->count() / $resolved->count()) * 100, 1)
                        : null,
                    'avgResolutionMinutes' => $resolved->isNotEmpty()
                        ? round($resolved->avg(
                            fn (Complaint $complaint) => $complaint->created_at
                                ->diffInMinutes($complaint->resolved_at)
                        ), 1)
                        : null,
                ];
            })
            ->sortByDesc('assigned')
            ->values()
            ->all();
    }

    private function responseSlaLabel(Complaint $complaint): string
    {
        if (! $complaint->response_due_at) {
            return 'not_available';
        }

        if ($complaint->first_response_at) {
            return $complaint->first_response_at->lte($complaint->response_due_at)
                ? 'compliant'
                : 'breached';
        }

        return now()->gt($complaint->response_due_at)
            ? 'overdue'
            : 'pending';
    }

    private function resolutionSlaLabel(Complaint $complaint): string
    {
        if (! $complaint->resolution_due_at || $complaint->status === 'rejected') {
            return 'not_applicable';
        }

        if ($complaint->resolved_at) {
            return $complaint->resolved_at->lte($complaint->resolution_due_at)
                ? 'compliant'
                : 'breached';
        }

        return now()->gt($complaint->resolution_due_at)
            ? 'overdue'
            : 'pending';
    }
}
