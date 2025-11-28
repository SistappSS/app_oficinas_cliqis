<?php


use App\Http\Controllers\Application\Auth\Authenticate\AuthController;
use App\Http\Controllers\Application\Auth\Authenticate\GoogleAuthController;
use App\Http\Controllers\Application\Auth\Permissions\Modules\BuyModuleController;
use App\Http\Controllers\Application\Auth\Permissions\Modules\ModuleController;
use App\Http\Controllers\Application\Auth\Permissions\PermissionController;
use App\Http\Controllers\Application\Auth\RegisterCustomer\AdditionalCustomerInfoController;
use App\Http\Controllers\Application\Auth\User\MyAccountController;
use App\Http\Controllers\Application\Catalogs\EquipmentController;
use App\Http\Controllers\Application\Catalogs\EquipmentPartController;
use App\Http\Controllers\Application\Catalogs\PartController;
use App\Http\Controllers\Application\ChatIA\ChatIAController;
use App\Http\Controllers\Application\ChatIA\KnowLedgeBaseController;
use App\Http\Controllers\Application\DashboardController;
use App\Http\Controllers\Application\Entities\Customers\SecondaryCustomer\SecondaryCustomerController;
use App\Http\Controllers\Application\Entities\Suppliers\Supplier\SupplierController;
use App\Http\Controllers\Application\Entities\Users\UserController;
use App\Http\Controllers\Application\HumanResources\BenefitController;
use App\Http\Controllers\Application\HumanResources\DepartmentController;
use App\Http\Controllers\Application\HumanResources\EmployeeBenefitController;
use App\Http\Controllers\Application\HumanResources\EmployeeController;
use App\Http\Controllers\Application\ServiceOrders\CompletedServiceOrderController;
use App\Http\Controllers\Application\ServiceOrders\ServiceOrderController;
use App\Http\Controllers\Application\ServiceOrders\ServiceOrderEquipmentController;
use App\Http\Controllers\Application\ServiceOrders\ServiceOrderLaborEntryController;
use App\Http\Controllers\Application\ServiceOrders\ServiceOrderPartItemController;
use App\Http\Controllers\Application\ServiceOrders\ServiceOrderServiceItemController;
use App\Http\Controllers\Application\Services\ServiceItemController;
use App\Http\Controllers\Application\Services\ServiceTypeController;
use App\Http\Controllers\General\Notifications\NotificationController;
use App\Http\Middleware\CheckSubscription;
use Illuminate\Support\Facades\Route;

// HUMAN RESOURCES

// CATALOGS

// SERVICES

// SERVICE ORDERS


