<!-- Import / Export Modal (SINGLE) -->
<div id="io-modal" class="hidden fixed inset-0 z-[80] bg-black/30 p-3 sm:p-4">
    <div class="mx-auto flex max-h-[90vh] w-[min(920px,94vw)] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">

        <!-- Header -->
        <div class="flex items-center justify-between gap-3 px-4 sm:px-6 py-4 border-b border-slate-100 shrink-0">
            <div class="min-w-0">
                <p class="text-sm text-slate-500">Importar / Exportar</p>
                <h3 id="io-title" class="truncate text-lg font-semibold text-slate-900">—</h3>
            </div>
            <button id="io-close" type="button" class="shrink-0 rounded-lg p-2 hover:bg-slate-100">✕</button>
        </div>

        <!-- Tabs -->
        <div class="px-4 sm:px-6 pt-4 shrink-0">
            <div class="flex flex-wrap gap-2">
                <button id="io-tab-export" type="button"
                        class="rounded-xl px-4 py-2 text-sm font-medium border border-blue-200 bg-blue-50 text-blue-700">
                    Exportar
                </button>
                <button id="io-tab-import" type="button"
                        class="rounded-xl px-4 py-2 text-sm font-medium border border-purple-200 bg-purple-50 text-purple-700">
                    Importar
                </button>
            </div>
        </div>

        <!-- Body (scroll) -->
        <div class="px-4 sm:px-6 pb-6 pt-4 overflow-y-auto">
            <!-- EXPORT -->
            <div id="io-pane-export" class="space-y-4">
                <p id="io-export-hint" class="text-sm text-slate-500"></p>

                <!-- Accordion: Filtros -->
                <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
                    <button id="io-filters-toggle" type="button"
                            class="w-full px-4 py-3 flex items-center justify-between gap-3 hover:bg-slate-50">
                        <span class="text-sm font-semibold text-slate-800">Filtros</span>
                        <span id="io-filters-icon" class="text-slate-400">▾</span>
                    </button>

                    <!-- fechado por padrão -->
                    <div id="io-filters-body" class="hidden px-4 pb-4">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Criado de</label>
                                <input id="io-created-from" type="date"
                                       class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700
                                              hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none"/>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Criado até</label>
                                <input id="io-created-to" type="date"
                                       class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700
                                              hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none"/>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
                                <select id="io-status"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700
                                               hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none">
                                    <option value="all">Todas</option>
                                    <option value="active">Ativas</option>
                                    <option value="inactive">Inativas</option>
                                </select>
                            </div>

                            <!-- Genérico: o JS mostra/esconde e popula se existir no options -->
                            <div id="io-supplier-wrap" class="hidden">
                                <label class="block text-xs font-medium text-slate-600 mb-1">Fornecedor</label>
                                <select id="io-supplier"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700
                                               hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none">
                                    <option value="">Todos</option>
                                </select>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-slate-600 mb-1">Prefixo do código</label>
                                <input id="io-code-prefix" type="text" placeholder="Ex.: SKU, P-, 0001..."
                                       class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700
                                              hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none"/>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Colunas -->
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-800 mb-2">Colunas</p>
                    <div id="io-export-columns" class="grid gap-2 sm:grid-cols-2"></div>
                    <p class="mt-2 text-xs text-slate-500">Na próxima etapa: filtros e geração do arquivo.</p>
                </div>
            </div>

            <!-- IMPORT -->
            <div id="io-pane-import" class="space-y-4 hidden">
                <p id="io-import-hint" class="text-sm text-slate-500"></p>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <p class="text-sm font-semibold text-slate-800 mb-2">Obrigatórios</p>
                        <ul id="io-import-required" class="list-disc list-inside text-sm text-slate-700 space-y-1"></ul>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <p class="text-sm font-semibold text-slate-800 mb-2">Opcionais</p>
                        <ul id="io-import-optional" class="list-disc list-inside text-sm text-slate-700 space-y-1"></ul>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-800 mb-2">Template</p>
                    <p class="text-xs text-slate-500 mb-2">Ordem sugerida das colunas:</p>
                    <div id="io-import-template" class="flex flex-wrap gap-2"></div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-xl bg-white border border-slate-200 p-3">
                            <p class="text-xs text-slate-500">Formatos</p>
                            <p id="io-import-formats" class="text-sm font-semibold text-slate-800">—</p>
                        </div>
                        <div class="rounded-xl bg-white border border-slate-200 p-3">
                            <p class="text-xs text-slate-500">Limite de linhas</p>
                            <p id="io-import-max-rows" class="text-sm font-semibold text-slate-800">—</p>
                        </div>
                        <div class="rounded-xl bg-white border border-slate-200 p-3">
                            <p class="text-xs text-slate-500">Tamanho máx.</p>
                            <p id="io-import-max-mb" class="text-sm font-semibold text-slate-800">—</p>
                        </div>
                    </div>

                    <p class="mt-3 text-xs text-slate-500">Na próxima etapa: upload + preview + importar.</p>
                </div>

                <!-- Upload + opções -->
                <div class="rounded-2xl border border-slate-200 bg-white p-4 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-800 mb-2">Arquivo</label>

                        <input id="io-import-file" type="file" accept=".csv"
                               class="block w-full text-sm text-slate-700
                                      file:mr-3 file:rounded-xl file:border-0
                                      file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-700
                                      hover:file:bg-slate-200"/>
                        <p class="mt-2 text-xs text-slate-500">
                            Dica: CSV com separador “;” (padrão Excel pt-br).
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Modo</label>
                            <select id="io-import-mode"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700
                                           hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none">
                                <option value="create_only">Criar apenas</option>
                                <option value="upsert">Atualizar se existir</option>
                            </select>
                            <p class="mt-1 text-[11px] text-slate-500">Upsert depende da regra do importer.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Separador</label>
                            <select id="io-import-delimiter"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700
                                           hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none">
                                <option value=";">;</option>
                                <option value=",">,</option>
                            </select>
                        </div>

                        <div class="sm:col-span-1 flex items-end">
                            <button id="io-import-template-download" type="button"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Baixar template CSV
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Resultado -->
                <div id="io-import-result" class="hidden rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-800 mb-2">Resultado</p>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-xl bg-white border border-slate-200 p-3">
                            <p class="text-xs text-slate-500">Criados</p>
                            <p id="io-import-created" class="text-sm font-semibold text-slate-800">0</p>
                        </div>
                        <div class="rounded-xl bg-white border border-slate-200 p-3">
                            <p class="text-xs text-slate-500">Atualizados</p>
                            <p id="io-import-updated" class="text-sm font-semibold text-slate-800">0</p>
                        </div>
                        <div class="rounded-xl bg-white border border-slate-200 p-3">
                            <p class="text-xs text-slate-500">Ignorados</p>
                            <p id="io-import-skipped" class="text-sm font-semibold text-slate-800">0</p>
                        </div>
                    </div>

                    <div id="io-import-errors-wrap" class="hidden mt-4">
                        <p class="text-xs font-semibold text-slate-700 mb-2">Erros</p>
                        <div class="max-h-40 overflow-auto rounded-xl bg-white border border-slate-200 p-3">
                            <ul id="io-import-errors" class="text-xs text-slate-700 space-y-1"></ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer (sticky) -->
        <div class="flex items-center justify-end gap-2 px-4 sm:px-6 py-4 border-t border-slate-100 shrink-0">
            <button id="io-cancel" type="button"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Fechar
            </button>

            <!-- Export -->
            <button id="io-export-download" type="button"
                    class="hidden rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-800">
                Baixar CSV
            </button>

            <!-- Import -->
            <button id="io-import-submit" type="button"
                    class="hidden rounded-xl bg-purple-700 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-purple-800">
                Importar
            </button>
        </div>
    </div>
</div>
