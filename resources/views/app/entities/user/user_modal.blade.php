<x-modal modalId="user-modal" formId="user-form" modalTitle="Editar usuário" :input="$input">
    <input type="hidden" id="user_id" name="user_id">

    <div class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-input col="" set="" id="name" name="name" type="text"
                     label="Nome"
                     placeholder="Nome do usuário"></x-input>

            <x-input col="" set="" id="email" name="email" type="email"
                     label="E-mail"
                     placeholder="email@exemplo.com"></x-input>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-1">
                <label for="user_type" class="block text-sm font-medium text-slate-700">
                    Tipo de usuário
                </label>
                <input id="user_type" name="user_type" type="text"
                       class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-700 outline-none"
                       readonly>
            </div>

            <div class="space-y-1">
                <label for="created_at" class="block text-sm font-medium text-slate-700">
                    Criado em
                </label>
                <input id="created_at" name="created_at" type="text"
                       class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-700 outline-none"
                       readonly>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-input col="" set="" id="password" name="password" type="password"
                     label="Nova senha (opcional)"
                     placeholder="Deixe em branco para não alterar"></x-input>

            <x-input col="" set="" id="password_confirmation" name="password_confirmation" type="password"
                     label="Confirmar nova senha"
                     placeholder="Repita a nova senha"></x-input>
        </div>

        <div class="section-title">
            Perfis
        </div>

        <div id="roles_edit_list" class="flex flex-wrap gap-2 text-xs"></div>
    </div>
</x-modal>
