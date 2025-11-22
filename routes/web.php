<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckSubscription;


use App\Http\Controllers\APP\Admin\AdminCenterController;
use App\Http\Controllers\APP\Admin\Modules\BuyModuleController;
use App\Http\Controllers\APP\Admin\Modules\ModuleController;

use App\Http\Controllers\APP\Authenticate\AdditionalCustomerInfoController;
use App\Http\Controllers\APP\Authenticate\AuthController;
use App\Http\Controllers\APP\Authenticate\GoogleAuthController;

use App\Http\Controllers\APP\DashboardController;

use App\Http\Controllers\APP\Entities\Customers\CustomerArea\CustomerAreaController;
use App\Http\Controllers\APP\Entities\Customers\CustomerController;

use App\Http\Controllers\APP\Finances\Payables\AccountPayableController;
use App\Http\Controllers\APP\Finances\Receivables\AccountReceivableController;

use App\Http\Controllers\APP\Sales\Budgets\BudgetConfigController;
use App\Http\Controllers\APP\Sales\Budgets\BudgetController;
use App\Http\Controllers\APP\Sales\Invoices\Billing\BillingController;
use App\Http\Controllers\APP\Sales\Invoices\Reminder\ReminderInvoiceConfigController;
use App\Http\Controllers\APP\Sales\Services\ServiceController;

use App\Http\Controllers\General\Notifications\NotificationController;
use App\Models\Sales\Budgets\Subscriptions\Subscription;

/*
|--------------------------------------------------------------------------
| PÚBLICO / AUTH
|--------------------------------------------------------------------------
*/

Route::view('/', 'app.home.index')->name('home');

