<?php

namespace App\Http\Controllers\General\ImportExport;

use App\Http\Controllers\Controller;
use App\Support\CustomerContext;
use App\Support\ImportExport\ImportExportRegistry;
use App\Traits\RoleCheckTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportExportController extends Controller
{
    use RoleCheckTrait;
    public function options(Request $request)
    {
        $resourceKey = $request->query('resource')
            ?: ImportExportRegistry::fromPath($request->query('path', '/'));

        if (!$resourceKey) {
            return response()->json(['message' => 'Recurso não encontrado.'], 404);
        }

        $cfg = ImportExportRegistry::get($resourceKey);
        if (!$cfg) {
            return response()->json(['message' => 'Recurso não registrado no registry.'], 404);
        }

        return response()->json([
            'resource' => $resourceKey,
            'label'    => $cfg['label'] ?? $resourceKey,
            'export'   => [
                'columns' => $cfg['exportable_columns'] ?? [],
                'hint'    => data_get($cfg, 'ui.export_hint'),
                // ✅ agora vem do config
                'filters' => data_get($cfg, 'export.filters', []),
            ],
            'import'   => [
                'required' => data_get($cfg, 'import_schema.required', []),
                'optional' => data_get($cfg, 'import_schema.optional', []),
                'template_columns' => data_get($cfg, 'import_schema.template_columns', []),
                'formats'  => data_get($cfg, 'import_schema.formats', ['csv']),
                'limits'   => $cfg['limits'] ?? [],
                'hint'     => data_get($cfg, 'ui.import_hint'),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $resourceKey = $request->input('resource')
            ?: ImportExportRegistry::fromPath($request->input('path', '/'));

        if (!$resourceKey) abort(404, 'Recurso não encontrado para exportação.');

        $exporter = ImportExportRegistry::exporter($resourceKey);
        if (!$exporter) abort(422, 'Export ainda não habilitado para este módulo.');

        $requestedCols = $request->input('columns', []);
        if (!is_array($requestedCols) || !count($requestedCols)) abort(422, 'Selecione pelo menos uma coluna.');

        $allowed = collect($exporter->exportableColumns())->pluck('key')->all();
        $columns = array_values(array_intersect($requestedCols, $allowed));
        if (!count($columns)) abort(422, 'Colunas inválidas.');

        $filters = (array)($request->input('filters', []));

        return response()->streamDownload(function () use ($exporter, $columns, $filters) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, $exporter->headers($columns), ';');

            $q = $exporter->baseQuery();
            $exporter->applyFilters($q, $filters);

            $q->chunk($exporter->chunkSize(), function ($rows) use ($out, $exporter, $columns) {
                foreach ($rows as $row) {
                    fputcsv($out, $exporter->row($row, $columns), ';');
                }
            });

            fclose($out);
        }, $exporter->filename(), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function import(Request $request)
    {
        $resourceKey = $request->input('resource')
            ?: ImportExportRegistry::fromPath($request->input('path','/'));

        if (!$resourceKey) return response()->json(['message'=>'Recurso não encontrado.'],404);

        $importer = ImportExportRegistry::importer($resourceKey);
        if (!$importer) return response()->json(['message'=>'Import não habilitado para este módulo.'],422);

        $maxMb = $importer->maxFileMb();

        $v = Validator::make($request->all(), [
            'file' => ['required','file','mimes:csv,txt','max:'.($maxMb*1024)],
            'mode' => ['nullable', Rule::in(['create_only','upsert'])],
            'delimiter' => ['nullable', Rule::in([';', ','])],
        ]);

        if ($v->fails()) {
            return response()->json([
                'message' => 'Arquivo inválido.',
                'errors' => $v->errors(),
            ], 422);
        }

        $customerSistappId = auth()->user()->customerLogin->customer_sistapp_id;
//        dd($customerSistappId);

        $result = $importer->import($request->file('file'), [
            'mode' => $request->input('mode','create_only'),
            'delimiter' => $request->input('delimiter',';'),
            'path' => $request->input('path','/'),
            'customer_sistapp_id' => $customerSistappId,
        ]);

        return response()->json(['message'=>'Import concluído.','summary'=>$result]);
    }
}