// ****** --------- Entities --------- ******
// Secondary Customers


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
        // Users
        Route::get('/user', [UserController::class, 'view'])->name('user.view');
        Route::resource('/user-api', UserController::class);

        // Secondary Customers
        Route::get('/customer', [SecondaryCustomerController::class, 'view'])->name('customer.view');
        Route::resource('/customer-api', SecondaryCustomerController::class);

        // Supplier
        Route::get('/supplier', [SupplierController::class, 'view'])->name('supplier.view');
        Route::resource('/supplier-api', SupplierController::class);
    });

    /* --->| Human Resource |<--- */
    Route::group(['prefix' => 'human-resources'], function () {
        // Departments
        Route::get('/department', [DepartmentController::class, 'view'])->name('department.view');
        Route::resource('/department-api', DepartmentController::class);

        // Employees
        Route::get('/employee', [EmployeeController::class, 'view'])->name('employee.view');
        Route::resource('/employee-api', EmployeeController::class);

        // Benefits
        Route::get('/benefit', [BenefitController::class, 'view'])->name('benefit.view');
        Route::resource('/benefit-api', BenefitController::class);

        // EmployeeBenefits (pivot/relacionamento)
        Route::get('/employee-benefit', [EmployeeBenefitController::class, 'view'])->name('employee-benefit.view');
        Route::resource('/employee-benefit-api', EmployeeBenefitController::class);
    });


    /* --->| Catalog |<--- */
    Route::group(['prefix' => 'catalogs'], function () {
        // Service Types
        Route::get('/service-type', [ServiceTypeController::class, 'view'])->name('service-type.view');
        Route::resource('/service-type-api', ServiceTypeController::class);

        // Service Items
        Route::get('/service-item', [ServiceItemController::class, 'view'])->name('service-item.view');
        Route::resource('/service-item-api', ServiceItemController::class);

        // Parts
        Route::get('/part', [PartController::class, 'view'])->name('part.view');
        Route::resource('/part-api', PartController::class);

        // Equipments
        Route::get('/equipment', [EquipmentController::class, 'view'])->name('equipment.view');
        Route::resource('/equipment-api', EquipmentController::class);

        // Equipment-Part (relação peças x equipamentos)
        Route::get('/equipment-part', [EquipmentPartController::class, 'view'])->name('equipment-part.view');
        Route::resource('/equipment-part-api', EquipmentPartController::class);
    });


    /* --->| Service Orders |<--- */
    Route::group(['prefix' => 'service-orders'], function () {
        // Service Orders
        Route::get('/service-order', [ServiceOrderController::class, 'view'])->name('service-order.view');
        Route::get('/service-order/create/{serviceOrder?}', [ServiceOrderController::class, 'create'])->name('service-order.create');
        Route::resource('/service-order-api', ServiceOrderController::class);

        // Equipments da OS
        Route::get('/service-order-equipment', [ServiceOrderEquipmentController::class, 'view'])->name('service-order-equipment.view');
        Route::resource('/service-order-equipment-api', ServiceOrderEquipmentController::class);

        // Serviços da OS
        Route::get('/service-order-service-item', [ServiceOrderServiceItemController::class, 'view'])->name('service-order-service-item.view');
        Route::resource('/service-order-service-item-api', ServiceOrderServiceItemController::class);

        // Peças da OS
        Route::get('/service-order-part-item', [ServiceOrderPartItemController::class, 'view'])->name('service-order-part-item.view');
        Route::resource('/service-order-part-item-api', ServiceOrderPartItemController::class);

        // Horas de mão de obra
        Route::get('/service-order-labor-entry', [ServiceOrderLaborEntryController::class, 'view'])->name('service-order-labor-entry.view');
        Route::resource('/service-order-labor-entry-api', ServiceOrderLaborEntryController::class);

        // OS concluídas / assinaturas
        Route::get('/completed-service-order', [CompletedServiceOrderController::class, 'view'])->name('completed-service-order.view');
        Route::resource('/completed-service-order-api', CompletedServiceOrderController::class)->except('store');
        Route::post('/{serviceOrder}/client-signature', [CompletedServiceOrderController::class, 'store'])->name('service-orders.client-signature');
    });

    /* --->| Chat IA |<--- */
    Route::group(['prefix' => 'chat'], function () {
        // Chat
        Route::get('/', [ChatIAController::class, 'view'])->name('chat.view');
        Route::post('/message', [ChatIAController::class, 'message'])->name('chat.message');

        // KnowLedge
        Route::get('/knowledge', [KnowLedgeBaseController::class, 'view'])->name('knowledge.view');
        Route::post('/knowledge', [KnowLedgeBaseController::class, 'store'])->name('knowledge.store');
        Route::delete('/knowledge/{document}', [KnowLedgeBaseController::class, 'destroy'])->name('knowledge.destroy');
    });


    /* --->| Config |<--- */
    Route::group(['prefix' => 'config'], function () {
        Route::get('/my-account', [MyAccountController::class, 'myAccount'])->name('my-account.index');
        Route::post('/my-account/change-information/{userId}', [MyAccountController::class, 'changeInformation'])->name('change-information.update');
        Route::post('/my-account/change-password/{userId}', [MyAccountController::class, 'changePassword'])->name('change-password.update');
        Route::post('/my-account/change-image', [MyAccountController::class, 'changeImage'])->name('change-image.update');
    });

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
});

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->group(function () {
        /* --->| Admin |<--- */
        Route::group(['prefix' => 'admin'], function () {
            /* --->| Roles |<--- */
            Route::prefix('roles')->group(function () {
                Route::get('/', [PermissionController::class, 'roleIndex'])->name('roles.index');
                Route::get('/create', [PermissionController::class, 'roleCreate'])->name('roles.create');
                Route::post('/store', [PermissionController::class, 'roleStore'])->name('roles.store');
            });

            /* --->| Permissions |<--- */
            Route::prefix('permissions')->group(function () {
                Route::get('/', [PermissionController::class, 'permissionIndex'])->name('permissions.index');
                Route::get('/create', [PermissionController::class, 'roleCreate'])->name('permissions.create');
                Route::post('/store', [PermissionController::class, 'permissionStore'])->name('permissions.store');
            });

            Route::get('/permissions/list', [PermissionController::class, 'getPermissions'])->name('permissions.list');
        });
    });
