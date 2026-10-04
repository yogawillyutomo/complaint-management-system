<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Complaint System | PT Telkom</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="font-sans text-slate2-900 antialiased">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-brand-50/60">
            <div class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
                <div class="w-full max-w-md">
                    <div class="mb-6 flex items-center justify-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-600 text-lg font-bold text-white shadow-sm shadow-brand-200">
                            C
                        </div>

                        <div>
                            <p class="font-bold leading-tight text-slate2-900">
                                Complaint System
                            </p>

                            <p class="mt-1 text-xs font-medium text-slate2-600">
                                PT Telkom
                            </p>
                        </div>
                    </div>

                    <main class="overflow-hidden rounded-3xl border border-surface-200 bg-white p-6 shadow-[0_18px_50px_rgba(15,23,42,0.08)] sm:p-8">
                        {{ $slot }}
                    </main>

                    <p class="mt-6 text-center text-xs text-slate2-600">
                        Internal Employee Complaint Management
                    </p>
                </div>
            </div>
        </div>
    </body>
</html>
