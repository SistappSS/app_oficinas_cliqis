<?php

namespace App\Support\ImportExport;

use App\Support\ImportExport\Contracts\ExportResource;
use App\Support\ImportExport\Contracts\ImportResource;
use App\Support\ImportExport\Resources\PartsExport;
use App\Support\ImportExport\Resources\PartsImport;

class ImportExportRegistry
{
    public static function fromPath(string $path): ?string
    {
        $path = trim(parse_url($path, PHP_URL_PATH) ?? $path, '/');

        $map = [
            'catalogs/part' => 'parts',
            'human-resources/employee' => 'employees',
            'entities/supplier' => 'suppliers',
        ];

        return $map[$path] ?? null;
    }

    public static function resources(): array
    {
        return config('import_export.resources', []);
    }

    public static function get(string $key): ?array
    {
        return self::resources()[$key] ?? null;
    }

    public static function exporters(): array
    {
        return [
            'parts' => PartsExport::class,
        ];
    }

    public static function exporter(string $key): ?ExportResource
    {
        $map = self::exporters();
        return isset($map[$key]) ? app($map[$key]) : null;
    }

    public static function importers(): array
    {
        return [
            'parts' => PartsImport::class,
        ];
    }

    public static function importer(string $key): ?ImportResource
    {
        $map = self::importers();
        return isset($map[$key]) ? app($map[$key]) : null;
    }
}
