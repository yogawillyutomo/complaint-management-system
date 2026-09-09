<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Complaint\ComplaintIndex;
use App\Livewire\Complaint\ComplaintShow;
use App\Livewire\User\UserIndex;
use App\Livewire\Report\ManagementReport;
use App\Livewire\ActionCenter\ActionCenter;
use App\Http\Middleware\EnsureUserIsActive;

// Route::view('/', 'welcome');

Route::get('/', function () {

    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});


Route::get('/dashboard', App\Livewire\Dashboard::class)
    ->middleware(['auth', EnsureUserIsActive::class, 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth', EnsureUserIsActive::class])
    ->name('profile');

Route::middleware(['auth', EnsureUserIsActive::class, 'verified'])->group(function () {

    Route::get('/complaints', ComplaintIndex::class)
        ->name('complaints.index');

    Route::get('/complaints/{complaint:ticket_number}', ComplaintShow::class)
        ->name('complaints.show');

    Route::get('/reports', ManagementReport::class)
        ->name('reports.index');

    Route::get('/action-center', ActionCenter::class)
        ->name('action-center.index');
});

Route::get('/users', UserIndex::class)
    ->middleware(['auth', EnsureUserIsActive::class])
    ->name('users.index');

require __DIR__ . '/auth.php';
