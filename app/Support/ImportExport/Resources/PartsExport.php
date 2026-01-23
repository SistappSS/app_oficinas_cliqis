<?php

namespace App\Support\ImportExport\Resources;

use App\Models\Catalogs\Parts\Part;
use App\Support\ImportExport\Contracts\ExportResource;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class PartsExport implements ExportResource
{
    public function key(): string { return 'parts'; }
    public function label(): string { return 'Peças'; }

    public function filename(): string
    {
        return 'pecas_' . now()->format('Y-m-d_His') . '.csv';
    }

    public function exportableColumns(): array
    {
        return [
            ['key' => 'code',       'label' => 'Código'],
            ['key' => 'name',       'label' => 'Nome da peça'],
            ['key' => 'supplier',   'label' => 'Fornecedor'],
            ['key' => 'ncm_code',   'label' => 'NCM'],
            ['key' => 'unit_price', 'label' => 'Valor unitário'],
            ['key' => 'is_active',  'label' => 'Status'],
            ['key' => 'description','label' => 'Descrição'],
            ['key' => 'created_at', 'label' => 'Criado em'],
        ];
    }

    public function headers(array $columns): array
    {
        $map = collect($this->exportableColumns())->pluck('label', 'key')->all();
        return array_map(fn($c) => $map[$c] ?? $c, $columns);
    }

    public function baseQuery(): Builder
    {
        return Part::query()
            ->with(['supplier:id,name'])
            ->orderByDesc('created_at'); // ✅ mais recente primeiro
    }

    public function applyFilters(Builder $q, array $filters): void
    {
        // status
        if (!empty($filters['status']) && in_array($filters['status'], ['all','active','inactive'], true)) {
            if ($filters['status'] === 'active') $q->where('is_active', true);
            if ($filters['status'] === 'inactive') $q->where('is_active', false);
        }

        // supplier
        if (!empty($filters['supplier_id'])) {
            $q->where('supplier_id', $filters['supplier_id']);
        }

        // code prefix
        if (!empty($filters['code_prefix'])) {
            $prefix = trim((string)$filters['code_prefix']);
            $q->where('code', 'like', $prefix . '%');
        }

        // created_at range
        $from = !empty($filters['created_from']) ? Carbon::parse($filters['created_from'])->startOfDay() : null;
        $to   = !empty($filters['created_to']) ? Carbon::parse($filters['created_to'])->endOfDay() : null;

        if ($from) $q->where('created_at', '>=', $from);
        if ($to)   $q->where('created_at', '<=', $to);
    }

    public function row($p, array $columns): array
    {
        $get = fn(string $c) => match ($c) {
            'code'       => $p->code ?? '',
            'name'       => $p->name ?? '',
            'supplier'   => $p->supplier?->name ?? '',
            'ncm_code'   => $p->ncm_code ?? '',
            'unit_price' => number_format((float)($p->unit_price ?? 0), 2, '.', ''),
            'is_active'  => $p->is_active ? 'Ativo' : 'Inativo',
            'description'=> $p->description ?? '',
            'created_at' => optional($p->created_at)->format('Y-m-d H:i:s') ?? '',
            default      => '',
        };

        return array_map($get, $columns);
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
