@push('styles')
<style>
  /* Escopo só deste modal */
  #client-modal .section-title{
    display:flex;align-items:center;gap:.5rem;
    color:#0f172a;font-weight:600
  }
  #client-modal .section-title .dot{
    display:inline-grid;place-items:center;
    width:20px;height:20px;border-radius:9999px;
    background:rgb(219 234 254);color:rgb(37 99 235)
  }

  #client-modal .card{
    border:1px solid rgb(191 219 254);           /* blue-200 */
    background:rgba(239,246,255,.6);             /* blue-50/60 */
    border-radius:1rem;padding:1rem
  }

  /* Inputs padrão (sem mudar backend) */
  #client-modal input[type="text"],
  #client-modal input[type="email"],
  #client-modal input[type="tel"],
  #client-modal input[type="password"],
  #client-modal input[type="date"],
  #client-modal input[type="number"],
  #client-modal select,
  #client-modal textarea{
    width:100%;
    border:1px solid rgb(226 232 240);           /* slate-200 */
    background:rgb(248 250 252);                 /* slate-50 */
    border-radius:.75rem;
    padding:.75rem 1rem;
    color:rgb(15 23 42);                         /* slate-900 */
  }
  #client-modal input:focus,
  #client-modal select:focus,
  #client-modal textarea:focus{
    outline:0;
    border-color:rgb(37 99 235);                 /* blue-600 */
    box-shadow:0 0 0 4px rgba(37,99,235,.14);    /* ring azul */
    background:#fff;
  }

  #client-modal label{
    font-size:.875rem;
    color:rgb(51 65 85);                          /* slate-700 */
    font-weight:500
  }
</style>
@endpush

<x-modal modalId="client-modal" formId="client-form" modalTitle="Novo cliente" :input="$input">
    <div class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-input col="" set="" id="name" name="name" type="text" label="Nome do cliente" placeholder="Sistapp Soluções e Sistemas"></x-input>
            <x-input col="" set="" id="cpfCnpj" name="cpfCnpj" type="text" label="CNPJ/CPF" placeholder="CNPJ/CPF"></x-input>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-input col="12 col-sm-8" set="" id="mobilePhone" name="mobilePhone" type="text" label="Telefone" placeholder="(11) 99999-9999"></x-input>
            <x-input col="12 col-sm-8" set="" id="email" name="email" type="email" label="E-mail" placeholder="john-doe@email.com"></x-input>
        </div>

        <!-- Card do status -->
        <div class="card">
            <div class="grid gap-4 sm:grid-cols-2 items-center">
                <div class="flex items-center gap-3">
                    <span class="inline-grid h-7 w-7 place-items-center rounded-full bg-white text-blue-600 ring-1 ring-blue-200">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none">
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/>
                            <path d="m9.5 12 2 2 3-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm font-medium text-slate-800">Cliente ativo?</p>
                        <p class="text-xs text-slate-500">Habilite para ativar o acesso/relacionamento</p>
                    </div>
                </div>
                <div>
                    <x-check-input col="3" id="is_active" name="is_active" type="checkbox" label="Ativo" check="1"></x-check-input>
                </div>
            </div>
        </div>

        <!-- Título seção endereço -->
        <div class="section-title">
            <span class="dot">
                <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none">
                    <path d="M12 21s7-6.3 7-11a7 7 0 0 0-14 0c0 4.7 7 11 7 11Z" stroke="currentColor" stroke-width="1.6"/>
                    <circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.6"/>
                </svg>
            </span>
            Endereço
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-input col="12 col-sm-8" set="" id="postalCode" name="postalCode" type="text" label="CEP" placeholder="00000-000"></x-input>
            <x-input col="" set="" id="addressNumber" name="addressNumber" type="text" label="N°" placeholder="130"></x-input>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-input col="" set="" id="address" name="address" type="text" label="Endereço" placeholder="..." disable="1"></x-input>
            <x-input col="" set="" id="province" name="province" type="text" label="Bairro" placeholder="..." disable="1"></x-input>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-input col="" set="" id="cityName" name="cityName" type="text" label="Cidade" placeholder="..." disable="1"></x-input>
            <x-input col="" set="" id="state" name="state" type="text" label="Estado" placeholder="..." disable="1"></x-input>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-input col="" set="" id="complement" name="complement" type="text" label="Complemento" placeholder="Apartamento ..."></x-input>
        </div>
    </div>
</x-modal>

@push('scripts')
    <script src="{{asset('assets/js/common/mask_input.js')}}"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
      $(function () {
        $('#postalCode').on('blur', function () {
          var cep = $(this).val().replace(/\D/g, '');
          if (cep.length !== 8) { alert('Por favor, insira um CEP válido.'); return; }

          $.ajax({
            url: `https://viacep.com.br/ws/${cep}/json/`,
            type: 'GET',
            dataType: 'json',
            success: function (data) {
              if (!("erro" in data)) {
                $('#address').val(data.logradouro).prop('readonly', false).prop('disabled', false);
                $('#province').val(data.bairro).prop('readonly', false).prop('disabled', false);
                $('#cityName').val(data.localidade).prop('readonly', false).prop('disabled', false);
                $('#state').val(data.uf).prop('readonly', false).prop('disabled', false);
              } else {
                alert('CEP não encontrado.');
              }
            },
            error: function () {
              alert('Erro ao consultar o CEP. Tente novamente.');
            }
          });
        });
      });
    </script>
@endpush
