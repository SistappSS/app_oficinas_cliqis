<x-modal modalId="modalService" formId="formService" modalTitle="Novo serviço" :input="$input">
    <div class="grid gap-4 sm:grid-cols-2">
        <x-input col="" set="" id="name" name="name" type="text" label="Serviço" placeholder="Landing Page"></x-input>
        <x-input col="" set="" class="money-brl" id="price" name="price" type="text" label="Valor do serviço"
                 placeholder="499,00"></x-input>
    </div>

    <div class="grid gap-4 sm:grid-cols-1 mb-2">
        <x-textarea col="" set="" row="3" id="description" name="description" label="Descrição"
                    placeholder="Desenvolvimento de landing page"></x-textarea>
    </div>

    <label class="text-sm font-medium" for="type">Pagamento</label>
    <div class="grid gap-4 sm:grid-cols-3">
        <x-radio-input col="" id="payment_unique" value="payment_unique" name="type" type="radio" label="Pagamento único"
                       check="1"></x-radio-input>
        <x-radio-input col="" id="monthly" value="monthly" name="type" type="radio" label="Mensal"></x-radio-input>
        <x-radio-input col="" id="yearly" value="yearly" name="type" type="radio" label="Anual"></x-radio-input>
    </div>
</x-modal>

@push('scripts')
    <script src="{{asset('assets/js/common/mask_price.js')}}"></script>
@endpush
