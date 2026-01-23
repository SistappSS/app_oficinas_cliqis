<?php

namespace App\Support\ImportExport\Contracts;

use Illuminate\Http\UploadedFile;

interface ImportResource
{
    public function key(): string;
    public function label(): string;

    public function templateColumns(): array;
    public function requiredColumns(): array;
    public function optionalColumns(): array;

    public function formats(): array;        // ['csv'] por enquanto
    public function maxRows(): int;          // ex 5000
    public function maxFileMb(): int;        // ex 5

    /**
     * Retorna resumo: created/updated/skipped/errors
     */
    public function import(UploadedFile $file, array $opts = []): array;
}
