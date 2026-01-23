<?php

namespace App\Support\ImportExport\Resources;

use App\Models\Catalogs\Parts\Part;
use App\Models\Entities\Suppliers\Supplier;
use App\Support\ImportExport\Contracts\ImportResource;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PartsImport implements ImportResource
{
    public function key(): string { return 'parts'; }
    public function label(): string { return 'Peças'; }

    public function formats(): array { return ['csv']; }
    public function maxRows(): int { return 5000; }
    public function maxFileMb(): int { return 5; }

    public function templateColumns(): array
    {
        return ['code','name','supplier_name','ncm_code','unit_price','is_active','description'];
    }

    public function requiredColumns(): array { return ['name']; }

    public function optionalColumns(): array
    {
        return ['code','supplier_name','supplier_id','ncm_code','unit_price','is_active','description'];
    }

    public function import(UploadedFile $file, array $opts = []): array
    {
        $delimiter = $opts['delimiter'] ?? ';';
        $mode = $opts['mode'] ?? 'create_only';
        $customerSistappId = $opts['customer_sistapp_id'] ?? null;

        $created = 0; $updated = 0; $skipped = 0;
        $errors = [];

        if (!$customerSistappId) {
            return [
                'created'=>0,'updated'=>0,'skipped'=>0,
                'errors'=>[['line'=>0,'message'=>'customer_sistapp_id não informado no import (tenant obrigatório).']]
            ];
        }

        $fp = fopen($file->getRealPath(), 'r');
        if (!$fp) {
            return ['created'=>0,'updated'=>0,'skipped'=>0,'errors'=>[['line'=>0,'message'=>'Não foi possível ler o arquivo']]];
        }

        $header = fgetcsv($fp, 0, $delimiter);
        if (!$header) {
            fclose($fp);
            return ['created'=>0,'updated'=>0,'skipped'=>0,'errors'=>[['line'=>0,'message'=>'CSV sem cabeçalho']]];
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$header[0]);
        $header = array_map(fn($h) => trim((string)$h), $header);

        $allowed = array_unique(array_merge($this->requiredColumns(), $this->optionalColumns()));

        foreach ($header as $col) {
            if (!in_array($col, $allowed, true)) {
                $errors[] = ['line'=>1,'message'=>"Coluna inválida no CSV: {$col}"];
            }
        }
        foreach ($this->requiredColumns() as $req) {
            if (!in_array($req, $header, true)) {
                $errors[] = ['line'=>1,'message'=>"Coluna obrigatória ausente: {$req}"];
            }
        }
        if ($errors) {
            fclose($fp);
            return compact('created','updated','skipped','errors');
        }

        $indexes = array_flip($header);

        $line = 1;
        while (($row = fgetcsv($fp, 0, $delimiter)) !== false) {
            $line++;

            if ($line > $this->maxRows() + 1) {
                $errors[] = ['line'=>$line,'message'=>'Limite de linhas excedido'];
                break;
            }

            $data = $this->mapRow($row, $indexes);

            if (empty($data['name'])) {
                $skipped++;
                $errors[] = ['line'=>$line,'message'=>'Campo name é obrigatório'];
                continue;
            }

            // tenant sempre
            $data['customer_sistapp_id'] = $customerSistappId;

            // resolve supplier (prioridade: id > name)
            $supplierId = null;

            if (!empty($data['supplier_id'])) {
                $supplierId = trim((string)$data['supplier_id']);

                $exists = Supplier::query()
                    ->where('customer_sistapp_id', $customerSistappId)
                    ->where('id', $supplierId)
                    ->exists();

                if (!$exists) {
                    $skipped++;
                    $errors[] = ['line'=>$line,'message'=>"supplier_id inválido (não existe no tenant): {$supplierId}"];
                    continue;
                }
            } elseif (!empty($data['supplier_name'])) {
                $name = trim((string)$data['supplier_name']);

                $supplier = Supplier::query()
                    ->where('customer_sistapp_id', $customerSistappId)
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->first();

                if (!$supplier) {
                    $supplier = Supplier::create([
                        'id' => (string) Str::uuid(),
                        'customer_sistapp_id' => $customerSistappId,
                        'name' => $name,
                        'is_active' => true,
                    ]);
                }

                $supplierId = $supplier->id;
            }

            unset($data['supplier_name'], $data['supplier_id']);
            if ($supplierId) $data['supplier_id'] = $supplierId;

            // normaliza unit_price (se veio)
            if (array_key_exists('unit_price', $data)) {
                $data['unit_price'] = $this->toDecimal($data['unit_price']);
            }

            // normaliza is_active (só seta se veio no CSV)
            if ($data['is_active'] === null || $data['is_active'] === '') {
                unset($data['is_active']);
            } else {
                $data['is_active'] = $this->toBool($data['is_active']);
            }

            try {
                DB::transaction(function () use ($mode, $data, &$created, &$updated) {
                    if ($mode === 'upsert' && !empty($data['code'])) {
                        $part = Part::query()
                            ->where('customer_sistapp_id', $data['customer_sistapp_id'])
                            ->where('code', $data['code'])
                            ->first();

                        if ($part) {
                            $part->update($data);
                            $updated++;
                            return;
                        }
                    }

                    Part::create(array_merge($data, [
                        'id' => (string) Str::uuid(),
                    ]));
                    $created++;
                });
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = ['line'=>$line,'message'=>$e->getMessage()];
            }
        }

        fclose($fp);

        return compact('created','updated','skipped','errors');
    }

    private function mapRow(array $row, array $indexes): array
    {
        $get = fn(string $k) => isset($indexes[$k]) ? trim((string)($row[$indexes[$k]] ?? '')) : null;

        return [
            'code'          => $get('code'),
            'name'          => $get('name'),
            'supplier_id'   => $get('supplier_id'),
            'supplier_name' => $get('supplier_name'),
            'ncm_code'      => $get('ncm_code'),
            'unit_price'    => $get('unit_price'),
            'is_active'     => $get('is_active'),
            'description'   => $get('description'),
        ];
    }

    private function toBool($v): bool
    {
        $s = mb_strtolower(trim((string)$v));
        return in_array($s, ['1','true','sim','yes','y','ativo'], true);
    }

    private function toDecimal($v): float
    {
        $s = trim((string)$v);
        if ($s === '') return 0.0;

        $hasComma = str_contains($s, ',');
        $hasDot   = str_contains($s, '.');

        // pt-br: 1.234,56
        if ($hasComma && $hasDot) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
            return (float) $s;
        }

        // 1234,56
        if ($hasComma && !$hasDot) {
            $s = str_replace(',', '.', $s);
            return (float) $s;
        }

        // 1234.56
        return (float) $s;
    }
}
