<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Cliqis — Seleção de segmento</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-screen overflow-hidden grid grid-rows-[auto_1fr_auto] bg-white text-slate-900">
<header class="border-b border-slate-200">
    <div class="mx-auto max-w-7xl px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-blue-700 text-white shadow">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 13h4v8l6-12h-4V1L7 13Z"/>
                </svg>
            </span>
            <span class="text-lg font-semibold">Cliqis</span>
        </div>
        <div class="hidden sm:flex items-center gap-6">
            <a class="text-sm text-slate-600 hover:text-slate-900" href="https://wa.me/5511988313151?text=Ol%C3%A1%2C%20preciso%20de%20ajuda%20no%20Cliqis." target="_blank" rel="noopener">Ajuda</a>
            <a class="text-sm text-slate-600 hover:text-slate-900" href="https://wa.me/5511988313151?text=Ol%C3%A1%2C%20gostaria%20de%20falar%20com%20o%20suporte%20Cliqis." target="_blank" rel="noopener">Contato</a>
            <a href="{{ route('logout') }}" class="rounded-xl bg-white px-4 py-2 text-slate-700 shadow border border-slate-200 hover:bg-slate-50">Sair</a>
        </div>
    </div>
</header>

<main class="mx-auto max-w-7xl w-full px-6">
    <section class="h-full grid place-items-center">
        <div class="w-full max-w-6xl">
            <div class="text-center mb-10">
                <h1 class="text-3xl sm:text-4xl font-bold">Quem é você?</h1>
                <p class="mt-2 text-slate-600">Escolha seu perfil para personalizar a experiência.</p>
            </div>

            <form id="segment-form" class="space-y-8" method="post" action="{{ route('company-segment.store') }}">
                @csrf

                <fieldset>
                    <legend class="sr-only">Segmento</legend>

                    @php $seg = old('segment', $selectedSegment); @endphp

                    <div class="grid gap-5 md:grid-cols-3">

                        <!-- Agência -->
                        <label class="group relative cursor-pointer rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-blue-500 focus-within:border-blue-600">
                            <!-- halo/borda azul quando selecionado -->
                            <span class="pointer-events-none absolute inset-0 rounded-2xl ring-2 ring-blue-600 ring-offset-0 hidden peer-checked:block"></span>

                            <input type="radio" name="segment" value="agencia" class="peer sr-only"
                                   {{ $seg === 'agencia' ? 'checked' : '' }}>

                            <div class="flex flex-col items-center text-center gap-3 relative z-[1]">
                                <span class="grid h-12 w-12 place-items-center rounded-full bg-blue-50 text-blue-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M4 6h16v12H4zM8 6v12M16 6v12M4 10h16M4 14h16"/>
                                    </svg>
                                </span>
                                <div>
                                    <div class="text-lg font-semibold">Agência</div>
                                    <p class="mt-1 text-sm text-slate-600">Atendo múltiplos clientes.</p>
                                </div>
                            </div>

                            <!-- check -->
                            <div class="pointer-events-none absolute right-4 top-4 hidden h-6 w-6 rounded-full bg-blue-600 text-white shadow flex items-center justify-center peer-checked:flex">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                     class="h-3.5 w-3.5"
                                     viewBox="0 0 24 24"
                                     fill="none"
                                     stroke="currentColor"
                                     stroke-width="3"
                                     stroke-linecap="round"
                                     stroke-linejoin="round">
                                    <path d="M4.5 12.75l6 6 9-13.5"/>
                                </svg>
                            </div>
                        </label>

                        <!-- Empresa física -->
                        <label class="group relative cursor-pointer rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-blue-500 focus-within:border-blue-600">
                            <span class="pointer-events-none absolute inset-0 rounded-2xl ring-2 ring-blue-600 ring-offset-0 hidden peer-checked:block"></span>

                            <input type="radio" name="segment" value="empresa" class="peer sr-only"
                                   {{ $seg === 'empresa' ? 'checked' : '' }}>

                            <div class="flex flex-col items-center text-center gap-3 relative z-[1]">
                                <span class="grid h-12 w-12 place-items-center rounded-full bg-blue-50 text-blue-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M3 11l9-8 9 8v8a2 2 0 0 1-2 2h-4v-6H9v6H5a2 2 0 0 1-2-2v-8Z"/>
                                    </svg>
                                </span>
                                <div>
                                    <div class="text-lg font-semibold">Empresa física</div>
                                    <p class="mt-1 text-sm text-slate-600">Loja/clínica/oficina.</p>
                                </div>
                            </div>

                            <div class="pointer-events-none absolute right-4 top-4 hidden h-6 w-6 rounded-full bg-blue-600 text-white shadow flex items-center justify-center peer-checked:flex">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                     class="h-3.5 w-3.5"
                                     viewBox="0 0 24 24"
                                     fill="none"
                                     stroke="currentColor"
                                     stroke-width="3"
                                     stroke-linecap="round"
                                     stroke-linejoin="round">
                                    <path d="M4.5 12.75l6 6 9-13.5"/>
                                </svg>
                            </div>
                        </label>

                        <!-- Freelancer -->
                        <label class="group relative cursor-pointer rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-blue-500 focus-within:border-blue-600">
                            <span class="pointer-events-none absolute inset-0 rounded-2xl ring-2 ring-blue-600 ring-offset-0 hidden peer-checked:block"></span>

                            <input type="radio" name="segment" value="freelancer" class="peer sr-only"
                                   {{ $seg === 'freelancer' ? 'checked' : '' }}>

                            <div class="flex flex-col items-center text-center gap-3 relative z-[1]">
                                <span class="grid h-12 w-12 place-items-center rounded-full bg-blue-50 text-blue-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm-7 9a7 7 0 0 1 14 0Z"/>
                                    </svg>
                                </span>
                                <div>
                                    <div class="text-lg font-semibold">Freelancer</div>
                                    <p class="mt-1 text-sm text-slate-600">Atendimento individual.</p>
                                </div>
                            </div>

                            <div class="pointer-events-none absolute right-4 top-4 hidden h-6 w-6 rounded-full bg-blue-600 text-white shadow flex items-center justify-center peer-checked:flex">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                     class="h-3.5 w-3.5"
                                     viewBox="0 0 24 24"
                                     fill="none"
                                     stroke="currentColor"
                                     stroke-width="3"
                                     stroke-linecap="round"
                                     stroke-linejoin="round">
                                    <path d="M4.5 12.75l6 6 9-13.5"/>
                                </svg>
                            </div>
                        </label>

                    </div>
                </fieldset>

                <div class="text-center">
                    <button id="btn-continue" type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-blue-700 px-6 py-3 font-semibold text-white shadow transition hover:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed"
                            {{ $seg ? '' : 'disabled' }}>
                        Continuar
                    </button>

                    <div class="mt-4 text-sm text-slate-600">
                        <button type="button" id="ns-btn"
                                class="inline-flex items-center gap-1.5 align-middle text-slate-600 hover:text-slate-900">
                            <span class="leading-none">Não tem certeza?</span>
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M9.09 9a3 3 0 1 1 5.83 1c0 2-3 2-3 4"/>
                                <path d="M12 17h.01"/>
                            </svg>
                        </button>

                        <span id="ns-tip" class="ml-2 hidden text-slate-500">
                            Escolha o que mais se aproxima. Você poderá mudar depois.
                        </span>
                    </div>
                </div>
            </form>
        </div>
    </section>
</main>

<footer class="py-6 text-center text-xs text-slate-500">© 2025 Cliqis. Todos os direitos reservados.</footer>

<script>
    const form   = document.getElementById('segment-form');
    const btn    = document.getElementById('btn-continue');
    const tipBtn = document.getElementById('ns-btn');
    const tip    = document.getElementById('ns-tip');

    form.addEventListener('change', (e) => {
        if (e.target.name === 'segment') {
            btn.disabled = false;
        }
    });

    tipBtn.addEventListener('click', () => {
        tip.classList.toggle('hidden');
    });
</script>
</body>
</html>
