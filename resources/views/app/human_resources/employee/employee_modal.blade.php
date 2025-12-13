<x-modal modalId="employee-modal" formId="employee-form" modalTitle="Novo funcionário" :input="$input">
    <input type="hidden" id="employee_id" name="employee_id">
    <input type="hidden" id="user_id" name="user_id"><!-- se quiser vincular com User depois -->

    <div class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-input col="" set="" id="full_name" name="full_name" type="text"
                     label="Nome completo"
                     placeholder="Ex.: João da Silva"></x-input>

            <x-input col="" set="" id="position" name="position" type="text"
                     label="Cargo"
                     placeholder="Ex.: Técnico de campo"></x-input>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-input col="" set="" id="document_number" name="document_number" type="text"
                     label="Documento (CPF/CNPJ)"
                     placeholder="000.000.000-00"></x-input>

            <x-input col="" set="" id="phone" name="phone" type="text"
                     label="Telefone"
                     placeholder="(11) 99999-9999"></x-input>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-input col="" set="" id="email" name="email" type="email"
                     label="E-mail"
                     placeholder="joao@email.com"></x-input>

            <x-input col="" set="" id="hourly_rate" name="hourly_rate" type="number"
                     step="0.01" min="0"
                     label="Valor hora (R$)"
                     placeholder="0,00"></x-input>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="department_search" class="block text-sm font-medium text-slate-700 mb-1">
                    Departamento
                </label>

                <div class="relative">
                    {{-- hidden que vai o UUID pro backend --}}
                    <input type="hidden" id="department_id" name="department_id">

                    {{-- input texto para buscar / criar --}}
                    <input id="department_search"
                           type="text"
                           autocomplete="off"
                           placeholder="Digite para buscar ou criar..."
                           class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700
                      hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none">

                    {{-- dropdown de resultados --}}
                    <div id="department_results"
                         class="absolute z-50 mt-1 w-full max-h-64 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg hidden">
                        {{-- preenchido via JS --}}
                    </div>
                </div>

                <p class="mt-1 text-xs text-slate-500">
                    Digite o nome do departamento. Se não existir, você poderá criar um novo.
                </p>
            </div>

            <div class="space-y-3">
                {{-- Funcionário ativo? --}}
                <div class="card">
                    <label for="is_active" class="flex items-center justify-between gap-3 px-4 py-3 cursor-pointer">
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
                                <p class="text-sm font-medium text-slate-800">Funcionário ativo?</p>
                                <p class="text-xs text-slate-500">Use para habilitar/desabilitar o colaborador.</p>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <input
                                id="is_active"
                                name="is_active"
                                type="checkbox"
                                value="1"
                                class="peer sr-only"
                            >

                            <!-- Track (anima via padding) -->
                            <div class="relative inline-flex h-5 w-9 items-center rounded-full bg-slate-300
                                        pl-0.5 pr-0.5 transition-all duration-200 ease-in-out
                                        peer-checked:bg-blue-600 peer-checked:pl-4">
                                <span class="inline-block h-4 w-4 rounded-full bg-white shadow"></span>
                            </div>
                        </div>
                    </label>
                </div>

                {{-- É técnico? --}}
                <div class="card">
                    <label for="is_technician" class="flex items-center justify-between gap-3 px-4 py-3 cursor-pointer">
                        <div class="flex items-center gap-3">
                            <span class="inline-grid h-7 w-7 place-items-center rounded-full bg-white text-purple-600 ring-1 ring-purple-200">
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none">
                                    <path d="M12 3 4 9v12h16V9Z" stroke="currentColor" stroke-width="1.6"
                                          stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M9 21v-6h6v6" stroke="currentColor" stroke-width="1.6"
                                          stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <div>
                                <p class="text-sm font-medium text-slate-800">É técnico?</p>
                                <p class="text-xs text-slate-500">Marque para técnicos que registram horas em OS.</p>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <input
                                id="is_technician"
                                name="is_technician"
                                type="checkbox"
                                value="1"
                                class="peer sr-only"
                            >

                            <!-- Track (anima via padding) -->
                            <div class="relative inline-flex h-5 w-9 items-center rounded-full bg-slate-300
                                        pl-0.5 pr-0.5 transition-all duration-200 ease-in-out
                                        peer-checked:bg-purple-600 peer-checked:pl-4">
                                <span class="inline-block h-4 w-4 rounded-full bg-white shadow"></span>
                            </div>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </div>
</x-modal>
