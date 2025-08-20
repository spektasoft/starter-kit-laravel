<?php

use App\Livewire\Page\ViewPage;
use Illuminate\Support\Facades\Route;

Route::get('/pages/{record}', ViewPage::class)
    ->name('pages.show');
