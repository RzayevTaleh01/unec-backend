<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Profile\ApiKeyController;
use App\Http\Controllers\Profile\NotificationSettingsController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Profile\RoleController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\Submission\FileDownloadController;
use App\Http\Controllers\Submission\MySubmissionsController;
use App\Http\Controllers\Submission\ReviewController;
use App\Http\Controllers\Submission\SubmissionWizardController;
use Illuminate\Support\Facades\Route;

// Public site
Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('/lang/{locale}', [SiteController::class, 'locale'])->name('lang');

Route::get('/archive', [ArchiveController::class, 'index'])->name('archive.index');
Route::get('/archive/{issue}', [ArchiveController::class, 'show'])->name('archive.show')->whereNumber('issue');
Route::get('/articles/{article}', [ArticleController::class, 'show'])->name('articles.show')->whereNumber('article');
Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/announcements/{announcement:slug}', [AnnouncementController::class, 'show'])->name('announcements.show');
Route::get('/search', SearchController::class)->name('search');
Route::get('/editorial-board', [SiteController::class, 'editorial'])->name('editorial');
Route::get('/contact', [SiteController::class, 'contact'])->name('contact');

Route::get('/privacy', fn () => app(SiteController::class)->standalone('privacy'))->name('privacy');
Route::get('/terms', fn () => app(SiteController::class)->standalone('terms'))->name('terms');
Route::get('/open-journal-systems', fn () => app(SiteController::class)->standalone('open-journal-systems'))->name('systems');

// Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/register', fn () => redirect()->route('register.personal'))->name('register');
    Route::get('/register/personal', [RegisterController::class, 'personal'])->name('register.personal');
    Route::post('/register/personal', [RegisterController::class, 'storePersonal'])->name('register.personal.store');
    Route::get('/register/account', [RegisterController::class, 'account'])->name('register.account');
    Route::post('/register/account', [RegisterController::class, 'storeAccount'])->middleware('throttle:10,1')->name('register.account.store');
    Route::get('/register/success', [RegisterController::class, 'success'])->name('register.success');

    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/forgot-password/sent', [PasswordResetController::class, 'sent'])->name('password.sent');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// Profile: one page per former tab
Route::middleware('auth')->prefix('profile')->name('profile.')->group(function () {
    Route::redirect('/', '/profile/identity');

    Route::get('/identity', [ProfileController::class, 'identity'])->name('identity');
    Route::put('/identity', [ProfileController::class, 'updateIdentity'])->name('identity.update');

    Route::get('/contact', [ProfileController::class, 'contact'])->name('contact');
    Route::put('/contact', [ProfileController::class, 'updateContact'])->name('contact.update');

    Route::get('/roles', [RoleController::class, 'edit'])->name('roles');
    Route::put('/roles', [RoleController::class, 'update'])->name('roles.update');
    Route::get('/roles/journals', [RoleController::class, 'journals'])->name('roles.journals');
    Route::put('/roles/journals', [RoleController::class, 'updateJournals'])->name('roles.journals.update');

    Route::get('/public', [ProfileController::class, 'publicProfile'])->name('public');
    Route::put('/public', [ProfileController::class, 'updatePublic'])->name('public.update');

    Route::get('/password', [ProfileController::class, 'password'])->name('password');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');

    Route::get('/notifications', [NotificationSettingsController::class, 'edit'])->name('notifications');
    Route::put('/notifications', [NotificationSettingsController::class, 'update'])->name('notifications.update');

    Route::get('/api-key', [ApiKeyController::class, 'show'])->name('api-key');
    Route::post('/api-key', [ApiKeyController::class, 'store'])->name('api-key.store');
    Route::delete('/api-key', [ApiKeyController::class, 'destroy'])->name('api-key.destroy');

    Route::get('/submissions', [MySubmissionsController::class, 'index'])->name('submissions');
    Route::get('/submissions/{article}', [MySubmissionsController::class, 'show'])->name('submissions.show')->whereNumber('article');
    Route::post('/submissions/{article}/revision', [MySubmissionsController::class, 'storeRevision'])->name('submissions.revision')->whereNumber('article');

    // Reviewing is a staff role: it is granted by an administrator and never self-selected.
    Route::middleware('role:reviewer')->group(function () {
        Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews');
        Route::get('/reviews/{review}', [ReviewController::class, 'show'])->name('reviews.show')->whereNumber('review');
        Route::post('/reviews/{review}/respond', [ReviewController::class, 'respond'])->name('reviews.respond')->whereNumber('review');
        Route::post('/reviews/{review}/submit', [ReviewController::class, 'submit'])->name('reviews.submit')->whereNumber('review');
    });

    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox');
    Route::post('/inbox/read-all', [InboxController::class, 'readAll'])->name('inbox.read-all');
});

// Submission wizard: one page, steps are switched with JavaScript
Route::middleware('auth')->prefix('submit')->name('submit.')->group(function () {
    Route::get('/', [SubmissionWizardController::class, 'start'])->name('start');
    Route::post('/', [SubmissionWizardController::class, 'store'])->name('create');

    Route::prefix('{article}')->whereNumber('article')->group(function () {
        Route::get('/', [SubmissionWizardController::class, 'edit'])->name('edit');
        Route::put('/', [SubmissionWizardController::class, 'update'])->name('update');
        Route::delete('/', [SubmissionWizardController::class, 'destroy'])->name('destroy');
        // Former per-step pages
        Route::get('/{step}', [SubmissionWizardController::class, 'legacyStep'])->where('step', 'upload|metadata|contributors|review');
    });
});

Route::get('/files/{file}', FileDownloadController::class)->middleware('auth')->name('files.download')->whereNumber('file');

// Every former tab is its own page, managed from the admin panel. Registered last so
// fixed routes (and reserved section keys, see PageSectionResource) always win.
Route::prefix('{section}')->group(function () {
    Route::get('/', [PageController::class, 'first'])->name('pages.first');
    Route::get('/{slug}', [PageController::class, 'show'])->name('pages.show');
});
