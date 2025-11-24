<?php

namespace App\Http\Controllers\Application\HumanResources;

use App\Http\Controllers\Controller;
use App\Models\Benefit;
use App\Traits\CrudResponse;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Illuminate\Http\Request;

class BenefitController extends Controller
{
    use CrudResponse, RoleCheckTrait, WebIndex;

    protected Benefit $benefit;

    public function __construct(Benefit $benefit)
    {
        $this->benefit = $benefit;
    }

    public function view()
    {
        return $this->webRoute('app.human_resources.benefit.benefit_index', 'benefit');
    }

    public function index(Request $request)
    {
        $q = $this->benefit->query()->orderBy('name');

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
        ]);

        return $this->storeMethod($this->benefit, $validated);
    }

    public function show(string $id)
    {
        return $this->showMethod($this->benefit->find($id));
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        return $this->updateMethod($this->benefit->find($id), $validated);
    }

    public function destroy(string $id)
    {
        return $this->destroyMethod($this->benefit->find($id));
    }
}
