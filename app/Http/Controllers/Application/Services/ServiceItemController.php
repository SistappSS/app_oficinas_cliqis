<?php

namespace App\Http\Controllers\Application\Services;

use App\Http\Controllers\Controller;
use App\Models\Catalogs\Services\ServiceItems\ServiceItem;
use App\Traits\CrudResponse;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Illuminate\Http\Request;

class ServiceItemController extends Controller
{
    use CrudResponse, RoleCheckTrait, WebIndex;

    protected ServiceItem $serviceItem;

    public function __construct(ServiceItem $serviceItem)
    {
        $this->serviceItem = $serviceItem;
    }

    public function view()
    {
        return $this->webRoute('app.catalogs.service_item.service_item_index', 'service-item');
    }

    public function index(Request $request)
    {
        $q = $this->serviceItem->query()
            ->with('serviceType')
            ->orderBy('name');

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
            'service_type_id' => ['nullable', 'uuid'],
            'name'            => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'unit_price'      => ['nullable', 'numeric'],
            'is_active'       => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = (bool)($validated['is_active'] ?? true);
        $validated['customer_sistapp_id'] = $this->employeeSistappID();

        return $this->trait("store", $this->serviceItem->create($validated));

    }

    public function show(string $id)
    {
        return $this->showMethod($this->serviceItem->with('serviceType')->find($id));
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'service_type_id' => ['nullable', 'uuid'],
            'name'            => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'unit_price'      => ['nullable', 'numeric'],
            'is_active'       => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = (bool)($validated['is_active'] ?? true);

        return $this->updateMethod($this->serviceItem->find($id), $validated);
    }

    public function destroy(string $id)
    {
        return $this->destroyMethod($this->serviceItem->find($id));
    }
}
