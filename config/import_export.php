<?php

return [
    'resources' => [
        'parts' => [
            'label' => 'Peças',
            'model' => \App\Models\Catalogs\Parts\Part::class,

            'exportable_columns' => [
                ['key' => 'code',        'label' => 'Código'],
                ['key' => 'name',        'label' => 'Nome da peça'],
                ['key' => 'supplier',    'label' => 'Fornecedor'],
                ['key' => 'ncm_code',    'label' => 'NCM'],
                ['key' => 'unit_price',  'label' => 'Valor unitário'],
                ['key' => 'is_active',   'label' => 'Status'],
                ['key' => 'description', 'label' => 'Descrição'],
                ['key' => 'created_at',  'label' => 'Criado em'],
            ],

            // ✅ filtros configuráveis por resource (GENÉRICO)
            'export' => [
                'filters' => [
                    'created_at_range' => true,
                    'status' => [
                        'enabled' => true,
                        'key' => 'status',
                        'options' => [
                            ['value' => 'all', 'label' => 'Todas'],
                            ['value' => 'active', 'label' => 'Ativas'],
                            ['value' => 'inactive', 'label' => 'Inativas'],
                        ],
                    ],
                    'supplier' => [
                        'enabled' => true,
                        'key' => 'supplier_id',
                        'endpoint' => '/entities/supplier-api',
                        'value_field' => 'id',
                        'label_field' => 'name',
                    ],
                    'code_prefix' => [
                        'enabled' => true,
                        'key' => 'code_prefix',
                    ],
                ],
            ],

            'import_schema' => [
                'required' => [
                    ['key' => 'name', 'label' => 'Nome da peça'],
                ],
                'optional' => [
                    ['key' => 'code',         'label' => 'Código'],
                    ['key' => 'supplier_name','label' => 'Fornecedor (nome)'],
                    ['key' => 'supplier_id',  'label' => 'Fornecedor (id)'],
                    ['key' => 'ncm_code',     'label' => 'NCM'],
                    ['key' => 'unit_price',   'label' => 'Valor unitário'],
                    ['key' => 'is_active',    'label' => 'Ativo (1/0)'],
                    ['key' => 'description',  'label' => 'Descrição'],
                ],
                'template_columns' => [
                    'code','name','supplier_name','ncm_code','unit_price','is_active','description',
                ],
                'formats' => ['csv','xlsx'],
            ],

            'limits' => [
                'max_rows' => 5000,
                'max_file_mb' => 5,
            ],

            'ui' => [
                'import_hint' => 'Dica: comece com CSV. Se usar XLSX, o modelo aceita a mesma ordem de colunas.',
                'export_hint' => 'Escolha as colunas e aplique filtros (na próxima etapa).',
            ],
        ],
    ],
];
