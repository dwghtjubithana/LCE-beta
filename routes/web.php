<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('user.dashboard', ['active' => 'dashboard']);
});

Route::get('/dashboard', function () {
    return view('user.dashboard', ['active' => 'dashboard']);
});

Route::get('/documents', function () {
    return view('user.documents', ['active' => 'documents']);
});

Route::get('/profile', function () {
    return view('user.profile', ['active' => 'profile']);
});

Route::get('/digital-id', function () {
    return view('user.digital-id', ['active' => 'digital-id']);
});

Route::get('/upgrade', function () {
    return view('user.upgrade', ['active' => 'upgrade']);
});

Route::get('/tenders', function () {
    return view('tenders.public');
});

Route::get('/tenders/{id}', function ($id) {
    return view('tenders.show', ['id' => $id]);
});

Route::get('/p/{slug}', function ($slug) {
    return view('public.profile', ['slug' => $slug]);
});

Route::get('/admin/login', function () {
    return view('admin.login');
});

Route::middleware('admin.basic')->group(function () {
    Route::get('/admin', function () {
        return view('admin.dashboard', ['active' => 'dashboard']);
    });
    Route::get('/admin/users', function () {
        return view('admin.users.index', ['active' => 'users']);
    });
    Route::get('/admin/users/new', function () {
        return view('admin.users.create', ['active' => 'users']);
    });
    Route::get('/admin/users/{id}', function ($id) {
        return view('admin.users.show', ['id' => $id, 'active' => 'users']);
    });
    Route::get('/admin/users/{id}/edit', function ($id) {
        return view('admin.users.edit', ['id' => $id, 'active' => 'users']);
    });

    Route::get('/admin/companies', function () {
        return view('admin.companies.index', ['active' => 'companies']);
    });
    Route::get('/admin/companies/new', function () {
        return view('admin.companies.create', ['active' => 'companies']);
    });
    Route::get('/admin/companies/{id}', function ($id) {
        return view('admin.companies.show', ['id' => $id, 'active' => 'companies']);
    });

    Route::get('/admin/documents', function () {
        return view('admin.documents.index', ['active' => 'documents']);
    });
    Route::get('/admin/documents/{id}', function ($id) {
        return view('admin.documents.show', ['id' => $id, 'active' => 'documents']);
    });

    Route::get('/admin/tenders', function () {
        return view('admin.tenders.index', ['active' => 'tenders']);
    });
    Route::get('/admin/tenders/new', function () {
        return view('admin.tenders.create', ['active' => 'tenders']);
    });
    Route::get('/admin/tenders/{id}', function ($id) {
        return view('admin.tenders.show', ['id' => $id, 'active' => 'tenders']);
    });

    Route::get('/admin/compliance-rules', function () {
        return view('admin.rules.index', ['active' => 'rules']);
    });
    Route::get('/admin/compliance-rules/new', function () {
        return view('admin.rules.create', ['active' => 'rules']);
    });
    Route::get('/admin/compliance-rules/{id}', function ($id) {
        return view('admin.rules.show', ['id' => $id, 'active' => 'rules']);
    });
    Route::get('/admin/compliance-rules/{id}/edit', function ($id) {
        return view('admin.rules.edit', ['id' => $id, 'active' => 'rules']);
    });
    Route::get('/admin/notifications', function () {
        return view('admin.notifications.index', ['active' => 'notifications']);
    });
    Route::get('/admin/notifications/{id}', function ($id) {
        return view('admin.notifications.show', ['id' => $id, 'active' => 'notifications']);
    });
    Route::get('/admin/payment-proofs', function () {
        return view('admin.payment-proofs.index', ['active' => 'payment-proofs']);
    });
    Route::get('/admin/audit-logs', function () {
        return view('admin.logs.index', ['active' => 'logs']);
    });
    Route::get('/admin/system', function () {
        return view('admin.system.index', ['active' => 'system']);
    });
});
