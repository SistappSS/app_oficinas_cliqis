<?php

namespace App\Http\Controllers\Application\Catalogs;

use App\Http\Controllers\Controller;
use App\Models\Catalogs\Equipments\Equipment;
use App\Traits\CrudResponse;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    use CrudResponse, RoleCheckTrait, WebIndex;

    protected Equipment $equipment;

    public function __construct(Equipment $equipment)
    {
        $this->equipment = $equipment;
    }

    public function view()
    {
        return $this->webRoute('app.catalog.equipment.equipment_index', 'equipment');
    }

    public function index(Request $request)
    {
        $q = $this->equipment->query()->orderBy('name');

        if ($term = trim($request->input('q', ''))) {
            $q->where(function ($w) use ($term) {
                $w->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('serial_number', 'like', "%{$term}%");
            });
        }

        $data = $q->paginate(20);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'          => ['nullable', 'string', 'max:255'],
            'name'          => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'notes'         => ['nullable', 'string'],
        ]);

        return $this->storeMethod($this->equipment, $validated);
    }

    public function show(string $id)
    {
        return $this->showMethod($this->equipment->find($id));
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'code'          => ['nullable', 'string', 'max:255'],
            'name'          => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'notes'         => ['nullable', 'string'],
        ]);

        return $this->updateMethod($this->equipment->find($id), $validated);
    }

    public function destroy(string $id)
    {
        return $this->destroyMethod($this->equipment->find($id));
    }
}
