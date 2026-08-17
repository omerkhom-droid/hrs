<?php

use App\Http\Controllers\System\AuthController;
use App\Http\Controllers\System\DashboardController;
use App\Http\Controllers\System\PlanController;
use App\Http\Controllers\System\PlanFeatureController;
use App\Http\Controllers\System\SubscriptionController;
use App\Http\Controllers\System\SubscriptionLifecycleController;
use App\Http\Controllers\System\TenantController;
use App\Http\Controllers\Tenant\AuthController as TenantAuthController;
use App\Http\Controllers\Tenant\BranchController;
use App\Http\Controllers\Tenant\DashboardController as TenantDashboardController;
use App\Http\Controllers\Tenant\DepartmentController;
use App\Http\Controllers\Tenant\JobTitleController;
use App\Http\Controllers\Tenant\WorkLocationController;

use App\Http\Controllers\Tenant\RoleController as TenantRoleController;
use App\Http\Controllers\Tenant\UserController as TenantUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Home
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    $user = auth()->user();

    if ($user?->is_system_admin) {
        return redirect()->route('system.dashboard');
    }

    if ($user?->tenant_id) {
        return redirect()->route('app.dashboard');
    }

    return redirect()->route('system.login');
});

/*
|--------------------------------------------------------------------------
| System Admin Portal
|--------------------------------------------------------------------------
*/

Route::prefix('system')
    ->name('system.')
    ->group(function () {
        /* Authentication */
        Route::get('/login', [AuthController::class, 'showLogin'])
            ->name('login');

        Route::post('/login', [AuthController::class, 'login'])
            ->name('login.submit');

        Route::middleware(['auth', 'system.admin'])
            ->group(function () {
                /* Dashboard */
                Route::get('/dashboard', [DashboardController::class, 'index'])
                    ->name('dashboard');

                /* Tenants */
                // يجب أن يسبق هذا المسار Route::resource حتى لا تُعامل data كمعرّف عميل.
                Route::get('/tenants/data', [TenantController::class, 'data'])
                    ->name('tenants.data');

                Route::resource('tenants', TenantController::class)
                    ->except(['create', 'edit']);

                /* Plans */
                // يجب تسجيل المسارات الخاصة قبل Route::resource.
                Route::get('/plans/data', [PlanController::class, 'data'])
                    ->name('plans.data');

                Route::get('/plans/{plan}/features', [PlanFeatureController::class, 'edit'])
                    ->name('plans.features.edit');

                Route::put('/plans/{plan}/features', [PlanFeatureController::class, 'update'])
                    ->name('plans.features.update');

                Route::resource('plans', PlanController::class)
                    ->except(['create', 'edit']);

                /* Subscriptions */
                Route::get('/subscriptions/data', [SubscriptionController::class, 'data'])
                    ->name('subscriptions.data');

                Route::get('/subscriptions', [SubscriptionController::class, 'index'])
                    ->name('subscriptions.index');

                Route::post('/subscriptions', [SubscriptionController::class, 'store'])
                    ->name('subscriptions.store');

                Route::get('/subscriptions/{subscription}', [SubscriptionController::class, 'show'])
                    ->name('subscriptions.show');

                /* Subscription Lifecycle */
                Route::prefix('subscriptions/{subscription}')
                    ->name('subscriptions.')
                    ->group(function () {
                        Route::post('/convert-trial', [SubscriptionLifecycleController::class, 'convertTrial'])
                            ->name('convert-trial');

                        Route::post('/renew', [SubscriptionLifecycleController::class, 'renew'])
                            ->name('renew');

                        Route::post('/change-plan', [SubscriptionLifecycleController::class, 'changePlan'])
                            ->name('change-plan');

                        Route::post('/suspend', [SubscriptionLifecycleController::class, 'suspend'])
                            ->name('suspend');

                        Route::post('/resume', [SubscriptionLifecycleController::class, 'resume'])
                            ->name('resume');

                        Route::post('/cancel', [SubscriptionLifecycleController::class, 'cancel'])
                            ->name('cancel');
                    });

                /* Logout */
                Route::post('/logout', [AuthController::class, 'logout'])
                    ->name('logout');
            });
    });

/*
|--------------------------------------------------------------------------
| Tenant Application
|--------------------------------------------------------------------------
*/

