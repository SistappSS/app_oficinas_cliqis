<?php

namespace App\Support\ImportExport\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface ExportResource
{
    public function key(): string;              // 'parts'
    public function label(): string;            // 'Peças'
    public function filename(): string;         // pecas_2026-01-16_123000.csv

    /** Lista de colunas exportáveis (mesmo que o modal usa) */
    public function exportableColumns(): array; // [['key'=>'code','label'=>'Código'], ...]

    /** Map key => header csv */
    public function headers(array $columns): array;

    /** Builder base já com with/order/customer scope etc */
    public function baseQuery(): Builder;

    /** Aplica filtros vindos do modal no builder */
    public function applyFilters(Builder $q, array $filters): void;

    /** Monta a linha CSV com base nas colunas */
    public function row($model, array $columns): array;

    /** Chunk size (opcional) */
    public function chunkSize(): int;
}
