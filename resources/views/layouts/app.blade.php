<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Complaint System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body class="font-sans bg-gradient-to-br from-slate-50 to-surface-100">

    <div class="flex min-h-screen">

        {{-- Sidebar --}}
        <aside class="w-72 bg-white border-r border-surface-200 flex flex-col">

            {{-- Logo --}}
            <div class="h-20 flex items-center px-6 border-b border-surface-100">

                <div class="flex items-center gap-3">

                    <div class="w-11 h-11 rounded-2xl bg-brand-600 flex items-center justify-center text-white font-bold text-lg">
                        C
                    </div>

                    <div>
                        <h1 class="font-bold text-slate2-900">
                            Complaint System
                        </h1>

                        <p class="text-xs text-slate2-600">
                            PT Telkom
                        </p>
                    </div>

                </div>

            </div>

            {{-- Menu --}}
            <nav class="flex-1 p-4 space-y-2">

                <a href="{{ route('dashboard') }}"
                    class="sidebar-item {{ request()->routeIs('dashboard') ? 'sidebar-item-active' : '' }}">

                    <x-heroicon-o-home class="w-5 h-5" />

                    <span>Dashboard</span>
                </a>

                <a href="{{ route('complaints.index') }}"
                    class="sidebar-item {{ request()->routeIs('complaints.*') ? 'sidebar-item-active' : '' }}">

                    <x-heroicon-o-chat-bubble-left-right class="w-5 h-5" />

                    <span>Complaints</span>
                </a>

                @if(auth()->user()->roles()->where('name', 'admin')->exists())
                <a href="{{ route('users.index') }}"
                    class="sidebar-item {{ request()->routeIs('users.*') ? 'sidebar-item-active' : '' }}">

                    <x-heroicon-o-users class="w-5 h-5" />

                    <span>Users</span>
                </a>
                @endif

                <a href="#"
                    class="sidebar-item">

                    <x-heroicon-o-chart-bar class="w-5 h-5" />

                    <span>Reports</span>
                </a>


            </nav>

        </aside>

        {{-- Main --}}
        <div class="flex-1 flex flex-col">

            {{-- Topbar --}}
            <header class="h-20 bg-white border-b border-surface-200 px-8 flex items-center justify-between">

                {{-- Left --}}
                <div>

                    <h2 class="text-2xl font-bold text-slate2-900">
                        Dashboard
                    </h2>

                    <p class="text-sm text-slate2-600">
                        Welcome back 👋
                    </p>

                </div>

                {{-- Right --}}
                <div class="flex items-center gap-4">

                    {{-- Notification --}}
                    <button class="relative w-11 h-11 rounded-2xl border border-surface-200 flex items-center justify-center hover:bg-surface-100 transition">

                        <x-heroicon-o-bell class="w-5 h-5" />

                        <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full"></span>

                    </button>

                    {{-- Profile Dropdown --}}
                    <div
                        x-data="{ open: false }"
                        class="relative">

                        {{-- Button --}}
                        <button
                            @click="open = !open"
                            class="flex items-center gap-3 hover:bg-surface-100 rounded-2xl px-3 py-2 transition">

                            {{-- Avatar --}}
                            <div class="w-11 h-11 rounded-2xl bg-brand-600 text-white flex items-center justify-center font-semibold">

                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}

                            </div>

                            {{-- Info --}}
                            <div class="text-left">

                                <p class="font-semibold text-sm text-slate2-900">
                                    {{ auth()->user()->name }}
                                </p>

                                <p class="text-xs text-slate2-600">
                                    {{ auth()->user()->roles()->first()?->name ?? 'User' }}
                                </p>

                            </div>

                            {{-- Arrow --}}
                            <x-heroicon-o-chevron-down class="w-4 h-4 text-slate2-500" />

                        </button>

                        {{-- Dropdown --}}
                        <div
                            x-show="open"
                            @click.away="open = false"
                            x-transition
                            class="absolute right-0 mt-3 w-56 bg-white rounded-3xl border border-surface-100 shadow-xl overflow-hidden z-50">

                            {{-- Header --}}
                            <div class="px-5 py-4 border-b border-surface-100">

                                <p class="font-semibold text-slate2-900">
                                    {{ auth()->user()->name }}
                                </p>

                                <p class="text-sm text-slate2-500 mt-1">
                                    {{ auth()->user()->email }}
                                </p>

                            </div>

                            {{-- Menu --}}
                            <div class="p-2">

                                <a
                                    href="#"
                                    class="flex items-center gap-3 px-4 py-3 rounded-2xl hover:bg-surface-100 transition">

                                    <x-heroicon-o-user class="w-5 h-5 text-slate2-500" />

                                    <span class="text-sm font-medium">
                                        Profile
                                    </span>

                                </a>

                                <a
                                    href="#"
                                    class="flex items-center gap-3 px-4 py-3 rounded-2xl hover:bg-surface-100 transition">

                                    <x-heroicon-o-cog-6-tooth class="w-5 h-5 text-slate2-500" />

                                    <span class="text-sm font-medium">
                                        Settings
                                    </span>

                                </a>

                                {{-- Logout --}}
                                <form
                                    method="POST"
                                    action="{{ route('logout') }}">

                                    @csrf

                                    <button
                                        type="submit"
                                        class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl hover:bg-red-50 text-red-500 transition">

                                        <x-heroicon-o-arrow-right-on-rectangle class="w-5 h-5" />

                                        <span class="text-sm font-medium">
                                            Logout
                                        </span>

                                    </button>

                                </form>

                            </div>

                        </div>

                    </div>

                </div>

            </header>

            {{-- Content --}}
            <main class="flex-1 p-8">

                @if(isset($slot))
                {{ $slot }}
                @else
                @yield('content')
                @endif

            </main>

            @if(session()->has('success'))

            <div
                x-data="{ show: true }"
                x-init="setTimeout(() => show = false, 3000)"
                x-show="show"
                x-transition
                class="fixed top-6 right-6 z-[9999]">

                <div
                    class="flex items-center gap-4 bg-slate2-900 text-white px-6 py-4 rounded-2xl shadow-2xl min-w-[320px]">

                    <div class="w-10 h-10 rounded-full bg-green-500/20 flex items-center justify-center">

                        <x-heroicon-o-check class="w-6 h-6 text-green-400" />

                    </div>

                    <div>

                        <p class="font-semibold">
                            Success
                        </p>

                        <p class="text-sm text-slate-300">
                            {{ session('success') }}
                        </p>

                    </div>

                </div>

            </div>

            @endif

            <div
                x-data="{
        open: false,
        message: '',
        type: 'success'
    }"

                x-on:toast.window="
        message = $event.detail.message;
        type = $event.detail.type ?? 'success';

        open = true;

        setTimeout(() => open = false, 3000);
    "

                class="fixed top-6 right-6 z-[9999]">

                <template x-if="open">

                    <div
                        class="flex items-center gap-4 px-6 py-4 rounded-2xl shadow-2xl min-w-[320px] text-white transition-all duration-300"

                        :class="{
                'bg-emerald-600': type === 'success',
                'bg-red-500': type === 'error',
                'bg-amber-500': type === 'warning',
                'bg-blue-500': type === 'info',
            }">

                        {{-- Icon --}}
                        <div
                            class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">

                            {{-- Success --}}
                            <template x-if="type === 'success'">

                                <x-heroicon-o-check class="w-6 h-6 text-white" />

                            </template>

                            {{-- Error --}}
                            <template x-if="type === 'error'">

                                <x-heroicon-o-x-mark class="w-6 h-6 text-white" />

                            </template>

                            {{-- Warning --}}
                            <template x-if="type === 'warning'">

                                <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-white" />

                            </template>

                            {{-- Info --}}
                            <template x-if="type === 'info'">

                                <x-heroicon-o-information-circle class="w-6 h-6 text-white" />

                            </template>

                        </div>

                        {{-- Content --}}
                        <div>

                            <p
                                class="font-semibold capitalize"
                                x-text="type">
                            </p>

                            <p
                                class="text-sm text-white/90"
                                x-text="message">
                            </p>

                        </div>

                    </div>

                </template>

            </div>
        </div>

    </div>


    @livewireScripts

</body>

</html>
