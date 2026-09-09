<div class="max-w-7xl mx-auto space-y-8">

    <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-6">
        <div>
            <p class="text-sm font-semibold text-brand-600 mb-2">
                Management Insight
            </p>
            <h1 class="text-4xl font-bold tracking-tight text-slate2-900">
                Complaint Analytics
            </h1>
            <p class="text-slate2-500 mt-2 max-w-3xl">
                Review complaint volume, root-cause patterns, SLA performance, handler workload,
                and escalation activity for complaints created in the selected period.
            </p>
        </div>

        <button
            wire:click="exportCsv"
            wire:loading.attr="disabled"
            wire:target="exportCsv"
            class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition">
            <x-heroicon-o-arrow-down-tray class="w-5 h-5" />
            Export CSV
        </button>
    </div>

    <div class="bg-white rounded-3xl p-6 border border-surface-100 shadow-sm">
        <form wire:submit="applyFilters" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-[1fr_1fr_auto_auto] gap-4 items-end">
            <div>
                <label class="block text-sm font-semibold text-slate2-700 mb-2">
                    Date From
                </label>
                <input type="date" wire:model="date_from" class="input">
                @error('date_from')
                    <p class="text-sm text-red-500 mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate2-700 mb-2">
                    Date To
                </label>
                <input type="date" wire:model="date_to" class="input">
                @error('date_to')
                    <p class="text-sm text-red-500 mt-2">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="px-5 py-3 rounded-2xl bg-brand-600 text-white font-semibold hover:bg-brand-700 transition">
                Apply
            </button>

            <button
                type="button"
                wire:click="resetFilters"
                class="px-5 py-3 rounded-2xl border border-surface-200 bg-white text-slate2-700 font-semibold hover:bg-surface-50 transition">
                Reset
            </button>
        </form>

        <p class="text-xs text-slate2-400 mt-4">
            Maximum reporting window: 366 days. Analytics are based on complaints created within the applied period.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        <div class="card p-6">
            <p class="text-sm font-medium text-slate2-500">Total Complaints</p>
            <p class="text-4xl font-bold text-slate2-900 mt-3">{{ $report['totalComplaints'] }}</p>
        </div>

        <div class="card p-6">
            <p class="text-sm font-medium text-slate2-500">Response SLA Compliance</p>
            <p class="text-4xl font-bold text-blue-600 mt-3">
                {{ $report['sla']['responseComplianceRate'] === null ? '-' : number_format($report['sla']['responseComplianceRate'], 1) . '%' }}
            </p>
            <p class="text-xs text-slate2-400 mt-2">
                {{ $report['sla']['responseCompliant'] }} compliant of {{ $report['sla']['responseSamples'] }} responded tickets
            </p>
        </div>

        <div class="card p-6">
            <p class="text-sm font-medium text-slate2-500">Resolution SLA Compliance</p>
            <p class="text-4xl font-bold text-emerald-600 mt-3">
                {{ $report['sla']['resolutionComplianceRate'] === null ? '-' : number_format($report['sla']['resolutionComplianceRate'], 1) . '%' }}
            </p>
            <p class="text-xs text-slate2-400 mt-2">
                {{ $report['sla']['resolutionCompliant'] }} compliant of {{ $report['sla']['resolutionSamples'] }} resolved tickets
            </p>
        </div>

        <div class="card p-6">
            <p class="text-sm font-medium text-slate2-500">Recurrence Proxy</p>
            <p class="text-4xl font-bold text-purple-600 mt-3">
                {{ $report['recurrence']['proxyRate'] === null ? '-' : number_format($report['recurrence']['proxyRate'], 1) . '%' }}
            </p>
            <p class="text-xs text-slate2-400 mt-2">
                Root-cause categories appearing at least twice in this period
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card p-6">
            <h2 class="text-xl font-bold text-slate2-900">SLA & Time Metrics</h2>
            <div class="grid grid-cols-2 gap-4 mt-6">
                <div class="rounded-2xl bg-surface-50 p-4">
                    <p class="text-xs uppercase tracking-wide font-semibold text-slate2-400">MTTA</p>
                    <p class="text-2xl font-bold text-blue-600 mt-2">
                        {{ $report['sla']['avgFirstResponseMinutes'] === null ? '-' : number_format($report['sla']['avgFirstResponseMinutes'], 1) }}
                    </p>
                    <p class="text-xs text-slate2-500 mt-1">minutes</p>
                </div>

                <div class="rounded-2xl bg-surface-50 p-4">
                    <p class="text-xs uppercase tracking-wide font-semibold text-slate2-400">MTTR</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-2">
                        {{ $report['sla']['avgResolutionMinutes'] === null ? '-' : number_format($report['sla']['avgResolutionMinutes'], 1) }}
                    </p>
                    <p class="text-xs text-slate2-500 mt-1">minutes</p>
                </div>

                <div class="rounded-2xl bg-surface-50 p-4">
                    <p class="text-xs uppercase tracking-wide font-semibold text-slate2-400">Response Overdue</p>
                    <p class="text-2xl font-bold {{ $report['sla']['responseOverdueOpen'] > 0 ? 'text-red-600' : 'text-slate2-900' }} mt-2">
                        {{ $report['sla']['responseOverdueOpen'] }}
                    </p>
                    <p class="text-xs text-slate2-500 mt-1">open without response</p>
                </div>

                <div class="rounded-2xl bg-surface-50 p-4">
                    <p class="text-xs uppercase tracking-wide font-semibold text-slate2-400">Resolution Overdue</p>
                    <p class="text-2xl font-bold {{ $report['sla']['resolutionOverdueOpen'] > 0 ? 'text-red-600' : 'text-slate2-900' }} mt-2">
                        {{ $report['sla']['resolutionOverdueOpen'] }}
                    </p>
                    <p class="text-xs text-slate2-500 mt-1">open past resolution SLA</p>
                </div>
            </div>
        </div>

        <div class="card p-6">
            <h2 class="text-xl font-bold text-slate2-900">Escalation Summary</h2>

            <div class="grid grid-cols-3 gap-4 mt-6">
                <div class="rounded-2xl bg-surface-50 p-4">
                    <p class="text-xs text-slate2-500">Total</p>
                    <p class="text-2xl font-bold text-slate2-900 mt-1">{{ $report['escalations']['total'] }}</p>
                </div>
                <div class="rounded-2xl bg-purple-50 p-4">
                    <p class="text-xs text-purple-600">Active</p>
                    <p class="text-2xl font-bold text-purple-700 mt-1">{{ $report['escalations']['active'] }}</p>
                </div>
                <div class="rounded-2xl bg-emerald-50 p-4">
                    <p class="text-xs text-emerald-600">Resolved</p>
                    <p class="text-2xl font-bold text-emerald-700 mt-1">{{ $report['escalations']['resolved'] }}</p>
                </div>
            </div>

            <div class="mt-6 space-y-3">
                @forelse($report['escalations']['reasonDistribution'] as $label => $count)
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-sm text-slate2-700">{{ $label }}</span>
                        <span class="px-3 py-1 rounded-xl bg-slate-100 text-sm font-semibold text-slate2-700">{{ $count }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate2-500">No escalation records in the selected complaint cohort.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="card p-6">
            <h2 class="text-xl font-bold text-slate2-900">Complaint Categories</h2>
            <div class="mt-6 space-y-4">
                @php($categoryMax = max(1, collect($report['categoryDistribution'])->max() ?? 1))
                @forelse($report['categoryDistribution'] as $label => $count)
                    <div>
                        <div class="flex justify-between gap-4 text-sm mb-2">
                            <span class="font-medium text-slate2-700">{{ $label }}</span>
                            <span class="font-semibold text-slate2-900">{{ $count }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-surface-100 overflow-hidden">
                            <div class="h-full bg-brand-600 rounded-full" style="width: {{ ($count / $categoryMax) * 100 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate2-500">No complaint category data for this period.</p>
                @endforelse
            </div>
        </div>

        <div class="card p-6">
            <h2 class="text-xl font-bold text-slate2-900">Root-cause Distribution</h2>
            <div class="mt-6 space-y-4">
                @php($rootCauseMax = max(1, collect($report['rootCauseDistribution'])->max() ?? 1))
                @forelse($report['rootCauseDistribution'] as $label => $count)
                    <div>
                        <div class="flex justify-between gap-4 text-sm mb-2">
                            <span class="font-medium text-slate2-700">{{ $label }}</span>
                            <span class="font-semibold text-slate2-900">{{ $count }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-surface-100 overflow-hidden">
                            <div class="h-full bg-purple-600 rounded-full" style="width: {{ ($count / $rootCauseMax) * 100 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate2-500">Root-cause analysis has not been recorded for this period.</p>
                @endforelse
            </div>

            <div class="mt-6 rounded-2xl border border-purple-100 bg-purple-50/60 p-4">
                <p class="text-sm font-semibold text-purple-800">
                    Recurrence proxy
                </p>
                <p class="text-sm text-purple-700 mt-1">
                    {{ $report['recurrence']['repeatedGroups'] }} repeated root-cause group(s),
                    covering {{ $report['recurrence']['complaintsInRepeatedGroups'] }}
                    of {{ $report['recurrence']['categorizedComplaints'] }} categorized complaints.
                </p>
            </div>
        </div>
    </div>

    <div class="card p-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate2-900">Complaint Trend</h2>
                <p class="text-sm text-slate2-500 mt-1">Daily complaint volume in the applied reporting period.</p>
            </div>
        </div>

        @php($trendMax = max(1, collect($report['trend'])->max('count') ?? 1))
        <div class="mt-6 overflow-x-auto">
            <div class="flex items-end gap-2 min-w-max h-52 px-1">
                @foreach($report['trend'] as $point)
                    <div class="w-8 flex flex-col justify-end items-center h-full">
                        <span class="text-[10px] font-semibold text-slate2-600 mb-1">{{ $point['count'] }}</span>
                        <div
                            class="w-5 rounded-t-lg bg-brand-600 min-h-[2px]"
                            style="height: {{ max(2, ($point['count'] / $trendMax) * 150) }}px">
                        </div>
                        <span class="text-[9px] text-slate2-400 mt-2 rotate-[-45deg] origin-top whitespace-nowrap">
                            {{ $point['label'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="px-6 py-5 border-b border-surface-100">
            <h2 class="text-xl font-bold text-slate2-900">PIC / HRD Performance</h2>
            <p class="text-sm text-slate2-500 mt-1">
                Workload and resolution performance for complaints assigned in the selected cohort.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="text-left px-6 py-4 text-xs uppercase tracking-wide text-slate2-500">PIC</th>
                        <th class="text-right px-6 py-4 text-xs uppercase tracking-wide text-slate2-500">Assigned</th>
                        <th class="text-right px-6 py-4 text-xs uppercase tracking-wide text-slate2-500">Resolved</th>
                        <th class="text-right px-6 py-4 text-xs uppercase tracking-wide text-slate2-500">Open</th>
                        <th class="text-right px-6 py-4 text-xs uppercase tracking-wide text-slate2-500">Resolution SLA</th>
                        <th class="text-right px-6 py-4 text-xs uppercase tracking-wide text-slate2-500">Avg Resolution</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['handlerPerformance'] as $row)
                        <tr class="border-t border-surface-100">
                            <td class="px-6 py-5 font-semibold text-slate2-900">{{ $row['handlerName'] }}</td>
                            <td class="px-6 py-5 text-right text-slate2-700">{{ $row['assigned'] }}</td>
                            <td class="px-6 py-5 text-right text-slate2-700">{{ $row['resolved'] }}</td>
                            <td class="px-6 py-5 text-right text-slate2-700">{{ $row['openBacklog'] }}</td>
                            <td class="px-6 py-5 text-right text-slate2-700">
                                {{ $row['resolutionComplianceRate'] === null ? '-' : number_format($row['resolutionComplianceRate'], 1) . '%' }}
                            </td>
                            <td class="px-6 py-5 text-right text-slate2-700">
                                {{ $row['avgResolutionMinutes'] === null ? '-' : number_format($row['avgResolutionMinutes'], 1) . ' min' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate2-500">
                                No assigned complaint data for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
        <strong>Interpretation note:</strong>
        the recurrence figure is a management proxy based on repeated root-cause categories within the selected period.
        It does not prove that individual incidents are identical recurrences.
    </div>
</div>
