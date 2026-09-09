<div class="max-w-7xl mx-auto space-y-8">

    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
        <div>
            <p class="text-sm font-semibold text-brand-600 mb-2">
                Source-of-truth Work Queue
            </p>
            <h1 class="text-4xl font-bold tracking-tight text-slate2-900">
                Action Center
            </h1>
            <p class="text-slate2-500 mt-2 max-w-3xl">
                Items remain here until the underlying complaint condition is actually resolved.
                Reading a notification does not remove an action item.
            </p>
        </div>

        <div class="inline-flex items-center gap-2 px-4 py-3 rounded-2xl bg-white border border-surface-100 shadow-sm">
            <span class="text-sm text-slate2-500">Open actions</span>
            <span class="px-3 py-1 rounded-xl bg-brand-100 text-brand-700 font-bold">{{ $allCount }}</span>
        </div>
    </div>

    <div class="flex flex-wrap gap-3">
        @foreach(['' => 'All', 'critical' => 'Critical', 'warning' => 'Warning', 'info' => 'Info'] as $value => $label)
            <button
                wire:click="$set('urgency', '{{ $value }}')"
                class="px-4 py-2 rounded-xl border transition {{ $urgency === $value ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-slate2-700 border-surface-200 hover:bg-surface-50' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="space-y-4">
        @forelse($items as $item)
            <a
                href="{{ route('complaints.show', $item['ticket_number']) }}"
                class="block bg-white rounded-3xl p-6 border border-surface-100 shadow-sm hover:-translate-y-0.5 hover:shadow-md transition">

                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-5">
                    <div class="flex gap-4">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0
                            {{ $item['urgency'] === 'critical' ? 'bg-red-100 text-red-600' : ($item['urgency'] === 'warning' ? 'bg-amber-100 text-amber-600' : 'bg-blue-100 text-blue-600') }}">
                            @if($item['urgency'] === 'critical')
                                <x-heroicon-o-exclamation-triangle class="w-6 h-6" />
                            @elseif($item['urgency'] === 'warning')
                                <x-heroicon-o-clock class="w-6 h-6" />
                            @else
                                <x-heroicon-o-information-circle class="w-6 h-6" />
                            @endif
                        </div>

                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs font-semibold uppercase tracking-wide
                                    {{ $item['urgency'] === 'critical' ? 'text-red-600' : ($item['urgency'] === 'warning' ? 'text-amber-600' : 'text-blue-600') }}">
                                    {{ $item['urgency'] }}
                                </span>
                                <span class="text-xs text-slate2-400">{{ $item['ticket_number'] }}</span>
                            </div>

                            <h2 class="text-lg font-bold text-slate2-900 mt-2">
                                {{ $item['title'] }}
                            </h2>

                            <p class="text-sm text-slate2-600 mt-2 leading-6">
                                {{ $item['description'] }}
                            </p>

                            <p class="text-sm font-medium text-slate2-800 mt-3">
                                {{ $item['complaint_title'] }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 text-sm font-semibold text-brand-600 shrink-0">
                        Open Complaint
                        <x-heroicon-o-arrow-right class="w-4 h-4" />
                    </div>
                </div>
            </a>
        @empty
            <div class="bg-white rounded-3xl p-12 border border-dashed border-surface-200 text-center">
                <div class="w-16 h-16 mx-auto rounded-3xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                    <x-heroicon-o-check-circle class="w-8 h-8" />
                </div>
                <h2 class="text-xl font-bold text-slate2-900 mt-5">No open actions</h2>
                <p class="text-sm text-slate2-500 mt-2">
                    There are no source-of-truth work items matching this filter.
                </p>
            </div>
        @endforelse
    </div>
</div>
