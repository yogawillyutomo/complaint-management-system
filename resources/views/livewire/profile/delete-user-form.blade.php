<?php

use Livewire\Volt\Component;

new class extends Component
{
    //
}; ?>

<section class="space-y-4">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Account Lifecycle') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Self-service account deletion is disabled because complaint records, discussions, and audit history must be retained. Contact an administrator if your account should be deactivated.') }}
        </p>
    </header>

    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        {{ __('Deactivation blocks access without deleting historical organizational records.') }}
    </div>
</section>
