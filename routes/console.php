<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notifications:prune {--days=90}', function (): int {
    $days = max(1, (int) $this->option('days'));

    $deleted = DB::table('notifications')
        ->whereNotNull('read_at')
        ->where('read_at', '<', now()->subDays($days))
        ->delete();

    $this->info("Pruned {$deleted} read notification(s) older than {$days} day(s).");

    return self::SUCCESS;
})->purpose('Prune old read in-app notifications while retaining unread items');

Schedule::command('notifications:prune --days=90')
    ->dailyAt('02:30')
    ->withoutOverlapping();
