<?php

namespace App\Http\Controllers\Application\Catalogs;

use App\Http\Controllers\Controller;
use App\Models\Catalogs\Parts\Part;
use App\Traits\CrudResponse;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PartController extends Controller
{
    use CrudResponse, RoleCheckTrait, WebIndex;

    protected Part $part;

    public function __construct(Part $part)
    {
        $this->part = $part;
    }

    public function view()
    {
        return $this->webRoute('app.catalogs.part.part_index', 'part');
    }

    public function index(Request $request)
    {
        $q = $this->part
            ->query()
            ->with(['supplier:id,name', 'equipments:id,name']) // <- carrega equipamentos
            ->orderBy('name');

        if ($term = trim($request->input('q', ''))) {
            $q->where(function ($w) use ($term) {
                $w->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%")
                    ->orWhere('ncm_code', 'like', "%{$term}%");
            });
        }

        $data = $q->paginate(20);

        return response()->json($data);
    }

    public function show(string $id)
    {
        $part = $this->part
            ->with(['supplier:id,name', 'equipments:id,name,code'])
            ->find($id);

        return $this->showMethod($part);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => ['nullable', 'uuid', 'exists:suppliers,id'],
            'code'        => ['nullable', 'string', 'max:255'],
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'ncm_code'    => ['nullable', 'string', 'max:20'],
            'unit_price'  => ['nullable', 'numeric'],
            'is_active'   => ['boolean'],
        ]);

        return $this->storeMethod($this->part, $validated);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'supplier_id' => ['nullable', 'uuid', 'exists:suppliers,id'],
            'code'        => ['nullable', 'string', 'max:255'],
            'name'        => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'ncm_code'    => ['nullable', 'string', 'max:20'],
            'unit_price'  => ['nullable', 'numeric'],
            'is_active'   => ['nullable', 'boolean'],

            // NOVO: vincular equipamentos
            'equipment_ids'   => ['nullable', 'array'],
            'equipment_ids.*' => ['uuid', 'exists:equipments,id'],
        ]);

        $part = $this->part->findOrFail($id);

       // dd('oi');
        DB::transaction(function () use ($part, $validated) {
            $data = Arr::only($validated, [
                'supplier_id', 'code', 'name', 'description',
                'ncm_code', 'unit_price', 'is_active',
            ]);

            if (!empty($data)) {
                $part->update($data);
            }

            if (array_key_exists('equipment_ids', $validated)) {
                $part->equipments()->sync($validated['equipment_ids'] ?? []);
            }
        });

        // se quiser manter padrão do CrudResponse:
        return response()->json([
            'message' => 'Peça atualizada com sucesso.',
            'data'    => $part->fresh('equipments'),
        ]);
    }

    public function destroy(string $id)
    {
        return $this->destroyMethod($this->part->find($id));
    }
}
