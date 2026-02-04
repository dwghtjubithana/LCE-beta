<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminHealthController;
use App\Http\Controllers\AdminMetricsController;
use App\Http\Controllers\AdminGeminiController;
use App\Http\Controllers\AdminAiSettingsController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminCompanyController;
use App\Http\Controllers\AdminTenderController;
use App\Http\Controllers\AdminDocumentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\AdminPaymentProofController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ComplianceRuleController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PaymentProofController;
use App\Http\Controllers\TenderController;
use App\Http\Controllers\UserNotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::middleware('throttle:10,1')->post('register', [AuthController::class, 'register']);
    Route::middleware('throttle:10,1')->post('login', [AuthController::class, 'login']);

    Route::middleware('jwt')->get('me', [AuthController::class, 'me']);
    Route::middleware('jwt')->post('logout', [AuthController::class, 'logout']);
});

// Public company profile
Route::get('public/companies/{slug}', [CompanyController::class, 'publicProfile']);
Route::get('public/companies/{slug}/photo', [CompanyController::class, 'publicPhoto']);

// Public tender feed (no auth)
Route::get('public/tenders', [TenderController::class, 'indexPublic']);
Route::get('public/tenders/{id}', [TenderController::class, 'showPublic']);

Route::middleware('jwt')->group(function () {
    // User-facing routes
    Route::post('companies', [CompanyController::class, 'store']);
    Route::get('companies/me', [CompanyController::class, 'me']);
    Route::get('companies/me/dashboard', [CompanyController::class, 'dashboardMe']);
    Route::get('companies/me/profile.pdf', [CompanyController::class, 'profilePdfMe']);
    Route::post('companies/me/profile-photo', [CompanyController::class, 'uploadProfilePhoto']);
    Route::get('companies/slug-check', [CompanyController::class, 'slugCheck']);
    Route::get('companies/me/documents', [DocumentController::class, 'listMine']);
    Route::get('companies/{id}', [CompanyController::class, 'show'])->where('id', '[0-9]+');
    Route::patch('companies/{id}', [CompanyController::class, 'update'])->where('id', '[0-9]+');
    Route::get('companies/{id}/dashboard', [CompanyController::class, 'dashboard'])->where('id', '[0-9]+');
    Route::get('companies/{id}/profile.pdf', [CompanyController::class, 'profilePdf'])->where('id', '[0-9]+');
    Route::get('companies/me/profile-photo', [CompanyController::class, 'profilePhotoMe']);
    Route::post('geocode', [CompanyController::class, 'geocode']);

    Route::post('documents/upload', [DocumentController::class, 'upload']);
    Route::post('documents/upload/bulk', [DocumentController::class, 'uploadBulk']);
    Route::get('documents/{id}', [DocumentController::class, 'show']);
    Route::get('companies/{id}/documents', [DocumentController::class, 'listByCompany']);
    Route::get('documents/{id}/summary', [DocumentController::class, 'downloadSummary']);
    Route::post('documents/{id}/reprocess', [DocumentController::class, 'reprocess']);
    Route::post('documents/{id}/confirm', [DocumentController::class, 'confirm']);
    Route::delete('documents/{id}', [DocumentController::class, 'destroy']);

    Route::post('payment-proofs', [PaymentProofController::class, 'store']);
    Route::get('payment-proofs/latest', [PaymentProofController::class, 'latest']);
    Route::get('payment-proofs/latest/file', [PaymentProofController::class, 'latestFile']);
    Route::get('notifications', [UserNotificationController::class, 'index']);
    Route::get('gemini/health', [AdminGeminiController::class, 'health']);

    Route::get('tenders', [TenderController::class, 'index']);
    Route::get('tenders/mine', [TenderController::class, 'listMine']);
    Route::get('tenders/{id}/attachments/{index}', [TenderController::class, 'downloadAttachment']);
    Route::get('tenders/{id}', [TenderController::class, 'show']);
    Route::post('tenders', [TenderController::class, 'store']);

    // Admin-only routes
    Route::middleware('admin.role')->group(function () {
        // (moved to user-facing routes)

        Route::get('admin/audit-logs', [AuditLogController::class, 'index']);
        Route::get('admin/companies', [AdminCompanyController::class, 'index']);
        Route::get('admin/companies/{id}', [AdminCompanyController::class, 'show']);
        Route::post('admin/companies', [AdminCompanyController::class, 'store']);
        Route::get('admin/users', [AdminUserController::class, 'index']);
        Route::get('admin/users/{id}', [AdminUserController::class, 'show']);
        Route::patch('admin/users/{id}', [AdminUserController::class, 'update']);
        Route::post('admin/users', [AdminUserController::class, 'store']);
        Route::get('admin/documents', [AdminDocumentController::class, 'index']);
        Route::get('admin/documents/{id}', [AdminDocumentController::class, 'show']);
        Route::get('admin/documents/{id}/file/{side?}', [AdminDocumentController::class, 'downloadFile']);
        Route::post('admin/documents/{id}/approve', [AdminDocumentController::class, 'approve']);
        Route::post('admin/documents/{id}/reject', [AdminDocumentController::class, 'reject']);
        Route::get('admin/compliance-rules', [ComplianceRuleController::class, 'index']);
        Route::get('admin/compliance-rules/{id}', [ComplianceRuleController::class, 'show']);
        Route::post('admin/compliance-rules', [ComplianceRuleController::class, 'store']);
        Route::patch('admin/compliance-rules/{id}', [ComplianceRuleController::class, 'update']);
        Route::delete('admin/compliance-rules/{id}', [ComplianceRuleController::class, 'destroy']);
        Route::get('admin/tenders', [AdminTenderController::class, 'index']);
        Route::get('admin/tenders/{id}', [AdminTenderController::class, 'show']);
        Route::get('admin/tenders/{id}/attachments/{index}', [AdminTenderController::class, 'downloadAttachment']);
        Route::post('admin/tenders', [AdminTenderController::class, 'store']);
        Route::patch('admin/tenders/{id}', [AdminTenderController::class, 'update']);
        Route::delete('admin/tenders/{id}', [AdminTenderController::class, 'destroy']);
        Route::post('admin/tenders/{id}/approve', [AdminTenderController::class, 'approve']);
        Route::post('admin/tenders/{id}/reject', [AdminTenderController::class, 'reject']);
        Route::get('admin/notifications', [AdminNotificationController::class, 'index']);
        Route::get('admin/notifications/{id}', [AdminNotificationController::class, 'show']);
        Route::post('admin/notifications/{id}/resend', [AdminNotificationController::class, 'resend']);
        Route::post('admin/notifications/{id}/mark-sent', [AdminNotificationController::class, 'markSent']);
        Route::post('admin/notifications/mark-sent', [AdminNotificationController::class, 'markBulk']);
        Route::get('admin/health', [AdminHealthController::class, 'show']);
        Route::get('admin/metrics', [AdminMetricsController::class, 'index']);
        Route::get('admin/gemini/health', [AdminGeminiController::class, 'health']);
        Route::get('admin/ai-settings', [AdminAiSettingsController::class, 'show']);
        Route::put('admin/ai-settings', [AdminAiSettingsController::class, 'update']);
        Route::get('admin/payment-proofs', [AdminPaymentProofController::class, 'index']);
        Route::post('admin/payment-proofs/{id}/approve', [AdminPaymentProofController::class, 'approve']);
        Route::post('admin/payment-proofs/{id}/reject', [AdminPaymentProofController::class, 'reject']);
    });
});
