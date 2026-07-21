<?php

use App\Http\Controllers\Api\AnalysisController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChecklistController;
use App\Http\Controllers\Api\ClientMachineController;
use App\Http\Controllers\Api\ClientMaPdfController;
use App\Http\Controllers\Api\ClientMaRecordController;
use App\Http\Controllers\Api\CorrectiveActionController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FormRegisterController;
use App\Http\Controllers\Api\FormTemplateController;
use App\Http\Controllers\Api\StandardProfileController;
use App\Http\Controllers\Api\MaintenanceRecordController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RecordAttachmentController;
use App\Http\Controllers\Api\RecordPdfController;
use App\Http\Controllers\Api\ServerController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// ---- Public ----
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::get('/document-years', [DashboardController::class, 'documentYears'])->middleware('throttle:60,1');

// ---- Authenticated ----
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard & form register (module-agnostic)
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/my-tasks', [DashboardController::class, 'myTasks']);
    Route::get('/form-register', [FormRegisterController::class, 'index']);

    // In-app notifications (header bell / MyJob)
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);

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
    Route::delete('/maintenance-records/{record}', [MaintenanceRecordController::class, 'destroy'])
        ->middleware('permission:records.delete');
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

    // Client machines master (FM-IT-03)
    Route::get('/client-machines', [ClientMachineController::class, 'index']);
    Route::get('/client-machines/{machine}/history', [ClientMachineController::class, 'history']);
    Route::post('/client-machines', [ClientMachineController::class, 'store'])->middleware('permission:servers.manage');
    Route::put('/client-machines/{machine}', [ClientMachineController::class, 'update'])->middleware('permission:servers.manage');
    Route::delete('/client-machines/{machine}', [ClientMachineController::class, 'destroy'])->middleware('permission:servers.manage');
    Route::post('/client-machines/import', [ClientMachineController::class, 'import'])->middleware('permission:servers.manage');
    Route::get('/client-machine-imports', [ClientMachineController::class, 'imports'])->middleware('permission:servers.manage');
    Route::get('/client-machine-imports/{import}', [ClientMachineController::class, 'importShow'])->middleware('permission:servers.manage');

    // Client maintenance records (FM-IT-03)
    Route::get('/client-ma', [ClientMaRecordController::class, 'index']);
    Route::get('/client-ma/{record}', [ClientMaRecordController::class, 'show']);
    Route::post('/client-ma', [ClientMaRecordController::class, 'store'])->middleware('permission:records.create');
    Route::put('/client-ma/{record}', [ClientMaRecordController::class, 'update'])->middleware('permission:records.update');
    Route::post('/client-ma/{record}/submit', [ClientMaRecordController::class, 'submit'])->middleware('permission:records.submit');
    Route::post('/client-ma/{record}/approve', [ClientMaRecordController::class, 'approve'])->middleware('permission:records.approve');
    Route::post('/client-ma/{record}/reject', [ClientMaRecordController::class, 'reject'])->middleware('permission:records.approve');
    Route::post('/client-ma/{record}/report', [ClientMaRecordController::class, 'uploadReport'])->middleware('permission:records.update');
    Route::delete('/client-ma/{record}/report', [ClientMaRecordController::class, 'deleteReport'])->middleware('permission:records.update');
    Route::delete('/client-ma/{record}', [ClientMaRecordController::class, 'destroy'])->middleware('permission:records.delete');
    Route::get('/client-ma/{record}/pdf', [ClientMaPdfController::class, 'show']);

    // General corrective-action / remark log (attaches to any form record)
    Route::get('/corrective-actions', [CorrectiveActionController::class, 'index']);
    Route::post('/corrective-actions', [CorrectiveActionController::class, 'store'])->middleware('permission:records.update');
    Route::put('/corrective-actions/{correctiveAction}', [CorrectiveActionController::class, 'update'])->middleware('permission:records.update');
    Route::delete('/corrective-actions/{correctiveAction}', [CorrectiveActionController::class, 'destroy'])->middleware('permission:records.update');

    // Admin: server master data
    Route::post('/servers', [ServerController::class, 'store'])->middleware('permission:servers.manage');
    Route::put('/servers/{server}', [ServerController::class, 'update'])->middleware('permission:servers.manage');

    // Admin: checklist master data (FM-IT-02 items)
    Route::post('/checklist-items', [ChecklistController::class, 'storeItem'])->middleware('permission:users.manage');
    Route::put('/checklist-items/{item}', [ChecklistController::class, 'updateItem'])->middleware('permission:users.manage');
    Route::delete('/checklist-items/{item}', [ChecklistController::class, 'destroyItem'])->middleware('permission:users.manage');

    // Admin: threshold standards (per-form or global default)
    Route::post('/checklist-standards', [ChecklistController::class, 'storeStandard'])->middleware('permission:users.manage');
    Route::put('/checklist-standards/{standard}', [ChecklistController::class, 'updateStandard'])->middleware('permission:users.manage');
    Route::delete('/checklist-standards/{standard}', [ChecklistController::class, 'destroyStandard'])->middleware('permission:users.manage');

    // Admin: standard profiles (reusable metric sets: Server/PC, UPS, Network ฯลฯ)
    Route::get('/standard-profiles', [StandardProfileController::class, 'index']);
    Route::get('/standard-profiles/{profile}', [StandardProfileController::class, 'show']);
    Route::post('/standard-profiles', [StandardProfileController::class, 'store'])->middleware('permission:users.manage');
    Route::put('/standard-profiles/{profile}', [StandardProfileController::class, 'update'])->middleware('permission:users.manage');
    Route::delete('/standard-profiles/{profile}', [StandardProfileController::class, 'destroy'])->middleware('permission:users.manage');

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
