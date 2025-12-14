<?php

namespace App\Http\Controllers\Application\Services;

use App\Http\Controllers\Controller;
use App\Models\Catalogs\Services\ServiceTypes\ServiceType;
use App\Traits\CrudResponse;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Illuminate\Http\Request;

class ServiceTypeController extends Controller
{
    use CrudResponse, RoleCheckTrait, WebIndex;

    protected ServiceType $serviceType;

    public function __construct(ServiceType $serviceType)
    {
        $this->serviceType = $serviceType;
    }

    public function view()
    {
        return $this->webRoute('app.catalogs.service_type.service_type_index', 'service-type');
    }

    public function index(Request $request)
    {
        $q = $this->serviceType->query()->orderBy('name');

        if ($term = trim($request->input('q', ''))) {
            $q->where(function ($w) use ($term) {
                $w->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        $data = $q->paginate(20);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = (bool)($validated['is_active'] ?? true);
        $validated['customer_sistapp_id'] = $this->employeeSistappID();

        return $this->trait("store", $this->serviceType->create($validated));
    }

    public function show(string $id)
    {
        return $this->showMethod($this->serviceType->find($id));
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = (bool)($validated['is_active'] ?? true);

        return $this->updateMethod($this->serviceType->find($id), $validated);
    }

    public function destroy(string $id)
    {
        return $this->destroyMethod($this->serviceType->find($id));
    }
}