Route::prefix('app')
    ->name('app.')
    ->group(function () {
        /* Authentication */
        Route::get('/login', [TenantAuthController::class, 'showLogin'])
            ->name('login');

        Route::post('/login', [TenantAuthController::class, 'login'])
            ->name('login.submit');

        Route::middleware(['auth', 'tenant.user'])
            ->group(function () {
                /*
                 * تسجيل الخروج خارج subscription.active ليبقى متاحًا
                 * حتى عند انتهاء اشتراك الشركة أو إيقافه.
                 */
                Route::post('/logout', [TenantAuthController::class, 'logout'])
                    ->name('logout');

                Route::middleware('subscription.active')
                    ->group(function () {
                        /* Dashboard */
                        Route::get('/dashboard', [TenantDashboardController::class, 'index'])
                            ->name('dashboard');


                        require __DIR__ . '/tenant-employees.php';
                        require __DIR__ . '/tenant-contracts.php';
                        require __DIR__ . '/tenant-documents.php';
                        require __DIR__ . '/tenant-attendance.php';
                        
                        
                        /* Tenant Users */
                        Route::prefix('users')
                            ->name('users.')
                            ->group(function () {
                                Route::get('/', [TenantUserController::class, 'index'])
                                    ->middleware('permission:users.view')
                                    ->name('index');

                                Route::post('/', [TenantUserController::class, 'store'])
                                    ->middleware('permission:users.create')
                                    ->name('store');

                                Route::get('/{user}', [TenantUserController::class, 'show'])
                                    ->whereNumber('user')
                                    ->middleware('permission:users.view')
                                    ->name('show');

                                Route::put('/{user}', [TenantUserController::class, 'update'])
                                    ->whereNumber('user')
                                    ->middleware('permission:users.update')
                                    ->name('update');

                                Route::patch('/{user}/status', [TenantUserController::class, 'status'])
                                    ->whereNumber('user')
                                    ->middleware('permission:users.deactivate')
                                    ->name('status');
                            });

                        /* Roles and Permissions */
                        Route::prefix('roles')
                            ->name('roles.')
                            ->group(function () {
                                Route::get('/', [TenantRoleController::class, 'index'])
                                    ->middleware('permission:roles.view')
                                    ->name('index');

                                Route::get('/{role}', [TenantRoleController::class, 'show'])
                                    ->whereNumber('role')
                                    ->middleware('permission:roles.view')
                                    ->name('show');

                                Route::post('/', [TenantRoleController::class, 'store'])
                                    ->middleware('permission:roles.manage')
                                    ->name('store');

                                Route::put('/{role}', [TenantRoleController::class, 'update'])
                                    ->whereNumber('role')
                                    ->middleware('permission:roles.manage')
                                    ->name('update');

                                Route::delete('/{role}', [TenantRoleController::class, 'destroy'])
                                    ->whereNumber('role')
                                    ->middleware('permission:roles.manage')
                                    ->name('destroy');
                            });

                        /* Organization Structure */
                        Route::prefix('organization')
                            ->name('organization.')
                            ->group(function () {
                                /* Branches */
                                Route::get('/branches/data', [BranchController::class, 'data'])
                                    ->name('branches.data');

                                Route::resource('branches', BranchController::class)
                                    ->only(['index', 'store', 'show', 'update', 'destroy']);

                                /* Departments */
                                // يجب أن تسبق هذه المسارات Route::resource بالترتيب التالي.
                                Route::get('/departments/data', [DepartmentController::class, 'data'])
                                    ->name('departments.data');

                                Route::get('/departments/options', [DepartmentController::class, 'options'])
                                    ->name('departments.options');

                                Route::get('/departments/tree', [DepartmentController::class, 'tree'])
                                    ->name('departments.tree');

                                Route::resource('departments', DepartmentController::class)
                                    ->only(['index', 'store', 'show', 'update', 'destroy']);

                                /*
                                |--------------------------------------------------------------------------
                                | Job Titles
                                |--------------------------------------------------------------------------
                                */

                                Route::get('/job-titles/data', [
                                    JobTitleController::class,
                                    'data',
                                ])->name('job-titles.data');

                                Route::get('/job-titles/options', [
                                    JobTitleController::class,
                                    'options',
                                ])->name('job-titles.options');

                                Route::resource('job-titles', JobTitleController::class)
                                    ->parameters(['job-titles' => 'jobTitle',])
                                    ->only(['index','store','show','update','destroy',]);

                                    
                                /* Work Locations */
                                Route::get('/work-locations/data', [WorkLocationController::class, 'data'])
                                    ->name('work-locations.data');

                                Route::get('/work-locations/options', [WorkLocationController::class, 'options'])
                                    ->name('work-locations.options');

                                Route::resource('work-locations', WorkLocationController::class)
                                    ->parameters([
                                        'work-locations' => 'workLocation',
                                    ])
                                    ->only(['index', 'store', 'show', 'update', 'destroy']);
                            });
                    });
            });
    });