<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResponseTemplateController;
use App\Http\Controllers\SystemHealthController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware(['auth', 'engineer'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/system/health', SystemHealthController::class)->name('system.health');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/tickets/bulk-action', [TicketController::class, 'bulkAction'])->name('tickets.bulk-action');
    Route::get('/tickets/import/history', [TicketImportController::class, 'history'])->name('tickets.import.history');
    Route::get('/tickets/import', [TicketImportController::class, 'create'])->name('tickets.import.create');
    Route::post('/tickets/import', [TicketImportController::class, 'store'])->name('tickets.import.store');
    Route::get('/tickets/{ticket}/attachments/{attachment}/download', [TicketController::class, 'downloadAttachment'])->scopeBindings()->name('tickets.attachments.download');
    Route::delete('/tickets/{ticket}/attachments/{attachment}', [TicketController::class, 'destroyAttachment'])->scopeBindings()->name('tickets.attachments.destroy');
    Route::get('/tickets/export/excel', [TicketController::class, 'exportExcel'])->name('tickets.export.excel');
    Route::get('/tickets/export', [TicketController::class, 'export'])->name('tickets.export');
    Route::post('/tickets/{ticket}/archive', [TicketController::class, 'archive'])->name('tickets.archive');
    Route::post('/tickets/{ticket}/restore', [TicketController::class, 'restore'])->name('tickets.restore');
    Route::post('/tickets/{ticket}/claim', [TicketController::class, 'claim'])->name('tickets.claim');
    Route::resource('tickets', TicketController::class);
    Route::get('/response-templates', [ResponseTemplateController::class, 'index'])->name('response-templates.index');
    Route::post('/response-templates', [ResponseTemplateController::class, 'store'])->name('response-templates.store');
    Route::put('/response-templates/{responseTemplate}', [ResponseTemplateController::class, 'update'])->name('response-templates.update');
    Route::delete('/response-templates/{responseTemplate}', [ResponseTemplateController::class, 'destroy'])->name('response-templates.destroy');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
