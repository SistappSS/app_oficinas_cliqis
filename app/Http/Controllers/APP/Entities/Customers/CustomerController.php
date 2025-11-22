<?php

namespace App\Http\Controllers\APP\Entities\Customers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entities\Customers\StoreCustomerRequest;
use App\Http\Requests\Entities\Customers\UpdateCustomerRequest;
use App\Models\Entities\Customers\Customer;
use App\Models\Entities\Users\CustomerUserLogin;
use App\Models\Entities\Users\User;
use App\Traits\CreateCustomerAsaas;
use App\Traits\CrudResponse;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use function PHPUnit\Framework\isEmpty;

class CustomerController extends Controller
{
    use CrudResponse, RoleCheckTrait, CreateCustomerAsaas, WebIndex;

    public $user;
    public $customer;
    public $customerUserLogin;
    public function __construct(User $user, Customer $customer, CustomerUserLogin $customerUserLogin)
    {
        $this->user = $user;
        $this->customer = $customer;
        $this->customerUserLogin = $customerUserLogin;
    }

    public function view()
    {
        return $this->webRoute('app.entities.customer.customer_index', 'customer');
    }

    public function index(Request $request)
    {
        $q = Customer::query()->orderBy('name'); // HasCustomerScope já filtra por tenant

        // usuários NÃO admin veem apenas os clientes secundários
        if (!$this->userHasRole('admin')) {
            $q->whereNull('customerId'); // em vez de starts_with('cus_')
        }

        // busca opcional
        if ($term = trim($request->input('q', ''))) {
            $q->where(function($w) use ($term) {
                $w->where('name','like',"%{$term}%")
                    ->orWhere('cpfCnpj','like',"%{$term}%")
                    ->orWhere('email','like',"%{$term}%");
            });
        }

        // carrega info de login sem matar secundários
        $q->with(['logins' => fn($l) =>
        $l->select('id','customer_sistapp_id','subscription','trial_ends_at','is_master_customer')
        ]);

        $data = $q->paginate(20);

        return response()->json($data);
    }

    public function store(StoreCustomerRequest $request)
    {
        $request->validated();

        $isAdmin = $this->userHasRole('admin');

        $customerData = [
            'name' => $request->name,
            'cpfCnpj' => $request->cpfCnpj,
            'mobilePhone' => $request->mobilePhone,
            'address' => $request->address,
            'addressNumber' => $request->addressNumber,
            'postalCode' => $request->postalCode,
        ];

        if ($isAdmin && $request->role != 'admin') {
            // Gera customer_sistapp_id único para cliente principal
            $prefix = 'sist_';
            do {
                $randomNumber = mt_rand(100000, 999999);
                $customerSistappId = $prefix . $randomNumber;
            } while ($this->customerUserLogin->where('customer_sistapp_id', $customerSistappId)->exists());

            // Cria cliente no Asaas para obter customerId (cus_...)
            $customerId = $this->createCustomerIfAdmin($customerData);
        } else {
            // Cliente filho: herda customer_sistapp_id e customerId do usuário logado
            $customerSistappId = $this->customerSistappID();
            $customerId = null;
        }

        if ((bool) $request->is_access) {
            $email = $request->access_email ?? generateEmail($request->name);
            $password = $request->password ? Hash::make($request->password) : generatePassword($request->name, $request->mobilePhone);

            $user = $this->user->create([
                'name' => $customerData['name'],
                'email' => $email,
                'password' => $password,
                'is_active' => (bool) ($request->is_active ?? true),
                'created_at' => Carbon::now(),
            ])->assignRole($this->determineUserRole($request));

            $this->customerUserLogin->create([
                'user_id' => $user->id,
                'customer_sistapp_id' => $customerSistappId,
                'trial_ends_at' => $this->trialEnds(),
                'subscription' => $this->subscription(),
                'is_master_customer' => $isAdmin,
            ]);
        }

        $newData = array_merge($customerData, [
            'customer_sistapp_id' => $customerSistappId,
            'customerId' => $customerId,
            'cityName' => $request->cityName,
            'state' => $request->state,
            'province' => $request->province,
            'company_email' => $request->company_email
        ]);

        return $this->trait("store", $this->customer->create($newData));
    }

    public function show($id)
    {
        return $this->showMethod($this->customer->find($id));
    }

    public function update(UpdateCustomerRequest $request, $id)
    {
        $request->validated();

        $data = [
            'name' => $request->name,
            'cpfCnpj' => $request->cpfCnpj,
            'mobilePhone' => $request->mobilePhone,

            'address' => $request->address,
            'addressNumber' => $request->addressNumber,
            'postalCode' => $request->postalCode,

            'cityName' => $request->cityName,
            'state' => $request->state,
            'province' => $request->province,

            'is_active' => (bool)$request->is_active,

            'company_email' => $request->company_email,

            'updated_at' => Carbon::now(),
        ];

        return $this->updateMethod($this->customer->find($id), $data);
    }

    public function destroy($id)
    {
        return $this->destroyMethod($this->customer->find($id));
    }
}
