@extends('layouts.templates.template')

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
         <div class="my-4 flex items-center gap-4">
            <div class="ml-auto flex items-center gap-2 shrink-0">
                <a href="{{route('knowledge.view')}}" class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-800">
                    Base de conhecimento
                </a>


                <button id="toggle-header"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow hover:bg-slate-50"
                        aria-expanded="true" aria-controls="header-collapsible" type="button"
                        title="Expandir/contrair cabeçalho">
                    <i id="toggle-icon" class="fa-solid fa-up-right-and-down-left-from-center"></i>
                </button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[280px,1fr]">
            <aside class="hidden lg:flex flex-col rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <button
                    class="mb-4 w-full inline-flex items-center justify-center gap-2 rounded-xl bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                    <span class="text-base">+</span> Novo chat
                </button>
                <p class="text-[11px] font-semibold text-slate-400 uppercase mb-2">Recentes</p>
                <div class="space-y-1 text-xs">
                    <div class="cursor-pointer truncate rounded-lg bg-slate-100 px-2 py-2 text-slate-800">
                        Erro E4 balança Toledo
                    </div>
                    <div class="cursor-pointer truncate rounded-lg px-2 py-2 text-slate-600 hover:bg-slate-50">
                        Preço mecanismo Fujitsu
                    </div>
                </div>
            </aside>

            <section class="flex flex-col rounded-2xl border border-slate-200 bg-white shadow-sm h-[70vh]">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 sm:px-5 py-3">
                    <div class="flex items-center gap-3">
                        <div class="grid h-9 w-9 place-items-center rounded-2xl bg-blue-600 text-white shadow">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2">
                                <path d="M12 12c2.8 0 5-2.2 5-5S14.8 2 12 2 7 4.2 7 7s2.2 5 5 5Z"/>
                                <path d="M5 22a7 7 0 0 1 14 0"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-[11px] font-medium text-slate-500">Chat IA • Cliqis</p>
                            <h1 class="text-sm sm:text-base font-semibold text-slate-900">
                                Assistente do painel
                            </h1>
                            <p class="text-[11px] text-slate-500">
                                Responde com base nos documentos cadastrados na sua base de conhecimento.
                            </p>
                        </div>
                    </div>
                    <div class="hidden sm:flex items-center gap-2 text-[11px] text-emerald-600">
                        <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="uppercase tracking-wide">Online</span>
                    </div>
                </div>

                <div id="chat-messages"
                     class="flex-1 overflow-y-auto px-4 sm:px-5 py-4 space-y-4 bg-slate-50/60">
                    <div class="flex gap-3">
                        <div
                            class="mt-0.5 flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-blue-700 text-[11px] font-semibold">
                            IA
                        </div>
                        <div
                            class="max-w-[80%] rounded-2xl rounded-tl-none bg-white px-4 py-3 text-sm text-slate-800 border border-slate-200 shadow-sm">
                            Olá! Me diz em poucas palavras o que você precisa
                            (ex.: "balança não liga", "erro E4", "preço código 6206088").
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-200 bg-white px-4 sm:px-5 py-3">
                    <form id="chat-form" class="max-w-3xl mx-auto">
                        @csrf

                        <div class="relative flex items-center">
                            <input id="chat-input"
                                   type="text"
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 pr-11 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder:text-slate-400"
                                   placeholder="Digite sua pergunta aqui..."
                                   autocomplete="off">
                            <button type="submit"
                                    class="absolute right-1.5 top-1.5 flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-white text-xs hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                    id="chat-send-btn">
                                ➤
                            </button>
                        </div>
                        <p class="mt-1 text-[10px] text-slate-400 text-center">
                            A IA usa primeiro os documentos da sua base. Se não encontrar nada, responde de forma geral.
                        </p>
                    </form>
                </div>
            </section>
        </div>
    </div>

    <script>
        const form = document.getElementById('chat-form');
        const input = document.getElementById('chat-input');
        const sendBtn = document.getElementById('chat-send-btn');
        const messagesEl = document.getElementById('chat-messages');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const text = input.value.trim();
            if (!text) return;

            // adiciona mensagem do usuário
            addUserMessage(text);
            input.value = '';
            input.focus();

            // loading
            const loadingId = addLoadingMessage();

            sendBtn.disabled = true;

            try {
                const res = await fetch('{{ route('chat.message') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({message: text}),
                    }
                );
                const data = await res.json();
                removeLoadingMessage(loadingId);
                addAssistantMessage(data.answer || 'Sem resposta da IA.');
            } catch (error) {
                console.error(error);
                removeLoadingMessage(loadingId);
                addAssistantMessage('Erro ao falar com o servidor.');
            } finally {
                sendBtn.disabled = false;
            }
        });

        function scrollToBottom() {
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }

        function addUserMessage(text) {
            const wrapper = document.createElement('div');
            wrapper.className = 'flex gap-3 justify-end';

            const bubble = document.createElement('div');
            bubble.className = 'max-w-[80%] rounded-2xl rounded-br-none bg-blue-600 px-4 py-3 text-sm text-white shadow-sm';
            bubble.textContent = text;

            const avatar = document.createElement('div');
            avatar.className = 'mt-0.5 flex h-7 w-7 items-center justify-center rounded-full bg-slate-800 text-[11px] font-semibold text-white';
            avatar.textContent = 'Você';

            wrapper.appendChild(bubble);
            wrapper.appendChild(avatar);

            messagesEl.appendChild(wrapper);
            scrollToBottom();
        }

        function addAssistantMessage(text) {
            const wrapper = document.createElement('div');
            wrapper.className = 'flex gap-3 justify-start';

            const avatar = document.createElement('div');
            avatar.className = 'mt-0.5 flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-blue-700 text-[11px] font-semibold';
            avatar.textContent = 'IA';

            const bubble = document.createElement('div');
            bubble.className = 'max-w-[80%] rounded-2xl rounded-tl-none bg-white px-4 py-3 text-sm text-slate-800 border border-slate-200 shadow-sm whitespace-pre-line';
            bubble.textContent = text;

            wrapper.appendChild(avatar);
            wrapper.appendChild(bubble);

            messagesEl.appendChild(wrapper);
            scrollToBottom();
        }

        let loadingCounter = 0;

        function addLoadingMessage() {
            const id = ++loadingCounter;
            const wrapper = document.createElement('div');
            wrapper.className = 'flex gap-3 justify-start';
            wrapper.dataset.loadingId = id;

            const avatar = document.createElement('div');
            avatar.className = 'mt-0.5 flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-blue-700 text-[11px] font-semibold';
            avatar.textContent = 'IA';

            const bubble = document.createElement('div');
            bubble.className = 'flex items-center gap-2 max-w-[80%] rounded-2xl rounded-tl-none bg-white px-4 py-3 text-xs text-slate-500 border border-slate-200 shadow-sm';
            bubble.innerHTML = `
                <span>Pensando</span>
                <span class="flex gap-1">
                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400 animate-bounce"></span>
                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400 animate-bounce [animation-delay:0.15s]"></span>
                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400 animate-bounce [animation-delay:0.3s]"></span>
                </span>
            `;

            wrapper.appendChild(avatar);
            wrapper.appendChild(bubble);

            messagesEl.appendChild(wrapper);
            scrollToBottom();
            return id;
        }

        function removeLoadingMessage(id) {
            const el = messagesEl.querySelector(`[data-loading-id="${id}"]`);
            if (el) el.remove();
        }
    </script>
@endsection

{{--@push('scripts')--}}
{{--    <script type="module" src="{{ asset('assets/js/template/views/chat-ia/chat.js') }}"></script>--}}
{{--@endpush--}}
