<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Automação de Válvulas Industriais | Bongas</title>

    <!-- Tailwind CDN (para protótipo / publicação rápida). Em produção, prefira build com Tailwind. -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    boxShadow: {
                        glow: "0 0 0 1px rgba(255,255,255,.06), 0 10px 30px rgba(0,0,0,.35)",
                    },
                },
            },
        };
    </script>
</head>

<body class="min-h-screen bg-zinc-950 text-zinc-100 antialiased">
<!-- Background accents -->
<div aria-hidden="true" class="pointer-events-none fixed inset-0">
    <div class="absolute -top-24 left-1/2 h-72 w-[40rem] -translate-x-1/2 rounded-full bg-emerald-500/10 blur-3xl"></div>
    <div class="absolute -bottom-24 right-1/3 h-72 w-[40rem] rounded-full bg-sky-500/10 blur-3xl"></div>
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(255,255,255,.06),transparent_40%)]"></div>
</div>

<!-- Sticky header -->
<header class="sticky top-0 z-40 border-b border-white/10 bg-zinc-950/70 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
        <div class="flex items-center gap-3">
            <!-- Replace with your logo -->
            <div class="grid h-9 w-9 place-items-center rounded-xl bg-white/5 ring-1 ring-white/10">
                <span class="text-sm font-semibold tracking-tight">B</span>
            </div>
            <div class="leading-tight">
                <p class="text-sm font-semibold">Bongas</p>
                <p class="text-xs text-zinc-400">Automação de Válvulas</p>
            </div>
        </div>

        <!-- Header CTA -->
        <a
            href="https://wa.me/551130933967?text=Ol%C3%A1%21%20Gostaria%20de%20automatizar%20v%C3%A1lvulas%20industriais.%0A%0ATipo%20de%20v%C3%A1lvula%3A%20___%0ADi%C3%A2metro%20%28DN%2Fpol%29%3A%20___%0APress%C3%A3o%2Ftemperatura%3A%20___%0AFluido%3A%20___%0AControle%20%28On%2FOff%20ou%20modulante%29%3A%20___%0APrefer%C3%AAncia%20de%20atuador%20%28el%C3%A9trico%2Fpneum%C3%A1tico%29%3A%20___%0A%0AP%C3%A1gina%3A%20https%3A%2F%2Fbongas.com.br%2Fautomacao-e-aplicacao-de-valvula-industrial%2F%0AUTM%3A%20utm_source%3Dwebsite%26utm_medium%3Dwhatsapp%26utm_campaign%3Dautomacao_valvulas_industriais%26utm_content%3Dheader_orcamento%0A%0AObrigado%21"
            class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-zinc-950 shadow-glow transition hover:bg-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-300/60"
            rel="noopener noreferrer"
            target="_blank"
            data-cta="header_orcamento"
        >
            <span>Orçamento no WhatsApp</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M5 12h14" />
                <path d="m13 5 7 7-7 7" />
            </svg>
        </a>
    </div>
</header>

