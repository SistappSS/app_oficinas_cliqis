<?php

namespace App\Http\Controllers\APP\Sales\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\Services\StoreServiceRequest;
use App\Http\Requests\Sales\Services\UpdateServiceRequest;
use App\Models\Sales\Services\Service;
use App\Traits\CrudResponse;
use App\Traits\HttpResponse;
use App\Traits\WebIndex;

class ServiceController extends Controller
{
    use CrudResponse, HttpResponse, WebIndex;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    public function view()
    {
        return $this->webRoute('app.sales.service.service_index', 'service');
    }

    public function index()
    {
        return $this->indexMethod($this->service->get());
    }

    public
    function store(StoreServiceRequest $request)
    {
        $request->validated();

        $data = [
            'name' => $request->name,
            'price' => decimalPrice($request->price),
            'description' => $request->description,
            'type' => $request->type
        ];

        return $this->storeMethod($this->service, $data);
    }

    public
    function show($id)
    {
        return $this->showMethod($this->service->find($id), 'user');
    }

    public
    function update(UpdateServiceRequest $request, $id)
    {
        $request->validated();

        $data = [
            'name' => $request->name,
            'price' => $request->price,
            'description' => $request->description,
            'type' => $request->type
        ];

        return $this->updateMethod($this->service->find($id), $data);
    }

    public
    function destroy($id)
    {
        return $this->destroyMethod($this->service->find($id));
    }
}
