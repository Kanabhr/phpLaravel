<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes(['register' => false]);
Route::get('admin', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('admin.login');

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth'])
    ->group(function () {
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        // Profile Routes
        Route::get('/profile', [App\Http\Controllers\Admin\ProfileController::class, 'index'])->name('profile.index');
        Route::post('/profile/change-password', [App\Http\Controllers\Admin\ProfileController::class, 'changePassword'])->name('profile.change-password');
        
        // Testimonials
        Route::resource('testimonials', App\Http\Controllers\Admin\TestimonialController::class)->except(['create', 'edit']);

        // Contact Leads
        Route::resource('contact-leads', App\Http\Controllers\Admin\ContactLeadController::class)->only(['index', 'show', 'update', 'destroy']);

        // Resources Routes
        Route::resource('resources', App\Http\Controllers\Admin\ResourceController::class);

        // Blog CMS Routes
        Route::resource('blogs', App\Http\Controllers\Admin\BlogController::class);
        Route::resource('categories', App\Http\Controllers\Admin\CategoryController::class)->except(['create', 'edit', 'show']);
        Route::resource('tags', App\Http\Controllers\Admin\TagController::class)->except(['create', 'edit', 'show']);
        
        // Media Library
        Route::resource('media', App\Http\Controllers\Admin\MediaController::class)->except(['create', 'edit', 'show'])->parameters(['media' => 'media']);

        // Site Settings
        Route::get('/settings', [App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings/header', [App\Http\Controllers\Admin\SettingController::class, 'saveHeader'])->name('settings.header');
        Route::post('/settings/footer', [App\Http\Controllers\Admin\SettingController::class, 'saveFooter'])->name('settings.footer');

        // SEO Settings
        Route::get('/seo-settings', [App\Http\Controllers\Admin\SeoSettingController::class, 'index'])->name('seo-settings.index');
        Route::post('/seo-settings/save', [App\Http\Controllers\Admin\SeoSettingController::class, 'save'])->name('seo-settings.save');
    });
