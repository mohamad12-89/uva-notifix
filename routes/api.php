<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InstructorRoleRegistryController;
use App\Http\Controllers\OfficeHourController;
use App\Http\Controllers\TaBioController;
use Illuminate\Support\Facades\Route;
 
Route::middleware('cognito.auth')->group(function () {
    Route::get('/instructor/role-registry', [InstructorRoleRegistryController::class, 'index']);
    Route::post('/instructor/sync-role-registry', [InstructorRoleRegistryController::class, 'sync'])
        ->middleware('role:professor');

    Route::get('/office-hours', [OfficeHourController::class, 'index']);
    Route::post('/office-hours', [OfficeHourController::class, 'store'])->middleware('role:ta,professor');
    Route::put('/office-hours/{officeHour}', [OfficeHourController::class, 'update'])->middleware('role:ta,professor');
    Route::delete('/office-hours/{officeHour}', [OfficeHourController::class, 'destroy'])->middleware('role:ta,professor');
    Route::post('/office-hours/{officeHour}/join', [OfficeHourController::class, 'join']);
    Route::delete('/office-hours/{officeHour}/join', [OfficeHourController::class, 'unjoin']);
    Route::get('/office-hours/{officeHour}/signups', [OfficeHourController::class, 'signups'])->middleware('role:ta,professor');
    Route::post('/office-hours/{officeHour}/signups/{signup}/check-in', [OfficeHourController::class, 'checkIn'])->middleware('role:ta,professor');
    Route::get('/analytics/office-hours', [OfficeHourController::class, 'analytics'])->middleware('role:professor');
    Route::get('/analytics/join-times', [OfficeHourController::class, 'joinTimesAnalytics'])->middleware('role:ta,professor');

    Route::get('/announcements', [AnnouncementController::class, 'index']);
    Route::post('/announcements', [AnnouncementController::class, 'store'])->middleware('role:professor');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->middleware('role:professor');

    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update']);
    Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy']);

    Route::get('/ta-bios', [TaBioController::class, 'index']);
    Route::post('/ta-bios', [TaBioController::class, 'store'])->middleware('role:ta,professor');
    Route::put('/ta-bios/{taBio}', [TaBioController::class, 'update'])->middleware('role:ta,professor');
    Route::delete('/ta-bios/{taBio}', [TaBioController::class, 'destroy'])->middleware('role:ta,professor');
});

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/verify', [AuthController::class, 'verify']);
