<?php

namespace App\Http\Controllers\Endpoint\Modules;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\StoreModuleRequest;
use App\Http\Requests\Modules\UpdateModuleRequest;
use App\Models\Modules\Module;
use App\Traits\CrudResponse;
use App\Traits\RoleCheckTrait;
use Illuminate\Support\Facades\Auth;

class ModuleController extends Controller
{
    use CrudResponse, RoleCheckTrait;

    public function __construct(Module $module) {
        $this->module = $module;
    }

    public function index()
    {
        return $this->indexMethod($this->module->with('features')->get());
    }

    public function store(StoreModuleRequest $request)
    {
        $request->validated();

        $data = [
            'user_id' => Auth::user()->id,
            'customer_sistapp_id' => $this->customerSistappID(),
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'is_active' => (bool) $request->is_active,
            'icon' => $request->icon,
            'required_for_segments' => $request->input('required_segments', [])
        ];

        return $this->storeMethod($this->module, $data);
    }


    public function show($id)
    {
        return $this->showMethod($this->module->find($id));
    }

    public function update(UpdateModuleRequest $request, $id)
    {
        $request->validated();

        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'permission' => $request->permission,
            'is_active' => (bool) $request->is_active,
            'required_for_segments' => $request->input('required_segments', [])
        ];

        return $this->updateMethod($this->module->find($id), $data);
    }

    public function destroy($id)
    {
        $module = $this->module->find($id);

        if ($module === null) {
            return $this->trait("error");
        } else {

            $module->delete();

            return $this->trait("delete", $module);
        }
    }
}
