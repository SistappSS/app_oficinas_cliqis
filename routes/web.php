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
use App\Http\Controllers\Application\Entities\Customers\Permisions\PermissionUserController;
use App\Http\Controllers\Application\Entities\Customers\SecondaryCustomer\SecondaryCustomerController;
use App\Http\Controllers\Application\Entities\Suppliers\Supplier\SupplierController;
use App\Http\Controllers\Application\Entities\Users\UserController;
use App\Http\Controllers\Application\Finances\Payables\AccountPayableAuditController;
use App\Http\Controllers\Application\Finances\Payables\AccountPayableController;
use App\Http\Controllers\Application\Finances\Receivables\AccountReceivableController;
use App\Http\Controllers\Application\HumanResources\BenefitController;
use App\Http\Controllers\Application\HumanResources\DepartmentController;
use App\Http\Controllers\Application\HumanResources\EmployeeBenefitController;
use App\Http\Controllers\Application\HumanResources\EmployeeController;
use App\Http\Controllers\Application\Invoices\ServiceOrderBillingController;
use App\Http\Controllers\Application\PartOrderController;
use App\Http\Controllers\Application\ServiceOrders\CompletedServiceOrderController;
use App\Http\Controllers\Application\ServiceOrders\ServiceOrderController;
use App\Http\Controllers\Application\ServiceOrders\ServiceOrderEquipmentController;
use App\Http\Controllers\Application\ServiceOrders\ServiceOrderLaborEntryController;
use App\Http\Controllers\Application\ServiceOrders\ServiceOrderPartItemController;
use App\Http\Controllers\Application\ServiceOrders\ServiceOrderServiceItemController;
use App\Http\Controllers\Application\Services\ServiceItemController;
use App\Http\Controllers\Application\Services\ServiceTypeController;
use App\Http\Controllers\Finances\Payables\PayableCustomFieldController;
use App\Http\Controllers\General\ImportExport\ImportExportController;
use App\Http\Controllers\General\Notifications\NotificationController;
use App\Http\Controllers\PartOrderSettingController;
use App\Http\Controllers\Public\ServiceOrderPublicSignatureController;
use App\Http\Controllers\ServiceOrderSignatureLinkController;
use App\Http\Controllers\Stock\MovementStockController;
use App\Http\Controllers\Stock\Settings\StockLocationController;
use App\Http\Controllers\Stock\StockController;
use App\Http\Controllers\Stock\StockReasonController;
use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EnsureOnboarding;
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

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), CheckSubscription::class])->group(function () {
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

    Route::get('/module/buy-module/billing/{userId}', [BuyModuleController::class, 'billing'])->name('billing.index');

    Route::post('/modules/upgrade-annual', [BuyModuleController::class, 'upgradeToAnnual'])->name('modules.upgrade-annual');

    Route::group(['prefix' => 'modules/buy-module'], function () {
        Route::get('/{id}', [BuyModuleController::class, 'buyView'])->name('buy-module.index');
        Route::post('/', [BuyModuleController::class, 'buyModule'])->name('module.checkout');
        Route::post('/verificar-pix-pendente', [BuyModuleController::class, 'checkPendingPix'])->name('verificar-pix-pendente');
        Route::post('/gerar-qrcode-module', [BuyModuleController::class, 'qrCodeGenerate'])->name('gerar-qrcode.module');
        Route::get('/checar-status-pagamento/{paymentId}', [BuyModuleController::class, 'checkPaymentStatus'])->name('checar-pagamento.module');
        Route::post('/cancelar/{paymentId}', [BuyModuleController::class, 'cancel'])->name('module.cancel');
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

    /* --->| Roles/Permissions |<--- */
    Route::group(['prefix' => 'permissions-user'], function () {
        Route::get('/roles-permissions', [PermissionUserController::class, 'view'])
            ->name('roles-permissions.view');

        // ROLES (perfis)
        Route::get('/roles-api', [PermissionUserController::class, 'rolesIndex']);
        Route::post('/roles-api', [PermissionUserController::class, 'rolesStore']);
        Route::put('/roles-api/{role}', [PermissionUserController::class, 'rolesUpdate']);
        Route::delete('/roles-api/{role}', [PermissionUserController::class, 'rolesDestroy']);

        Route::get('/permissions-api', [PermissionUserController::class, 'permissionsIndex']);

        Route::get('/roles-api/{role}/permissions', [PermissionUserController::class, 'rolePermissions']);
        Route::post('/roles-api/{role}/permissions', [PermissionUserController::class, 'syncRolePermissions']);
    });

    /* --->| Entities |<--- */
    Route::group(['prefix' => 'entities'], function () {
        // Users
        Route::get('/user', [UserController::class, 'view'])->name('user.view');
        Route::get('/user/permissions', [UserController::class, 'permissions'])->name('user.permissions');
        Route::resource('/user-api', UserController::class);

        // Secondary Customers
        Route::get('/customer', [SecondaryCustomerController::class, 'view'])->name('customer.view');
        Route::resource('/customer-api', SecondaryCustomerController::class);

        // Supplier
        Route::get('/supplier', [SupplierController::class, 'view'])->name('supplier.view');
        Route::get('/supplier/typeahead', [SupplierController::class, 'partOrderConfig']);
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
        Route::get('/service-order/create/{id?}', [ServiceOrderController::class, 'create'])->name('service-order.create');
        Route::get('/service-order/{serviceOrder}/edit', [ServiceOrderController::class, 'edit'])->name('service-order.edit');
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

        // Link assinatura
        Route::post('/{serviceOrder}/signature-link/send', [ServiceOrderSignatureLinkController::class, 'send'])->name('service-orders.signature.send');

        // Ações botão
        Route::get('/{serviceOrder}/pdf', [ServiceOrderController::class, 'pdf'])->name('service-order.pdf');
        Route::get('/{serviceOrder}/pdf/download', [ServiceOrderController::class, 'pdfDownload'])->name('service-order.pdf.download');
        Route::post('{serviceOrder}/email', [ServiceOrderController::class, 'sendPdfEmail'])->name('service-orders.email');
        Route::post('{serviceOrder}/duplicate', [ServiceOrderController::class, 'duplicate'])->name('service-orders.duplicate');
        Route::post('/{serviceOrder}/status', [ServiceOrderController::class, 'setStatus']);

        // API para a tabela de cobranças + NF
        Route::get('/billing', [ServiceOrderBillingController::class, 'index'])->name('service-order-invoice.view');
        Route::get('/billing-api', [ServiceOrderBillingController::class, 'list'])->name('service-orders.billing.api');
        Route::post('/billing', [ServiceOrderBillingController::class, 'store'])->name('service-orders.billing.store');
    });

    Route::group(['prefix' => 'part-orders'], function () {

        Route::get('/part-order', [PartOrderController::class, 'view'])->name('part-order.view');
        Route::get('/part-order/create/{id?}', [PartOrderController::class, 'create'])->name('part-order.create');
        Route::get('/part-order/{partOrder}/edit', [PartOrderController::class, 'edit'])->name('part-order.edit');

        // ✅ API (sem create/edit do resource)
        Route::apiResource('/part-order-api', PartOrderController::class)
            ->parameters(['part-order-api' => 'partOrder']);

        // Ações
        Route::get('/{partOrder}/pdf', [PartOrderController::class, 'pdf'])->name('part-order.pdf');
        Route::get('/{partOrder}/pdf/download', [PartOrderController::class, 'pdfDownload'])->name('part-order.pdf.download');
        Route::post('/{partOrder}/send', [PartOrderController::class, 'send'])->name('part-orders.email');
        Route::post('/{partOrder}/duplicate', [PartOrderController::class, 'duplicate'])->name('part-orders.duplicate');

        Route::post('/{partOrder}/resend', [PartOrderController::class, 'resend'])->name('part-orders.resend-email');

        Route::get('/{partOrder}/receive-data', [PartOrderController::class, 'receiveData'])->name('part-orders.receiveData');
        Route::post('/{partOrder}/receive', [PartOrderController::class, 'receive'])->name('part-orders.receive');

        Route::get('/settings', [PartOrderSettingController::class, 'show']);
        Route::put('/settings', [PartOrderSettingController::class, 'upsert']);

        /*
        // Itens do pedido (se existir módulo separado)
        Route::get('/part-order-item', [PartOrderItemController::class, 'view'])->name('part-order-item.view');
        Route::resource('/part-order-item-api', PartOrderItemController::class);

        // Catálogo de peças (se existir módulo separado)
        Route::get('/parts-catalog', [PartsCatalogController::class, 'view'])->name('parts-catalog.view');
        Route::resource('/parts-catalog-api', PartsCatalogController::class);
        */
    });

    /* --->| Finances |<--- */
    Route::group(['prefix' => 'finances'], function () {
        // Payables
        Route::get('/payables', [AccountPayableController::class, 'view'])->name('account-payable.view')->middleware('can:finance_payable_view');
        Route::get('/payable-api', [AccountPayableController::class, 'index'])->middleware('can:finance_payable_view');
        Route::post('/payable-api', [AccountPayableController::class, 'store'])->middleware('can:finance_payable_create');
        Route::post('/payable-api/{id}/pay', [AccountPayableController::class, 'pay'])->middleware('can:finance_payable_edit');
        Route::get('/payable-api/{id}/payments', [AccountPayableController::class, 'payments'])->middleware('can:finance_payable_view');
        Route::patch('/payable-api/{id}/amount', [AccountPayableController::class, 'updateParcelAmount'])->middleware('can:finance_payable_edit');
        Route::post('/payable-api/{id}/cancel', [AccountPayableController::class, 'cancelParcel'])->middleware('can:finance_payable_delete');

        // Payables -> Setting Custom Field
        Route::resource('/payables/custom-field-api', PayableCustomFieldController::class);
        Route::post('/payables/custom-fields/{id}/toggle', [PayableCustomFieldController::class, 'toggle']);

        // Audit
        Route::get('/payables/audit-api', [AccountPayableAuditController::class, 'index']);

        // Receivables
        Route::get('/receivables/service-orders', [AccountReceivableController::class, 'view'])->name('account-receivable.view');
        Route::get('/receivables/service-orders/api', [AccountReceivableController::class, 'index'])->name('receivables.service-orders.index');
        Route::post('/receivables/invoices/{invoice}/pay', [AccountReceivableController::class, 'setPaid'])->name('receivables.service-orders.pay');
    });

    /* --->| Stock |<--- */
    Route::group(['prefix' => 'stock'], function (){
        Route::get('/stock', [StockController::class, 'view'])->name('stock.view');
        Route::resource('/stock-api', StockController::class);
        Route::get('/kpis-api', [StockController::class, 'kpis'])->name('stock.kpis');

        Route::get('/stock-api/{id}/detail', [StockController::class, 'detail']);
        Route::put('/stock-api/{id}/pricing', [StockController::class, 'updatePricing']);
        Route::put('/stock-api/{id}/balance', [StockController::class, 'updateBalance']);
        Route::get('/stock-api/{id}/price-logs', [StockController::class, 'priceLogs']);
        Route::post('/stock-api/{id}/adjust', [StockController::class, 'adjust'])->name('stock.adjust');

        Route::get('/movements', [MovementStockController::class, 'view'])->name('movements.view');
        Route::get('/movements-api', [MovementStockController::class, 'movementsData']);
        Route::get('/movements-api/{id}', [MovementStockController::class, 'show']);
        Route::post('/movements-api/adjust', [MovementStockController::class, 'adjust']);

        Route::post('/movements-api/manual-in',  [MovementStockController::class, 'manualIn']);
        Route::post('/movements-api/manual-out', [MovementStockController::class, 'manualOut']);

        Route::get('/settings/reasons', [StockReasonController::class, 'view'])->name('stock-reasons.view');
        Route::resource('/settings/reason-api', StockReasonController::class);
        Route::get('/settings/reasons-picklist', [StockReasonController::class, 'picklist']);

        Route::get('/settings/locations', [StockLocationController::class, 'view'])->name('stock-location.view');
        Route::resource('/settings/location-api', StockLocationController::class)->only(['index','store','update','destroy']);
        Route::get('/settings/locations-picklist', [StockLocationController::class, 'picklist']);
        Route::get('/location-api/{id}/delete-check', [StockLocationController::class, 'deleteCheck']);
    });

    Route::post('/service-orders/{serviceOrder}/billing/generate', [ServiceOrderBillingController::class, 'generate'])->name('service-orders.billing.generate');

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

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {
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

    Route::get('/modules', [ModuleController::class, 'index'])->name('module.index');
    Route::post('/modules', [ModuleController::class, 'store'])->name('module.store');

    Route::post('/modules/feature', [ModuleController::class, 'storeFeature'])->name('feature.store');
});

// Link público para assinatura
Route::middleware(['throttle:30,1'])
    ->withoutMiddleware([
        CheckSubscription::class,
        EnsureOnboarding::class,
    ])
    ->group(function () {
        Route::get('/service-orders/sign/{token}', [ServiceOrderPublicSignatureController::class, 'show'])
            ->name('service-orders.signature.public.show');

        Route::post('/os/assinar/{token}', [ServiceOrderPublicSignatureController::class, 'store'])
            ->name('service-orders.signature.public.store');
    });

Route::get('/run/{tenantId}', [DashboardController::class, 'run']);

Route::get('/io/options', [ImportExportController::class, 'options'])->name('io.options');
Route::post('/io/export', [ImportExportController::class, 'export'])->name('io.export');
Route::post('/io/import', [ImportExportController::class, 'import'])->name('io.import');
Route::get('/io/template', [ImportExportController::class, 'template'])->name('io.template');

/*

Ultima mexida foi em ordem de serviço, analisado que precisamos criar um json na tabela de orders. Caso não quiser salvos o cliente, esse json irá ser salvo
assim da pra saber quem é o cliente da order de serviço. (utilizar os mesmos dados).

Se selecionar pra salvar o cliente, o json pode ser salvo também mas não será preciso.

Está salvado o cliente no banco, mas peças/equipamentos... não.

*/
