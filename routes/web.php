<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Core controllers
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\TraceEventController;
use App\Http\Controllers\VietmapController;
use App\Http\Controllers\QrAdminController;
use App\Http\Controllers\QrScanController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BatchRecallController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicController;



// Onboarding
use App\Http\Controllers\OnboardingEnterpriseController;
use App\Http\Controllers\OnboardingEnterpriseStatusController;

// Enterprise admin
use App\Http\Controllers\Enterprise\UserController as EnterpriseUserController;
use App\Http\Controllers\Enterprise\SettingsController as EnterpriseSettingsController;

// Sys admin
use App\Http\Controllers\Sys\EnterpriseApprovalController;

/*
|--------------------------------------------------------------------------
| Public (no auth)
|--------------------------------------------------------------------------
*/
Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/san-pham', [PublicController::class, 'products'])->name('public.products');
Route::get('/categories', [PublicController::class, 'categories'])->name('public.categories'); // redirect -> /san-pham
Route::get('/verify', [PublicController::class, 'verify'])->name('public.verify');

// QR public gates
Route::get('/t/{token}', [QrScanController::class, 'gatePublic'])->name('trace.gate.public');
Route::post('/t/{token}/resolve', [QrScanController::class, 'resolvePublic'])->name('trace.resolve.public');

Route::get('/v/{token}', [QrScanController::class, 'gatePrivate'])->name('trace.gate.private');
Route::post('/v/{token}/resolve', [QrScanController::class, 'resolvePrivate'])->name('trace.resolve.private');

// IPFS verify — public endpoint cho người tiêu dùng
Route::get('/verify/ipfs/{cid}', [TraceEventController::class, 'verifyIpfs'])->name('verify.ipfs');

/*
|--------------------------------------------------------------------------
| Auth-only (NO tenant scope)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Onboarding DN (bắt buộc sau register)
    Route::get('/onboarding/enterprise', [OnboardingEnterpriseController::class, 'create'])
        ->name('onboarding.enterprise.create');
    Route::post('/onboarding/enterprise', [OnboardingEnterpriseController::class, 'store'])
        ->name('onboarding.enterprise.store');
    Route::get('/onboarding/enterprise/status', [OnboardingEnterpriseStatusController::class, 'status'])
        ->name('onboarding.enterprise.status');
    Route::get('/onboarding/enterprise/pending', [OnboardingEnterpriseStatusController::class, 'pending'])
        ->name('onboarding.enterprise.pending');
    Route::get('/onboarding/enterprise/rejected', [OnboardingEnterpriseStatusController::class, 'rejected'])
        ->name('onboarding.enterprise.rejected');
});

/*
|--------------------------------------------------------------------------
| Dashboard (auth + verified + tenant.ready)
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'tenant.ready', 'tenant'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Internal app (auth + verified + tenant.ready + tenant scope)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'tenant.ready', 'tenant'])->group(function () {

    // ── Products ──────────────────────────────────────────
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::post('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    // ── Batches ───────────────────────────────────────────
    Route::get('/batches', [BatchController::class, 'index'])->name('batches.index');
    Route::post('/batches', [BatchController::class, 'store'])->name('batches.store');
    Route::put('/batches/{batch}', [BatchController::class, 'update'])->name('batches.update');
    Route::delete('/batches/{batch}', [BatchController::class, 'destroy'])->name('batches.destroy');

    // Batch recall
    Route::post('/batches/{batch}/recall', [BatchRecallController::class, 'store'])->name('batches.recall.store');
    Route::patch('/batches/{batch}/recall/resolve', [BatchRecallController::class, 'resolve'])->name('batches.recall.resolve');

    // QR admin theo batch
    Route::get('/batches/{batch}/qrs', [QrAdminController::class, 'index'])->name('batches.qrs');
    Route::post('/batches/{batch}/qrs/ensure', [QrAdminController::class, 'ensure'])->name('batches.qrs.ensure');
    Route::post('/qrcodes/{qrcode}/configure-public', [QrAdminController::class, 'configurePublic'])->name('qrcodes.configurePublic');

    // ── Trace Events ──────────────────────────────────────
    Route::get('/events', [TraceEventController::class, 'index'])->name('events.index');
    Route::post('/events', [TraceEventController::class, 'store'])->name('events.store');
    Route::put('/events/{traceEvent}', [TraceEventController::class, 'update'])->name('events.update');
    Route::delete('/events/{traceEvent}', [TraceEventController::class, 'destroy'])->name('events.destroy');
    Route::post('/events/{traceEvent}/publish', [TraceEventController::class, 'publish'])->name('events.publish');

    // Attachment upload lên IPFS
    Route::post('/events/{traceEvent}/attachments', [TraceEventController::class, 'uploadAttachment'])
        ->name('events.attachments.store');

    // ── Vietmap proxy ─────────────────────────────────────
    Route::post('/vietmap/autocomplete', [VietmapController::class, 'autocomplete'])->name('vietmap.autocomplete');
    Route::post('/vietmap/place', [VietmapController::class, 'place'])->name('vietmap.place');

    // ── Enterprise Admin ──────────────────────────────────
    // Quản lý nhân sự (chỉ enterprise_admin)
    Route::get('/enterprise/users', [EnterpriseUserController::class, 'index'])->name('enterprise.users.index');
    Route::post('/enterprise/users', [EnterpriseUserController::class, 'store'])->name('enterprise.users.store');
    Route::put('/enterprise/users/{user}', [EnterpriseUserController::class, 'update'])->name('enterprise.users.update');
    Route::delete('/enterprise/users/{user}', [EnterpriseUserController::class, 'destroy'])->name('enterprise.users.destroy');

    // Cài đặt DN (chỉ enterprise_admin)
    Route::get('/enterprise/settings', [EnterpriseSettingsController::class, 'show'])->name('enterprise.settings.show');
    Route::put('/enterprise/settings', [EnterpriseSettingsController::class, 'update'])->name('enterprise.settings.update');

    // ── Dev ───────────────────────────────────────────────
    Route::get('/dev/vietmap-place-test', function () {
        return Inertia::render('Dev/VietmapPlaceTest');
    })->name('dev.vietmap-place-test');
});

/*
|--------------------------------------------------------------------------
| API (auth + tenant) — trả về JSON
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'tenant.ready', 'tenant'])
    ->prefix('api')
    ->group(function () {
        // CTE templates theo category + completeness check cho batch
        Route::get('/cte-templates', [TraceEventController::class, 'getTemplates'])
            ->name('api.cte-templates');
    });

/*
|--------------------------------------------------------------------------
| System Admin (super admin only)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'super'])
    ->prefix('sys')
    ->group(function () {
        Route::get('/enterprises', [EnterpriseApprovalController::class, 'index'])->name('sys.enterprises.index');
        Route::get('/enterprises/{enterprise}', [EnterpriseApprovalController::class, 'show'])->name('sys.enterprises.show');
        Route::post('/enterprises/{enterprise}/approve', [EnterpriseApprovalController::class, 'approve'])->name('sys.enterprises.approve');
        Route::post('/enterprises/{enterprise}/reject', [EnterpriseApprovalController::class, 'reject'])->name('sys.enterprises.reject');
    });

require __DIR__ . '/auth.php';