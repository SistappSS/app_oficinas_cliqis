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
        const res = await fetch('/chat/message', {
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
