<?php

namespace App\Http\Controllers\Application\Entities\Suppliers\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Entities\Suppliers\Supplier;
use App\Traits\CrudResponse;
use App\Traits\WebIndex;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    use CrudResponse, WebIndex;

    public $supplier;

    public function __construct(Supplier $supplier)
    {
        $this->supplier = $supplier;
    }

    public function view()
    {
        return $this->webRoute('app.entities.supplier.supplier_index', 'supplier');
    }

    public function index(Request $request)
    {
        $q = $this->supplier->query()->orderBy('name');

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

    public function store(StoreSupplierRequest $request)
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

        return $this->storeMethod($this->supplier, $data);
    }

    public function show($id)
    {
        return $this->showMethod($this->supplier->find($id));
    }

    public function update(UpdateSupplierRequest $request, $id)
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

        return $this->updateMethod($this->supplier->find($id), $data);
    }

    public function destroy($id)
    {
        return $this->destroyMethod($this->supplier->find($id));
    }
}
