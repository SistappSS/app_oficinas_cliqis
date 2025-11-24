<x-modal modalId="part-modal" formId="part-form" modalTitle="Nova peça" :input="$input">
    <input type="hidden" id="part_id" name="part_id">

    <div class="space-y-6">
        <div class="section-title">
            <span class="dot">
                <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none">
                    <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm7 7a7 7 0 0 0-14 0"
                          stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
            </span>
            Informações da peça
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <x-input col="" set="" id="code" name="code" type="text"
                     label="Código interno"
                     placeholder="Ex.: P-001, SKU, etc."></x-input>

            <x-input col="" set="" id="name" name="name" type="text"
                     label="Nome da peça"
                     placeholder="Ex.: Bateria 12V, Placa lógica..."></x-input>

            <div>
                <label for="supplier_id" class="block text-sm font-medium text-slate-700 mb-1">
                    Fornecedor
                </label>
                <select id="supplier_id" name="supplier_id"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none">
                    <option value="">Selecione...</option>
                    {{-- popular via backend ou JS --}}
                </select>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <x-input col="" set="" id="ncm_code" name="ncm_code" type="text"
                     label="Código NCM"
                     placeholder="Ex.: 8407.34.10"></x-input>

            <x-input col="" set="" id="unit_price" name="unit_price" type="number"
                     step="0.01" min="0"
                     label="Valor unitário (R$)"
                     placeholder="0,00"></x-input>

            <div class="card">
                <div class="grid gap-4 sm:grid-cols-2 items-center">
                    <div class="flex items-center gap-3">
                        <span class="inline-grid h-7 w-7 place-items-center rounded-full bg-white text-blue-600 ring-1 ring-blue-200">
                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none">
                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/>
                                <path d="m9.5 12 2 2 3-4"
                                      stroke="currentColor" stroke-width="1.6"
                                      stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-medium text-slate-800">Peça ativa?</p>
                            <p class="text-xs text-slate-500">Use para habilitar/desabilitar esta peça.</p>
                        </div>
                    </div>
                    <div>
                        <x-check-input col="3" id="is_active" name="is_active" type="checkbox" label="Ativo" check="1"></x-check-input>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-slate-700 mb-1">
                Descrição
            </label>
            <textarea id="description" name="description"
                      rows="3"
                      class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none"
                      placeholder="Detalhes da peça, compatibilidade, observações, etc."></textarea>
        </div>
    </div>
</x-modal>
