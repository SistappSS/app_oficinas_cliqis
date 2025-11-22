<x-modal modalId="modalModule" formId="formModule" :input="$input">
    <div class="row">
        <x-input col="12" set="" id="name" name="name" type="text" label="Módulo" placeholder="Financeiro"></x-input>
    </div>

    <div class="row">
        <x-textarea col="12" set="" row="4" id="description" name="description" label="Descrição" placeholder="Esse módulo irá habilitar as opções .."></x-textarea>
    </div>

    <div class="row mt-3 mb-1">
        <x-input col="12 col-sm-8" set="" id="price" name="price" type="text" label="Valor" placeholder="R$ 4,99"></x-input>
    </div>

    <div class="row mx-3">
        <div class="row d-flex flex-column">
            <label class="text-muted fw-bold">Permissão</label>

            <ul style="margin: 0; padding: 0; list-style: none;">
                @foreach(Spatie\Permission\Models\Role::all() as $permission)
                    <li><x-radio-input col="12" id="{{$permission->name}}" name="permission" type="radio" label="{{ucwords($permission->name)}}"></x-radio-input></li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="row mx-1 my-3">
        <x-check-input col="6" id="is_active" name="is_active" type="checkbox" label="Módulo ativo?" check="1"></x-check-input>
    </div>
</x-modal>
