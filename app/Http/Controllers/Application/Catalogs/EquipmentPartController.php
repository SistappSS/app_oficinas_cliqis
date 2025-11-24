<?php

namespace App\Http\Controllers\Application\Catalogs;

use App\Http\Controllers\Controller;
use App\Models\EquipmentPart;
use App\Traits\CrudResponse;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Illuminate\Http\Request;

class EquipmentPartController extends Controller
{
    use CrudResponse, RoleCheckTrait, WebIndex;

    protected EquipmentPart $equipmentPart;

    public function __construct(EquipmentPart $equipmentPart)
    {
        $this->equipmentPart = $equipmentPart;
    }

    public function view()
    {
        return $this->webRoute('app.catalog.equipment_part.equipment_part_index', 'equipment-part');
    }

    public function index(Request $request)
    {
        $q = $this->equipmentPart->query()
            ->with(['equipment', 'part'])
            ->orderByDesc('created_at');

        if ($term = trim($request->input('q', ''))) {
            $q->whereHas('equipment', function ($w) use ($term) {
                $w->where('name', 'like', "%{$term}%");
            })->orWhereHas('part', function ($w) use ($term) {
                $w->where('name', 'like', "%{$term}%");
            });
        }

        $data = $q->paginate(20);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'equipment_id' => ['required', 'uuid'],
            'part_id'      => ['required', 'uuid'],
        ]);

        return $this->storeMethod($this->equipmentPart, $validated);
    }

    public function show(string $id)
    {
        return $this->showMethod($this->equipmentPart->with(['equipment', 'part'])->find($id));
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'equipment_id' => ['required', 'uuid'],
            'part_id'      => ['required', 'uuid'],
        ]);

        return $this->updateMethod($this->equipmentPart->find($id), $validated);
    }

    public function destroy(string $id)
    {
        return $this->destroyMethod($this->equipmentPart->find($id));
    }
}
