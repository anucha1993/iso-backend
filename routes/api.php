<?php

use App\Http\Controllers\Api\AnalysisController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChecklistController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FormRegisterController;
use App\Http\Controllers\Api\FormTemplateController;
use App\Http\Controllers\Api\MaintenanceRecordController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RecordAttachmentController;
use App\Http\Controllers\Api\RecordPdfController;
use App\Http\Controllers\Api\ServerController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// ---- Public ----
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

// ---- Authenticated ----
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard & form register (module-agnostic)
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/form-register', [FormRegisterController::class, 'index']);

    // Profile signature (stored in user profile)
    Route::post('/profile/signature', [ProfileController::class, 'updateSignature']);
    Route::delete('/profile/signature', [ProfileController::class, 'deleteSignature']);

    // Master data (read)
    Route::get('/servers', [ServerController::class, 'index']);
    Route::get('/checklist-items', [ChecklistController::class, 'items']);
    Route::get('/checklist-standards', [ChecklistController::class, 'standards']);

    // Maintenance records (read)
    Route::get('/maintenance-records', [MaintenanceRecordController::class, 'index']);
    Route::get('/maintenance-records/{record}', [MaintenanceRecordController::class, 'show']);
    Route::get('/maintenance-records/{record}/analysis', [AnalysisController::class, 'record']);
    Route::get('/maintenance-records/{record}/pdf', [RecordPdfController::class, 'show']);
    Route::get('/analysis/summary', [AnalysisController::class, 'summary']);

    // Maintenance records (write / workflow)
    Route::post('/maintenance-records', [MaintenanceRecordController::class, 'store'])
        ->middleware('permission:records.create');
    Route::put('/maintenance-records/{record}', [MaintenanceRecordController::class, 'update'])
        ->middleware('permission:records.update');
    Route::post('/maintenance-records/{record}/rounds/{month}/submit', [MaintenanceRecordController::class, 'submitMonth'])
        ->middleware('permission:records.submit');
    Route::post('/maintenance-records/{record}/rounds/{month}/approve', [MaintenanceRecordController::class, 'approveMonth'])
        ->middleware('permission:records.approve');
    Route::post('/maintenance-records/{record}/rounds/{month}/reject', [MaintenanceRecordController::class, 'rejectMonth'])
        ->middleware('permission:records.approve');

    // Evidence attachments (images / PDF)
    Route::post('/maintenance-records/{record}/attachments', [RecordAttachmentController::class, 'store'])
        ->middleware('permission:records.update');
    Route::delete('/maintenance-records/{record}/attachments/{attachment}', [RecordAttachmentController::class, 'destroy'])
        ->middleware('permission:records.update');

    // Admin: server master data
    Route::post('/servers', [ServerController::class, 'store'])->middleware('permission:servers.manage');
    Route::put('/servers/{server}', [ServerController::class, 'update'])->middleware('permission:servers.manage');

    // Admin: users & roles
    Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.manage');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.manage');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:users.manage');

    // Admin: form register (document control)
    Route::post('/form-templates', [FormTemplateController::class, 'store'])->middleware('permission:users.manage');
    Route::put('/form-templates/{formTemplate}', [FormTemplateController::class, 'update'])->middleware('permission:users.manage');

    // Admin: audit trail
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware('permission:audit.view');
});
