<?php

namespace App\Http\Controllers\Finances\Payables;

use App\Http\Controllers\Controller;
use App\Models\Finances\Payables\PayableCustomField;
use App\Support\Audit\Payables\PayablesAudit;
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

        PayablesAudit::log(
            $tenantId,
            (string) auth()->id(),
            'custom_field',
            'created',
            (string) $field->id,
            null,
            [
                'id' => (string) $field->id,
                'name' => $field->name,
                'type' => $field->type,
                'active' => (bool) $field->active,
            ]
        );

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

        $before = [
            'id' => (string) $field->id,
            'name' => $field->name,
            'type' => $field->type,
            'active' => (bool) $field->active,
        ];

        $field->update([
            'name' => trim($data['name']),
            'type' => $data['type'],
            'active' => $data['active'] ?? $field->active,
        ]);

        $after = [
            'id' => (string) $field->id,
            'name' => $field->name,
            'type' => $field->type,
            'active' => (bool) $field->active,
        ];

        PayablesAudit::log(
            $tenantId,
            (string) auth()->id(),
            'custom_field',
            'updated',
            (string) $field->id,
            $before,
            $after
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $r, $id)
    {
        $tenantId = $this->customerSistappID();

        $field = PayableCustomField::where('customer_sistapp_id', $tenantId)->findOrFail($id);

        $before = [
            'id' => (string) $field->id,
            'name' => $field->name,
            'type' => $field->type,
            'active' => (bool) $field->active,
        ];

        $field->delete();

        PayablesAudit::log(
            $tenantId,
            (string) auth()->id(),
            'custom_field',
            'deleted',
            (string) $id,
            $before,
            ['deleted' => true]
        );

        return response()->json(['ok' => true]);
    }

    public function toggle(Request $r, $id)
    {
        $tenantId = $this->customerSistappID();

        $field = PayableCustomField::where('customer_sistapp_id', $tenantId)->findOrFail($id);

        $before = [
            'id' => (string) $field->id,
            'active' => (bool) $field->active,
        ];

        $field->update(['active' => ! $field->active]);

        $after = [
            'id' => (string) $field->id,
            'active' => (bool) $field->active,
        ];

        PayablesAudit::log(
            $tenantId,
            (string) auth()->id(),
            'custom_field',
            'toggled',
            (string) $field->id,
            $before,
            $after
        );

        return response()->json(['ok' => true, 'active' => (bool)$field->active]);
    }
}