Route::get('/login', [AuthController::class, 'loginView'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.post');

Route::get('/register', [AuthController::class, 'registerView'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');

Route::get('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');

Route::any('/logout', [AuthController::class, 'destroy'])->name('logout');

/*
|--------------------------------------------------------------------------
| ONBOARDING + MÓDULOS / ASSINATURA (sem verified, com CheckSubscription)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), CheckSubscription::class])
    ->group(function () {

        // Etapas de onboarding
        Route::get('/company-information', [AdditionalCustomerInfoController::class, 'additionalInfoIndex'])->name('additional-customer-info.index');
        Route::post('/company-information', [AdditionalCustomerInfoController::class, 'additionalInfoIndexStore'])->name('additional-customer-info.store');

        Route::get('/company-information/segment', [AdditionalCustomerInfoController::class, 'segmentCompanyIndex'])->name('company-segment.index');
        Route::post('/company-information/segment', [AdditionalCustomerInfoController::class, 'segmentCompanyStore'])->name('company-segment.store');

        Route::get('/company-information/addons', [AdditionalCustomerInfoController::class, 'addonsIndex'])->name('addons.index');
        Route::post('/company-information/addons', [AdditionalCustomerInfoController::class, 'addonsStore'])->name('addons.store');

        /*
        |--------------------------------------------------------------------------
        | SUBSCRIPTION FALSE ROUTES
        |--------------------------------------------------------------------------
        */

        Route::get('/modules', [ModuleController::class, 'index'])->name('module.index');
        Route::post('/modules', [ModuleController::class, 'store'])->name('module.store');

        Route::post('/modules/feature', [ModuleController::class, 'storeFeature'])->name('feature.store');

        Route::get('/module/buy-module/billing/{userId}', [BuyModuleController::class, 'billing'])->name('billing.index');

        Route::post('/modules/upgrade-annual', [BuyModuleController::class, 'upgradeToAnnual'])
            ->name('modules.upgrade-annual');

        Route::group(['prefix' => 'modules/buy-module'], function () {
            Route::get('/{id}', [BuyModuleController::class, 'buyView'])->name('buy-module.index');
            Route::post('/', [BuyModuleController::class, 'buyModule'])->name('module.checkout');
            Route::post('/verificar-pix-pendente', [BuyModuleController::class, 'checkPendingPix'])->name('verificar-pix-pendente');
            Route::post('/gerar-qrcode-module', [BuyModuleController::class, 'qrCodeGenerate'])->name('gerar-qrcode.module');
            Route::get('/checar-status-pagamento/{paymentId}', [BuyModuleController::class, 'checkPaymentStatus'])->name('checar-pagamento.module');
            Route::post('/cancelar/{paymentId}', [BuyModuleController::class, 'cancel'])
                ->name('module.cancel');
        });
    });

/*
|--------------------------------------------------------------------------
| APP PRINCIPAL (verified + CheckSubscription + customer.context)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified', CheckSubscription::class, 'customer.context',])->group(function () {
    /* --->| Dashboard |<--- */
    Route::group(['prefix' => 'dashboard'], function () {
        Route::get('/', [DashboardController::class, 'dashboard'])->name('dashboard');
    });

    /* --->| Entities |<--- */
    Route::group(['prefix' => 'entities'], function () {
        Route::get('/customer', [CustomerController::class, 'view'])->name('customer.view')->middleware('can:entitie_customer_view');

        Route::resource('/customer-api', CustomerController::class)->middleware([
            'can:entitie_customer_create',
            'can:entitie_customer_view',
            'can:entitie_customer_edit',
            'can:entitie_customer_delete',
        ]);

        Route::get('customer/customer-area/{customer}', [CustomerAreaController::class, 'customerArea'])->name('customer-area')->middleware('can:sales_budget_view');

        Route::patch('customer/customer-area/subscriptions/{subscription}', [CustomerAreaController::class, 'updateSubscription'])->name('subscriptions.update')->middleware('can:sales_budget_edit');

        Route::delete('customer/customer-area/subscriptions/{subscription}', [CustomerAreaController::class, 'cancelSubscription'])->name('subscriptions.cancel')->middleware('can:sales_budget_delete');

        // Users
        Route::resource('/user-api', \App\Http\Controllers\Endpoint\Entities\Users\UserController::class)->middleware([
                    'can:entitie_user_create',
                    'can:entitie_user_view',
                    'can:entitie_user_edit',
                    'can:entitie_user_delete',
                ]);
    });

    /* --->| Sales |<--- */
    Route::group(['prefix' => 'sales'], function () {
        // Services
        Route::get('/service', [ServiceController::class, 'view'])->name('service.view')->middleware('can:sales_service_view');

        Route::resource('/service-api', ServiceController::class)->middleware([
            'can:sales_service_create',
            'can:sales_service_view',
            'can:sales_service_edit',
            'can:sales_service_delete',
        ]);

        Route::get('/budgets', [BudgetController::class, 'view'])
            ->name('budget.view')
            ->middleware('can:sales_budget_view');

        Route::get('/budgets/create', [BudgetController::class, 'create'])
            ->name('budget.create')
            ->middleware('can:sales_budget_create');

        Route::post('/budgets/store', [BudgetController::class, 'store'])
            ->middleware('can:sales_budget_create');

        // Budget Config
        Route::get('/budgets/config', [BudgetConfigController::class, 'index'])
            ->name('budget-config.index')
            ->middleware('can:sales_budget_view');

        Route::post('/budgets/config/store', [BudgetConfigController::class, 'store'])
            ->name('budget-config.store')
            ->middleware('can:sales_budget_create');
    });

    // Orçamentos (raiz /budgets)
    Route::get('/budgets', [BudgetController::class, 'index'])
        ->name('budget.index')
        ->middleware('can:sales_budget_view');

    Route::post('/budgets/{id}/approve', [BudgetController::class, 'approve'])
        ->middleware('can:sales_budget_edit');

    Route::post('/budgets/{id}/reject', [BudgetController::class, 'reject'])
        ->middleware('can:sales_budget_edit');

    Route::post('/sales/budget/{id}/view-budget', [BudgetController::class, 'viewPdf'])
        ->name('budget.view.pdf')
        ->middleware('can:sales_budget_view');

    Route::post('/sales/budget/{id}/send-email', [BudgetController::class, 'sendEmail'])
        ->name('budget.sendEmail')
        ->middleware('can:sales_budget_edit');

    Route::delete('/budgets/{id}', [BudgetController::class, 'destroy'])
        ->middleware('can:sales_budget_delete');

    Route::get('/budgets/{id}/json', [BudgetController::class, 'json'])
        ->name('budget.json')
        ->middleware('can:sales_budget_view');

    // Cobranças (sem permissão específica no seeder -> sem can)
    Route::get('/invoice', [BillingController::class, 'view'])->name('invoice.index');
    Route::get('/invoices', [BillingController::class, 'index']);
    Route::post('/invoices', [BillingController::class, 'store']);
    Route::patch('/invoices/{id}', [BillingController::class, 'update']);
    Route::delete('/invoices/{id}', [BillingController::class, 'destroy']);

    Route::get('/invoices/reminder-config', [ReminderInvoiceConfigController::class, 'index'])
        ->name('invoices.reminder-config.index');

    Route::post('/invoices/reminder-config', [ReminderInvoiceConfigController::class, 'store'])
        ->name('invoices.reminder-config.store');

    Route::post('/invoices/{invoice}/send-reminder', [BillingController::class, 'sendReminder'])
        ->name('invoices.send-reminder');

    Route::get('/invoices/{id}/send-reminder-preview', [BillingController::class, 'previewReminder'])
        ->name('invoices.send-reminder.preview');

    Route::get('/subscriptions', fn() => Subscription::query()->with('customer')->latest()->paginate(50))->middleware('can:sales_budget_view');

    /* --->| Finances |<--- */
    Route::group(['prefix' => 'finances'], function () {
        // Payables
        Route::get('/payables', [AccountPayableController::class, 'view'])
            ->name('account-payable-view')
            ->middleware('can:finance_payable_view');

        Route::get('/payable-api', [AccountPayableController::class, 'index'])
            ->middleware('can:finance_payable_view');

        Route::post('/payable-api', [AccountPayableController::class, 'store'])
            ->middleware('can:finance_payable_create');

        Route::post('/payable-api/{id}/pay', [AccountPayableController::class, 'pay'])
            ->middleware('can:finance_payable_edit');

        Route::get('/payable-api/{id}/payments', [AccountPayableController::class, 'payments'])
            ->middleware('can:finance_payable_view');

        Route::patch('/payable-api/{id}/amount', [AccountPayableController::class, 'updateParcelAmount'])
            ->middleware('can:finance_payable_edit');

        Route::post('/payable-api/{id}/cancel', [AccountPayableController::class, 'cancelParcel'])
            ->middleware('can:finance_payable_delete');

        Route::get('/supplier-api', [AccountPayableController::class, 'suppliers'])
            ->middleware('can:finance_payable_view');

        // Receivables
        Route::get('/account/receivables/monthDue', [AccountReceivableController::class, 'monthDue'])
            ->name('account-receivable-monthDue')
            ->middleware('can:finance_receivable_view');

        Route::get('/account/receivables/forecast', [AccountReceivableController::class, 'forecast'])
            ->name('account-receivable-forecast')
            ->middleware('can:finance_receivable_view');

        Route::get('/account/receivables', [AccountReceivableController::class, 'view'])
            ->name('account-receivable-view')
            ->middleware('can:finance_receivable_view');

        Route::get('/account/account_receivable', [AccountReceivableController::class, 'index'])
            ->name('account_receivable.index')
            ->middleware('can:finance_receivable_view');

        Route::post('/account/account_receivable/store', [AccountReceivableController::class, 'store'])
            ->name('account_receivable.store')
            ->middleware('can:finance_receivable_create');

        Route::post('/account/account_receivable/status-update/{id}', [AccountReceivableController::class, 'setPaid'])
            ->name('account_receivable_status_update.update')
            ->middleware('can:finance_receivable_edit');

        Route::post('/subscriptions/{id}/pay', [AccountReceivableController::class, 'paySubscription'])
            ->middleware('can:finance_receivable_edit');
    });

    /* --->| Config |<--- */
    Route::group(['prefix' => 'config'], function () {
        Route::get('/my-account', [AuthController::class, 'myAccount'])->name('my-account.index');
        Route::post('/my-account/change-information/{userId}', [AuthController::class, 'changeInformation'])->name('change-information.update');
        Route::post('/my-account/change-password/{userId}', [AuthController::class, 'changePassword'])->name('change-password.update');
        Route::post('/my-account/change-image', [AuthController::class, 'changeImage'])->name('change-image.update');
    });

    /* --->| Modules |<--- */
    Route::resource('/module-api', \App\Http\Controllers\Endpoint\Modules\ModuleController::class);

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
});

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES (auth + verified)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->group(function () {
        /* --->| Admin |<--- */
        Route::group(['prefix' => 'admin'], function () {
            /* --->| Roles |<--- */
            Route::prefix('roles')->group(function () {
                Route::get('/', [AdminCenterController::class, 'roleIndex'])->name('roles.index');
                Route::get('/create', [AdminCenterController::class, 'roleCreate'])->name('roles.create');
                Route::post('/store', [AdminCenterController::class, 'roleStore'])->name('roles.store');
            });

            /* --->| Permissions |<--- */
            Route::prefix('permissions')->group(function () {
                Route::get('/', [AdminCenterController::class, 'permissionIndex'])->name('permissions.index');
                Route::get('/create', [AdminCenterController::class, 'roleCreate'])->name('permissions.create');
                Route::post('/store', [AdminCenterController::class, 'permissionStore'])->name('permissions.store');
            });

            Route::get('/permissions/list', [AdminCenterController::class, 'getPermissions'])->name('permissions.list');
        });
    });
