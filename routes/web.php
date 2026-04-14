<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BioburdenController;
use App\Http\Controllers\BioburdenUploadController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\CheckProgramAccess;
use Illuminate\Support\Facades\Route;

// Landing page — redirect straight to login
Route::get('/', function () {
    return redirect()->route('login');
})->name('welcome');

// Locale switch (works before and after login)
Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'ms'])) {
        session(['locale' => $locale]);
    }
    return redirect()->back();
})->name('lang.switch');

// Auth routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware(['auth', CheckProgramAccess::class])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/{prodline}', [DashboardController::class, 'detail'])->name('dashboard.detail');

    // Home / program menu
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // Bioburden trending
    Route::get('/bioburden', [BioburdenController::class, 'index'])->name('bioburden.index');
    Route::post('/bioburden/store', [BioburdenController::class, 'store'])->name('bioburden.store');
    Route::post('/bioburden/remove', [BioburdenController::class, 'remove'])->name('bioburden.remove');

    // Upload result data (original — CSV format)
    Route::get('/upload', [BioburdenController::class, 'uploadForm'])->name('upload.form');
    Route::post('/upload', [BioburdenController::class, 'uploadFile'])->name('upload.file');

    // Smart upload — accepts original monitoring Excel files as-is
    Route::get('/smart-upload', [BioburdenUploadController::class, 'showForm'])->name('bioburden.smart-upload');
    Route::post('/smart-upload/preview', [BioburdenUploadController::class, 'preview'])->name('bioburden.smart-upload.preview');
    Route::post('/smart-upload', [BioburdenUploadController::class, 'upload'])->name('bioburden.smart-upload.post');

    // Monthly remarks
    Route::post('/bioburden/remark', [BioburdenController::class, 'storeRemark'])->name('bioburden.remark.store');
    Route::post('/bioburden/remark/update', [BioburdenController::class, 'updateRemark'])->name('bioburden.remark.update');
});
