@extends('layouts.templates.public-signature')
@section('content')
    <main class="mx-auto max-w-4xl px-3 sm:px-6 pb-[calc(7rem+env(safe-area-inset-bottom))] lg:pb-14">
        <div class="pt-6 sm:pt-8">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-4 sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                    <div>
                        <h1 class="text-lg font-semibold text-slate-900">Assinatura da Ordem de Serviço</h1>
                        <p class="text-sm text-slate-500 mt-1">
                            OS <span class="font-medium text-slate-700">{{ $os->order_number ?? $os->id }}</span>
                            — {{ $os->secondaryCustomer->name ?? $os->client_name ?? '-' }}
                        </p>
                        <p class="text-xs text-slate-400 mt-1">
                            Válido até {{ optional($req->expires_at)->format('d/m/Y H:i') }}
                        </p>
                    </div>

                    @if($signed)
                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                            Assinado com sucesso
                        </span>
                    @endif
                </div>

                @if(!$signed && $errors->any())
                    <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        {{ $errors->first() }}
                    </div>
                @endif
            </div>

            {{-- Resumo OS --}}
            <div class="mt-4 sm:mt-5 grid gap-4 lg:grid-cols-3">
                <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white shadow-sm p-4 sm:p-6">
                    <h2 class="text-sm font-semibold text-slate-900">Resumo</h2>

                    <div class="mt-3 grid gap-3 sm:grid-cols-2 text-sm">
                        <div class="rounded-xl bg-slate-50 px-4 py-3">
                            <div class="text-xs text-slate-500">Cliente</div>
                            <div class="font-medium text-slate-900">
                                {{ $os->secondaryCustomer->name ?? $os->client_name ?? '-' }}
                            </div>
                            <div class="text-xs text-slate-500 mt-1">
                                Doc: {{ $os->secondaryCustomer->cpfCnpj ?? '-' }}
                            </div>
                            <div class="text-xs text-slate-500">
                                E-mail: {{ $req->to_email ?? ($os->secondaryCustomer->email ?? '-') }}
                            </div>
                        </div>

                        <div class="rounded-xl bg-slate-50 px-4 py-3">
                            <div class="text-xs text-slate-500">Técnico</div>
                            <div class="font-medium text-slate-900">
                                {{ $os->technician->full_name ?? '-' }}
                            </div>
                            <div class="text-xs text-slate-500 mt-1">
                                Data: {{ $os->order_date }}
                            </div>
                            <div class="text-xs text-slate-500">
                                Status atual: {{ $os->status ?? '-' }}
                            </div>
                        </div>
                    </div>

                    {{-- Serviços --}}
                    <div class="mt-5">
                        <h3 class="text-sm font-semibold text-slate-900">Serviços</h3>

                        <div class="mt-2 overflow-x-auto rounded-2xl border border-slate-200">
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-50 text-left text-slate-600">
                                <tr>
                                    <th class="px-4 py-3">Descrição</th>
                                    <th class="px-4 py-3 text-right whitespace-nowrap">Qtd</th>
                                    <th class="px-4 py-3 text-right whitespace-nowrap">Unit</th>
                                    <th class="px-4 py-3 text-right whitespace-nowrap">Total</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                @forelse(($os->serviceItems ?? []) as $s)
                                    <tr>
                                        <td class="px-4 py-3">{{ $s->description ?? '-' }}</td>
                                        <td class="px-4 py-3 text-right">{{ $s->quantity ?? 0 }}</td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">R$ {{ number_format((float)($s->unit_price ?? 0), 2, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">R$ {{ number_format((float)($s->total ?? (($s->quantity ?? 0)*($s->unit_price ?? 0))), 2, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="px-4 py-4 text-slate-500" colspan="4">Nenhum serviço.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Peças --}}
                    <div class="mt-5">
                        <h3 class="text-sm font-semibold text-slate-900">Peças</h3>

                        <div class="mt-2 overflow-x-auto rounded-2xl border border-slate-200">
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-50 text-left text-slate-600">
                                <tr>
                                    <th class="px-4 py-3 whitespace-nowrap">Código</th>
                                    <th class="px-4 py-3">Descrição</th>
                                    <th class="px-4 py-3 text-right whitespace-nowrap">Qtd</th>
                                    <th class="px-4 py-3 text-right whitespace-nowrap">Unit</th>
                                    <th class="px-4 py-3 text-right whitespace-nowrap">Total</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                @forelse(($os->partItems ?? []) as $p)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap">{{ $p->part->code ?? $p->code ?? '-' }}</td>
                                        <td class="px-4 py-3">{{ $p->description ?? ($p->part->name ?? '-') }}</td>
                                        <td class="px-4 py-3 text-right">{{ $p->quantity ?? 0 }}</td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">R$ {{ number_format((float)($p->unit_price ?? 0), 2, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">R$ {{ number_format((float)($p->total ?? (($p->quantity ?? 0)*($p->unit_price ?? 0))), 2, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="px-4 py-4 text-slate-500" colspan="5">Nenhuma peça.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Totais + Assinatura --}}
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-4 sm:p-6">
                    <h2 class="text-sm font-semibold text-slate-900">Totais</h2>

                    <div class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-slate-600">Serviços</span><span class="font-semibold">R$ {{ number_format((float)($os->services_subtotal ?? 0), 2, ',', '.') }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-600">Peças</span><span class="font-semibold">R$ {{ number_format((float)($os->parts_subtotal ?? 0), 2, ',', '.') }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-600">Desconto</span><span class="font-semibold">R$ {{ number_format((float)($os->discount_amount ?? 0), 2, ',', '.') }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-600">Acréscimo</span><span class="font-semibold">R$ {{ number_format((float)($os->addition_amount ?? 0), 2, ',', '.') }}</span></div>
                        <div class="pt-2 border-t border-slate-100 flex justify-between">
                            <span class="text-slate-900 font-semibold">Total</span>
                            <span class="text-slate-900 font-extrabold">R$ {{ number_format((float)($os->grand_total ?? 0), 2, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="mt-6">
                        <h2 class="text-sm font-semibold text-slate-900">Assinatura</h2>

                        @if($signed)
                            <div class="mt-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                                Assinatura registrada.
                            </div>
                        @else
                            <form id="sign-form" method="POST" action="{{ route('service-orders.signature.public.store', ['token' => $token]) }}" class="mt-3 space-y-3">
                                @csrf

                                <div>
                                    <label class="text-xs text-slate-600">Nome (opcional)</label>
                                    {{-- 16px no mobile evita zoom do iPhone ao focar --}}
                                    <input name="client_name"
                                           class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[16px] sm:text-sm"
                                           value="{{ $os->secondaryCustomer->name ?? '' }}">
                                </div>

                                <div>
                                    <label class="text-xs text-slate-600">E-mail (opcional)</label>
                                    <input name="client_email"
                                           class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[16px] sm:text-sm"
                                           value="{{ $req->to_email ?? ($os->secondaryCustomer->email ?? '') }}">
                                </div>

                                <div class="border border-slate-300 rounded-2xl overflow-hidden bg-slate-50">
                                    <canvas id="signature-pad" class="w-full h-48 sm:h-56 touch-none"></canvas>
                                </div>

                                <input type="hidden" name="image_base64" id="image_base64">

                                <div class="flex justify-between gap-2">
                                    <button type="button" id="signature-clear"
                                            class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                        Limpar
                                    </button>
                                    <button type="submit" id="signature-save"
                                            class="inline-flex items-center rounded-xl bg-blue-700 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-800">
                                        Assinar OS
                                    </button>
                                </div>

                                <p class="text-[11px] text-slate-500">
                                    Ao assinar, você confirma ciência e aprovação desta ordem de serviço.
                                </p>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </main>

    @if(!$signed)
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                const canvas = document.getElementById('signature-pad');
                const clearBtn = document.getElementById('signature-clear');
                const form = document.getElementById('sign-form');
                const saveBtn = document.getElementById('signature-save');
                const hidden = document.getElementById('image_base64');

                if (!canvas || !form || !saveBtn) return;

                const ctx = canvas.getContext('2d');
                let isDrawing = false, lastX = 0, lastY = 0;
                let hasInk = false;

                const resizeCanvas = () => {
                    const rect = canvas.getBoundingClientRect();
                    if (!rect.width || !rect.height) return;

                    const dpr = window.devicePixelRatio || 1;

                    canvas.width  = Math.round(rect.width * dpr);
                    canvas.height = Math.round(rect.height * dpr);

                    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
                    ctx.clearRect(0, 0, rect.width, rect.height);
                    hasInk = false;
                };

                setTimeout(resizeCanvas, 30);
                window.addEventListener('resize', resizeCanvas);

                const pos = (evt) => {
                    const rect = canvas.getBoundingClientRect();
                    let x, y;
                    if (evt.touches && evt.touches.length) {
                        x = evt.touches[0].clientX - rect.left;
                        y = evt.touches[0].clientY - rect.top;
                    } else {
                        x = evt.clientX - rect.left;
                        y = evt.clientY - rect.top;
                    }
                    return {x, y};
                };

                const start = (evt) => {
                    evt.preventDefault?.();
                    isDrawing = true;
                    const p = pos(evt);
                    lastX = p.x; lastY = p.y;
                };

                const draw = (evt) => {
                    if (!isDrawing) return;
                    hasInk = true;

                    evt.preventDefault?.();
                    const p = pos(evt);

                    ctx.lineWidth = 2;
                    ctx.lineCap = 'round';
                    ctx.strokeStyle = '#111827';
                    ctx.beginPath();
                    ctx.moveTo(lastX, lastY);
                    ctx.lineTo(p.x, p.y);
                    ctx.stroke();

                    lastX = p.x; lastY = p.y;
                };

                const stop = (evt) => { evt?.preventDefault?.(); isDrawing = false; };

                canvas.addEventListener('mousedown', start);
                canvas.addEventListener('mousemove', draw);
                canvas.addEventListener('mouseup', stop);
                canvas.addEventListener('mouseleave', stop);

                canvas.addEventListener('touchstart', start, {passive:false});
                canvas.addEventListener('touchmove', draw, {passive:false});
                canvas.addEventListener('touchend', stop, {passive:false});
                canvas.addEventListener('touchcancel', stop, {passive:false});

                clearBtn?.addEventListener('click', () => {
                    const rect = canvas.getBoundingClientRect();
                    ctx.clearRect(0, 0, rect.width, rect.height);
                    hasInk = false;
                });

                form.addEventListener('submit', (e) => {
                    if (!hasInk) {
                        e.preventDefault();
                        alert('Assine no quadro antes de enviar.');
                        return;
                    }

                    hidden.value = canvas.toDataURL('image/png');

                    const original = saveBtn.innerHTML;
                    saveBtn.disabled = true;
                    saveBtn.innerHTML = `
                        <span class="inline-flex items-center gap-2">
                            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            Assinando...
                        </span>
                    `;

                    setTimeout(() => { saveBtn.innerHTML = original; }, 4000);
                });
            });
        </script>
    @endif
@endsection
