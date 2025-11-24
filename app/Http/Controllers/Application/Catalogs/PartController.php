<?php

namespace App\Http\Controllers\Application\Catalogs;

use App\Http\Controllers\Controller;
use App\Models\Catalogs\Parts\Part;
use App\Traits\CrudResponse;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Illuminate\Http\Request;

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
        return $this->webRoute('app.catalog.part.part_index', 'part');
    }

    public function index(Request $request)
    {
        $q = $this->part->query()->orderBy('name');

        if ($term = trim($request->input('q', ''))) {
            $q->where(function ($w) use ($term) {
                $w->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('ncm_code', 'like', "%{$term}%");
            });
        }

        $data = $q->paginate(20);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id'  => ['nullable', 'uuid'],
            'code'         => ['nullable', 'string', 'max:255'],
            'name'         => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'ncm_code'     => ['nullable', 'string', 'max:20'],
            'unit_price'   => ['nullable', 'numeric'],
            'is_active'    => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = (bool)($validated['is_active'] ?? true);

        return $this->storeMethod($this->part, $validated);
    }

    public function show(string $id)
    {
        return $this->showMethod($this->part->find($id));
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'supplier_id'  => ['nullable', 'uuid'],
            'code'         => ['nullable', 'string', 'max:255'],
            'name'         => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'ncm_code'     => ['nullable', 'string', 'max:20'],
            'unit_price'   => ['nullable', 'numeric'],
            'is_active'    => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = (bool)($validated['is_active'] ?? true);

        return $this->updateMethod($this->part->find($id), $validated);
    }

    public function destroy(string $id)
    {
        return $this->destroyMethod($this->part->find($id));
    }
}
