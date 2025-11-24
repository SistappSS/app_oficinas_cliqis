<x-modal modalId="service-item-modal" formId="service-item-form" modalTitle="Novo serviço" :input="$input">
    <input type="hidden" id="service_item_id" name="service_item_id">

    <div class="space-y-6">
        <div class="section-title">
            <span class="dot">
                <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none">
                    <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm7 7a7 7 0 0 0-14 0"
                          stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
            </span>
            Informações do serviço
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-input col="" set="" id="name" name="name" type="text"
                     label="Nome do serviço"
                     placeholder="Ex.: Manutenção preventiva, Instalação de equipamento"></x-input>

            <div>
                <label for="service_type_id" class="block text-sm font-medium text-slate-700 mb-1">
                    Tipo de serviço
                </label>
                <select id="service_type_id" name="service_type_id"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none">
                    <option value="">Selecione...</option>
                    {{-- popular via backend ou JS --}}
                </select>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
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
                            <p class="text-sm font-medium text-slate-800">Serviço ativo?</p>
                            <p class="text-xs text-slate-500">Use para habilitar/desabilitar este serviço no catálogo.</p>
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
                Descrição detalhada
            </label>
            <textarea id="description" name="description"
                      rows="3"
                      class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none"
                      placeholder="Descreva o que está incluído neste serviço, condições, observações, etc."></textarea>
        </div>
    </div>
</x-modal>
