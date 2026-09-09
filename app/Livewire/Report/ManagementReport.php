<?php

namespace App\Livewire\Report;

use App\Services\ComplaintAnalyticsService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ManagementReport extends Component
{
    public string $date_from = '';
    public string $date_to = '';
    public string $applied_date_from = '';
    public string $applied_date_to = '';

    public function mount(): void
    {
        $this->authorizeManagementReport();

        $this->date_from = now()->startOfMonth()->toDateString();
        $this->date_to = now()->toDateString();

        $this->applied_date_from = $this->date_from;
        $this->applied_date_to = $this->date_to;
    }

    public function applyFilters(): void
    {
        $this->authorizeManagementReport();

        $this->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $from = Carbon::parse($this->date_from);
        $to = Carbon::parse($this->date_to);

        if ($from->diffInDays($to) > 366) {
            throw ValidationException::withMessages([
                'date_to' => 'Reporting range cannot exceed 366 days.',
            ]);
        }

        $this->applied_date_from = $from->toDateString();
        $this->applied_date_to = $to->toDateString();
    }

    public function resetFilters(): void
    {
        $this->date_from = now()->startOfMonth()->toDateString();
        $this->date_to = now()->toDateString();

        $this->applied_date_from = $this->date_from;
        $this->applied_date_to = $this->date_to;

        $this->resetValidation();
    }

    public function exportCsv(ComplaintAnalyticsService $analytics)
    {
        $this->authorizeManagementReport();

        [$from, $to] = $this->appliedRange();

        $rows = $analytics->exportRows($from, $to);

        $headers = [
            'Ticket Number',
            'Created At',
            'Title',
            'Status',
            'Priority',
            'Category',
            'Root Cause Category',
            'Reporter',
            'Handler',
            'First Response At',
            'Response Due At',
            'Response SLA',
            'Resolved At',
            'Resolution Due At',
            'Resolution SLA',
            'Follow-up Required',
            'Follow-up Completed At',
            'Escalation Count',
            'Active Escalation',
        ];

        $filename = sprintf(
            'complaint-management-report-%s-to-%s.csv',
            $this->applied_date_from,
            $this->applied_date_to
        );

        return response()->streamDownload(function () use ($rows, $headers): void {
            $output = fopen('php://output', 'w');

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headers);

            foreach ($rows as $row) {
                fputcsv($output, array_values($row));
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function render(ComplaintAnalyticsService $analytics)
    {
        $this->authorizeManagementReport();

        [$from, $to] = $this->appliedRange();

        return view('livewire.report.management-report', [
            'report' => $analytics->build($from, $to),
        ])->layout('layouts.app');
    }

    private function appliedRange(): array
    {
        return [
            Carbon::parse($this->applied_date_from)->startOfDay(),
            Carbon::parse($this->applied_date_to)->endOfDay(),
        ];
    }

    private function authorizeManagementReport(): void
    {
        abort_unless(
            auth()->check()
                && auth()->user()->hasAnyRole([
                    'admin',
                    'supervisor',
                    'hrd',
                    'pimpinan',
                ]),
            403
        );
    }
}