<main class="relative">
    <!-- Hero -->
    <section class="mx-auto max-w-6xl px-4 pb-10 pt-10 sm:px-6 sm:pt-14">
        <div class="grid gap-8 lg:grid-cols-2 lg:items-center">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-white/5 px-3 py-1 text-xs text-zinc-300 ring-1 ring-white/10">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    Suporte técnico para especificação e aplicação
                </div>

                <h1 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">
                    Automação de Válvulas Industriais com Especificação Técnica
                </h1>

                <p class="mt-4 max-w-xl text-zinc-300">
                    Indicamos o atuador ideal <span class="text-zinc-100">(elétrico ou pneumático)</span> para sua aplicação.
                    Envie diâmetro, pressão e fluido e receba <span class="text-zinc-100">orientação + orçamento rápido</span>.
                </p>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <a
                        href="https://wa.me/551130933967?text=Ol%C3%A1%21%20Gostaria%20de%20automatizar%20v%C3%A1lvulas%20industriais.%0A%0ATipo%20de%20v%C3%A1lvula%3A%20___%0ADi%C3%A2metro%20%28DN%2Fpol%29%3A%20___%0APress%C3%A3o%2Ftemperatura%3A%20___%0AFluido%3A%20___%0AControle%20%28On%2FOff%20ou%20modulante%29%3A%20___%0APrefer%C3%AAncia%20de%20atuador%20%28el%C3%A9trico%2Fpneum%C3%A1tico%29%3A%20___%0A%0AP%C3%A1gina%3A%20https%3A%2F%2Fbongas.com.br%2Fautomacao-e-aplicacao-de-valvula-industrial%2F%0AUTM%3A%20utm_source%3Dwebsite%26utm_medium%3Dwhatsapp%26utm_campaign%3Dautomacao_valvulas_industriais%26utm_content%3Dhero_falar_especialista%0A%0AObrigado%21"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-500 px-5 py-3 text-sm font-semibold text-zinc-950 shadow-glow transition hover:bg-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-300/60"
                        rel="noopener noreferrer"
                        target="_blank"
                        data-cta="hero_falar_especialista"
                    >
                        <span>Falar com especialista no WhatsApp</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14" />
                            <path d="m13 5 7 7-7 7" />
                        </svg>
                    </a>

                    <a
                        href="https://wa.me/551130933967?text=Ol%C3%A1%21%20Quero%20automatizar%20v%C3%A1lvulas%20industriais.%20Seguem%20os%20dados%3A%0A%0A%E2%80%A2%20Tipo%20de%20v%C3%A1lvula%3A%20___%0A%E2%80%A2%20Di%C3%A2metro%20%28DN%2Fpol%29%3A%20___%0A%E2%80%A2%20Press%C3%A3o%20%28bar%29%3A%20___%0A%E2%80%A2%20Temperatura%3A%20___%0A%E2%80%A2%20Fluido%3A%20___%0A%E2%80%A2%20Controle%3A%20On%2FOff%20%28%20%29%20%20Modulante%204%E2%80%9320mA%20%28%20%29%0A%E2%80%A2%20Prefer%C3%AAncia%3A%20El%C3%A9trico%20%28%20%29%20Pneum%C3%A1tico%20%28%20%29%0A%E2%80%A2%20Ambiente%3A%20Interno%20%28%20%29%20Externo%20%28%20%29%0A%0AP%C3%A1gina%3A%20https%3A%2F%2Fbongas.com.br%2Fautomacao-e-aplicacao-de-valvula-industrial%2F%0AUTM%3A%20utm_source%3Dwebsite%26utm_medium%3Dwhatsapp%26utm_campaign%3Dautomacao_valvulas_industriais%26utm_content%3Dhero_enviar_dados"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white/5 px-5 py-3 text-sm font-semibold text-zinc-100 ring-1 ring-white/10 transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/30"
                        rel="noopener noreferrer"
                        target="_blank"
                        data-cta="hero_enviar_dados"
                    >
                        <span>Enviar dados da aplicação</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 2 11 13" />
                            <path d="M22 2 15 22l-4-9-9-4 20-7Z" />
                        </svg>
                    </a>
                </div>

                <!-- Three quick cards -->
                <div class="mt-8 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl bg-white/5 p-4 ring-1 ring-white/10">
                        <p class="text-sm font-semibold">Seleção do atuador correto</p>
                        <p class="mt-1 text-xs text-zinc-400">White-E • Grey-MZ • Grey-Q • Pneumáticos</p>
                    </div>
                    <div class="rounded-2xl bg-white/5 p-4 ring-1 ring-white/10">
                        <p class="text-sm font-semibold">Aplicação por tipo de válvula</p>
                        <p class="mt-1 text-xs text-zinc-400">Gaveta • Esfera • Borboleta • Comporta</p>
                    </div>
                    <div class="rounded-2xl bg-white/5 p-4 ring-1 ring-white/10">
                        <p class="text-sm font-semibold">Orçamento ágil</p>
                        <p class="mt-1 text-xs text-zinc-400">Mensagem pronta + checklist técnico</p>
                    </div>
                </div>
            </div>

            <!-- Right side "technical flyer" card -->
            <div class="rounded-3xl bg-white/5 p-6 ring-1 ring-white/10 shadow-glow">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold">Checklist técnico (rápido)</p>
                    <span class="rounded-full bg-emerald-500/15 px-3 py-1 text-xs text-emerald-300 ring-1 ring-emerald-500/20">
                Para orçamento
              </span>
                </div>

                <ul class="mt-4 space-y-3 text-sm text-zinc-200">
                    <li class="flex gap-3">
                        <span class="mt-1 h-2 w-2 rounded-full bg-emerald-400"></span>
                        Tipo de válvula (gaveta/esfera/borboleta/comporta/outra)
                    </li>
                    <li class="flex gap-3">
                        <span class="mt-1 h-2 w-2 rounded-full bg-emerald-400"></span>
                        Diâmetro (DN/pol) + pressão e temperatura
                    </li>
                    <li class="flex gap-3">
                        <span class="mt-1 h-2 w-2 rounded-full bg-emerald-400"></span>
                        Fluido (água, gás, vapor etc.)
                    </li>
                    <li class="flex gap-3">
                        <span class="mt-1 h-2 w-2 rounded-full bg-emerald-400"></span>
                        Controle: On/Off ou modulante (4–20mA)
                    </li>
                    <li class="flex gap-3">
                        <span class="mt-1 h-2 w-2 rounded-full bg-emerald-400"></span>
                        Preferência: atuador elétrico ou pneumático
                    </li>
                    <li class="flex gap-3">
                        <span class="mt-1 h-2 w-2 rounded-full bg-emerald-400"></span>
                        Ambiente: interno/externo (umidade/agressivo)
                    </li>
                </ul>

                <div class="mt-6 rounded-2xl bg-zinc-950/60 p-4 ring-1 ring-white/10">
                    <p class="text-xs text-zinc-400">Dica</p>
                    <p class="mt-1 text-sm text-zinc-200">
                        Quanto mais dados você enviar, mais rápido conseguimos indicar o conjunto (válvula + atuador + acessórios).
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section: Actuators compatibility -->
    <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        <div class="flex items-end justify-between gap-6">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight">Atuadores compatíveis para automação industrial</h2>
                <p class="mt-2 max-w-2xl text-sm text-zinc-300">
                    A seleção depende do tipo de válvula, diâmetro, pressão, fluido e modo de controle (On/Off ou modulante).
                </p>
            </div>

            <a
                href="https://wa.me/551130933967?text=Ol%C3%A1%21%20Preciso%20de%20ajuda%20para%20indicar%20o%20atuador%20ideal%20para%20automatizar%20uma%20v%C3%A1lvula%20industrial.%0A%0ATipo%20de%20v%C3%A1lvula%3A%20___%0ADi%C3%A2metro%20%28DN%2Fpol%29%3A%20___%0APress%C3%A3o%2Ftemperatura%3A%20___%0AFluido%3A%20___%0AControle%20%28On%2FOff%20ou%20modulante%29%3A%20___%0A%0AP%C3%A1gina%3A%20https%3A%2F%2Fbongas.com.br%2Fautomacao-e-aplicacao-de-valvula-industrial%2F%0AUTM%3A%20utm_source%3Dwebsite%26utm_medium%3Dwhatsapp%26utm_campaign%3Dautomacao_valvulas_industriais%26utm_content%3Dsec_atuadores_indicar%0A%0AObrigado%21"
                class="hidden items-center gap-2 rounded-xl bg-white/5 px-4 py-2 text-sm font-semibold text-zinc-100 ring-1 ring-white/10 transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/30 sm:inline-flex"
                rel="noopener noreferrer"
                target="_blank"
                data-cta="sec_atuadores_indicar"
            >
                Me indique o atuador ideal
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14" />
                    <path d="m13 5 7 7-7 7" />
                </svg>
            </a>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-3xl bg-white/5 p-5 ring-1 ring-white/10">
                <p class="text-sm font-semibold">White-E</p>
                <p class="mt-1 text-xs text-zinc-400">¼ de volta • aplicações gerais</p>
                <div class="mt-4 text-xs text-zinc-300">
                    Indicado para automações com exigência de robustez e padronização.
                </div>
            </div>

            <div class="rounded-3xl bg-white/5 p-5 ring-1 ring-white/10">
                <p class="text-sm font-semibold">Grey-Q</p>
                <p class="mt-1 text-xs text-zinc-400">¼ de volta • depende do tamanho</p>
                <div class="mt-4 text-xs text-zinc-300">
                    Opção para ¼ de volta conforme dimensionamento de torque.
                </div>
            </div>

            <div class="rounded-3xl bg-white/5 p-5 ring-1 ring-white/10">
                <p class="text-sm font-semibold">Grey-MZ</p>
                <p class="mt-1 text-xs text-zinc-400">multivoltas</p>
                <div class="mt-4 text-xs text-zinc-300">
                    Indicado para automação multivoltas em aplicações industriais.
                </div>
            </div>

            <div class="rounded-3xl bg-white/5 p-5 ring-1 ring-white/10">
                <p class="text-sm font-semibold">Pneumáticos</p>
                <p class="mt-1 text-xs text-zinc-400">On/Off e aplicações específicas</p>
                <div class="mt-4 text-xs text-zinc-300">
                    Opção para automação pneumática conforme processo e infraestrutura.
                </div>
            </div>
        </div>

        <!-- Mobile CTA -->
        <div class="mt-6 sm:hidden">
            <a
                href="https://wa.me/551130933967?text=Ol%C3%A1%21%20Preciso%20de%20ajuda%20para%20indicar%20o%20atuador%20ideal%20para%20automatizar%20uma%20v%C3%A1lvula%20industrial.%0A%0ATipo%20de%20v%C3%A1lvula%3A%20___%0ADi%C3%A2metro%20%28DN%2Fpol%29%3A%20___%0APress%C3%A3o%2Ftemperatura%3A%20___%0AFluido%3A%20___%0AControle%20%28On%2FOff%20ou%20modulante%29%3A%20___%0A%0AP%C3%A1gina%3A%20https%3A%2F%2Fbongas.com.br%2Fautomacao-e-aplicacao-de-valvula-industrial%2F%0AUTM%3A%20utm_source%3Dwebsite%26utm_medium%3Dwhatsapp%26utm_campaign%3Dautomacao_valvulas_industriais%26utm_content%3Dsec_atuadores_indicar%0A%0AObrigado%21"
                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-white/5 px-5 py-3 text-sm font-semibold text-zinc-100 ring-1 ring-white/10 transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/30"
                rel="noopener noreferrer"
                target="_blank"
            >
                Me indique o atuador ideal
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14" />
                    <path d="m13 5 7 7-7 7" />
                </svg>
            </a>
        </div>
    </section>

    <!-- Section: Automation by valve type -->
    <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        <div class="rounded-3xl bg-white/5 p-6 ring-1 ring-white/10">
            <h2 class="text-2xl font-semibold tracking-tight">Automação por tipo de válvula</h2>
            <p class="mt-2 text-sm text-zinc-300">
                Escolha o tipo para uma página mais específica (melhor match e orçamento mais rápido).
            </p>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <a href="/automacao-e-aplicacao-de-valvula-gaveta/" class="group rounded-3xl bg-zinc-950/50 p-5 ring-1 ring-white/10 transition hover:bg-zinc-950/70">
                    <p class="text-sm font-semibold">Gaveta + Comporta</p>
                    <p class="mt-1 text-xs text-zinc-400">Grey-MZ • Grey-L</p>
                    <p class="mt-4 text-xs text-zinc-300 group-hover:text-zinc-200">Ver detalhes e aplicações →</p>
                </a>

                <a href="/automacao-e-aplicacao-de-valvula-esfera/" class="group rounded-3xl bg-zinc-950/50 p-5 ring-1 ring-white/10 transition hover:bg-zinc-950/70">
                    <p class="text-sm font-semibold">Esfera + Globo + Macho</p>
                    <p class="mt-1 text-xs text-zinc-400">White-E • Pneumático</p>
                    <p class="mt-4 text-xs text-zinc-300 group-hover:text-zinc-200">Ver detalhes e aplicações →</p>
                </a>

                <a href="/automacao-e-aplicacao-de-valvula-borboleta/" class="group rounded-3xl bg-zinc-950/50 p-5 ring-1 ring-white/10 transition hover:bg-zinc-950/70">
                    <p class="text-sm font-semibold">Borboleta</p>
                    <p class="mt-1 text-xs text-zinc-400">White-E • Pneumático • Grey-Q*</p>
                    <p class="mt-4 text-xs text-zinc-300 group-hover:text-zinc-200">Ver detalhes e aplicações →</p>
                </a>

                <a href="/automacao-e-aplicacao-com-atuador-multivolta-1-4-volta/" class="group rounded-3xl bg-zinc-950/50 p-5 ring-1 ring-white/10 transition hover:bg-zinc-950/70">
                    <p class="text-sm font-semibold">Atuadores</p>
                    <p class="mt-1 text-xs text-zinc-400">¼ volta • multivoltas</p>
                    <p class="mt-4 text-xs text-zinc-300 group-hover:text-zinc-200">Ver modelos e seleção →</p>
                </a>
            </div>

            <div class="mt-6">
                <a
                    href="https://wa.me/551130933967?text=Ol%C3%A1%21%20Quero%20cotar%20a%20automa%C3%A7%C3%A3o%20de%20v%C3%A1lvulas%20industriais.%0A%0AInforme%20o%20tipo%20de%20v%C3%A1lvula%20e%20aplica%C3%A7%C3%A3o%20que%20eu%20envio%20os%20dados%20t%C3%A9cnicos%3A%0A%0ATipo%20de%20v%C3%A1lvula%3A%20___%0AAplica%C3%A7%C3%A3o%3A%20___%0ADi%C3%A2metro%3A%20___%0A%0AP%C3%A1gina%3A%20https%3A%2F%2Fbongas.com.br%2Fautomacao-e-aplicacao-de-valvula-industrial%2F%0AUTM%3A%20utm_source%3Dwebsite%26utm_medium%3Dwhatsapp%26utm_campaign%3Dautomacao_valvulas_industriais%26utm_content%3Dsec_tipos_cotar"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-500 px-5 py-3 text-sm font-semibold text-zinc-950 shadow-glow transition hover:bg-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-300/60 sm:w-auto"
                    rel="noopener noreferrer"
                    target="_blank"
                    data-cta="sec_tipos_cotar"
                >
                    Quero cotar agora no WhatsApp
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14" />
                        <path d="m13 5 7 7-7 7" />
                    </svg>
                </a>

                <p class="mt-2 text-xs text-zinc-400">
                    *Em válvula borboleta, Grey-Q pode variar conforme tamanho/torque necessário.
                </p>
            </div>
        </div>
    </section>

    <!-- Section: What to send -->
    <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        <div class="grid gap-6 lg:grid-cols-3 lg:items-start">
            <div class="lg:col-span-2">
                <h2 class="text-2xl font-semibold tracking-tight">Para indicar o conjunto ideal, envie no WhatsApp:</h2>
                <p class="mt-2 text-sm text-zinc-300">
                    Essas informações aceleram a especificação e aumentam a precisão da cotação.
                </p>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-3xl bg-white/5 p-5 ring-1 ring-white/10">
                        <p class="text-sm font-semibold">Dados da válvula</p>
                        <ul class="mt-3 space-y-2 text-sm text-zinc-200">
                            <li>• Tipo (gaveta/esfera/borboleta/comporta/outra)</li>
                            <li>• Diâmetro (DN/pol)</li>
                            <li>• Pressão e temperatura</li>
                        </ul>
                    </div>

                    <div class="rounded-3xl bg-white/5 p-5 ring-1 ring-white/10">
                        <p class="text-sm font-semibold">Dados do processo</p>
                        <ul class="mt-3 space-y-2 text-sm text-zinc-200">
                            <li>• Fluido (água, gás, vapor etc.)</li>
                            <li>• Controle: On/Off ou modulante (4–20mA)</li>
                            <li>• Ambiente: interno/externo</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl bg-white/5 p-6 ring-1 ring-white/10 shadow-glow">
                <p class="text-sm font-semibold">Enviar checklist pronto</p>
                <p class="mt-2 text-sm text-zinc-300">
                    Clique e envie a mensagem com os campos para preencher.
                </p>

                <a
                    href="https://wa.me/551130933967?text=Ol%C3%A1%21%20Segue%20checklist%20para%20automa%C3%A7%C3%A3o%20de%20v%C3%A1lvulas%20industriais%3A%0A%0A%E2%80%A2%20Tipo%20de%20v%C3%A1lvula%3A%20___%0A%E2%80%A2%20Di%C3%A2metro%20%28DN%2Fpol%29%3A%20___%0A%E2%80%A2%20Press%C3%A3o%3A%20___%0A%E2%80%A2%20Temperatura%3A%20___%0A%E2%80%A2%20Fluido%3A%20___%0A%E2%80%A2%20Controle%3A%20On%2FOff%20%2F%20Modulante%204%E2%80%9320mA%0A%E2%80%A2%20Prefer%C3%AAncia%20de%20atuador%3A%20El%C3%A9trico%20%2F%20Pneum%C3%A1tico%0A%E2%80%A2%20Ambiente%3A%20Interno%20%2F%20Externo%0A%0AP%C3%A1gina%3A%20https%3A%2F%2Fbongas.com.br%2Fautomacao-e-aplicacao-de-valvula-industrial%2F%0AUTM%3A%20utm_source%3Dwebsite%26utm_medium%3Dwhatsapp%26utm_campaign%3Dautomacao_valvulas_industriais%26utm_content%3Dsec_checklist_enviar"
                    class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-500 px-5 py-3 text-sm font-semibold text-zinc-950 shadow-glow transition hover:bg-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-300/60"
                    rel="noopener noreferrer"
                    target="_blank"
                    data-cta="sec_checklist_enviar"
                >
                    Enviar dados agora
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 2 11 13" />
                        <path d="M22 2 15 22l-4-9-9-4 20-7Z" />
                    </svg>
                </a>

                <p class="mt-3 text-xs text-zinc-400">
                    Você pode escrever “não sei” em algum campo e a gente te orienta.
                </p>
            </div>
        </div>
    </section>

    <!-- Benefits -->
    <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        <div class="rounded-3xl bg-white/5 p-6 ring-1 ring-white/10">
            <h2 class="text-2xl font-semibold tracking-tight">Benefícios da automação de válvulas industriais</h2>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-3xl bg-zinc-950/50 p-5 ring-1 ring-white/10">
                    <p class="text-sm font-semibold">Segurança</p>
                    <p class="mt-2 text-xs text-zinc-300">Operação mais previsível e padronizada.</p>
                </div>
                <div class="rounded-3xl bg-zinc-950/50 p-5 ring-1 ring-white/10">
                    <p class="text-sm font-semibold">Controle do processo</p>
                    <p class="mt-2 text-xs text-zinc-300">Melhor repetibilidade e resposta operacional.</p>
                </div>
                <div class="rounded-3xl bg-zinc-950/50 p-5 ring-1 ring-white/10">
                    <p class="text-sm font-semibold">Menos manutenção</p>
                    <p class="mt-2 text-xs text-zinc-300">Redução de falhas e paradas não planejadas.</p>
                </div>
                <div class="rounded-3xl bg-zinc-950/50 p-5 ring-1 ring-white/10">
                    <p class="text-sm font-semibold">Produtividade</p>
                    <p class="mt-2 text-xs text-zinc-300">Agilidade e eficiência na operação.</p>
                </div>
            </div>

            <div class="mt-6">
                <a
                    href="https://wa.me/551130933967?text=Ol%C3%A1%21%20Quero%20automatizar%20v%C3%A1lvulas%20industriais%20e%20entender%20a%20melhor%20solu%C3%A7%C3%A3o%20para%20minha%20aplica%C3%A7%C3%A3o.%0A%0ATipo%20de%20v%C3%A1lvula%3A%20___%0AAplica%C3%A7%C3%A3o%3A%20___%0ADi%C3%A2metro%3A%20___%0A%0AP%C3%A1gina%3A%20https%3A%2F%2Fbongas.com.br%2Fautomacao-e-aplicacao-de-valvula-industrial%2F%0AUTM%3A%20utm_source%3Dwebsite%26utm_medium%3Dwhatsapp%26utm_campaign%3Dautomacao_valvulas_industriais%26utm_content%3Dsec_beneficios_especialista"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-white/5 px-5 py-3 text-sm font-semibold text-zinc-100 ring-1 ring-white/10 transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/30 sm:w-auto"
                    rel="noopener noreferrer"
                    target="_blank"
                    data-cta="sec_beneficios_especialista"
                >
                    Quero automatizar — falar com especialista
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14" />
                        <path d="m13 5 7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="mx-auto max-w-6xl px-4 pb-16 pt-10 sm:px-6">
        <div class="rounded-3xl bg-gradient-to-br from-emerald-500/15 to-sky-500/10 p-8 ring-1 ring-white/10 shadow-glow">
            <h2 class="text-2xl font-semibold tracking-tight">Pronto para automatizar suas válvulas?</h2>
            <p class="mt-2 max-w-2xl text-sm text-zinc-200">
                Clique no WhatsApp e envie seus dados. Respondemos com orientação e cotação.
            </p>

            <div class="mt-6">
                <a
                    href="https://wa.me/551130933967?text=Ol%C3%A1%21%20Quero%20automatizar%20v%C3%A1lvulas%20industriais%20e%20solicitar%20um%20or%C3%A7amento.%0A%0ATipo%20de%20v%C3%A1lvula%3A%20___%0ADi%C3%A2metro%20%28DN%2Fpol%29%3A%20___%0AFluido%3A%20___%0APress%C3%A3o%2Ftemperatura%3A%20___%0AControle%3A%20___%0A%0AP%C3%A1gina%3A%20https%3A%2F%2Fbongas.com.br%2Fautomacao-e-aplicacao-de-valvula-industrial%2F%0AUTM%3A%20utm_source%3Dwebsite%26utm_medium%3Dwhatsapp%26utm_campaign%3Dautomacao_valvulas_industriais%26utm_content%3Dfooter_cta_whatsapp%0A%0AObrigado%21"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-500 px-6 py-4 text-base font-semibold text-zinc-950 shadow-glow transition hover:bg-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-300/60 sm:w-auto"
                    rel="noopener noreferrer"
                    target="_blank"
                    data-cta="footer_cta_whatsapp"
                >
                    Falar no WhatsApp
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14" />
                        <path d="m13 5 7 7-7 7" />
                    </svg>
                </a>
            </div>

            <p class="mt-3 text-xs text-zinc-300">
                Atendimento técnico • Especificação • Orçamento rápido
            </p>
        </div>
    </section>
</main>

<!-- Minimal footer -->
<footer class="border-t border-white/10 bg-zinc-950/60">
    <div class="mx-auto max-w-6xl px-4 py-6 text-xs text-zinc-400 sm:px-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <p>© <span id="year"></span> Bongas. Todos os direitos reservados.</p>
            <a href="/politica-de-privacidade/" class="hover:text-zinc-200">Política de privacidade</a>
        </div>
    </div>
</footer>

<script>
    document.getElementById("year").textContent = new Date().getFullYear();
</script>
</body>
</html>



<p class="text-xs text-zinc-700">¼ de volta • aplicações gerais</p>
<div class="mt-2 text-xs text-zinc-400">
    <span class="text-xs text-zinc-700">Serve para: </span>automatizar válvulas de esfera e borboleta.<br>
    <span class="text-xs text-zinc-700">Onde usar: </span>aplicações industriais comuns: água, saneamento, HVAC, mineração, processos gerais, petróleo e gás.<br>
    <span class="text-xs text-zinc-700">Perfil: </span> linha mais genérica e versátil. Quando não quer errar, vai de White-E.
</div>
