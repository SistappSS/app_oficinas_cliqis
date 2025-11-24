<?php

namespace App\Http\Controllers\Application\Auth\RegisterCustomer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\Onboarding\StoreAdditionalCustomerInfoRequest;
use App\Models\Authenticate\AdditionalCustomerInfo;
use App\Models\Authenticate\ModuleSegmentRequirement;
use App\Models\Entities\Customers\Customer;
use App\Models\Entities\Users\CustomerUserLogin;
use App\Models\Entities\Users\User;
use App\Models\Modules\Module;
use App\Models\Modules\UserModuleControl;
use App\Models\Modules\UserModulePermission;
use App\Traits\CreateCustomerAsaas;
use App\Traits\RoleCheckTrait;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdditionalCustomerInfoController extends Controller
{
    use CreateCustomerAsaas, RoleCheckTrait;

    protected $guard;
    protected $user;
    protected $module;
    protected $customerUserLogin;
    protected $customer;

    public function __construct(StatefulGuard $guard, Module $module, User $user, CustomerUserLogin $customerUserLogin, Customer $customer)
    {
        $this->guard = $guard;
        $this->module = $module;
        $this->user = $user;
        $this->customerUserLogin = $customerUserLogin;
        $this->customer = $customer;
    }

    public function additionalInfoIndex()
    {
        $user = auth()->user();

        if (AdditionalCustomerInfo::where('user_id', $user->id)->exists()) {
            return redirect()->route('company-segment.index');
        }

        return view('auth.additional_customer_info_index', compact('user'));
    }


    public function additionalInfoIndexStore(StoreAdditionalCustomerInfoRequest $request)
    {
        $user = auth()->user();

        // JÁ EXISTE ONBOARDING? então só atualiza e vai pra próxima tela
        $existingLogin = $this->customerUserLogin
            ->where('user_id', $user->id)
            ->first();

        if ($existingLogin) {

            // Atualiza avatar se veio de novo
            if ($request->hasFile('image')) {
                $userImage = $request->file('image');
                $imageData = file_get_contents($userImage->getRealPath());
                $image = imagecreatefromstring($imageData);

                if ($image !== false) {
                    $w = 250;
                    $h = 250;
                    $resizedImage = imagescale($image, $w, $h);

                    ob_start();
                    imagejpeg($resizedImage);
                    $rawImage = ob_get_clean();

                    $imagemBase64 = base64_encode($rawImage);

                    imagedestroy($resizedImage);
                    imagedestroy($image);

                    $user->update([
                        'image'      => $imagemBase64,
                        'updated_at' => Carbon::now()
                    ]);
                }
            }

            // Atualiza dados básicos do Customer (se quiser refletir alterações)
            $customer = $this->customer->find($existingLogin->customer_id);
            if ($customer) {
                $customer->update([
                    'name'          => $user->name,
                    'cpfCnpj'       => $request->cpfCnpj,
                    'mobilePhone'   => $request->mobilePhone,
                    'address'       => $request->address,
                    'addressNumber' => $request->addressNumber,
                    'postalCode'    => $request->postalCode,
                    'cityName'      => $request->cityName,
                    'state'         => $request->state,
                    'province'      => $request->province,
                    'complement'    => $request->complement,
                    'company_name'  => $request->company_name,
                    'company_email' => $request->company_email,
                ]);
            }

            // Garante que AdditionalCustomerInfo existe e só atualiza
            AdditionalCustomerInfo::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'customer_sistapp_id' => $existingLogin->customer_sistapp_id,
                    'website_url'         => $request->website_url,
                ]
            );

            return redirect()->route('company-segment.index');
        }

        // ==========================
        // PRIMEIRO ACESSO (fluxo original)
        // ==========================

        $customerData = [
            'name'          => ucwords($request->company_name),
            'cpfCnpj'       => $request->cpfCnpj,
            'mobilePhone'   => $request->mobilePhone,
            'address'       => $request->address,
            'addressNumber' => $request->addressNumber,
            'postalCode'    => $request->postalCode,
            'company_email' => $request->company_email
        ];

        $customerId = $this->createCustomer($customerData);

        if (is_null($customerId)) {
            return back()->with('error', 'Erro ao cadastrar seu usuário no sistema. Por favor, entre em contato com o suporte ou tente novamente mais tarde!');
        }

        // avatar (igual você já tinha)
        if ($request->hasFile('image')) {
            $userImage = $request->file('image');
            $imageData = file_get_contents($userImage->getRealPath());
            $image = imagecreatefromstring($imageData);

            if ($image !== false) {
                $w = 250;
                $h = 250;
                $resizedImage = imagescale($image, $w, $h);

                ob_start();
                imagejpeg($resizedImage);
                $rawImage = ob_get_clean();

                $imagemBase64 = base64_encode($rawImage);

                imagedestroy($resizedImage);
                imagedestroy($image);

                $user->update([
                    'image'      => $imagemBase64,
                    'updated_at' => Carbon::now()
                ]);
            }
        }

        $customerSistappId = generateCustomerSistappId($this->customerUserLogin);

        $customer = $this->customer->create([
            'customer_sistapp_id' => $customerSistappId,
            'customerId'          => $customerId,
            'name'                => $user->name,
            'cpfCnpj'             => $request->cpfCnpj,
            'mobilePhone'         => $request->mobilePhone,
            'address'             => $request->address,
            'addressNumber'       => $request->addressNumber,
            'postalCode'          => $request->postalCode,
            'cityName'            => $request->cityName,
            'state'               => $request->state,
            'province'            => $request->province,
            'complement'          => $request->complement,
            'company_name'        => $request->company_name,
            'company_email'       => $request->company_email,
            'is_active'           => 1,
            'created_at'          => Carbon::now()
        ]);

        $this->customerUserLogin->create([
            'user_id'             => $user->id,
            'customer_id'         => $customer->id,
            'customer_sistapp_id' => $customerSistappId,
            'trial_ends_at'       => Carbon::now()->addDays(14),
            'subscription'        => false,
            'is_master_customer'  => true,
            'created_at'          => Carbon::now()
        ]);

        // aqui também já uso updateOrCreate pra nunca duplicar
        AdditionalCustomerInfo::updateOrCreate(
            ['user_id' => $user->id],
            [
                'customer_sistapp_id' => $customerSistappId,
                'website_url'         => $request->website_url,
            ]
        );

        return redirect()->route('company-segment.index');
    }

    public function segmentCompanyIndex()
    {
        $user = auth()->user();
        $selectedSegment = optional($user->additionalInfo)->segment;

        return view('auth.company_segment_index', compact('selectedSegment'));
    }


    public function segmentCompanyStore(Request $request)
    {
        $request->validate([
            'segment' => 'required|in:agencia,empresa,freelancer',
        ]);

        $user = auth()->user();

        AdditionalCustomerInfo::updateOrCreate(
            ['user_id' => $user->id], // garante 1 registro por usuário
            [
                // importante: não incluir website_url aqui para não apagar o valor salvo na etapa anterior
                'customer_sistapp_id' => optional($user->customerLogin)->customer_sistapp_id,
                'segment'             => $request->segment,
            ]
        );

        return redirect()->route('addons.index');
    }


    public function addonsIndex()
    {
        $user    = auth()->user();
        $segment = optional($user->additionalInfo)->segment; // agencia|empresa|freelancer|null

        $modules = Module::where('is_active', true)->get();

        $requiredIds = collect();
        if ($segment) {
            $requiredIds = ModuleSegmentRequirement::where('segment', $segment)
                ->where('is_required', true)
                ->pluck('module_id')
                ->map(fn($id) => (string) $id);
        }

        return view('auth.addons_index', compact('modules', 'requiredIds'));
    }

    public function addonsStore(StoreAdditionalCustomerInfoRequest $request)
    {
        $user = auth()->user();

        $selectedIds = collect(is_string($request->selected) ? json_decode($request->selected, true) : (array)$request->selected)
            ->map(fn($v)=>(int)$v)->filter()->unique()->values();

        if ($selectedIds->isEmpty()) {
            return back()->withErrors(['selected' => 'Selecione ao menos um módulo.']);
        }

        // merge com obrigatórios
        $segment = optional($user->additionalInfo)->segment;
        if ($segment) {
            $required = ModuleSegmentRequirement::where('segment',$segment)->where('is_required',true)->pluck('module_id');
            $selectedIds = $selectedIds->merge($required)->unique()->values();
        }

        $cycle = 'monthly';                     // ← fixo
        $total = (float)$request->total;

        DB::transaction(function () use ($user, $selectedIds, $cycle, $total) {
            $modules = Module::with('features')->whereIn('id', $selectedIds)->get();

            foreach ($modules as $mod) {
                $ump = UserModulePermission::firstOrCreate([
                    'user_id'             => $user->id,
                    'module_id'           => $mod->id,
                    'customer_sistapp_id' => optional($user->customerLogin)->customer_sistapp_id,
                ]);

                // opcional: garantir expires_at = trial_ends_at se estiver nulo
                if (is_null($ump->expires_at) && optional($user->customerLogin)->trial_ends_at) {
                    $ump->update(['expires_at' => $user->customerLogin->trial_ends_at]);
                }

                UserModuleControl::create([
                    'user_module_permission_id' => $ump->id,
                    'cycle'           => $cycle,   // ← monthly
                    'total'           => $total,   // se quiser por módulo, ajuste aqui
                    'contracted_date' => now(),
                    'month_reference' => now()->month,
                    'year_reference'  => now()->year,
                ]);

                foreach ($mod->features as $feature) {
                    \App\Models\Modules\UserFeature::firstOrCreate(
                        ['user_module_permission_id' => $ump->id, 'feature_id' => $feature->id],
                        ['is_active' => true]
                    );
                }
            }

            $roles = $modules->pluck('features')->flatten()->pluck('roles')->flatten()->filter()->unique()->all();
            if (!empty($roles)) {
                $user->syncRoles($roles);
            }
        });

        return redirect()->route('dashboard');
    }
}
