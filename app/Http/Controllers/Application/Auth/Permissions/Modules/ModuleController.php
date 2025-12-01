<?php

namespace App\Http\Controllers\Application\Auth\Permissions\Modules;

use App\Http\Controllers\Controller;
use App\Models\Authenticate\ModuleSegmentRequirement;
use App\Models\Modules\Feature;
use App\Models\Modules\Module;
use App\Traits\RoleCheckTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ModuleController extends Controller
{
    use RoleCheckTrait;

    public $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public function index()
    {
        $modules  = Module::with(['features','segmentRequirements'])->latest()->get();
        $segments = config('segments', ['authorized']);

        return view('app.modules.module.module_index', compact('modules','segments'));
    }

    public function store(Request $request)
    {
        $segments = config('segments');

        $data = $request->validate([
            'name'        => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'price'       => ['required','numeric','min:0'],
            'icon'        => ['nullable','string','max:255'],
            'is_active'   => ['boolean'],

            // novos campos do formulário:
            'required_segments'   => ['array'],
            'required_segments.*' => ['string', Rule::in($segments)],
        ]);

        $module = Module::create([
            'user_id' => Auth::user()->id,
            'customer_sistapp_id' => $this->customerSistappID(),
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'price'       => $data['price'],
            'icon'        => $data['icon'] ?? null,
            'is_active'   => $data['is_active'] ?? false,
        ]);

        $selected = collect($data['required_segments'] ?? [])->unique()->values();

        // limpa e recria (simples e seguro)
        ModuleSegmentRequirement::where('module_id', $module->id)->delete();
        foreach ($selected as $seg) {
            ModuleSegmentRequirement::create([
                'module_id'  => $module->id,
                'segment'    => $seg,
                'is_required'=> true,
            ]);
        }

        return redirect()->route('module.index')->with('success','Módulo criado.');
    }

    public function update(Request $request, $id)
    {
        $segments = config('segments', ['agencia','empresa','freelancer']);

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'nullable|numeric|min:0',
            'icon'        => 'nullable|string|max:255',
            'is_active'   => 'nullable|boolean',
            'required_segments'   => 'array',
            'required_segments.*' => 'in:'.implode(',', $segments),
        ]);

        $module = Module::findOrFail($id);

        $module->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'price'       => $data['price'] ?? 0,
            'icon'        => $data['icon'] ?? null,
            'is_active'   => (bool)($data['is_active'] ?? false),
        ]);

        // Sincroniza a pivot: remove os que saíram, cria os novos
        $incoming = collect($data['required_segments'] ?? [])->unique()->values();
        $existing = $module->segmentRequirements()->pluck('segment');

        // remove
        $module->segmentRequirements()
            ->whereNotIn('segment', $incoming)->delete();

        // adiciona
        $toInsert = $incoming->diff($existing)->map(fn($s)=>[
            'segment'=>$s,'is_required'=>true,'created_at'=>now(),'updated_at'=>now()
        ])->values()->all();

        if ($toInsert) {
            $module->segmentRequirements()->insert($toInsert);
        }

        return back()->with('success','Módulo atualizado.');
    }

    public function storeFeature(Request $request)
    {
        $validated = $request->validate([
            'module_id'   => 'required|exists:modules,id',
            'name'        => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'roles'       => 'nullable|array',
            'is_required' => 'nullable|boolean', // <- novo
        ]);

        Feature::create([
            'module_id'   => $validated['module_id'],
            'name'        => $validated['name'],
            'price'       => $validated['price'],
            'roles'       => $validated['roles'] ?? [],
            'is_required' => (bool) ($validated['is_required'] ?? false), // <- novo
        ]);

        return redirect()->route('module.index')->with('success', 'Feature criada com sucesso!');
    }
}
