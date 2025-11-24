<x-modal modalId="employee-benefit-modal" formId="employee-benefit-form" modalTitle="Vincular benefício" :input="$input">
    <input type="hidden" id="employee_benefit_id" name="employee_benefit_id">

    <div class="space-y-6">
        <div class="section-title">
            <span class="dot">
                <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none">
                    <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm7 7a7 7 0 0 0-14 0"
                          stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
            </span>
            Vincular benefício ao funcionário
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="employee_id" class="block text-sm font-medium text-slate-700 mb-1">
                    Funcionário
                </label>
                <select id="employee_id" name="employee_id"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none">
                    <option value="">Selecione...</option>
                    {{-- popular via backend ou JS --}}
                </select>
            </div>

            <div>
                <label for="benefit_id" class="block text-sm font-medium text-slate-700 mb-1">
                    Benefício
                </label>
                <select id="benefit_id" name="benefit_id"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none">
                    <option value="">Selecione...</option>
                    {{-- popular via backend ou JS --}}
                </select>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-input col="" set="" id="value" name="value" type="number"
                     step="0.01" min="0"
                     label="Valor (R$)"
                     placeholder="0,00"></x-input>

            <x-input col="" set="" id="notes" name="notes" type="text"
                     label="Observações"
                     placeholder="Ex.: valor subsidiado, carência, etc."></x-input>
        </div>
    </div>
</x-modal>
