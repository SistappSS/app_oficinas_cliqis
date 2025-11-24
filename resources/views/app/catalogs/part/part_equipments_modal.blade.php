<div id="part-equipments-modal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 backdrop-blur-sm">
    <div class="w-full max-w-2xl rounded-2xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">
                    Vincular equipamentos
                </h2>
                <p class="text-xs text-slate-500">
                    Selecione os equipamentos que utilizam esta peça.
                </p>
                <p id="part-equipments-part-name"
                   class="mt-1 text-xs font-medium text-slate-700"></p>
            </div>
            <button type="button" id="part-equipments-close"
                    class="rounded-full p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2">
                    <path d="M6 6l12 12M18 6L6 18"
                          stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>

        <form id="part-equipments-form" class="px-5 py-4 space-y-4">
            <input type="hidden" id="part-equipments-id">

            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">
                    Buscar equipamentos
                </label>
                <div class="relative">
                    <input id="equipment-search" type="text"
                           placeholder="Buscar por nome, código..."
                           class="w-full rounded-xl border border-slate-300 bg-white pl-9 pr-2 py-2.5 text-sm placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none">
                    <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="M21 21l-4.3-4.3"/>
                    </svg>
                </div>
                <p class="mt-1 text-[11px] text-slate-500">
                    Mostrando até 8 resultados. Refine a busca para encontrar outros equipamentos.
                </p>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-slate-700">
                        Equipamentos encontrados
                    </span>
                </div>
                <div id="equipment-options"
                     class="max-h-40 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50/60 px-2 py-2 text-sm text-slate-700 space-y-1">
                    <!-- itens via JS -->
                </div>
            </div>

            <div>
                <span class="text-xs font-medium text-slate-700">
                    Equipamentos vinculados
                </span>
                <div id="equipment-selected"
                     class="mt-2 flex flex-wrap gap-2 text-xs">
                    <!-- badges -->
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-3">
                <button type="button" id="part-equipments-cancel"
                        class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                    Cancelar
                </button>
                <button type="submit" id="part-equipments-save"
                        class="inline-flex items-center rounded-xl bg-blue-700 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-800">
                    Salvar vínculos
                </button>
            </div>
        </form>
    </div>
</div>
