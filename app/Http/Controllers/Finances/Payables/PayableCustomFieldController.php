<?php

namespace App\Http\Controllers\Finances\Payables;

use App\Http\Controllers\Controller;
use App\Models\Finances\Payables\PayableCustomField;
use App\Traits\RoleCheckTrait;
use Illuminate\Http\Request;

class PayableCustomFieldController extends Controller
{
    use RoleCheckTrait;

    public function index(Request $r)
    {
        $tenantId = $this->customerSistappID();

        $rows = PayableCustomField::query()
            ->where('customer_sistapp_id', $tenantId)
            ->orderBy('active', 'desc')
            ->orderBy('name', 'asc')
            ->get()
            ->map(fn($f) => [
                'id' => $f->id,
                'name' => $f->name,
                'type' => $f->type,
                'active' => (bool)$f->active,
            ]);

        return response()->json(['data' => $rows]);
    }

    public function store(Request $r)
    {
        $tenantId = $this->customerSistappID();

        $data = $r->validate([
            'name' => ['required','string','max:80'],
            'type' => ['required','in:deduct,add'],
            'active' => ['nullable','boolean'],
        ]);

        $field = PayableCustomField::create([
            'customer_sistapp_id' => $tenantId,
            'created_by' => auth()->id(),
            'name' => trim($data['name']),
            'type' => $data['type'],
            'active' => $data['active'] ?? true,
        ]);

        return response()->json(['ok' => true, 'id' => $field->id]);
    }

    public function update(Request $r, $id)
    {
        $tenantId = $this->customerSistappID();

        $data = $r->validate([
            'name' => ['required','string','max:80'],
            'type' => ['required','in:deduct,add'],
            'active' => ['nullable','boolean'],
        ]);

        $field = PayableCustomField::where('customer_sistapp_id', $tenantId)->findOrFail($id);

        $field->update([
            'name' => trim($data['name']),
            'type' => $data['type'],
            'active' => $data['active'] ?? $field->active,
        ]);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $r, $id)
    {
        $tenantId = $this->customerSistappID();

        $field = PayableCustomField::where('customer_sistapp_id', $tenantId)->findOrFail($id);
        $field->delete();

        return response()->json(['ok' => true]);
    }

    public function toggle(Request $r, $id)
    {
        $tenantId = $this->customerSistappID();

        $field = PayableCustomField::where('customer_sistapp_id', $tenantId)->findOrFail($id);
        $field->update(['active' => ! $field->active]);

        return response()->json(['ok' => true, 'active' => (bool)$field->active]);
    }
}
