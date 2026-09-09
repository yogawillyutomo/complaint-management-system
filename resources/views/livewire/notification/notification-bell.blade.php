<div x-data="{ open: false }" class="relative">
    <button
        @click="open = !open"
        class="relative w-11 h-11 rounded-2xl border border-surface-200 flex items-center justify-center hover:bg-surface-100 transition">

        <x-heroicon-o-bell class="w-5 h-5" />

        @if($unreadCount > 0)
            <span class="absolute -top-1 -right-1 min-w-5 h-5 px-1.5 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        @click.away="open = false"
        x-transition
        class="absolute right-0 mt-3 w-[420px] max-w-[90vw] bg-white rounded-3xl border border-surface-100 shadow-2xl overflow-hidden z-50">

        <div class="flex items-center justify-between px-5 py-4 border-b border-surface-100">
            <div>
                <p class="font-bold text-slate2-900">Notifications</p>
                <p class="text-xs text-slate2-500 mt-1">{{ $unreadCount }} unread</p>
            </div>

            @if($unreadCount > 0)
                <button wire:click="markAllRead" class="text-xs font-semibold text-brand-600 hover:text-brand-700">
                    Mark all read
                </button>
            @endif
        </div>

        <div class="max-h-[480px] overflow-y-auto">
            @forelse($notifications as $notification)
                @php($data = $notification->data)
                <a
                    href="{{ route($data['route'] ?? 'dashboard', $data['ticket_number'] ?? null) }}"
                    wire:click="markRead('{{ $notification->id }}')"
                    class="block px-5 py-4 border-b border-surface-100 hover:bg-surface-50 transition {{ is_null($notification->read_at) ? 'bg-brand-50/50' : '' }}">

                    <div class="flex gap-3">
                        <div class="w-2 h-2 mt-2 rounded-full shrink-0 {{ is_null($notification->read_at) ? 'bg-brand-600' : 'bg-slate-200' }}"></div>

                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate2-900">
                                {{ $data['title'] ?? 'Complaint update' }}
                            </p>

                            <p class="text-sm text-slate2-600 mt-1 leading-5">
                                {{ $data['message'] ?? '' }}
                            </p>

                            <div class="flex items-center gap-2 mt-2 text-xs text-slate2-400">
                                @if(!empty($data['ticket_number']))
                                    <span>{{ $data['ticket_number'] }}</span>
                                    <span>•</span>
                                @endif
                                <span>{{ $notification->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="px-5 py-10 text-center text-sm text-slate2-500">
                    No notifications yet.
                </div>
            @endforelse
        </div>

        <a
            href="{{ route('action-center.index') }}"
            class="flex items-center justify-center gap-2 px-5 py-4 text-sm font-semibold text-brand-600 hover:bg-surface-50 transition">
            Open Action Center
            <x-heroicon-o-arrow-right class="w-4 h-4" />
        </a>
    </div>
</div>
