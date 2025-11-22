@extends('layouts.templates.template')

@section('content')
    <main id="profile-fragment" class="mx-auto max-w-7xl px-4 sm:px-6 mt-8" data-fragment>
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center gap-6">
                <div class="relative">
                    @if(auth()->user()->image)
                        <img id="avatar" alt="Foto do perfil" class="h-20 w-20 rounded-full object-cover ring-2 ring-white shadow" src="data:image/png;base64, {{Auth::user()->image}}"/>
                        <label class="absolute bottom-0 right-0 grid h-8 w-8 cursor-pointer place-items-center rounded-full bg-blue-600 text-white shadow hover:bg-blue-700">
                            <input id="avatar-input" type="file" accept="image/*" class="hidden"/>
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10Zm8-2h-3.17l-1.41-1.41A2 2 0 0 0 14.17 3H9.83a2 2 0 0 0-1.41.59L7 5H4a2 2 0 0 0-2 2v11a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a2 2 0 0 0-2-2Z"/>
                            </svg>
                        </label>
                    @else
                        <img id="avatar" alt="Foto do perfil" class="h-20 w-20 rounded-full object-cover ring-2 ring-white shadow" src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='160' height='160'><rect width='100%25' height='100%25' fill='%23e2e8f0'/><text x='50%25' y='54%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='64' fill='%2394a3b8'>👤</text></svg>"/>
                        <label class="absolute bottom-0 right-0 grid h-8 w-8 cursor-pointer place-items-center rounded-full bg-blue-600 text-white shadow hover:bg-blue-700">
                            <input id="avatar-input" type="file" accept="image/*" class="hidden"/>
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10Zm8-2h-3.17l-1.41-1.41A2 2 0 0 0 14.17 3H9.83a2 2 0 0 0-1.41.59L7 5H4a2 2 0 0 0-2 2v11a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a2 2 0 0 0-2-2Z"/>
                            </svg>
                        </label>
                    @endif
                </div>
                <div class="flex-1">
                    <h1 class="text-xl font-semibold"><span id="user-name">{{ucwords($user->name)}}</span></h1>
                    <p class="text-sm text-slate-600">
                        {{ $planLabel }} •
                        <span id="plan-status" class="text-amber-600">{{ $statusUser }}</span>
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{route('billing.index', $user->id)}}" class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Adquirir módulos</a>
                </div>
            </div>
        </section>

        <!-- Subnav -->
        <nav class="mt-6 overflow-x-auto no-scrollbar">
            <ul class="flex gap-2">
                <li>
                    <button data-tab="geral" class="tab-btn rounded-full bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700 ring-1 ring-inset ring-blue-200">Geral</button>
                </li>
                <li>
                    <button data-tab="seguranca" class="tab-btn rounded-full bg-white px-4 py-2 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-200 hover:bg-slate-50">Segurança</button>
                </li>
                <li>
                    <button data-tab="pagamentos" class="tab-btn rounded-full bg-white px-4 py-2 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-200 hover:bg-slate-50">Pagamentos</button>
                </li>
            </ul>
        </nav>

        <!-- Conteúdo -->
        <section class="mt-6 grid gap-6 lg:grid-cols-3">

            <!-- Lateral -->
            <aside class="order-last lg:order-first lg:col-span-1 space-y-6">
                @foreach($modules as $mod)
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-sm font-semibold">Módulo {{ $mod->module->name }}</h3>
                        <div class="mt-3 space-y-2 text-sm">
                            @php
                                $login        = auth()->user()->customerLogin;
                                $trialActive  = $login && !$login->subscription && $login->trial_ends_at && now()->lt($login->trial_ends_at);
                                $activeModule = $mod->expires_at?->isFuture(); // null-safe

                                $statusLabel  = $activeModule ? 'Ativo' : ($trialActive ? 'Em teste' : 'Expirado');
                                $statusClass  = $activeModule
                                    ? 'bg-emerald-50 text-emerald-700'
                                    : ($trialActive ? 'bg-blue-50 text-blue-700' : 'bg-amber-50 text-amber-700');

                                // Data a exibir: expiração do módulo OU fim do trial (se estiver em teste)
                                $expToUse = $mod->expires_at ?: ($trialActive ? $login->trial_ends_at : null);
                                if ($expToUse && ! $expToUse instanceof \Carbon\Carbon) {
                                    $expToUse = \Carbon\Carbon::parse($expToUse);
                                }

                                // Ciclo para a barra (trial / monthly / yearly)
                                $cycleForBar = $mod->expires_at ? ($mod->latestControl?->cycle ?? 'monthly') : 'trial';
                            @endphp

                            <div class="flex justify-between">
                                <span>Status</span>
                                <span class="inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-xs {{ $statusClass }}">
        {{ $statusLabel }}
    </span>
                            </div>

                            <div class="flex justify-between">
                                <span>Período</span>
                                <span>{{ $mod->latestControl?->cycle === 'yearly' ? 'Anual' : 'Mensal' }}</span>
                            </div>

                            <div class="flex justify-between">
                                <span>Próx. cobrança</span>
                                <span>{{ $expToUse?->format('d/m/Y') ?? '—' }}</span>
                            </div>

                            <div class="relative w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                                <div class="absolute inset-y-0 left-0 rounded-full progress-bar"
                                     data-exp="{{ $expToUse?->format('c') }}"
                                     data-cycle="{{ $cycleForBar }}">
                                </div>
                            </div>

                            @if($mod->userFeatures->count())
                                <div class="mt-3 border-t border-slate-200 pt-3">
                                    <p class="text-xs font-semibold text-slate-700">Funcionalidades</p>
                                    <ul class="mt-1 space-y-1 text-xs text-slate-600">
                                        @foreach($mod->userFeatures->sortBy(fn($uf) => $uf->feature->name) as $uf)
                                            <li class="flex justify-between">
                                                <span>• {{ $uf->feature->name }}</span>
                                                <span>{{ optional($uf->expires_at)->format('d/m/Y') ?: '—' }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @else
                                <p class="mt-3 text-xs text-slate-400">Sem funcionalidades individuais.</p>
                            @endif
                        </div>
                    </div>
                @endforeach

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold">Dicas rápidas</h3>
                    <ul class="mt-2 space-y-2 text-sm text-slate-600">
                        <li class="flex items-start gap-2"><span
                                class="mt-0.5 h-1.5 w-1.5 rounded-full bg-blue-600"></span>Use um e-mail profissional.
                        </li>
                        <li class="flex items-start gap-2"><span
                                class="mt-0.5 h-1.5 w-1.5 rounded-full bg-blue-600"></span>Senha forte com letras,
                            números e símbolos.
                        </li>
                        <li class="flex items-start gap-2"><span
                                class="mt-0.5 h-1.5 w-1.5 rounded-full bg-blue-600"></span>Mantenha o endereço
                            atualizado para notas fiscais.
                        </li>
                    </ul>
                </div>
            </aside>

            <!-- Principal -->
            <div class="lg:col-span-2">
                <!-- GERAl -->
                <div data-panel="geral" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-semibold">Informações gerais</h3>

                    <form class="mt-4 grid gap-4 sm:grid-cols-2" method="post" action="{{route('change-information.update', Auth::user()->id)}}">
                        @csrf

                        <div class="sm:col-span-2">
                            <label class="text-sm font-medium">Nome completo</label>
                            <input id="name" name="name" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 outline-none" placeholder="Seu nome" value="{{ucwords($user->name)}}"/>
                        </div>

                        <div>
                            <label class="text-sm font-medium">E-mail</label>
                            <input id="email" name="email" type="email"
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                                   placeholder="email@empresa.com" value="{{$user->email}}"/>
                        </div>

                        <div>
                            <label class="text-sm font-medium">Telefone</label>
                            <input id="mobilePhone" name="mobilePhone"
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                                   placeholder="(11) 99999-9999"
                                   value="{{$user->customerLogin->customer->mobilePhone ?? ""}}"/>
                        </div>

                        <div class="sm:col-span-2 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-sm font-medium">CEP</label>
                                <input id="postalCode" name="postalCode"
                                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                                       placeholder="00000-000"
                                       value="{{$user->customerLogin->customer->postalCode ?? ""}}"/>
                            </div>
                            <div>
                                <label class="text-sm font-medium">Endereço</label>
                                <input id="address" name="address"
                                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                                       placeholder="Rua Exemplo, Bairro"
                                       value="{{ucwords($user->customerLogin->customer->address ?? "")}}" readonly/>
                            </div>
                            <div>
                                <label class="text-sm font-medium">Número</label>
                                <input id="addressNumber" name="addressNumber"
                                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                                       placeholder="123"
                                       value="{{$user->customerLogin->customer->addressNumber ?? ""}}"/>
                            </div>
                            <div>
                                <label class="text-sm font-medium">Complemento</label>
                                <input id="complement" name="complement"
                                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                                       placeholder="Sala, Bloco..."
                                       value="{{ucwords($user->customerLogin->customer->complement ?? "")}}"/>
                            </div>
                            <div>
                                <label class="text-sm font-medium">Cidade</label>
                                <input id="cityName" name="cityName"
                                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                                       placeholder="São Paulo"
                                       value="{{ucwords($user->customerLogin->customer->cityName ?? "")}}" readonly/>
                            </div>
                            <div>
                                <label class="text-sm font-medium">Estado</label>
                                <input id="state" name="state"
                                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                                       placeholder="SP"
                                       value="{{strtoupper($user->customerLogin->customer->state ?? "")}}" readonly/>
                            </div>
                            <div>
                                <label class="text-sm font-medium">Bairro</label>
                                <input id="province" name="province"
                                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                                       placeholder="Alphaville"
                                       value="{{ucwords($user->customerLogin->customer->province ?? "")}}" readonly/>
                            </div>
                        </div>

                        <div class="sm:col-span-2 flex justify-end">
                            <button
                                class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800"
                                type="submit">Salvar alterações
                            </button>
                        </div>
                    </form>
                </div>

                <!-- SEGURANÇA -->
                <div data-panel="seguranca" class="hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-semibold">Segurança</h3>

                    <form class="mt-4 grid gap-4 sm:grid-cols-2" method="post"
                          action="{{route('change-password.update', Auth::user()->id)}}">
                        @csrf

                        <div class="sm:col-span-2">
                            <label class="text-sm font-medium">Senha atual</label>
                            <div class="relative">
                                <input id="p-current" name="old_password" type="password"
                                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 pr-10 outline-none"
                                       placeholder="••••••••"/>
                                <button type="button" data-toggle="#p-current"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-2 text-slate-500 hover:bg-slate-100"
                                        aria-label="Mostrar/ocultar">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="text-sm font-medium">Nova senha</label>
                            <div class="relative">
                                <input id="p-new" name="new_password" type="password"
                                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 pr-10 outline-none"
                                       placeholder="Mínimo 8 caracteres"/>
                                <button type="button" data-toggle="#p-new"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-2 text-slate-500 hover:bg-slate-100"
                                        aria-label="Mostrar/ocultar">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="mt-2">
                                <div aria-hidden="true" class="h-2 w-full overflow-hidden rounded-full bg-slate-200">
                                    <div id="p-meter"
                                         class="h-2 w-0 rounded-full bg-gradient-to-r from-rose-500 via-amber-500 to-emerald-500 transition-all duration-300"></div>
                                </div>
                                <p id="p-label" class="mt-1 text-xs text-slate-600">Força: —</p>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium">Confirmar nova senha</label>
                            <input id="p-confirm" name="password_confirmation" type="password"
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"
                                   placeholder="Repita a senha"/>
                        </div>
                        <div class="sm:col-span-2 flex justify-end">
                            <button
                                class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                                Atualizar senha
                            </button>
                        </div>


                    </form>
                </div>

                <!-- PAGAMENTOS -->
                <div data-panel="pagamentos" class="hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-semibold">Histórico de pagamentos</h3>
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-slate-500">
                            <tr>
                                <th class="px-3 py-2">Data</th>
                                <th class="px-3 py-2">Referência</th>
                                <th class="px-3 py-2">Descrição</th>
                                <th class="px-3 py-2 text-right">Valor</th>
                                <th class="px-3 py-2 text-right">Status</th>
                            </tr>
                            </thead>
                            <tbody id="pay-body" class="divide-y divide-slate-100">
                            @foreach($transactions as $t)
                                @php
                                    $isPending = $t['status'] === 'PENDING';
                                    $total = $isPending
                                        ? collect(json_decode($t['price_breakdown'],true))->sum('price')
                                        : ($t['price_paid'] ?? 0);
                                @endphp
                                <td>R$ {{ number_format($total,2,',','.') }}</td>

                                <tr>
                                    <td class="px-3 py-2">{{\Carbon\Carbon::parse($t['data'])->format('d/m/Y')}}</td>
                                    <td class="px-3 py-2">{{$t['charge_id']}}</td>
                                    <td class="px-3 py-2">{{$t['description']}}</td>
                                    <td class="px-3 py-2 text-right">{{brlPrice($total)}}</td>
                                    <td class="px-3 py-2 text-right">
                                        <span
                                            class="inline-flex rounded-full ${h.status==='Pago'?'bg-emerald-50 text-emerald-700':'bg-amber-50 text-amber-700'} px-2.5 py-1 text-xs font-medium">{{$t['status']}}</span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Modal: Adicionar/Atualizar Cartão -->
    <div id="modal-card" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm">
        <div
            class="absolute left-1/2 top-1/2 w-[min(560px,92vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h2 id="card-modal-title" class="text-lg font-semibold">Adicionar cartão</h2>
                <button data-close-card class="rounded-lg p-2 hover:bg-slate-100">
                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form id="form-card" class="mt-4 grid gap-3">
                <div>
                    <label class="text-sm font-medium">Número do cartão</label>
                    <input id="c-number" inputmode="numeric" placeholder="1234 5678 9012 3456"
                           class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"/>
                </div>
                <div class="grid sm:grid-cols-3 gap-3">
                    <div>
                        <label class="text-sm font-medium">Titular</label>
                        <input id="c-holder"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"/>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Validade (MM/AA)</label>
                        <input id="c-exp" placeholder="11/34"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"/>
                    </div>
                    <div>
                        <label class="text-sm font-medium">CVV</label>
                        <input id="c-cvv" inputmode="numeric"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none"/>
                    </div>
                </div>
                <label class="mt-1 flex items-center gap-2 text-sm"><input id="c-save" type="checkbox"
                                                                           class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                                                           checked>Salvar cartão para futuras cobranças</label>
                <div class="flex justify-between gap-2 pt-2">
                    <button id="btn-modal-delete" type="button"
                            class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-100">
                        Apagar cartão
                    </button>
                    <div class="ml-auto flex gap-2">
                        <button type="button" data-close-card
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button id="btn-card-save"
                                class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                            Salvar cartão
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast -->
    <div id="toast" class="pointer-events-none fixed bottom-5 right-5 z-50 hidden">
        <div class="rounded-xl bg-slate-900/90 px-4 py-3 text-sm font-medium text-white shadow-lg">Mensagem</div>
    </div>

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
        window.initProfile = function (root) {
            const q = s => (root.querySelector ? root : document).querySelector(s);
            const qa = s => (root.querySelector ? root : document).querySelectorAll(s);

            const avatar = q('#avatar');
            if (user.photo) avatar.src = user.photo;

            const tabs = qa('.tab-btn');
            const panels = qa('[data-panel]');

            function showTab(id) {
                panels.forEach(p => p.classList.toggle('hidden', p.getAttribute('data-panel') !== id));
                tabs.forEach(b => {
                    const active = b.dataset.tab === id;
                    b.className = 'tab-btn rounded-full px-4 py-2 text-sm font-medium ring-1 ring-inset ' + (active ? 'bg-blue-50 text-blue-700 ring-blue-200' : 'bg-white text-slate-700 ring-slate-200 hover:bg-slate-50');
                });
            }

            tabs.forEach(b => b.addEventListener('click', () => showTab(b.dataset.tab)));

            showTab('geral');

            // --- Segurança
            function togglePass(btn) {
                const input = root.querySelector(btn.getAttribute('data-toggle'));
                if (!input) return;
                input.type = input.type === 'password' ? 'text' : 'password';
            }

            qa('[data-toggle]').forEach(b => b.addEventListener('click', () => togglePass(b)));

            function strength(pw) {
                let s = 0;
                if (pw.length >= 8) s += 25;
                if (/[A-Z]/.test(pw)) s += 20;
                if (/[a-z]/.test(pw)) s += 20;
                if (/\d/.test(pw)) s += 20;
                if (/[^\w\s]/.test(pw)) s += 15;
                return Math.min(100, s);
            }

            const meter = q('#p-meter'), label = q('#p-label');

            q('#p-new').addEventListener('input', e => {
                const val = strength(e.target.value);
                meter.style.width = val + '%';
                label.textContent = 'Força: ' + (val >= 80 ? 'Excelente' : val >= 60 ? 'Boa' : val >= 40 ? 'Média' : 'Fraca');
            });

            const input = document.getElementById('avatar-input');
            const avatarImg = document.getElementById('avatar');
            const toast = document.getElementById('toast');

            if (input) {
                input.addEventListener('change', async (e) => {
                    if (!e.target.files.length) return;

                    const file = e.target.files[0];
                    const formData = new FormData();
                    formData.append('image', file); // precisa bater com $request->validate()

                    try {
                        const response = await fetch("{{ route('change-image.update') }}", {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: formData
                        });

                        const contentType = response.headers.get('content-type') || '';
                        if (!contentType.includes('application/json')) {
                            console.error('Resposta não é JSON:', await response.text());
                            alert('Erro inesperado ao atualizar imagem.');
                            return;
                        }

                        const result = await response.json();
                        if (result.success) {
                            avatarImg.src = result.image;

                            const toast = document.getElementById('toast');

                            if (toast) {
                                const msgBox = toast.querySelector('div');
                                if (msgBox) msgBox.textContent = 'Foto atualizada!';
                                toast.classList.remove('hidden');
                                setTimeout(() => toast.classList.add('hidden'), 3000);
                            }
                        } else {
                            alert('Erro ao atualizar imagem.');
                        }
                    } catch (err) {
                        console.error(err);
                        alert('Falha ao enviar a imagem.');
                    }
                });
            }
        };

        // init ao abrir direto
        window.addEventListener('DOMContentLoaded', () => window.initProfile(document));
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const bars = document.querySelectorAll('.progress-bar');

            bars.forEach(bar => {
                if (!bar.dataset.exp) return;

                const expDate = new Date(bar.dataset.exp);
                if (isNaN(expDate)) return;

                const now = new Date();
                const startDate = new Date(expDate); // base
                const cycle = bar.dataset.cycle || 'monthly';

                if (cycle === 'trial') {
                    startDate.setDate(startDate.getDate() - 14);
                } else if (cycle === 'yearly') {
                    startDate.setFullYear(startDate.getFullYear() - 1);
                } else {
                    startDate.setMonth(startDate.getMonth() - 1);
                }

                const total  = expDate - startDate;
                const passed = Math.min(total, Math.max(0, now - startDate));
                const pct    = Math.max(0, Math.min(100, Math.round((passed / total) * 100)));

                const daysLeft = Math.ceil((expDate - now) / (1000 * 60 * 60 * 24));
                let color = 'bg-green-500';
                if (daysLeft <= 3) color = 'bg-red-500';
                else if (daysLeft <= 10) color = 'bg-yellow-500';

                bar.title = daysLeft >= 0 ? `${daysLeft} dias restantes` : 'Expirado';
                bar.classList.add(color);
                bar.style.width = pct > 0 ? pct + '%' : '4px';
            });
        });
    </script>
@endsection
