<?php

use Illuminate\Support\Facades\Route;
use Nyumbani\Essentials\Http\Controllers\LeaveImpersonationController;

//  End an impersonation, which only signed-in users can reach
Route::post('essentials/impersonation/leave', LeaveImpersonationController::class)
    ->middleware(['web', 'auth'])
    ->name('essentials.impersonation.leave');
