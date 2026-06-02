<?php

use Illuminate\Support\Facades\Route;
use Webbestpractice\Posts\Http\Controllers\IndexController;

Route::post('/', [IndexController::class, 'index'])->name('index');
