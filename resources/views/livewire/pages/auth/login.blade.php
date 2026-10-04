<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-8">
        <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700">
            <span class="h-2 w-2 rounded-full bg-brand-500"></span>
            Internal Access
        </div>

        <h1 class="text-2xl font-bold tracking-tight text-slate2-900 sm:text-3xl">
            Complaint Management System
        </h1>

        <p class="mt-2 text-sm leading-6 text-slate2-600">
            Sign in to continue managing employee complaints and follow-up activities.
        </p>
    </div>

    <x-auth-session-status
        class="mb-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
        :status="session('status')"
    />

    <form wire:submit="login" class="space-y-5">
        <div>
            <label for="email" class="block text-sm font-semibold text-slate2-700">
                {{ __('Email') }}
            </label>

            <input
                wire:model="form.email"
                id="email"
                type="email"
                name="email"
                required
                autofocus
                autocomplete="username"
                placeholder="name@company.com"
                class="input mt-2 h-12 px-4 text-sm text-slate2-900 placeholder:text-slate-400 @error('form.email') border-red-300 focus:border-red-500 focus:ring-red-100 @enderror"
            >

            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="block text-sm font-semibold text-slate2-700">
                {{ __('Password') }}
            </label>

            <input
                wire:model="form.password"
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Enter your password"
                class="input mt-2 h-12 px-4 text-sm text-slate2-900 placeholder:text-slate-400 @error('form.password') border-red-300 focus:border-red-500 focus:ring-red-100 @enderror"
            >

            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <label for="remember" class="inline-flex cursor-pointer items-center gap-2.5">
                <input
                    wire:model="form.remember"
                    id="remember"
                    type="checkbox"
                    name="remember"
                    class="rounded border-surface-200 text-brand-600 shadow-sm focus:ring-brand-500"
                >

                <span class="text-sm text-slate2-600">
                    {{ __('Remember me') }}
                </span>
            </label>

            @if (Route::has('password.request'))
                <a
                    class="text-sm font-semibold text-brand-600 transition hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-200 focus:ring-offset-2"
                    href="{{ route('password.request') }}"
                    wire:navigate
                >
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <button
            type="submit"
            class="btn-primary flex w-full items-center justify-center py-3 text-sm font-semibold shadow-sm shadow-brand-200 disabled:cursor-not-allowed disabled:opacity-70"
            wire:loading.attr="disabled"
            wire:target="login"
        >
            <span wire:loading.remove wire:target="login">
                {{ __('Log in') }}
            </span>

            <span wire:loading wire:target="login">
                {{ __('Signing in...') }}
            </span>
        </button>
    </form>

    <div class="mt-7 border-t border-surface-100 pt-5">
        <p class="text-center text-xs leading-5 text-slate2-600">
            Use your registered PT Telkom account to access the internal complaint management workspace.
        </p>
    </div>
</div>
