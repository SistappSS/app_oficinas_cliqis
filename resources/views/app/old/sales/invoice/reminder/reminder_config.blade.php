@extends('layouts.templates.template')

@section('content')
    <main class="mx-auto max-w-7xl px-4 sm:px-6 pb-10 lg:pb-14">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold">Configuração de e-mails de cobrança</h1>
                <p class="text-sm text-slate-600">Personalize os lembretes enviados para os clientes.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid gap-5 lg:grid-cols-[2fr,1fr]">
            <div class="space-y-4">
                @php
                    /** @var \Illuminate\Support\Collection $configs */
                @endphp

                @foreach($triggersLabels as $key => $label)
                    @php
                        $cfg = $configs[$key] ?? null;
                    @endphp

                    <form method="POST"
                          action="{{ route('invoices.reminder-config.store') }}"
                          class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
                        @csrf
                        <input type="hidden" name="trigger" value="{{ $key }}">

                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ $label }}</p>
                                <p class="text-xs text-slate-500">
                                    Trigger:
                                    <code class="text-[11px] bg-slate-100 px-1 py-0.5 rounded">{{ $key }}</code>
                                </p>
                            </div>

                            <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                                <input type="checkbox" name="is_active" value="1"
                                       @checked(optional($cfg)->is_active ?? true)
                                       class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                Ativo
                            </label>
                        </div>

                        <div>
                            <label class="text-xs font-medium text-slate-700">Nome interno</label>
                            <input type="text" name="name"
                                   value="{{ old('name', $cfg->name ?? $label) }}"
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-200">
                        </div>

                        <div>
                            <label class="text-xs font-medium text-slate-700">Assunto do e-mail</label>

                            @php
                                // default com placeholder, sem Blade se metendo
                                $defaultSubject = 'Lembrete de vencimento - {{invoice_number}}';
                            @endphp

                            <input
                                type="text"
                                name="subject"
                                value="{{ old('subject', $cfg->subject ?? $defaultSubject) }}"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-200"
                            >
                        </div>


                        <div>
                            <label class="text-xs font-medium text-slate-700">Mensagem</label>
                            <textarea name="body" rows="4"
                                      class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-200">@if(old('body'))
                                    {{ old('body') }}
                                @elseif($cfg)
                                    {{ $cfg->body }}
                                @else
                                    Olá @{{ customer_name }}, sua fatura @{{ invoice_number }} no valor de @{{ amount }} @{{ days_diff_label }} (vencimento em @{{ due_date }}).
                                @endif</textarea>

                            <p class="mt-1 text-[11px] text-slate-500">
                                Placeholders aceitos:
                                <code>@{{ customer_name }}</code>,
                                <code>@{{ invoice_number }}</code>,
                                <code>@{{ amount }}</code>,
                                <code>@{{ due_date }}</code>,
                                <code>@{{ days_diff }}</code>,
                                <code>@{{ days_diff_label }}</code>
                            </p>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                    class="rounded-xl bg-blue-700 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-800">
                                Salvar
                            </button>
                        </div>
                    </form>
                @endforeach
            </div>

            <aside class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm text-xs text-slate-600 space-y-4">
                <h2 class="text-sm font-semibold text-slate-800 mb-1">Como funciona</h2>

                <p>
                    Aqui você define o texto padrão dos e-mails de cobrança que o sistema envia
                    automaticamente para o cliente.
                </p>

                <ul class="list-disc pl-4 space-y-1">
                    <li><strong>3 dias antes</strong>: enviado 3 dias antes do vencimento.</li>
                    <li><strong>No dia do vencimento</strong>: enviado na data de vencimento.</li>
                    <li><strong>1 dia após</strong>: enviado 1 dia depois, avisando que já venceu.</li>
                    <li><strong>Envio manual</strong>: enviado quando você clica no botão de lembrete na tela de cobranças.</li>
                </ul>

                <div class="mt-3 border-t border-slate-200 pt-3">
                    <h3 class="text-[13px] font-semibold text-slate-800 mb-1">Variáveis que você pode usar</h3>
                    <p class="mb-2">
                        Você pode usar estas “palavras mágicas” no assunto e na mensagem. Na hora de enviar,
                        o sistema troca automaticamente pelos dados da fatura/cliente:
                    </p>

                    <ul class="list-none space-y-1">
                        <li>
                            <code class="bg-slate-100 px-1 py-0.5 rounded">@{{ customer_name }}</code>
                            – nome do cliente
                        </li>
                        <li>
                            <code class="bg-slate-100 px-1 py-0.5 rounded">@{{ invoice_number }}</code>
                            – número da fatura
                        </li>
                        <li>
                            <code class="bg-slate-100 px-1 py-0.5 rounded">@{{ amount }}</code>
                            – valor da fatura (ex.: R$ 150,00)
                        </li>
                        <li>
                            <code class="bg-slate-100 px-1 py-0.5 rounded">@{{ due_date }}</code>
                            – data de vencimento (ex.: 10/12/2025)
                        </li>
                        <li>
                            <code class="bg-slate-100 px-1 py-0.5 rounded">@{{ days_diff }}</code>
                            – diferença em dias em relação a hoje (ex.: 3, 0, -1)
                        </li>
                        <li>
                            <code class="bg-slate-100 px-1 py-0.5 rounded">@{{ days_diff_label }}</code>
                            – texto pronto (ex.: “vence em 3 dias”, “vence hoje”, “venceu há 1 dia”)
                        </li>
                    </ul>
                </div>

                <div class="mt-3 border-t border-slate-200 pt-3 space-y-1">
                    <h3 class="text-[13px] font-semibold text-slate-800 mb-1">Exemplos de uso</h3>

                    <p><span class="font-semibold">Assunto:</span><br>
                        <code class="bg-slate-100 px-1 py-0.5 rounded block mt-0.5">
                            Lembrete de vencimento - @{{ invoice_number }}
                        </code>
                    </p>

                    <p><span class="font-semibold">Mensagem:</span><br>
                        <code class="bg-slate-100 px-1 py-0.5 rounded block mt-0.5">
                            Olá @{{ customer_name }}, sua fatura @{{ invoice_number }} no valor de @{{ amount }} @{{ days_diff_label }} (vencimento em @{{ due_date }}).
                        </code>
                    </p>

                    <p class="text-[11px] text-slate-500">
                        Se você não usar variáveis, o texto será enviado exatamente como estiver escrito.
                    </p>
                </div>
            </aside>
        </div>
    </main>
@endsection
