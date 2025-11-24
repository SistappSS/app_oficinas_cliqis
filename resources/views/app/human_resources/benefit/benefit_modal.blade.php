<x-modal modalId="benefit-modal" formId="benefit-form" modalTitle="Novo benefício" :input="$input">
    <input type="hidden" id="benefit_id" name="benefit_id">

    <div class="space-y-6">
        <div class="section-title">
            <span class="dot">
                <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none">
                    <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm7 7a7 7 0 0 0-14 0"
                          stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
            </span>
            Informações do benefício
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-input col="" set="" id="name" name="name" type="text"
                     label="Nome do benefício"
                     placeholder="Ex.: Vale refeição, Plano de saúde"></x-input>

            <x-input col="" set="" id="description" name="description" type="text"
                     label="Descrição (opcional)"
                     placeholder="Detalhes do benefício..."></x-input>
        </div>
    </div>
</x-modal>
