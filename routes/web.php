<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Complaint\ComplaintIndex;
use App\Livewire\Complaint\ComplaintShow;
use App\Livewire\User\UserIndex;

// Route::view('/', 'welcome');

Route::get('/', function () {

    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});


Route::get('/dashboard', App\Livewire\Dashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/complaints', ComplaintIndex::class)
        ->name('complaints.index');

    Route::get('/complaints/{complaint:ticket_number}', ComplaintShow::class)
        ->name('complaints.show');
});

Route::get('/users', UserIndex::class)
    ->middleware(['auth'])
    ->name('users.index');

require __DIR__ . '/auth.php';
