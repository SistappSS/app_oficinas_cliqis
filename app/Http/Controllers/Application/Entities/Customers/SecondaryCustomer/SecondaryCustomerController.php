<?php

namespace App\Http\Controllers\Application\Entities\Customers\SecondaryCustomer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entities\Customers\SecondaryCustomer\StoreSecondaryCustomerRequest;
use App\Http\Requests\Entities\Customers\UpdateCustomerRequest;
use App\Models\Entities\Customers\SecondaryCustomer;
use App\Models\Entities\Users\CustomerUserLogin;
use App\Models\Entities\Users\User;
use App\Traits\CrudResponse;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SecondaryCustomerController extends Controller
{
    use CrudResponse, RoleCheckTrait, WebIndex;

    public $user;
    public $customer;
    public $customerUserLogin;

    public function __construct(User $user, SecondaryCustomer $customer, CustomerUserLogin $customerUserLogin)
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
        $q = $this->customer->query()->orderBy('name'); // HasCustomerScope já filtra por tenant

        // busca opcional
        if ($term = trim($request->input('q', ''))) {
            $q->where(function($w) use ($term) {
                $w->where('name','like',"%{$term}%")
                    ->orWhere('cpfCnpj','like',"%{$term}%")
                    ->orWhere('email','like',"%{$term}%");
            });
        }

        $data = $q->paginate(20);

        return response()->json($data);
    }

    public function store(StoreSecondaryCustomerRequest $request)
    {
        $request->validated();

        $customerSistappId = Auth::user()->customerLogin->customer_sistapp_id;

        $data = [
            'customer_sistapp_id' => $customerSistappId,
            'name' => $request->name,
            'cpfCnpj' => $request->cpfCnpj,
            'mobilePhone' => $request->mobilePhone,
            'email' => $request->email,
            'address' => $request->address,
            'addressNumber' => $request->addressNumber,
            'postalCode' => $request->postalCode,
            'cityName' => $request->cityName,
            'state' => $request->state,
            'province' => $request->province,
            'complement' => $request->complement
        ];

        return $this->trait("store", $this->customer->create($data));
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
            'email' => $request->email,
            'address' => $request->address,
            'addressNumber' => $request->addressNumber,
            'postalCode' => $request->postalCode,
            'cityName' => $request->cityName,
            'state' => $request->state,
            'province' => $request->province,
            'complement' => $request->complement
        ];

        return $this->updateMethod($this->customer->find($id), $data);
    }

    public function destroy($id)
    {
        return $this->destroyMethod($this->customer->find($id));
    }
}
