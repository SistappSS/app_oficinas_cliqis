<?php

namespace App\Http\Controllers\Application\Catalogs;

use App\Http\Controllers\Controller;
use App\Models\Catalogs\Equipments\Equipment;
use App\Models\Catalogs\Equipments\EquipmentExtraInfos\EquipmentExtraInfo;
use App\Traits\CrudResponse;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EquipmentController extends Controller
{
    use CrudResponse, RoleCheckTrait, WebIndex;

    protected Equipment $equipment;
    protected EquipmentExtraInfo $extraInfo;

    public function __construct(Equipment $equipment, EquipmentExtraInfo $extraInfo)
    {
        $this->equipment = $equipment;
        $this->extraInfo = $extraInfo;
    }

    public function view()
    {
        return $this->webRoute('app.catalogs.equipment.equipment_index', 'equipment');
    }

    public function index(Request $request)
    {
        $q = $this->equipment
            ->query()
            ->with([
                'extraInfo',
                'parts:id,name', // lista de peças
            ])
            ->withCount('parts') // parts_count
            ->orderBy('name');

        if ($term = trim($request->input('q', ''))) {
            $q->where(function ($w) use ($term) {
                $w->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('serial_number', 'like', "%{$term}%");
            });
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 20)));
        $data = $q->paginate($perPage);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Equipment
            'code'          => ['nullable', 'string', 'max:255'],
            'name'          => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'notes'         => ['nullable', 'string'],

            // EquipmentExtraInfo (base64 em JSON)
            'extra_image_path' => ['nullable', 'array'], // {mime, data, name, size}
            'extra_iframe_url' => ['nullable', 'string'],
            'extra_notes'      => ['nullable', 'string'],

            // peças vinculadas (se quiser cadastrar daqui também no futuro)
            'part_ids'   => ['nullable', 'array'],
            'part_ids.*' => ['uuid', 'exists:parts,id'],
        ]);

        $equipment = DB::transaction(function () use ($validated) {
            // tabela equipments
            $equipmentData = Arr::only($validated, [
                'code',
                'name',
                'description',
                'serial_number',
                'notes',
            ]);

            $equipment = $this->equipment->create($equipmentData);

            // extra infos
            $imagePayload = $validated['extra_image_path'] ?? null;
            $extraData = [
                'image_path' => $imagePayload,                       // JSON com base64
                'iframe_url' => $validated['extra_iframe_url'] ?? null,
                'notes'      => $validated['extra_notes'] ?? null,
            ];

            $hasExtra = $extraData['image_path'] || $extraData['iframe_url'] || $extraData['notes'];

            if ($hasExtra) {
                $equipment->extraInfo()->create($extraData);
            }

            // peças vinculadas (se vier)
            if (!empty($validated['part_ids'])) {
                $equipment->parts()->sync($validated['part_ids']);
            }

            return $equipment->load(['extraInfo', 'parts']);
        });

        return response()->json([
            'message' => 'Equipamento criado com sucesso.',
            'data'    => $equipment,
        ]);
    }

    public function show(string $id)
    {
        $equipment = $this->equipment
            ->with(['extraInfo', 'parts'])
            ->find($id);

        return $this->showMethod($equipment);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            // Equipment
            'code'          => ['nullable', 'string', 'max:255'],
            'name'          => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'notes'         => ['nullable', 'string'],

            // EquipmentExtraInfo
            'extra_image_path' => ['nullable', 'array'], // só manda se trocar a imagem
            'extra_iframe_url' => ['nullable', 'string'],
            'extra_notes'      => ['nullable', 'string'],

            // peças vinculadas (opcional)
            'part_ids'   => ['nullable', 'array'],
            'part_ids.*' => ['uuid', 'exists:parts,id'],
        ]);

        $equipment = $this->equipment->findOrFail($id);

        $equipment = DB::transaction(function () use ($equipment, $validated) {
            // update em equipments
            $equipmentData = Arr::only($validated, [
                'code',
                'name',
                'description',
                'serial_number',
                'notes',
            ]);

            $equipment->update($equipmentData);

            // extra infos
            $imagePayload = $validated['extra_image_path'] ?? null;

            $extraData = [
                'image_path' => $imagePayload,
                'iframe_url' => $validated['extra_iframe_url'] ?? null,
                'notes'      => $validated['extra_notes'] ?? null,
            ];

            $hasExtra = $extraData['image_path'] || $extraData['iframe_url'] || $extraData['notes'];

            if ($equipment->extraInfo) {
                if ($hasExtra) {
                    $equipment->extraInfo->update($extraData);
                }
                // se quiser apagar quando tudo vier vazio, pode ativar:
                // elseif (!$hasExtra) { $equipment->extraInfo->delete(); }
            } else {
                if ($hasExtra) {
                    $equipment->extraInfo()->create($extraData);
                }
            }

            // update de peças, só se o front mandar part_ids
            if (array_key_exists('part_ids', $validated)) {
                $equipment->parts()->sync($validated['part_ids'] ?? []);
            }

            return $equipment->load(['extraInfo', 'parts']);
        });

        return response()->json([
            'message' => 'Equipamento atualizado com sucesso.',
            'data'    => $equipment,
        ]);
    }

    public function destroy(string $id)
    {
        $equipment = $this->equipment->find($id);

        return $this->destroyMethod($equipment);
    }
}
