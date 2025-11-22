<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Cliqis — Navbar + Hero</title>
    <meta name="description" content="Conecte clientes, pedidos e finanças em minutos. CRM modular, simples e com automações de cobrança." />
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        cliBlue: {
                            50: '#EEF2FF',
                            100: '#E0E7FF',
                            500: '#2563EB',
                            600: '#1D4ED8',
                            700: '#1E40AF'
                        }
                    },
                    boxShadow: {
                        soft: '0 10px 30px rgba(37,99,235,.15)'
                    }
                }
            }
        }
    </script>
    <script defer src="https://unpkg.com/lucide@latest"></script>
    <style>
        @media (prefers-reduced-motion: reduce){*{animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important}}
    </style>
</head>
<body class="bg-white text-slate-900 antialiased">

<!-- ===================== NAVBAR ===================== -->
<header class="sticky top-0 z-50 bg-white/90 backdrop-blur border-b border-slate-200/70">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <!-- Logo -->
        <a href="#" class="flex items-center gap-2">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-[#2465EA] to-[#0EA4E8] text-white font-bold shadow-soft">⚡</span>
            <span class="text-lg font-semibold tracking-tight">Cliqis</span>
        </a>

        <!-- Links desktop -->
        <nav class="hidden md:flex items-center gap-6 text-sm text-slate-600">
            <a class="hover:text-slate-900" href="#solucoes">Soluções</a>
            <a class="hover:text-slate-900" href="#diferenciais">Diferenciais</a>
            <a class="hover:text-slate-900" href="#recursos">Recursos</a>
            <a class="hover:text-slate-900" href="#planos">Planos</a>
            <a class="hover:text-slate-900" href="#duvidas">Dúvidas</a>
            <a class="hover:text-slate-900" href="#afiliado">Seja um afiliado</a>
        </nav>

        <!-- Ações -->
        <div class="hidden md:flex items-center gap-3">
            <a href="{{route('login')}}" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Login</a>
            <a href="{{route('register')}}" class="inline-flex items-center justify-center rounded-full bg-cliBlue-600 px-4 py-2 text-sm font-semibold text-white shadow-soft hover:bg-cliBlue-700">Criar conta</a>
        </div>

        <!-- Mobile toggle -->
        <button aria-label="Abrir menu" class="md:hidden inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-700" onclick="document.getElementById('mnav').classList.toggle('hidden')">☰</button>
    </div>

    <!-- Menu mobile -->
    <div id="mnav" class="md:hidden hidden border-t border-slate-200">
        <div class="mx-auto max-w-7xl px-4 py-4 flex flex-col gap-3 text-slate-700 text-sm">
            <a href="#solucoes">Soluções</a>
            <a href="#diferenciais">Diferenciais</a>
            <a href="#recursos">Recursos</a>
            <a href="#planos">Planos</a>
            <a href="#duvidas">Dúvidas</a>
            <a href="#afiliado">Seja um afiliado</a>
            <div class="pt-2 flex gap-3">
                <a href="{{route('login')}}" class="flex-1 inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-4 py-2">Login</a>
                <a href="{{route('register')}}" class="flex-1 inline-flex items-center justify-center rounded-full bg-cliBlue-600 px-4 py-2 font-semibold text-white">Criar conta</a>
            </div>
        </div>
    </div>
</header>

<!-- ===================== HERO ===================== -->
<section class="relative overflow-hidden bg-white text-slate-900">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-16 pb-28"> <!-- pb extra para a sobreposição da trust bar -->

        <!-- Badge -->
        <div class="flex justify-center">
            <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-100 px-3 py-1">
                <span class="inline-flex items-center rounded-full bg-blue-600 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white">crm</span>
                <span class="text-[12px] font-medium text-slate-600">Para freelancers, agências e oficinas</span>
            </div>
        </div>

        <!-- Headline -->
        <h1 class="mt-6 text-center font-extrabold tracking-tight text-4xl sm:text-5xl lg:text-6xl leading-tight">
            <span class="block">Conecte clientes,</span>
            <span class="block">pedidos e finanças</span>
            <span class="mt-2 inline-flex items-center justify-center gap-2">
          <span class="text-slate-900/90">em</span>
          <span class="inline-flex items-center rounded-full bg-blue-600 px-5 py-1.5 text-white text-3xl sm:text-4xl shadow-md">minutos</span>
        </span>
        </h1>

        <!-- Subheadline -->
        <p class="mt-5 mx-auto max-w-2xl text-center text-slate-600 text-base sm:text-lg">
            O Cliqis é um CRM moderno e modular — simples de usar, com automações de cobrança e os módulos certos para o seu dia a dia. Você monta o plano e paga só pelo que precisa.
        </p>

        <!-- CTAs -->
        <div class="mt-7 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="#criar" class="inline-flex items-center justify-center rounded-full bg-cliBlue-600 px-6 py-3 text-white font-semibold shadow-soft hover:bg-cliBlue-700">Teste grátis agora</a>
            <a href="#conheca" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-6 py-3 text-slate-700 hover:bg-slate-50">Conheça mais</a>
        </div>
        <p class="mt-3 text-center text-xs text-slate-500">Sem cartão no teste • Cancele quando quiser • Planos customizáveis</p>

        <!-- GUIA RÁPIDO -->
        <nav aria-label="Guia rápido" class="mt-10">
            <ul id="quickbar" class="mx-auto max-w-5xl flex justify-center gap-6 px-4 py-5 overflow-x-auto snap-x snap-mandatory [-ms-overflow-style:none] [scrollbar-width:none]">
                <style>#quickbar::-webkit-scrollbar{display:none}</style>
                <li class="snap-start">
                    <div class="group inline-flex flex-col items-center gap-2">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl border border-slate-200 bg-white text-neutral-700 opacity-90 shadow-sm transform-gpu transition-transform duration-150 ease-out group-hover:scale-[1.05]"><i data-lucide="users" class="h-5 w-5"></i></span>
                        <span class="text-xs font-medium text-slate-700">Time</span>
                    </div>
                </li>
                <li class="snap-start">
                    <div class="group inline-flex flex-col items-center gap-2">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl border border-slate-200 bg-white text-neutral-700 opacity-90 shadow-sm transform-gpu transition-transform duration-150 ease-out group-hover:scale-[1.05]"><i data-lucide="book-user" class="h-5 w-5"></i></span>
                        <span class="text-xs font-medium text-slate-700">Contatos</span>
                    </div>
                </li>
                <li class="snap-start">
                    <div class="group inline-flex flex-col items-center gap-2">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl border border-slate-200 bg-white text-neutral-700 opacity-90 shadow-sm transform-gpu transition-transform duration-150 ease-out group-hover:scale-[1.05]"><i data-lucide="list-checks" class="h-5 w-5"></i></span>
                        <span class="text-xs font-medium text-slate-700">Tarefas</span>
                    </div>
                </li>
                <li class="snap-start">
                    <div class="group inline-flex flex-col items-center gap-2">
                        <span class="grid h-16 w-16 place-items-center rounded-2xl border border-blue-600 bg-blue-600 text-white shadow-md transform-gpu -translate-y-1.5 transition-transform duration-150 ease-out group-hover:scale-[1.05]"><i data-lucide="layout-dashboard" class="h-5 w-5"></i></span>
                        <span class="text-xs font-semibold text-slate-900">Dashboard</span>
                    </div>
                </li>
                <li class="snap-start">
                    <div class="group inline-flex flex-col items-center gap-2">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl border border-slate-200 bg-white text-neutral-700 opacity-90 shadow-sm transform-gpu transition-transform duration-150 ease-out group-hover:scale-[1.05]"><i data-lucide="wallet" class="h-5 w-5"></i></span>
                        <span class="text-xs font-medium text-slate-700">Financeiro</span>
                    </div>
                </li>
                <li class="snap-start">
                    <div class="group inline-flex flex-col items-center gap-2">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl border border-slate-200 bg-white text-neutral-700 opacity-90 shadow-sm transform-gpu transition-transform duration-150 ease-out group-hover:scale-[1.05]"><i data-lucide="link-2" class="h-5 w-5"></i></span>
                        <span class="text-xs font-medium text-slate-700">Links Bio</span>
                    </div>
                </li>
                <li class="snap-start">
                    <div class="group inline-flex flex-col items-center gap-2">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl border border-slate-200 bg-white text-neutral-700 opacity-90 shadow-sm transform-gpu transition-transform duration-150 ease-out group-hover:scale-[1.05]"><i data-lucide="message-circle-more" class="h-5 w-5"></i></span>
                        <span class="text-xs font-medium text-slate-700">WhatsApp IA</span>
                    </div>
                </li>
            </ul>
        </nav>

        <!-- Mock do Dashboard + TRUST overlay full-width -->
        <div class="relative z-0 mt-8 mx-auto max-w-5xl" id="mock-wrap">
            <div class="relative z-10 rounded-2xl border border-slate-200 bg-white p-3 shadow-[0_32px_128px_rgba(14,164,232,0.35),0_12px_30px_rgba(0,0,0,0.08)]">
                <div class="relative">
                    <img src="{{asset('assets/img/home/banner.png')}}" alt="Preview do painel Cliqis" class="w-full rounded-xl border border-slate-200/60 relative z-0" loading="lazy" decoding="async" />
                    <!-- Gradiente apenas sobre a imagem (rodapé branco > transparente) -->
                    <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 bottom-0 h-16 sm:h-24 rounded-b-xl bg-gradient-to-t from-white via-white/80 to-transparent z-10"></div>
                </div>
            </div>

            <!-- TRUST BAR full-width com carrossel (direita) sobrepondo a base do mock -->
            <!-- TRUST BAR full-width com carrossel FULL-WIDTH sobrepondo a base do mock -->
            <div class="absolute left-1/2 -translate-x-1/2 w-screen -bottom-6 sm:-bottom-8 z-20">
                <div class="bg-gradient-to-r from-[#2465EA] to-[#0EA4E8]">
                    <div class="mx-auto max-w-7xl px-4 sm:px-2 lg:px-8 py-5 pb-0">
                        <p class="text-center text-white/90 text-[11px] font-medium uppercase tracking-wider">
                            Quem já confia no Cliqis
                        </p>
                    </div>

                    <!-- Carrossel FULL-WIDTH -->
                    <div class="relative left-1/2 -translate-x-1/2 w-screen overflow-hidden py-2">
                        <ul class="trust-marquee flex items-center gap-8 sm:gap-10 px-4 py-4">
                            <!-- Lote A (troque por <img> com os logos) -->
                            <li class="logo-pill">LOGO</li>
                            <li class="logo-pill">LOGO</li>
                            <li class="logo-pill">LOGO</li>
                            <li class="logo-pill">LOGO</li>
                            <li class="logo-pill">LOGO</li>
                            <li class="logo-pill">LOGO</li>
                            <!-- Lote B duplicado para loop contínuo -->
                            <li class="logo-pill">LOGO</li>
                            <li class="logo-pill">LOGO</li>
                            <li class="logo-pill">LOGO</li>
                            <li class="logo-pill">LOGO</li>
                            <li class="logo-pill">LOGO</li>
                            <li class="logo-pill">LOGO</li>
                        </ul>
                    </div>

                    <!-- estilos locais do carrossel -->
                    <style>
                        /* anima para a DIREITA */
                        #mock-wrap .trust-marquee{display:flex;width:max-content;animation:trust-right 26s linear infinite}
                        #mock-wrap .trust-marquee:hover{animation-play-state:paused}
                        @keyframes trust-right{from{transform:translateX(-50%)} to{transform:translateX(0)}}
                        @media (max-width:1024px){#mock-wrap .trust-marquee{animation-duration:20s}}
                        @media (prefers-reduced-motion:reduce){#mock-wrap .trust-marquee{animation:none;transform:none}}
                        /* pills dos logos */
                        #mock-wrap .logo-pill{
                            display:inline-flex;align-items:center;justify-content:center;
                            height:34px;width:100px;border-radius:10px;
                            color:#fff;font-size:11px;font-weight:700;letter-spacing:.04em;
                            background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.28);
                            backdrop-filter:saturate(120%) blur(2px);
                            box-shadow:inset 0 2px 0 rgba(255,255,255,.08),0 4px 18px rgba(0,0,0,.08);
                            user-select:none;white-space:nowrap
                        }
                        /* Para logos reais:
                           <img src="/logos/empresa.svg" alt="Empresa" class="h-6 w-auto opacity-90" /> */
                    </style>
                </div>
            </div>


        </div>
    </div>



    <!-- ===================== SEÇÃO — MÓDULOS (CARDS COM BENEFÍCIO) ===================== -->

    <section id="modulos" class="py-10 bg-white">
        <style>
            @keyframes fadeInUp { from {opacity:.0; transform: translateY(12px)} to {opacity:1; transform: translateY(0)} }
            #modulos .reveal { opacity: 0; transform: translateY(12px) }
            #modulos .reveal.is-visible { animation: fadeInUp .6s ease-out both }
        </style>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-slate-900">Módulos do Cliqis</h2>
                <p class="mt-3 text-slate-600">Ative só o que precisa. Cresça plugando novos módulos quando quiser.</p>
            </div>

            <ul class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Faturamento -->
                <li class="reveal rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-400">
                    <div class="flex items-start gap-3">
          <span class="inline-grid h-10 w-10 place-items-center rounded-xl bg-blue-600/10 text-blue-600">
            <i data-lucide="receipt" class="h-5 w-5"></i>
          </span>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Faturamento</h3>
                            <p class="mt-1 text-sm text-slate-600">Boletos, NF e lembretes automáticos de cobrança.</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] text-slate-600">Links de pagamento</span>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] text-slate-600">Histórico de envio</span>
                            </div>
                        </div>
                    </div>
                </li>

                <!-- Financeiro -->
                <li class="reveal rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-400">
                    <div class="flex items-start gap-3">
          <span class="inline-grid h-10 w-10 place-items-center rounded-xl bg-indigo-600/10 text-indigo-600">
            <i data-lucide="wallet" class="h-5 w-5"></i>
          </span>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Financeiro</h3>
                            <p class="mt-1 text-sm text-slate-600">Fluxo de caixa, MRR, recebíveis e relatórios.</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] text-slate-600">MRR em tempo real</span>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] text-slate-600">Exportar CSV/PDF</span>
                            </div>
                        </div>
                    </div>
                </li>

                <!-- Pedidos -->
                <li class="reveal rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-400">
                    <div class="flex items-start gap-3">
          <span class="inline-grid h-10 w-10 place-items-center rounded-xl bg-emerald-600/10 text-emerald-600">
            <i data-lucide="package" class="h-5 w-5"></i>
          </span>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Pedidos</h3>
                            <p class="mt-1 text-sm text-slate-600">Vendas, fulfillment e tracking sem planilhas.</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] text-slate-600">Status e prazos</span>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] text-slate-600">Notificações</span>
                            </div>
                        </div>
                    </div>
                </li>

                <!-- Orçamentos -->
                <li class="reveal rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-400">
                    <div class="flex items-start gap-3">
          <span class="inline-grid h-10 w-10 place-items-center rounded-xl bg-fuchsia-600/10 text-fuchsia-600">
            <i data-lucide="file-text" class="h-5 w-5"></i>
          </span>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Orçamentos</h3>
                            <p class="mt-1 text-sm text-slate-600">Propostas de alto padrão, versões e aprovações.</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] text-slate-600">Modelos pro</span>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] text-slate-600">Assinatura simples</span>
                            </div>
                        </div>
                    </div>
                </li>

                <!-- Estoque -->
                <li class="reveal rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-400">
                    <div class="flex items-start gap-3">
          <span class="inline-grid h-10 w-10 place-items-center rounded-xl bg-amber-600/10 text-amber-600">
            <i data-lucide="boxes" class="h-5 w-5"></i>
          </span>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Estoque</h3>
                            <p class="mt-1 text-sm text-slate-600">Níveis, rastreio e alertas de ruptura.</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] text-slate-600">SKU & lotes</span>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] text-slate-600">Alertas em tempo real</span>
                            </div>
                        </div>
                    </div>
                </li>

                <!-- Suporte -->
                <li class="reveal rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-400 sm:col-span-2 lg:col-span-1">
                    <div class="flex items-start gap-3">
          <span class="inline-grid h-10 w-10 place-items-center rounded-xl bg-teal-600/10 text-teal-600">
            <i data-lucide="life-buoy" class="h-5 w-5"></i>
          </span>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Suporte</h3>
                            <p class="mt-1 text-sm text-slate-600">Tickets e base de conhecimento.</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] text-slate-600">SLA claro</span>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] text-slate-600">FAQ integrado</span>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>

            <!-- Microcopy + CTA -->
            <div class="mt-6 text-center">
                <p class="text-xs text-slate-500">Ative/desative módulos a qualquer momento. Pague só pelo que usar.</p>
                <a href="#criar" class="mt-4 inline-flex items-center justify-center rounded-full bg-cliBlue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-cliBlue-700">
                    Criar módulos
                </a>
            </div>
        </div>

        <script>
            (function () {
                const root = document.getElementById('modulos');
                if (!root) return;
                const els = root.querySelectorAll('.reveal');
                const io = new IntersectionObserver((entries) => {
                    entries.forEach(e => {
                        if (e.isIntersecting) {
                            e.target.classList.add('is-visible');
                            io.unobserve(e.target);
                        }
                    });
                }, { threshold: 0.2 });
                els.forEach(el => io.observe(el));
                try { if (window.lucide) window.lucide.createIcons(); } catch (e) {}
            })();
        </script>
    </section>

    <!-- ===================== SEGMENTOS (com gradiente full-width) ===================== -->
    <section id="segmentos"
             class="relative left-1/2 -translate-x-1/2 w-screen py-20
         bg-gradient-to-r from-[#2465EA] to-[#0EA4E8]">
        <style>
            @keyframes fadeInUp { from {opacity:.0; transform: translateY(12px)} to {opacity:1; transform: translateY(0)} }
            #segmentos .reveal { opacity: 0; transform: translateY(12px) }
            #segmentos .reveal.is-visible { animation: fadeInUp .6s ease-out both }
        </style>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 grid gap-10 lg:grid-cols-12 items-center">
            <!-- Texto -->
            <div class="lg:col-span-5 text-center lg:text-left">
                <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-white">
                    Feito para cada segmento
                </h2>
                <p class="mt-3 text-white/90">
                    Escolha o seu e veja como o Cliqis se adapta.
                </p>
                <p class="mt-4 text-sm text-white/80">
                    Ative apenas os módulos essenciais para sua operação e evolua no seu ritmo — sem complexidade.
                </p>

                <a href="#descobrir-segmento"
                   class="mt-6 inline-flex items-center justify-center rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-[#1D4ED8] shadow-soft hover:bg-white/95">
                    Quero descobrir meu segmento
                </a>
            </div>

            <!-- 3 cards -->
            <div class="lg:col-span-7 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Freelancer -->
                <article class="reveal rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-[#2465EA]">
                    <div class="flex items-center gap-2">
          <span class="inline-grid h-10 w-10 place-items-center rounded-xl bg-[#2465EA]/10 text-[#2465EA]">
            <i data-lucide="wand-2" class="h-5 w-5"></i>
          </span>
                        <h3 class="text-base font-semibold text-slate-900">Freelancers</h3>
                    </div>
                    <p class="mt-2 text-sm text-slate-600">Propostas profissionais, clientes organizados e cobranças automáticas.</p>
                    <ul class="mt-3 space-y-1.5 text-sm text-slate-700">
                        <li class="flex items-center gap-2"><i data-lucide="file-text" class="h-4 w-4 text-[#2465EA]"></i> Modelos de proposta</li>
                        <li class="flex items-center gap-2"><i data-lucide="message-circle-more" class="h-4 w-4 text-[#2465EA]"></i> Lembretes WhatsApp/E-mail</li>
                        <li class="flex items-center gap-2"><i data-lucide="link-2" class="h-4 w-4 text-[#2465EA]"></i> Links de pagamento</li>
                    </ul>
                    <a href="#criar" class="mt-4 inline-flex items-center justify-center rounded-full bg-[#2465EA] px-4 py-2 text-sm font-semibold text-white hover:opacity-95">
                        Iniciar
                    </a>
                </article>

                <!-- Agência -->
                <article class="reveal rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-[#2465EA]">
                    <div class="flex items-center gap-2">
          <span class="inline-grid h-10 w-10 place-items-center rounded-xl bg-indigo-600/10 text-indigo-600">
            <i data-lucide="megaphone" class="h-5 w-5"></i>
          </span>
                        <h3 class="text-base font-semibold text-slate-900">Agências</h3>
                    </div>
                    <p class="mt-2 text-sm text-slate-600">MRR e recebíveis em tempo real, atividades recentes e previsibilidade.</p>
                    <ul class="mt-3 space-y-1.5 text-sm text-slate-700">
                        <li class="flex items-center gap-2"><i data-lucide="chart-line" class="h-4 w-4 text-indigo-600"></i> MRR e pipeline</li>
                        <li class="flex items-center gap-2"><i data-lucide="activity" class="h-4 w-4 text-indigo-600"></i> Atividades recentes</li>
                        <li class="flex items-center gap-2"><i data-lucide="clock" class="h-4 w-4 text-indigo-600"></i> Recebíveis por vencimento</li>
                    </ul>
                    <a href="#criar" class="mt-4 inline-flex items-center justify-center rounded-full bg-[#2465EA] px-4 py-2 text-sm font-semibold text-white hover:opacity-95">
                        Iniciar
                    </a>
                </article>

                <!-- Oficina -->
                <article class="reveal rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-[#2465EA]">
                    <div class="flex items-center gap-2">
          <span class="inline-grid h-10 w-10 place-items-center rounded-xl bg-emerald-600/10 text-emerald-600">
            <i data-lucide="wrench" class="h-5 w-5"></i>
          </span>
                        <h3 class="text-base font-semibold text-slate-900">Oficinas</h3>
                    </div>
                    <p class="mt-2 text-sm text-slate-600">OS, estoque e orçamentos — com lembretes que garantem pagamento.</p>
                    <ul class="mt-3 space-y-1.5 text-sm text-slate-700">
                        <li class="flex items-center gap-2"><i data-lucide="clipboard-check" class="h-4 w-4 text-emerald-600"></i> Orçamentos e aprovações</li>
                        <li class="flex items-center gap-2"><i data-lucide="boxes" class="h-4 w-4 text-emerald-600"></i> Controle de estoque</li>
                        <li class="flex items-center gap-2"><i data-lucide="message-circle-more" class="h-4 w-4 text-emerald-600"></i> Lembretes automáticos</li>
                    </ul>
                    <a href="#criar" class="mt-4 inline-flex items-center justify-center rounded-full bg-[#2465EA] px-4 py-2 text-sm font-semibold text-white hover:opacity-95">
                        Iniciar
                    </a>
                </article>
            </div>
        </div>

        <script>
            (function () {
                const root = document.getElementById('segmentos');
                if (!root) return;
                const els = root.querySelectorAll('.reveal');
                const io = new IntersectionObserver((entries) => {
                    entries.forEach(e => {
                        if (e.isIntersecting) {
                            e.target.classList.add('is-visible');
                            io.unobserve(e.target);
                        }
                    });
                }, { threshold: 0.2 });
                els.forEach(el => io.observe(el));
                try { if (window.lucide) window.lucide.createIcons(); } catch (e) {}
            })();
        </script>
    </section>




    <!-- ===================== SEÇÃO — POR QUE A CLIQIS (conteúdo ESQUERDA centralizado; chips FORA do gradiente) ===================== -->
    <section id="pq-cliqis" class="py-20 bg-white">
        <style>
            @keyframes float-slow { 0%{transform:translateY(0)} 50%{transform:translateY(-6px)} 100%{transform:translateY(0)} }
            @keyframes float-fast { 0%{transform:translateY(0)} 50%{transform:translateY(-10px)} 100%{transform:translateY(0)} }
            .animate-float-slow { animation: float-slow 4.5s ease-in-out infinite }
            .animate-float-fast { animation: float-fast 3.5s ease-in-out infinite }
        </style>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 grid gap-12 lg:grid-cols-12 items-start">
            <!-- ESQUERDA — CARD DE PREVIEW (altura = direita; CONTEÚDO CENTRALIZADO VERTICALMENTE) -->
            <div class="lg:col-span-6">
                <!-- chip acima do card (pode manter) -->
                <div class="relative pointer-events-none">
                    <div class="absolute -top-5 left-10 z-10 animate-float-slow">
                        <div class="rounded-xl border border-slate-200 bg-white/90 backdrop-blur px-3 py-1.5 text-[11px] text-slate-700 shadow-md">
                            <i data-lucide="wallet" class="mr-1 inline h-3.5 w-3.5 text-[#2465EA]"></i> Recebimentos
                        </div>
                    </div>
                </div>

                <article id="pq-left-card"
                         class="w-full max-w-xl mx-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl
                      grid place-content-center gap-4"> <!-- centraliza vertical (e mantém largura) -->
                    <!-- Conteúdo inicial (o JS pode trocar depois) -->
                    <header class="flex items-center justify-between rounded-2xl bg-gradient-to-r from-[#2465EA] to-[#0EA4E8] px-5 py-4 md:py-5 text-white">
                        <p class="font-semibold">MRR, recebíveis e fluxo de caixa</p>
                        <span class="inline-flex items-center gap-1 rounded-full bg-white/20 px-2.5 py-1 text-xs">
            <i data-lucide="eye" class="h-3.5 w-3.5"></i> Visão
          </span>
                    </header>
                    <ul class="grid sm:grid-cols-2 gap-3 text-sm text-slate-700">
                        <li class="flex items-center gap-2"><i data-lucide="check" class="h-4 w-4 text-emerald-600"></i> MRR e pipeline</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="h-4 w-4 text-emerald-600"></i> Recebíveis por vencimento</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="h-4 w-4 text-emerald-600"></i> Relatórios e exportações</li>
                        <li class="flex items-center gap-2"><i data-lucide="check" class="h-4 w-4 text-emerald-600"></i> Planejamento de caixa</li>
                    </ul>
                </article>
            </div>

            <!-- DIREITA — BLOCO GRADIENTE (referência de ALTURA) -->
            <div class="lg:col-span-6 relative flex justify-center">
                <div id="pq-right-card"
                     class="relative w-full max-w-xl mx-auto rounded-[28px] overflow-hidden
                  bg-gradient-to-r from-[#2465EA] to-[#0EA4E8] text-white p-8 md:p-10
                  shadow-[0_24px_80px_rgba(36,101,234,.25)] text-center">
                    <h2 class="text-2xl sm:text-3xl md:text-4xl font-extrabold tracking-tight">Por que a Cliqis?</h2>
                    <p class="mt-3 text-white/90 max-w-prose mx-auto">Um CRM que se adapta ao seu dia a dia — simples, modular e com automações que reduzem a inadimplência.</p>

                    <!-- Tabs centralizadas -->
                    <div class="mt-6 flex flex-wrap justify-center gap-2" role="tablist" aria-label="Benefícios Cliqis">
                        <button data-tab="modular" class="pq-tab active inline-flex items-center gap-2 rounded-full bg-white text-[#1E3A8A] px-4 py-2 text-sm font-medium shadow-sm">
                            <i data-lucide="puzzle" class="h-4 w-4"></i> Modular
                        </button>
                        <button data-tab="automacao" class="pq-tab inline-flex items-center gap-2 rounded-full bg-white/15 text-white px-4 py-2 text-sm font-medium ring-1 ring-white/25 hover:bg-white/20">
                            <i data-lucide="bot" class="h-4 w-4"></i> Automação
                        </button>
                        <button data-tab="financeiro" class="pq-tab inline-flex items-center gap-2 rounded-full bg-white/15 text-white px-4 py-2 text-sm font-medium ring-1 ring-white/25 hover:bg-white/20">
                            <i data-lucide="wallet" class="h-4 w-4"></i> Financeiro
                        </button>
                        <button data-tab="propostas" class="pq-tab inline-flex items-center gap-2 rounded-full bg-white/15 text-white px-4 py-2 text-sm font-medium ring-1 ring-white/25 hover:bg-white/20">
                            <i data-lucide="file-check-2" class="h-4 w-4"></i> Propostas
                        </button>
                        <button data-tab="simplicidade" class="pq-tab inline-flex items-center gap-2 rounded-full bg-white/15 text-white px-4 py-2 text-sm font-medium ring-1 ring-white/25 hover:bg-white/20">
                            <i data-lucide="mouse-pointer-click" class="h-4 w-4"></i> Simplicidade
                        </button>
                    </div>

                    <a href="#recursos" class="mt-8 inline-flex items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-semibold text-[#1D4ED8] shadow-soft hover:bg-white/95">
                        Explorar recursos
                    </a>
                </div>

                <!-- Chips flutuantes FORA do background (irmãos do card) -->
                <div class="pointer-events-none absolute -top-4 left-[8%] z-20 animate-float-slow">
                    <div class="rounded-xl border border-white/50 bg-white/95 backdrop-blur px-3 py-1.5 text-[11px] text-slate-700 shadow-md">
                        <i data-lucide="check-circle-2" class="mr-1 inline h-3.5 w-3.5 text-[#2465EA]"></i> Cobrança confirmada
                    </div>
                </div>
                <div class="pointer-events-none absolute -bottom-5 right-[8%] z-20 animate-float-fast">
                    <div class="rounded-xl border border-white/50 bg-white/95 backdrop-blur px-3 py-1.5 text-[11px] text-slate-700 shadow-md">
                        <i data-lucide="send" class="mr-1 inline h-3.5 w-3.5 text-[#2465EA]"></i> Proposta enviada
                    </div>
                </div>
            </div>
        </div>

        <!-- JS: esquerda = altura do card da direita; re-render e ícones -->
        <script>
            (function () {
                const root = document.getElementById('pq-cliqis');
                if (!root) return;
                const left = root.querySelector('#pq-left-card');
                const right = root.querySelector('#pq-right-card');
                const tabs = root.querySelectorAll('.pq-tab');

                const states = {
                    modular: {
                        title:'Monte seu plano por módulos', icon:'sparkles', pill:'Flexível',
                        items:['Pague só pelo que usa','Ative/desative quando quiser','Recomendações por segmento','Sem implementação complexa'],
                        from:'#2465EA', to:'#0EA4E8'
                    },
                    automacao:{
                        title:'Cobranças no automático', icon:'zap', pill:'Resultado',
                        items:['Mensagens personalizáveis','Histórico e prova de envio','Links de pagamento','Menos inadimplência'],
                        from:'#10B981', to:'#059669'
                    },
                    financeiro:{
                        title:'MRR, recebíveis e fluxo de caixa', icon:'chart-line', pill:'Visão',
                        items:['MRR e pipeline','Recebíveis por vencimento','Relatórios e exportações','Planejamento de caixa'],
                        from:'#6366F1', to:'#4F46E5'
                    },
                    propostas:{
                        title:'Propostas de alto padrão', icon:'file-check-2', pill:'Conversão',
                        items:['Modelos profissionais','Status e histórico','Assinatura simples','Conversão mais alta'],
                        from:'#D946EF', to:'#C026D3'
                    },
                    simplicidade:{
                        title:'Aprenda em minutos', icon:'mouse-pointer-click', pill:'Rápido',
                        items:['Onboarding claro','Próximo passo visível','Histórico por contexto','Ações rápidas'],
                        from:'#334155', to:'#0F172A'
                    }
                };

                function render(key){
                    const s = states[key]; if(!s) return;
                    left.innerHTML = `
          <header class="flex items-center justify-between rounded-2xl px-5 py-4 md:py-5 text-white"
                  style="background: linear-gradient(90deg, ${s.from}, ${s.to})">
            <p class="font-semibold">${s.title}</p>
            <span class="inline-flex items-center gap-1 rounded-full bg-white/20 px-2.5 py-1 text-xs">
              <i data-lucide="${s.icon}" class="h-3.5 w-3.5"></i> ${s.pill}
            </span>
          </header>
          <ul class="grid sm:grid-cols-2 gap-3 text-sm text-slate-700">
            ${s.items.map(t=>`<li class='flex items-center gap-2'><i data-lucide='check' class='h-4 w-4 text-emerald-600'></i>${t}</li>`).join('')}
          </ul>`;
                    try { if (window.lucide) window.lucide.createIcons(); } catch(e){}
                    syncHeights();
                }

                function syncHeights() {
                    if (!left || !right) return;
                    left.style.minHeight = '';
                    if (window.matchMedia('(min-width: 1024px)').matches) {
                        left.style.minHeight = right.offsetHeight + 'px'; // esquerda acompanha a direita
                    }
                }

                tabs.forEach(btn=>{
                    btn.addEventListener('click', ()=>{
                        tabs.forEach(b=>{
                            const a = b===btn;
                            b.classList.toggle('active', a);
                            b.classList.toggle('bg-white', a);
                            b.classList.toggle('text-[#1E3A8A]', a);
                            b.classList.toggle('bg-white/15', !a);
                            b.classList.toggle('text-white', !a);
                        });
                        render(btn.dataset.tab);
                    });
                });

                window.addEventListener('resize', syncHeights);
                new ResizeObserver(syncHeights).observe(right);

                // init
                render('financeiro');
                syncHeights();
                try { if (window.lucide) window.lucide.createIcons(); } catch(e){}
            })();
        </script>
    </section>


    <!-- ===================== SEÇÃO — COMO FUNCIONA (STEPPER DINÂMICO) ===================== -->
    <section id="como-funciona" class="py-20 bg-white border-t border-slate-200">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-slate-900">De zero ao controle em 1 dia</h2>
                <p class="mt-3 text-slate-600">Cadastre-se, ative módulos, conecte canais e acompanhe resultados no dashboard.</p>
            </div>

            <!-- Barra de progresso -->
            <div class="relative mt-8">
                <div class="h-2 rounded-full bg-slate-100"></div>
                <div id="cf-progress" class="absolute inset-y-0 left-0 h-2 rounded-full bg-gradient-to-r from-blue-500 to-indigo-500 transition-all duration-500" style="width: 16%;"></div>
            </div>

            <!-- Stepper -->
            <nav aria-label="Passos" class="mt-8 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <!-- Cada botão controla um painel abaixo -->
                <button data-step="0" class="cf-step active rounded-xl border border-slate-200 bg-white px-4 py-3 text-left hover:bg-slate-50">
                    <div class="flex items-center gap-2">
                        <span class="inline-grid h-8 w-8 place-items-center rounded-full bg-blue-600 text-white text-sm font-semibold">1</span>
                        <span class="text-sm font-medium text-slate-800">Cadastre-se</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">E-mail profissional ou Google.</p>
                </button>

                <button data-step="1" class="cf-step rounded-xl border border-slate-200 bg-white px-4 py-3 text-left hover:bg-slate-50">
                    <div class="flex items-center gap-2">
                        <span class="inline-grid h-8 w-8 place-items-center rounded-full bg-blue-600/10 text-blue-600 text-sm font-semibold">2</span>
                        <span class="text-sm font-medium text-slate-800">Escolha o segmento</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Freelancer, Agência ou Oficina.</p>
                </button>

                <button data-step="2" class="cf-step rounded-xl border border-slate-200 bg-white px-4 py-3 text-left hover:bg-slate-50">
                    <div class="flex items-center gap-2">
                        <span class="inline-grid h-8 w-8 place-items-center rounded-full bg-blue-600/10 text-blue-600 text-sm font-semibold">3</span>
                        <span class="text-sm font-medium text-slate-800">Ative módulos</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Faturamento, Financeiro, etc.</p>
                </button>

                <button data-step="3" class="cf-step rounded-xl border border-slate-200 bg-white px-4 py-3 text-left hover:bg-slate-50">
                    <div class="flex items-center gap-2">
                        <span class="inline-grid h-8 w-8 place-items-center rounded-full bg-blue-600/10 text-blue-600 text-sm font-semibold">4</span>
                        <span class="text-sm font-medium text-slate-800">Conecte canais</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">WhatsApp e e-mail.</p>
                </button>

                <button data-step="4" class="cf-step rounded-xl border border-slate-200 bg-white px-4 py-3 text-left hover:bg-slate-50">
                    <div class="flex items-center gap-2">
                        <span class="inline-grid h-8 w-8 place-items-center rounded-full bg-blue-600/10 text-blue-600 text-sm font-semibold">5</span>
                        <span class="text-sm font-medium text-slate-800">Importe sua base</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Clientes e fornecedores.</p>
                </button>

                <button data-step="5" class="cf-step rounded-xl border border-slate-200 bg-white px-4 py-3 text-left hover:bg-slate-50">
                    <div class="flex items-center gap-2">
                        <span class="inline-grid h-8 w-8 place-items-center rounded-full bg-blue-600/10 text-blue-600 text-sm font-semibold">6</span>
                        <span class="text-sm font-medium text-slate-800">Acompanhe resultados</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">MRR, recebíveis e alertas.</p>
                </button>
            </nav>

            <!-- Painéis de conteúdo -->
            <div class="mt-8">
                <div id="cf-panels" class="relative">
                    <!-- Painel 1 -->
                    <article data-panel="0" class="cf-panel rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
                        <div class="flex items-center gap-3 text-slate-800">
                            <i data-lucide="user-plus" class="h-5 w-5 text-blue-600"></i>
                            <h3 class="text-lg font-semibold">Crie sua conta em minutos</h3>
                        </div>
                        <p class="mt-2 text-slate-600 text-sm">Use seu e-mail profissional ou entre com Google. Primeiro acesso já sugere um setup ideal.</p>
                    </article>

                    <!-- Painel 2 -->
                    <article data-panel="1" class="cf-panel hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
                        <div class="flex items-center gap-3 text-slate-800">
                            <i data-lucide="sliders-horizontal" class="h-5 w-5 text-blue-600"></i>
                            <h3 class="text-lg font-semibold">Escolha o segmento</h3>
                        </div>
                        <p class="mt-2 text-slate-600 text-sm">Freelancer, Agência ou Oficina. O Cliqis ajusta módulos e fluxos para sua realidade.</p>
                    </article>

                    <!-- Painel 3 -->
                    <article data-panel="2" class="cf-panel hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
                        <div class="flex items-center gap-3 text-slate-800">
                            <i data-lucide="puzzle" class="h-5 w-5 text-blue-600"></i>
                            <h3 class="text-lg font-semibold">Ative apenas o essencial</h3>
                        </div>
                        <p class="mt-2 text-slate-600 text-sm">Faturamento, Financeiro, Pedidos, Orçamentos, Estoque e mais — plug and play.</p>
                    </article>

                    <!-- Painel 4 -->
                    <article data-panel="3" class="cf-panel hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
                        <div class="flex items-center gap-3 text-slate-800">
                            <i data-lucide="message-circle-more" class="h-5 w-5 text-blue-600"></i>
                            <h3 class="text-lg font-semibold">Conecte WhatsApp e e-mail</h3>
                        </div>
                        <p class="mt-2 text-slate-600 text-sm">Prepare as sequências de lembretes e reduza a inadimplência automaticamente.</p>
                    </article>

                    <!-- Painel 5 -->
                    <article data-panel="4" class="cf-panel hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
                        <div class="flex items-center gap-3 text-slate-800">
                            <i data-lucide="database" class="h-5 w-5 text-blue-600"></i>
                            <h3 class="text-lg font-semibold">Importe clientes e fornecedores</h3>
                        </div>
                        <p class="mt-2 text-slate-600 text-sm">Comece com sua base atual; o assistente guia o mapeamento dos campos.</p>
                    </article>

                    <!-- Painel 6 -->
                    <article data-panel="5" class="cf-panel hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
                        <div class="flex items-center gap-3 text-slate-800">
                            <i data-lucide="layout-dashboard" class="h-5 w-5 text-blue-600"></i>
                            <h3 class="text-lg font-semibold">Acompanhe tudo no dashboard</h3>
                        </div>
                        <p class="mt-2 text-slate-600 text-sm">MRR, recebíveis, atividades recentes e alertas em tempo real.</p>
                    </article>
                </div>

                <!-- Controles -->
                <div class="mt-6 flex items-center justify-center gap-3">
                    <button id="cf-prev" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        <i data-lucide="chevron-left" class="h-4 w-4"></i>
                    </button>
                    <button id="cf-next" class="inline-flex items-center justify-center rounded-full bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Próximo
                    </button>
                </div>
            </div>
        </div>

        <!-- Script isolado para stepper -->
        <script>
            (function () {
                const root = document.getElementById('como-funciona');
                if (!root) return;
                const steps = root.querySelectorAll('.cf-step');
                const panels = root.querySelectorAll('.cf-panel');
                const progress = root.querySelector('#cf-progress');
                const prevBtn = root.querySelector('#cf-prev');
                const nextBtn = root.querySelector('#cf-next');
                let idx = 0, total = steps.length;

                function render() {
                    steps.forEach((s, i) => {
                        s.classList.toggle('active', i === idx);
                        const dot = s.querySelector('span:first-child');
                        dot.className = 'inline-grid h-8 w-8 place-items-center rounded-full ' + (i === idx ? 'bg-blue-600 text-white' : 'bg-blue-600/10 text-blue-600');
                    });
                    panels.forEach((p, i) => p.classList.toggle('hidden', i !== idx));
                    const pct = ((idx + 1) / total) * 100;
                    progress.style.width = pct + '%';
                    try { if (window.lucide) window.lucide.createIcons(); } catch (e) {}
                }

                steps.forEach((s, i) => s.addEventListener('click', () => { idx = i; render(); }));
                prevBtn.addEventListener('click', () => { idx = (idx - 1 + total) % total; render(); });
                nextBtn.addEventListener('click', () => { idx = (idx + 1) % total; render(); });

                // auto-avanço com pausa ao passar o mouse
                let timer = setInterval(() => nextBtn.click(), 4500);
                root.addEventListener('mouseenter', () => clearInterval(timer));
                root.addEventListener('mouseleave', () => timer = setInterval(() => nextBtn.click(), 4500));

                render();
            })();
        </script>
    </section>

    <!-- ===================== SEÇÃO — AUTOMAÇÃO DE COBRANÇAS (FULL-WIDTH GRADIENT) ===================== -->
    <section id="automacao"
             class="relative left-1/2 -translate-x-1/2 w-screen py-20
         bg-gradient-to-r from-[#2465EA] to-[#0EA4E8] overflow-hidden">
        <style>
            @keyframes fadeInUp { from {opacity:.0; transform: translateY(12px)} to {opacity:1; transform: translateY(0)} }
            #automacao .reveal { opacity:0; transform:translateY(12px) }
            #automacao .reveal.is-visible { animation: fadeInUp .6s ease-out both }
        </style>

        <!-- conteúdo centralizado -->
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto">
                <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-white">
                    Menos inadimplência. Mais dinheiro no caixa.
                </h2>
                <p class="mt-3 text-white/90">
                    Programe lembretes por WhatsApp e e-mail: antes do vencimento, no dia e após.
                    Personalize mensagens, anexe link de pagamento e acompanhe respostas — tudo
                    rastreável no painel.
                </p>
            </div>

            <!-- Benefícios (cards com borda gradiente) -->
            <ul class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <li class="reveal rounded-2xl p-[1.5px] bg-gradient-to-br from-[#2465EA]/40 via-[#0EA4E8]/30 to-[#2465EA]/40">
                    <div class="h-full rounded-[1rem] bg-white p-6 shadow-soft hover:shadow-lg transition">
                        <div class="flex items-start gap-3">
            <span class="inline-grid h-10 w-10 place-items-center rounded-xl bg-[#2465EA]/10 text-[#2465EA]">
              <i data-lucide="sliders" class="h-5 w-5"></i>
            </span>
                            <div>
                                <h3 class="text-base font-semibold text-slate-900">Sequências inteligentes e editáveis</h3>
                                <p class="mt-1 text-sm text-slate-600">Ajuste prazos, canais e conteúdo por etapa.</p>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] text-slate-600">Pré • Dia • Pós</span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] text-slate-600">WhatsApp + E-mail</span>
                        </div>
                    </div>
                </li>

                <li class="reveal rounded-2xl p-[1.5px] bg-gradient-to-br from-[#2465EA]/40 via-[#0EA4E8]/30 to-[#2465EA]/40">
                    <div class="h-full rounded-[1rem] bg-white p-6 shadow-soft hover:shadow-lg transition">
                        <div class="flex items-start gap-3">
            <span class="inline-grid h-10 w-10 place-items-center rounded-xl bg-[#2465EA]/10 text-[#2465EA]">
              <i data-lucide="shield-check" class="h-5 w-5"></i>
            </span>
                            <div>
                                <h3 class="text-base font-semibold text-slate-900">Prova de envio e histórico</h3>
                                <p class="mt-1 text-sm text-slate-600">Tudo auditável: logs, respostas e tentativas.</p>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] text-slate-600">Rastreio por mensagem</span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] text-slate-600">Compliance</span>
                        </div>
                    </div>
                </li>

                <li class="reveal rounded-2xl p-[1.5px] bg-gradient-to-br from-[#2465EA]/40 via-[#0EA4E8]/30 to-[#2465EA]/40">
                    <div class="h-full rounded-[1rem] bg-white p-6 shadow-soft hover:shadow-lg transition">
                        <div class="flex items-start gap-3">
            <span class="inline-grid h-10 w-10 place-items-center rounded-xl bg-[#2465EA]/10 text-[#2465EA]">
              <i data-lucide="line-chart" class="h-5 w-5"></i>
            </span>
                            <div>
                                <h3 class="text-base font-semibold text-slate-900">Taxas de pagamento em tempo real</h3>
                                <p class="mt-1 text-sm text-slate-600">Acompanhe conversão por sequência e otimize.</p>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] text-slate-600">Dashboard</span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] text-slate-600">Exportar CSV</span>
                        </div>
                    </div>
                </li>
            </ul>

            <!-- KPIs -->
            <div class="mt-8 grid gap-4 sm:grid-cols-3">
                <div class="reveal rounded-2xl border border-white/30 bg-white/95 backdrop-blur p-5 text-center shadow-sm">
                    <p class="text-xs text-slate-500">Taxa de pagamento</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-900">92%</p>
                    <span class="mt-1 inline-flex items-center gap-1 text-[#2465EA] text-xs">
          <i data-lucide="trending-up" class="h-3.5 w-3.5"></i> +6%
        </span>
                </div>
                <div class="reveal rounded-2xl border border-white/30 bg-white/95 backdrop-blur p-5 text-center shadow-sm">
                    <p class="text-xs text-slate-500">Tempo médio</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-900">1,7 dia</p>
                    <span class="mt-1 inline-flex items-center gap-1 text-slate-500 text-xs">após pré-venc.</span>
                </div>
                <div class="reveal rounded-2xl border border-white/30 bg-white/95 backdrop-blur p-5 text-center shadow-sm">
                    <p class="text-xs text-slate-500">Mensagens/mês</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-900">1.240</p>
                    <span class="mt-1 inline-flex items-center gap-1 text-[#0EA4E8] text-xs">
          <i data-lucide="send" class="h-3.5 w-3.5"></i> auto
        </span>
                </div>
            </div>

            <div class="mt-10 text-center">
                <a href="#criar" class="inline-flex items-center justify-center rounded-full bg-white px-6 py-3 text-sm font-semibold text-[#1D4ED8] shadow-soft hover:bg-white/95">
                    Ativar automações
                </a>
                <p class="mt-2 text-xs text-white/80">Configuração guiada e reversível — ajuste a qualquer momento.</p>
            </div>
        </div>

        <script>
            (function () {
                const root = document.getElementById('automacao'); if (!root) return;
                const els = root.querySelectorAll('.reveal');
                const io = new IntersectionObserver((entries)=>entries.forEach(e=>{
                    if(e.isIntersecting){ e.target.classList.add('is-visible'); io.unobserve(e.target); }
                }),{threshold:.2});
                els.forEach(el=>io.observe(el));
                try{ if(window.lucide) window.lucide.createIcons(); }catch(e){}
            })();
        </script>
    </section>

    <!-- ===================== SEÇÃO — MONTE SEU PLANO (icons modernos + borda azul ao selecionar) ===================== -->
    <section id="plano" class="py-20 bg-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Headline -->
            <div class="text-center max-w-3xl mx-auto">
                <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-slate-900">
                    Monte seu plano em minutos — e ajuste quando quiser.
                </h2>
                <p class="mt-3 text-slate-500">14 dias grátis, depois cobrança mensal. Cancele a qualquer momento.</p>
                <div class="mt-6 h-px bg-slate-200/70"></div>
            </div>

            <!-- Builder -->
            <div class="mt-8 grid gap-8 lg:grid-cols-12 items-start">
                <!-- LEFT: módulos -->
                <div class="lg:col-span-8">
                    <!-- Search -->
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"></i>
                        <input id="plano-search" type="text" placeholder="Buscar módulos (ex: financeiro, estoque, faturas)…"
                               class="w-full rounded-full border border-slate-200 bg-white px-11 py-3 text-sm text-slate-700 placeholder-slate-400 outline-none focus:ring-4 focus:ring-blue-100 focus:border-blue-300"/>
                    </div>

                    <!-- Selected chips -->
                    <div id="plano-selected" class="mt-5 flex flex-wrap gap-2">
          <span data-key="estoque" class="chip selected">
            Estoque <em>obrigatório</em> <button type="button" aria-label="remover" class="x"></button>
          </span>
                        <span data-key="rh" class="chip selected">
            RH <em>obrigatório</em> <button type="button" aria-label="remover" class="x"></button>
          </span>
                        <span data-key="entidades" class="chip selected">
            Entidades <em>obrigatório</em> <button type="button" aria-label="remover" class="x"></button>
          </span>
                    </div>

                    <!-- Cards -->
                    <ul id="plano-cards" class="mt-5 grid gap-5 md:grid-cols-2">
                        <!-- Estoque -->
                        <li class="plano-card" data-key="estoque" data-name="Estoque" data-required="true">
                            <article class="card">
                                <header class="flex items-start justify-between">
                                    <div class="flex items-start gap-3">
                                        <span class="icon"><i data-lucide="boxes" class="h-5 w-5"></i></span>
                                        <div>
                                            <h3 class="title">Estoque</h3>
                                            <p class="desc">Controle de inventário, cadastro de produtos…</p>
                                        </div>
                                    </div>
                                    <span class="badge">obrigatório</span>
                                </header>
                            </article>
                        </li>

                        <!-- RH -->
                        <li class="plano-card" data-key="rh" data-name="RH" data-required="true">
                            <article class="card">
                                <header class="flex items-start justify-between">
                                    <div class="flex items-start gap-3">
                                        <span class="icon"><i data-lucide="id-card" class="h-5 w-5"></i></span>
                                        <div>
                                            <h3 class="title">RH</h3>
                                            <p class="desc">Gestão de funcionários, controle de folha…</p>
                                        </div>
                                    </div>
                                    <span class="badge">obrigatório</span>
                                </header>
                            </article>
                        </li>

                        <!-- Entidades -->
                        <li class="plano-card" data-key="entidades" data-name="Entidades" data-required="true">
                            <article class="card">
                                <header class="flex items-start justify-between">
                                    <div class="flex items-start gap-3">
                                        <span class="icon"><i data-lucide="users" class="h-5 w-5"></i></span>
                                        <div>
                                            <h3 class="title">Entidades</h3>
                                            <p class="desc">Cadastro de clientes, usuários e perfis…</p>
                                        </div>
                                    </div>
                                    <span class="badge">obrigatório</span>
                                </header>
                            </article>
                        </li>

                        <!-- Financeiro -->
                        <li class="plano-card" data-key="financeiro" data-name="Financeiro">
                            <article class="card">
                                <header class="flex items-start justify-between">
                                    <div class="flex items-start gap-3">
                                        <span class="icon"><i data-lucide="piggy-bank" class="h-5 w-5"></i></span>
                                        <div>
                                            <h3 class="title">Financeiro</h3>
                                            <p class="desc">MRR, recebíveis e fluxo de caixa…</p>
                                        </div>
                                    </div>
                                </header>
                            </article>
                        </li>

                        <!-- Faturas -->
                        <li class="plano-card" data-key="faturas" data-name="Faturas">
                            <article class="card">
                                <header class="flex items-start justify-between">
                                    <div class="flex items-start gap-3">
                                        <span class="icon"><i data-lucide="receipt" class="h-5 w-5"></i></span>
                                        <div>
                                            <h3 class="title">Faturas</h3>
                                            <p class="desc">Emissão, lembretes e link de pagamento…</p>
                                        </div>
                                    </div>
                                </header>
                            </article>
                        </li>

                        <!-- Estoque Avançado -->
                        <li class="plano-card" data-key="estoque-av" data-name="Estoque Avançado">
                            <article class="card">
                                <header class="flex items-start justify-between">
                                    <div class="flex items-start gap-3">
                                        <span class="icon"><i data-lucide="layers-3" class="h-5 w-5"></i></span>
                                        <div>
                                            <h3 class="title">Estoque Avançado</h3>
                                            <p class="desc">Lotes, validade e multi-depósitos…</p>
                                        </div>
                                    </div>
                                </header>
                            </article>
                        </li>
                    </ul>
                </div>

                <!-- RIGHT: resumo (sem preços) -->
                <aside class="lg:col-span-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sticky top-20">
                        <h3 class="text-base font-semibold text-slate-900">Seu Plano</h3>

                        <ul id="plano-resumo" class="mt-4 space-y-2 text-sm">
                            <li class="resumo-item" data-key="estoque">
                                <i data-lucide="boxes" class="mr-2 inline h-4 w-4 text-slate-500"></i>
                                Estoque <span class="badge">obrigatório</span>
                            </li>
                            <li class="resumo-item" data-key="rh">
                                <i data-lucide="id-card" class="mr-2 inline h-4 w-4 text-slate-500"></i>
                                RH <span class="badge">obrigatório</span>
                            </li>
                            <li class="resumo-item" data-key="entidades">
                                <i data-lucide="users" class="mr-2 inline h-4 w-4 text-slate-500"></i>
                                Entidades <span class="badge">obrigatório</span>
                            </li>
                        </ul>

                        <!-- ciclo (decorativo, sem preços) -->
                        <div class="mt-5">
                            <div class="inline-flex rounded-full border border-slate-200 bg-white p-1 text-xs font-medium">
                                <button type="button" class="cycle active">Mensal</button>
                                <button type="button" class="cycle">Anual</button>
                            </div>
                        </div>

                        <!-- CTA -->
                        <div class="mt-5 rounded-xl bg-slate-50 p-4 text-center">
                            <p class="text-[13px] text-slate-500">Você após 14 dias </p>
                            <p class="mt-1 text-lg font-semibold text-slate-900">100% customizável</p>
                            <p class="text-xs text-slate-500">Monte e ajuste quando quiser.</p>
                        </div>

                        <a href="#criar" class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-[#2465EA] px-5 py-3 text-sm font-semibold text-white shadow-soft hover:bg-[#1D4ED8]">
                            Veja todos os módulos
                        </a>
                        <!-- Botão "Voltar" removido conforme pedido -->
                    </div>
                </aside>
            </div>
        </div>

        <!-- estilos utilitários locais -->
        <style>
            /* Card base */
            #plano .card{
                border:1px solid rgba(15,23,42,.12);
                border-radius:1rem;
                background:#fff;
                padding:1rem;
                transition:.2s transform, .2s box-shadow, .2s border-color;
            }
            #plano .card:hover{ transform:translateY(-2px) }
            /* Ícone moderno */
            #plano .icon{
                display:grid; place-items:center;
                height:40px; width:40px; border-radius:12px;
                background:rgba(36,101,234,.10); color:#2465EA;
                box-shadow:inset 0 0 0 1px rgba(36,101,234,.12);
            }
            /* Estado SELECIONADO: borda azul + ícone com gradiente */
            #plano .plano-card.is-selected .card{
                border-color:#2563EB;
                box-shadow:0 0 0 3px rgba(37,99,235,.12);
            }
            #plano .plano-card.is-selected .icon{
                background:linear-gradient(135deg,#2465EA,#0EA4E8); color:#fff;
                box-shadow:none;
            }
            /* Hover indica seleção futura */
            #plano .plano-card .card:hover{ border-color:#93C5FD }
            /* Textos */
            #plano .title{font-weight:600; color:#0f172a}
            #plano .desc{font-size:.875rem; color:#475569}
            #plano .badge{font-size:.65rem; padding:.25rem .5rem; border-radius:9999px; background:#EEF2FF; color:#1D4ED8; border:1px solid rgba(37,99,235,.25)}
            /* Chips selecionados */
            #plano .chip{display:inline-flex; align-items:center; gap:.5rem; font-size:.75rem; padding:.4rem .6rem; border-radius:9999px; border:1px solid rgba(2,6,23,.12); background:#fff; color:#0f172a}
            #plano .chip em{font-style:normal; font-weight:600; color:#1D4ED8}
            #plano .chip .x{width:16px;height:16px;border-radius:9999px;background:#f1f5f9;border:1px solid #e2e8f0;display:inline-grid;place-items:center}
            #plano .chip .x::before{content:'×';font-size:.75rem;line-height:1}
            #plano .chip.selected{box-shadow:0 0 0 3px rgba(37,99,235,.10)}
            /* Toggle ciclo */
            #plano .cycle{padding:.35rem .7rem;border-radius:9999px}
            #plano .cycle.active{background:#EFF6FF;color:#1D4ED8;border:1px solid #BFDBFE}
        </style>

        <!-- JS: busca, seleção e sincronização de estilos -->
        <script>
            (function(){
                const root = document.getElementById('plano'); if(!root) return;
                const search = root.querySelector('#plano-search');
                const cards = [...root.querySelectorAll('.plano-card')];
                const selectedWrap = root.querySelector('#plano-selected');
                const resumo = root.querySelector('#plano-resumo');

                function markSelected(key, on){
                    const card = root.querySelector(`.plano-card[data-key="${key}"]`);
                    card?.classList.toggle('is-selected', !!on);
                }

                function addChip(key, name, required){
                    if(selectedWrap.querySelector(`[data-key="${key}"]`)) return;
                    const span = document.createElement('span');
                    span.className = 'chip selected';
                    span.dataset.key = key;
                    span.innerHTML = `${name} ${required?'<em>obrigatório</em>':''} <button type="button" class="x" aria-label="remover"></button>`;
                    selectedWrap.appendChild(span);
                    markSelected(key, true);
                    updateResumo();
                }

                function removeChip(key){
                    const card = root.querySelector(`.plano-card[data-key="${key}"]`);
                    if(card?.dataset.required === 'true') return; // não remove obrigatórios
                    selectedWrap.querySelector(`[data-key="${key}"]`)?.remove();
                    markSelected(key, false);
                    updateResumo();
                }

                function updateResumo(){
                    resumo.innerHTML = '';
                    selectedWrap.querySelectorAll('.chip').forEach(ch=>{
                        const key = ch.dataset.key;
                        const name = ch.childNodes[0].textContent.trim();
                        const li = document.createElement('li');
                        li.className = 'resumo-item';
                        li.dataset.key = key;
                        li.innerHTML = `<i data-lucide="check-circle" class="mr-2 inline h-4 w-4 text-slate-500"></i>${ch.innerText.replace('×','')}`;
                        resumo.appendChild(li);
                    });
                    try{ lucide?.createIcons(); }catch(e){}
                }

                // iniciar: marcar obrigatórios
                cards.forEach(c=>{
                    if(c.dataset.required==='true'){
                        addChip(c.dataset.key, c.dataset.name, true);
                    }
                });

                // click nos cards (toggle – exceto obrigatórios)
                cards.forEach(c=>{
                    c.addEventListener('click', ()=>{
                        const key = c.dataset.key, name = c.dataset.name, req = c.dataset.required==='true';
                        if(selectedWrap.querySelector(`[data-key="${key}"]`)){
                            if(!req) removeChip(key);
                        }else{
                            addChip(key, name, req);
                        }
                    });
                });

                // remover chip
                selectedWrap.addEventListener('click', (e)=>{
                    if(e.target.classList.contains('x')){
                        const key = e.target.parentElement.dataset.key;
                        removeChip(key);
                    }
                });

                // busca
                search.addEventListener('input', ()=>{
                    const q = search.value.toLowerCase().trim();
                    cards.forEach(c=>{
                        const n = (c.dataset.name||'').toLowerCase();
                        c.style.display = n.includes(q)?'':'none';
                    });
                });

                try{ lucide?.createIcons(); }catch(e){}
                updateResumo();
            })();
        </script>
    </section>

    <!-- ===================== CTA FINAL (BANNER GRADIENT + IMAGEM) ===================== -->
    <section id="cta-final" class="py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#2465EA] to-[#0EA4E8] p-8 md:p-10 shadow-[0_20px_60px_rgba(36,101,234,.25)]">
                <!-- brilho suave -->
                <div aria-hidden="true" class="pointer-events-none absolute -right-10 -top-10 h-56 w-56 rounded-full bg-white/20 blur-3xl"></div>

                <div class="grid items-center gap-10 lg:grid-cols-12">
                    <!-- Texto + CTAs -->
                    <div class="lg:col-span-7">
                        <h2 class="text-2xl sm:text-3xl md:text-4xl font-extrabold tracking-tight text-white">
                            Pronto para parar de perder pedidos — e dinheiro?
                        </h2>
                        <p class="mt-3 text-white/90">
                            Ative apenas o que precisa, automatize cobranças e ganhe previsibilidade hoje.
                        </p>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <a href="#criar"
                               class="inline-flex items-center justify-center rounded-full bg-white px-6 py-3 text-sm font-semibold text-[#1D4ED8] shadow-soft hover:bg-white/95">
                                Começar grátis agora
                            </a>
                            <a href="#vendas"
                               class="inline-flex items-center justify-center rounded-full border border-white/40 bg-white/10 px-6 py-3 text-sm font-semibold text-white backdrop-blur hover:bg-white/15">
                                Falar com vendas
                            </a>
                        </div>
                        <p class="mt-2 text-[11px] text-white/90">Teste sem cartão • Cancelamento a qualquer momento</p>
                    </div>

                    <!-- Imagem (substitua o src pela imagem em anexo/URL final) -->
                    <div class="lg:col-span-5">
                        <div class="rounded-2xl bg-white/10 p-3 ring-1 ring-white/20 shadow-inner">
                            <img
                                src="{{asset('assets/img/home/banner.png')}}"
                                alt="Preview de automações do Cliqis"
                                class="w-full rounded-xl border border-white/20"
                                loading="lazy" decoding="async"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================== SEÇÃO — DEPOIMENTOS / PROVA SOCIAL (ajustada) ===================== -->
    <section id="depoimentos" class="py-20 bg-white">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-slate-900">
                    O que nossos clientes dizem
                </h2>
            </div>

            <div class="mt-10 grid gap-6 md:grid-cols-2">
                <!-- Card 1 -->
                <blockquote
                    class="relative rounded-2xl bg-gradient-to-r from-[#2465EA] to-[#0EA4E8] p-6 text-white shadow-[0_12px_40px_rgba(36,101,234,.25)] ring-1 ring-white/10 transition-transform hover:-translate-y-0.5">
                    <p class="text-base sm:text-lg leading-relaxed">
                        “Ativei só os módulos que precisava e, no mesmo dia, automatizei as cobranças. Parou de escapar dinheiro.”
                    </p>
                    <footer class="mt-4 text-sm text-white/80">
                        [Nome, Empresa] — [FALTA DADO]
                    </footer>
                </blockquote>

                <!-- Card 2 -->
                <blockquote
                    class="relative rounded-2xl bg-gradient-to-r from-[#2465EA] to-[#0EA4E8] p-6 text-white shadow-[0_12px_40px_rgba(36,101,234,.25)] ring-1 ring-white/10 transition-transform hover:-translate-y-0.5">
                    <p class="text-base sm:text-lg leading-relaxed">
                        “O painel de MRR e recebíveis virou meu indicador diário. Simples, direto e confiável.”
                    </p>
                    <footer class="mt-4 text-sm text-white/80">
                        [Nome, Agência] — [FALTA DADO]
                    </footer>
                </blockquote>
            </div>

            <div class="mt-8 text-center">
                <a href="#historias"
                   class="inline-flex items-center justify-center rounded-full border border-[#2465EA] bg-white px-5 py-3 text-sm font-semibold text-[#2465EA] hover:bg-blue-50">
                    Ver mais histórias
                </a>
            </div>
        </div>
    </section>

    <!-- ===================== SEÇÃO — FAQ ===================== -->
    <section id="faq" class="py-20 bg-white">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-slate-900">Perguntas frequentes</h2>
            </div>

            <div class="mt-10 divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white">
                <!-- Item -->
                <details class="group p-6 open:bg-slate-50">
                    <summary class="flex cursor-pointer list-none items-center justify-between">
                        <h3 class="text-slate-900 font-semibold">O teste é realmente gratuito?</h3>
                        <i data-lucide="chevron-down" class="h-5 w-5 text-slate-500 transition group-open:rotate-180"></i>
                    </summary>
                    <p class="mt-3 text-slate-600">Sim, 14 dias sem cartão. Ao final, você decide se continua.</p>
                </details>

                <details class="group p-6 open:bg-slate-50">
                    <summary class="flex cursor-pointer list-none items-center justify-between">
                        <h3 class="text-slate-900 font-semibold">Posso trocar de módulos depois?</h3>
                        <i data-lucide="chevron-down" class="h-5 w-5 text-slate-500 transition group-open:rotate-180"></i>
                    </summary>
                    <p class="mt-3 text-slate-600">Sim. Ative ou desative quando quiser — você paga só pelo que usar.</p>
                </details>

                <details class="group p-6 open:bg-slate-50">
                    <summary class="flex cursor-pointer list-none items-center justify-between">
                        <h3 class="text-slate-900 font-semibold">É difícil começar?</h3>
                        <i data-lucide="chevron-down" class="h-5 w-5 text-slate-500 transition group-open:rotate-180"></i>
                    </summary>
                    <p class="mt-3 text-slate-600">Não. O Cliqis tem fluxos guiados e um guia rápido no painel.</p>
                </details>

                <details class="group p-6 open:bg-slate-50">
                    <summary class="flex cursor-pointer list-none items-center justify-between">
                        <h3 class="text-slate-900 font-semibold">Como funcionam os lembretes de cobrança?</h3>
                        <i data-lucide="chevron-down" class="h-5 w-5 text-slate-500 transition group-open:rotate-180"></i>
                    </summary>
                    <p class="mt-3 text-slate-600">Você define a sequência e o sistema envia WhatsApp e e-mail nos intervalos (pré, no dia e pós-vencimento).</p>
                </details>

                <details class="group p-6 open:bg-slate-50">
                    <summary class="flex cursor-pointer list-none items-center justify-between">
                        <h3 class="text-slate-900 font-semibold">Tenho suporte?</h3>
                        <i data-lucide="chevron-down" class="h-5 w-5 text-slate-500 transition group-open:rotate-180"></i>
                    </summary>
                    <p class="mt-3 text-slate-600">Sim, 24 horas.</p>
                </details>
            </div>
        </div>

        <script>(function(){try{if(window.lucide)window.lucide.createIcons();}catch(e){}})();</script>
    </section>

    <!-- ===================== RODAPÉ (FULL WIDTH) ===================== -->
    <footer id="rodape"
            class="relative left-1/2 -translate-x-1/2 w-screen bg-gradient-to-r from-[#2465EA] to-[#0EA4E8] text-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10">
            <div class="grid gap-8 md:grid-cols-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white text-[#2465EA] font-bold">⚡</span>
                        <span class="text-lg font-semibold tracking-tight">Cliqis</span>
                    </div>
                    <p class="mt-3 text-white/80 text-sm">Conecte clientes, pedidos e finanças em minutos.</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold">Produto</h3>
                    <ul class="mt-3 space-y-2 text-sm text-white/90">
                        <li><a href="#pq-cliqis" class="hover:underline">Por que a Cliqis</a></li>
                        <li><a href="#segmentos" class="hover:underline">Segmentos</a></li>
                        <li><a href="#automacao" class="hover:underline">Automações</a></li>
                        <li><a href="#plano" class="hover:underline">Planos</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-semibold">Recursos</h3>
                    <ul class="mt-3 space-y-2 text-sm text-white/90">
                        <li><a href="#recursos" class="hover:underline">Dashboard</a></li>
                        <li><a href="#recursos" class="hover:underline">Financeiro</a></li>
                        <li><a href="#recursos" class="hover:underline">Propostas</a></li>
                        <li><a href="#recursos" class="hover:underline">WhatsApp IA</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-semibold">Ajuda</h3>
                    <ul class="mt-3 space-y-2 text-sm text-white/90">
                        <li><a href="#duvidas" class="hover:underline">FAQ</a></li>
                        <li><a href="#historias" class="hover:underline">Histórias</a></li>
                        <li><a href="#vendas" class="hover:underline">Falar com vendas</a></li>
                        <li><a href="{{route('login')}}" class="hover:underline">Login</a></li>
                    </ul>
                </div>
            </div>

            <div class="mt-10 border-t border-white/20 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-xs text-white/80">© <span id="y"></span> Cliqis. Todos os direitos reservados.</p>
                <div class="flex gap-4 text-xs text-white/90">
                    <a href="#" class="hover:underline">Privacidade</a>
                    <a href="#" class="hover:underline">Termos</a>
                    <a href="#" class="hover:underline">Status</a>
                </div>
            </div>
        </div>
        <script>
            document.getElementById('y').textContent = new Date().getFullYear();
            try{ if(window.lucide) window.lucide.createIcons(); }catch(e){}
        </script>
    </footer>
    <!-- Opcional para garantir que nada crie scroll horizontal em breakouts -->
    <!-- <style>html,body{overflow-x:hidden}</style> -->



    <script>
        document.addEventListener('DOMContentLoaded', () => { try { lucide.createIcons(); } catch(e){} });
    </script>
</body>
</html>
