<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OfficeHourController;
use App\Http\Controllers\TaBioController;
use Illuminate\Support\Facades\Route;
 
Route::get('/office-hours', [OfficeHourController::class, 'index']);
Route::post('/office-hours', [OfficeHourController::class, 'store']);
Route::put('/office-hours/{officeHour}', [OfficeHourController::class, 'update']);
Route::delete('/office-hours/{officeHour}', [OfficeHourController::class, 'destroy']);
Route::post('/office-hours/{officeHour}/join', [OfficeHourController::class, 'join']);
Route::delete('/office-hours/{officeHour}/join', [OfficeHourController::class, 'unjoin']);
Route::get('/analytics/office-hours', [OfficeHourController::class, 'analytics']);

Route::get('/announcements', [AnnouncementController::class, 'index']);
Route::post('/announcements', [AnnouncementController::class, 'store']);

Route::get('/appointments', [AppointmentController::class, 'index']);
Route::post('/appointments', [AppointmentController::class, 'store']);
Route::put('/appointments/{appointment}', [AppointmentController::class, 'update']);
Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy']);

Route::get('/ta-bios', [TaBioController::class, 'index']);
Route::post('/ta-bios', [TaBioController::class, 'store']);
Route::put('/ta-bios/{taBio}', [TaBioController::class, 'update']);
Route::delete('/ta-bios/{taBio}', [TaBioController::class, 'destroy']);

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
