<x-modal modalId="service-type-modal" formId="service-type-form" modalTitle="Novo tipo de serviço" :input="$input">
    <input type="hidden" id="service_type_id" name="service_type_id">

    <div class="space-y-6">
        <div class="section-title">
            <span class="dot">
                <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none">
                    <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm7 7a7 7 0 0 0-14 0"
                          stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
            </span>
            Informações do tipo de serviço
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-input col="" set="" id="name" name="name" type="text"
                     label="Nome do tipo de serviço"
                     placeholder="Ex.: Mão de obra, Manutenção, Instalação"></x-input>

            <x-input col="" set="" id="description" name="description" type="text"
                     label="Descrição (opcional)"
                     placeholder="Detalhes deste tipo de serviço..."></x-input>
        </div>

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
                        <p class="text-sm font-medium text-slate-800">Tipo ativo?</p>
                        <p class="text-xs text-slate-500">Use para habilitar/desabilitar esse tipo de serviço.</p>
                    </div>
                </div>
                <div>
                    <x-check-input col="3" id="is_active" name="is_active" type="checkbox" label="Ativo" check="1"></x-check-input>
                </div>
            </div>
        </div>
    </div>
</x-modal>
