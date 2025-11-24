@extends('layouts.templates.template')

@section('content')
    <div class="mx-auto max-w-5xl px-4 sm:px-6 pb-10 lg:pb-14">
        @if(session('success'))
            <div class="mb-4 rounded-xl bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        @endif


            <div class="mt-3 flex items-center gap-2 flex-wrap">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-lg font-semibold">Configurações do Orçamento (PDF)</h2>
                </div>

                <div class="ml-auto flex items-center gap-2 shrink-0">
                    <a href="{{ route('budget.create') }}"
                       class="inline-flex items-center rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-blue-800">
                        Novo orçamento
                    </a>

                    <button id="toggle-header"
                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow hover:bg-slate-50"
                            aria-expanded="true" aria-controls="header-collapsible" type="button"
                            title="Expandir/contrair cabeçalho">
                        <i id="toggle-icon" class="fa-solid fa-up-right-and-down-left-from-center"></i>
                    </button>
                </div>
            </div>

        <form class="mt-4 grid gap-4 sm:grid-cols-2" method="post" action="{{ route('budget-config.store') }}" enctype="multipart/form-data">
            @csrf

            {{-- ORG --}}
            <div class="sm:col-span-2">
                <h3 class="text-sm font-semibold text-slate-700">Informações da empresa</h3>
            </div>

            <div class="sm:col-span-2">
                <label class="text-sm font-medium">Nome</label>
                <input name="org[name]" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none"
                       placeholder="Empresa LTDA" value="{{ old('org.name', $org['name'] ?? '') }}"/>
                @error('org.name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="text-sm font-medium">Documento (CNPJ/CPF)</label>
                <input name="org[document]" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                       placeholder="00.000.000/0000-00" id="cpfCnpj" value="{{ old('org.document', $org['document'] ?? '') }}"/>
            </div>

            <div>
                <label class="text-sm font-medium">Telefone</label>
                <input name="org[phone]" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                       placeholder="(11) 99999-9999" id="mobilePhone" value="{{ old('org.phone', $org['phone'] ?? '') }}"/>
            </div>

            <div>
                <label class="text-sm font-medium">E-mail</label>
                <input name="org[email]" type="email" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                       placeholder="contato@empresa.com" value="{{ old('org.email', $org['email'] ?? '') }}"/>
            </div>

                       <div>
                <label class="text-sm font-medium">Cidade</label>
                <input name="org[city]" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                       placeholder="São Paulo" value="{{ old('org.city', $org['city'] ?? '') }}"/>
            </div>

            <div>
                <label class="text-sm font-medium">Estado</label>
                <input name="org[state]" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                       placeholder="SP" value="{{ old('org.state', $org['state'] ?? '') }}"/>
            </div>

            {{-- REPRESENTANTE --}}
            <div class="sm:col-span-2 mt-2">
                <h3 class="text-sm font-semibold text-slate-700">Representante</h3>
            </div>

            <div class="sm:col-span-2">
                <label class="text-sm font-medium">Nome</label>
                <input name="representative[name]" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                       placeholder="Nome do representante" value="{{ old('representative.name', $rep['name'] ?? '') }}"/>
            </div>

            <div>
                <label class="text-sm font-medium">Documento</label>
                <input name="representative[document]" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                       placeholder="CPF/CNPJ" value="{{ old('representative.document', $rep['document'] ?? '') }}"/>
            </div>
            <div>
                <label class="text-sm font-medium">Telefone</label>
                <input name="representative[phone]" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                       placeholder="(11) 99999-9999" value="{{ old('representative.phone', $rep['phone'] ?? '') }}"/>
            </div>
            <div>
                <label class="text-sm font-medium">E-mail</label>
                <input name="representative[email]" type="email" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                       placeholder="email@empresa.com" value="{{ old('representative.email', $rep['email'] ?? '') }}"/>
            </div>
            <div>

            <label class="text-sm font-medium">Cidade</label>
                <input name="representative[city]" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                       placeholder="Cidade" value="{{ old('representative.city', $rep['city'] ?? '') }}"/>
            </div>
            <div>
                <label class="text-sm font-medium">Estado</label>
                <input name="representative[state]" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                       placeholder="UF" value="{{ old('representative.state', $rep['state'] ?? '') }}"/>
            </div>

            {{-- TEXTOS --}}
            <div class="sm:col-span-2 mt-2">
                <h3 class="text-sm font-semibold text-slate-700">Descrições</h3>
            </div>
            <div class="sm:col-span-2">
                <label class="text-sm font-medium">Descrição de serviços</label>
                <textarea name="texts[services]" rows="4" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none">{{ old('texts.services', $texts['services'] ?? '') }}</textarea>
            </div>
            <div class="sm:col-span-2">
                <label class="text-sm font-medium">Formato de Pagamento</label>
                <textarea name="texts[payment]" rows="4" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none">{{ old('texts.payment', $texts['payment'] ?? '') }}</textarea>
            </div>

            {{-- LOGO --}}
            <div class="sm:col-span-2">
                <h3 class="text-sm font-semibold text-slate-700">Logo</h3>
            </div>
            <div>
                <label class="text-sm font-medium">Arquivo (PNG/JPG/GIF)</label>
                <input type="file" name="logo_file" accept="image/*"
                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"/>
            </div>

            <div class="sm:col-span-2">
                @if($logo)
                    <img src="{{ $logo }}" alt="Logo" style="height: {{ $logoH ?? 60 }}px; object-fit: contain;">
                @endif
            </div>

            <div class="sm:col-span-2 flex justify-end">
                <button class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800" type="submit">
                    Salvar alterações
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="{{asset('assets/js/common/mask_input.js')}}"></script>
@endpush
