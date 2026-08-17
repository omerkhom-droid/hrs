<?php

use App\Http\Controllers\Tenant\EmployeeDocumentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Employee Documents
|--------------------------------------------------------------------------
|
| يستدعى هذا الملف داخل مجموعة app المحمية بواسطة:
| auth, tenant.user, subscription.active
|
*/

Route::prefix('documents')
    ->name('documents.')
    ->controller(EmployeeDocumentController::class)
    ->group(function () {
        Route::get('/data', 'data')
            ->name('data');

        Route::get('/options', 'options')
            ->name('options');

        Route::get('/', 'index')
            ->name('index');

        Route::post('/', 'store')
            ->name('store');

        Route::post('/{document}/verify', 'verify')
            ->whereNumber('document')
            ->name('verify');

        Route::post('/{document}/unverify', 'unverify')
            ->whereNumber('document')
            ->name('unverify');

        Route::post('/{document}/restore', 'restore')
            ->whereNumber('document')
            ->withTrashed()
            ->name('restore');

        Route::get('/{document}/preview', 'preview')
            ->whereNumber('document')
            ->withTrashed()
            ->name('preview');

        Route::get('/{document}/download', 'download')
            ->whereNumber('document')
            ->withTrashed()
            ->name('download');

        Route::get('/{document}', 'show')
            ->whereNumber('document')
            ->withTrashed()
            ->name('show');

        Route::post('/{document}', 'update')
            ->whereNumber('document')
            ->name('update');

        Route::delete('/{document}', 'destroy')
            ->whereNumber('document')
            ->name('destroy');
    });
