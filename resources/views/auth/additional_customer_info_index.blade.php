@extends('layouts.templates.guest')
@section('guest-content')

<section class="h-full grid place-items-center">
    <div class="w-full max-w-5xl rounded-2xl border border-slate-200 bg-white shadow p-6 sm:p-8">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold">Informações da empresa</h1>
            <p class="mt-2 text-slate-600">Comece com o básico. Você pode editar depois.</p>
        </div>

        <form enctype="multipart/form-data" method="post" action="{{route('additional-customer-info.store')}}" class="grid gap-6">
            @csrf

            <!-- Logo -->
            <div class="flex items-center gap-4">
                <div class="relative h-14 w-14 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                    <img id="logo-preview" alt="Logo da empresa" class="h-full w-full object-cover hidden"/>
                    <div id="logo-fallback" class="grid h-full w-full place-items-center text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="1.6">
                            <rect x="3" y="3" width="18" height="18" rx="3"/>
                            <path d="M8 13l3 3 5-6"/>
                        </svg>
                    </div>
                </div>
                <label
                    class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-700 shadow hover:bg-slate-50">
                    <input id="logo" name="image" type="file" accept="image/*" class="hidden"/>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.8">
                        <path d="M12 5v14m-7-7h14"/>
                    </svg>
                    Enviar logo
                </label>
            </div>

            <!-- Campos -->
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="name">Nome empresarial</label>
                    <input id="name" name="company_name" required
                           class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none"
                           placeholder="Ex.: Agência Alfa"/>
                </div>

            <div>
    <label class="mb-1 block text-sm font-medium text-slate-700" for="cpfCnpj">CNPJ / CPF</label>
    <input id="cpfCnpj" name="cpfCnpj"
           value="{{ old('cpfCnpj') }}"
           class="w-full rounded-xl border @error('cpfCnpj') border-red-500 @else border-slate-300 @enderror bg-white px-4 py-2.5 placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none"
           placeholder="00.000.000/0001-00"/>

    @error('cpfCnpj')
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="mobilePhone">Telefone</label>
                    <input id="mobilePhone" name="mobilePhone" type="tel"
                           class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none"
                           placeholder="(11) 99999-9999"/>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="email">E-mail para contato</label>
                    <input id="email" name="company_email" type="email"
                           class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none"
                           placeholder="voce@empresa.com"/>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="site">
                        Site <span class="text-slate-400">(opcional)</span>
                    </label>
                    <input id="site" name="website_url" type="text"
                           class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none"
                           placeholder="https://exemplo.com"/>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="role">Seu papel na empresa</label>
                    <select id="role" name="role"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none">
                        <option value="" selected disabled>Selecione…</option>
                        <option value="dono">Dono</option>
                        <option value="socio">Sócio / Partner</option>
                        <option value="gestor">Gestor</option>
                        <option value="funcionario">Funcionário</option>
                    </select>
                </div>

                <!-- Endereço -->
                <div class="md:col-span-3 grid gap-6">

                    <!-- Linha 1: CEP / Endereço / Número / Complemento -->
                    <div class="grid gap-4 md:grid-cols-4">
                        <div>
                            <label class="text-sm font-medium">CEP</label>
                            <input id="postalCode" name="postalCode"
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                                   placeholder="00000-000" value=""/>
                        </div>

                        <div>
                            <label class="text-sm font-medium">Endereço</label>
                            <input id="address" name="address"
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                                   placeholder="Rua Exemplo" value="" />
                        </div>

                        <div>
                            <label class="text-sm font-medium">Número</label>
                            <input id="addressNumber" name="addressNumber"
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                                   placeholder="123" value=""/>
                        </div>

                        <div>
                            <label class="text-sm font-medium">Complemento</label>
                            <input id="complement" name="complement"
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                                   placeholder="Sala, Bloco..." value=""/>
                        </div>
                    </div>

                    <!-- Linha 2: Cidade / Bairro / Estado -->
                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="text-sm font-medium">Cidade</label>
                            <input id="cityName" name="cityName"
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                                   placeholder="São Paulo" value="" />
                        </div>

                        <div>
                            <label class="text-sm font-medium">Bairro</label>
                            <input id="province" name="province"
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                                   placeholder="Alphaville" value="" />
                        </div>

                        <div>
                            <label class="text-sm font-medium">Estado</label>
                            <input id="state" name="state"
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                                   placeholder="SP" value="" />
                        </div>
                    </div>

                </div>
                <!-- /Endereço -->

            </div>

            <!-- Ações -->
            <div class="flex items-center justify-between pt-2">
                <a href="{{('login')}}"
                   class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-slate-700 shadow hover:bg-slate-50">
                    Voltar
                </a>

                <button type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-blue-700 px-6 py-3 font-semibold text-white shadow transition hover:bg-blue-800">
                    Salvar e continuar
                </button>
            </div>
        </form>
    </div>
</section>


    @push('scripts')
        <script src="{{asset('assets/js/common/mask_input.js')}}"></script>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            $(document).ready(function () {
                $('#postalCode').on('blur', function () {
                    var cep = $(this).val().replace(/\D/g, '');

                    if (cep.length === 8) {
                        $.ajax({
                            url: `https://viacep.com.br/ws/${cep}/json/`,
                            type: 'GET',
                            dataType: 'json',
                            success: function (data) {
                                if (!("erro" in data)) {
                                    $('#address').val(data.logradouro).prop('readonly', false);
                                    $('#province').val(data.bairro).prop('readonly', false);
                                    $('#cityName').val(data.localidade).prop('readonly', false);
                                    $('#state').val(data.uf).prop('readonly', false);
                                } else {
                                    alert('CEP não encontrado.');
                                }
                            },
                            error: function () {
                                alert('Erro ao consultar o CEP. Tente novamente.');
                            }
                        });
                    } else {
                        alert('Por favor, insira um CEP válido.');
                    }
                });
            });
        </script>

        <script>
            const logo = document.getElementById('logo');
            const preview = document.getElementById('logo-preview');
            const fallback = document.getElementById('logo-fallback');

            logo.addEventListener('change', () => {
                const f = logo.files?.[0]; if (!f) return;
                if (f.size > 1024*1024) { alert('Imagem acima de 1MB'); logo.value=''; return; }
                const url = URL.createObjectURL(f);
                preview.src = url; preview.classList.remove('hidden'); fallback.classList.add('hidden');
            });
        </script>
        

<script>
    document.getElementById('cpfCnpj').addEventListener('blur', function () {
        const digits = this.value.replace(/\D/g, '');
        const hasLength = (digits.length === 11 || digits.length === 14);

        if (!hasLength && digits.length > 0) {
            this.classList.add('border-red-500');
        } else {
            this.classList.remove('border-red-500');
        }
    });
</script>

    @endpush
@endsection
