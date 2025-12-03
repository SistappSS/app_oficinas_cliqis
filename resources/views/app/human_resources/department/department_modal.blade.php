<x-modal modalId="department-modal" formId="department-form" modalTitle="Novo departamento" :input="$input">
    <input type="hidden" id="department_id" name="department_id">

    <div class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-input col="" set="" id="name" name="name" type="text"
                     label="Nome do departamento"
                     placeholder="Ex.: Manutenção, Compras, Comercial"></x-input>

            <x-input col="" set="" id="description" name="description" type="text"
                     label="Descrição (opcional)"
                     placeholder="Responsável por..."></x-input>
        </div>
    </div>
</x-modal>
