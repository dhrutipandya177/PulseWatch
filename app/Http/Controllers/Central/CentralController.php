<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;

class CentralController extends Controller
{
    public static function routes(): void
    {
        Route::middleware(['web'])->group(function () {
            // Landing
            Route::get('/', [LandingController::class, 'index'])->name('landing');
            Route::get('/features', [LandingController::class, 'features'])->name('features');
            Route::get('/pricing', [LandingController::class, 'pricing'])->name('pricing');

            // Auth
            Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
            Route::post('/login', [AuthController::class, 'login']);
            Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
            Route::post('/register', [AuthController::class, 'register']);
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

            // Authenticated
            Route::middleware(['auth'])->group(function () {
                Route::get('/dashboard', [DashboardController::class, 'index'])->name('central.dashboard');
                Route::get('/profile/edit', [DashboardController::class, 'editProfile'])->name('profile.edit');
                Route::put('/profile', [DashboardController::class, 'updateProfile'])->name('profile.update');

                // Billing
                Route::get('/billing/select-plan', [BillingController::class, 'selectPlan'])->name('billing.select-plan');
                Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
                Route::get('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
                Route::get('/billing/success', [BillingController::class, 'success'])->name('billing.success');
                Route::get('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
            });

            // Stripe webhook (outside auth)
            Route::post('/billing/webhook', [BillingController::class, 'webhook'])->name('billing.webhook');
        });
    }
}
