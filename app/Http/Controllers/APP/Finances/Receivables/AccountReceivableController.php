<?php

namespace App\Http\Controllers\APP\Finances\Receivables;

use App\Http\Controllers\Controller;
use App\Http\Resources\Invoice\InvoiceResource;
use App\Models\Finances\Receivables\AccountReceivablePayment;
use App\Models\Sales\Budgets\Subscriptions\Subscription;
use App\Models\Sales\Invoices\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountReceivableController extends Controller
{
    public function view()
    {
        return view('app.finances.receivables.receivable_index');
    }

    public function index(Request $r)
    {
        $term       = $r->get('q');
        $tab        = $r->get('tab', 'all');
        $startDate  = $r->get('start_date'); // yyyy-mm-dd opcional
        $endDate    = $r->get('end_date');   // yyyy-mm-dd opcional

        /*
        | 1. INVOICES (avulsas / parcela / sinal)
        */
        $invQ = Invoice::with(['customer', 'items', 'budget']);

        if ($term) {
            $invQ->search($term);
        }

        // período: filtra por due_date
        if ($startDate) {
            $invQ->whereDate('due_date', '>=', $startDate);
        }
        if ($endDate) {
            $invQ->whereDate('due_date', '<=', $endDate);
        }

        $invoices = $invQ->get()->map(function ($inv) use ($r) {
            $row = (new InvoiceResource($inv))->toArray($r);

            return [
                'id'         => $row['id'],
                'kind'       => 'invoice',
                'number'     => $row['number'] ?? null,
                'date' => !empty($row['due_date'])
                    ? Carbon::parse($row['due_date'])->toDateString()
                    : null,
                'price'      => (float) ($row['amount'] ?? 0),
                'status_raw' => $row['status'] ?? 'pending',  // 'pending','overdue','paid' etc

                'parcel_label' => $this->buildParcelLabelFromInvoiceRow($row),

                'customer'   => [
                    'id'   => $row['customer']['id']   ?? null,
                    'name' => $row['customer']['name'] ?? null,
                ],
                'budget'     => [
                    'id'   => $row['budget']['id']   ?? null,
                    'code' => $row['budget']['code'] ?? null,
                ],
                'origin'     => [
                    'id'          => $row['id'],
                    'description' => $this->firstDescriptionFromInvoiceRow($row),
                ],
            ];
        });

        /*
        | 2. SUBSCRIPTIONS (recorrentes)
        */
        $subQ = Subscription::with(['customer', 'budget'])
            ->where('active', true);

        if ($term) {
            $like = "%{$term}%";
            $subQ->where(function ($qq) use ($like) {
                $qq->where('name', 'like', $like)
                    ->orWhereHas('customer', function ($c) use ($like) {
                        $c->where('name', 'like', $like);
                    })
                    ->orWhereHas('budget', function ($b) use ($like) {
                        $b->where('budget_code', 'like', $like);
                    });
            });
        }

        // período: filtra próxima cobrança (next_due_date)
        if ($startDate) {
            $subQ->whereDate('next_due_date', '>=', $startDate);
        }
        if ($endDate) {
            $subQ->whereDate('next_due_date', '<=', $endDate);
        }

        $subs = $subQ->get()->map(function ($s) {
            $row = $this->mapSubscriptionToRow($s);

            return [
                'id'         => $row['id'],
                'kind'       => 'subscription',
                'number'     => null, // "Recorrência" na view
                'date' => !empty($row['due_date'])
                    ? Carbon::parse($row['due_date'])->toDateString()
                    : null,
                'price'      => (float) ($row['amount'] ?? 0),
                'status_raw' => $row['status'] ?? 'pending',

                'parcel_label' => $this->buildParcelLabelFromSubscriptionRow($row),

                'customer'   => [
                    'id'   => $row['customer']['id']   ?? null,
                    'name' => $row['customer']['name'] ?? null,
                ],
                'budget'     => [
                    'id'   => $row['budget']['id']   ?? null,
                    'code' => $row['budget']['code'] ?? null,
                ],
                'origin'     => [
                    'id'          => $row['id'],
                    'description' => $row['name'] ?? 'Recorrência',
                ],
            ];
        });

        /*
        | 3. MERGE + calcular status efetivo
        |   pending/overdue calculado via due_date x hoje
        |   paid/canceled mantido
        */
        $today = Carbon::now()->startOfDay();

        $all = $invoices->concat($subs)->map(function ($row) use ($today) {
            $status = $row['status_raw'] ?? 'pending';

            if ($status === 'paid' || $status === 'canceled') {
                // usa o que veio do banco (invoice paga etc)
            } else {
                $dueRaw = $row['date'] ?? null;
                if ($dueRaw) {
                    $due = Carbon::parse($dueRaw)->startOfDay();
                    $status = $due->lt($today) ? 'overdue' : 'pending';
                } else {
                    $status = 'pending';
                }
            }

            $row['status'] = $status;
            unset($row['status_raw']);
            return $row;
        })->values();

        /*
        | 4. aplica filtro da aba (tab)
        */
        $filtered = $all->filter(function ($row) use ($tab) {
            $kind   = $row['kind']   ?? 'invoice';
            $status = $row['status'] ?? 'pending';

            return match ($tab) {
                'single'     => ($kind === 'invoice'),
                'recurring'  => ($kind === 'subscription'),
                'paid'       => ($status === 'paid'),
                'pending'    => ($status === 'pending'),
                'overdue'    => ($status === 'overdue'),
                default      => true,
            };
        })->values();

        /*
        | 5. Ordena por vencimento
        */
        $filtered = $filtered->sortBy('date')->values();

        /*
        | 6. KPIs
        | pendente   = soma dos que estão "pending" dentro do conjunto filtrado
        | atrasado   = soma dos que estão "overdue" dentro do conjunto filtrado
        | pago       = soma:
        |   a) invoices status 'paid' dentro do período
        |   b) payments (account_receivable_payments) feitos no período
        */

        // pendente
        $valorPendente = $filtered
            ->filter(fn($x) => $x['status'] === 'pending')
            ->sum(fn($x) => (float)$x['price']);

        // atrasado
        $valorAtrasado = $filtered
            ->filter(fn($x) => $x['status'] === 'overdue')
            ->sum(fn($x) => (float)$x['price']);

        $paidQ = AccountReceivablePayment::query();

        if ($startDate) {
            $paidQ->whereDate('paid_at', '>=', $startDate);
        }
        if ($endDate) {
            $paidQ->whereDate('paid_at', '<=', $endDate);
        }

        $valorPagoTotal = (float) $paidQ->sum('amount');

        return response()->json([
            'data' => $filtered,
            'kpis' => [
                'pendente' => $valorPendente,
                'atrasado' => $valorAtrasado,
                'pago'     => $valorPagoTotal,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /account/account_receivables/{id}/pay
    | -> marcar recorrente como pago e avançar próxima parcela
    |--------------------------------------------------------------------------
    */
    public function paySubscription(Request $r, $subscriptionId)
    {
        $data = $r->validate([
            'paid_at'   => ['required','date'],
            'amount'    => ['required','numeric','min:0.01'],
            'interest'  => ['nullable','numeric','min:0'],
            'fine'      => ['nullable','numeric','min:0'],
            'discount'  => ['nullable','numeric','min:0'],
            'reference' => ['nullable','string','max:255'],
            'notes'     => ['nullable','string'],
        ]);

        return DB::transaction(function () use ($subscriptionId, $data) {

            $sub = Subscription::lockForUpdate()->findOrFail($subscriptionId);

            if (!$sub->active) {
                return response()->json([
                    'ok'    => false,
                    'error' => 'Assinatura inativa',
                ], 422);
            }

            $currentDue = Carbon::parse($sub->next_due_date);

            AccountReceivablePayment::create([
                'customer_sistapp_id' => $sub->customer_sistapp_id,
                'user_id'             => auth()->id(),
                'subscription_id'     => $sub->id,
                'invoice_id'          => null,

                'paid_at'   => $data['paid_at'],
                'amount'    => $data['amount'],
                'interest'  => $data['interest']  ?? 0,
                'fine'      => $data['fine']      ?? 0,
                'discount'  => $data['discount']  ?? 0,

                'reference' => $data['reference'] ?? null,
                'notes'     => $data['notes']     ?? null,
            ]);

            $next = $this->bumpSubscriptionNextDueDate($sub, $currentDue);

            if ($next) {
                $sub->next_due_date = $next->toDateString();
                $sub->save();
            }

            return response()->json(['ok' => true]);
        });
    }

    public function setPaid(Request $request, $invoiceId)
    {
        $data = $request->validate([
            'paid_at'   => ['required','date'],
            'amount'    => ['required','numeric','min:0.01'],
            'interest'  => ['nullable','numeric','min:0'],
            'fine'      => ['nullable','numeric','min:0'],
            'discount'  => ['nullable','numeric','min:0'],
            'reference' => ['nullable','string','max:255'],
            'notes'     => ['nullable','string'],
        ]);

        return DB::transaction(function () use ($invoiceId, $data) {

            /** @var \App\Models\Sales\Invoices\Invoice $invoice */
            $invoice = Invoice::lockForUpdate()->findOrFail($invoiceId);

            // se já estiver paga, só retorna OK (já teve registro de pagamento)
            if ($invoice->status === 'paid') {
                return response()->json(['ok' => true]);
            }

            // cria o registro de pagamento padronizado
            AccountReceivablePayment::create([
                'customer_sistapp_id' => $invoice->customer_sistapp_id,
                'user_id'             => auth()->id(),
                'subscription_id'     => null,
                'invoice_id'          => $invoice->id,

                'paid_at'   => $data['paid_at'],
                'amount'    => $data['amount'],
                'interest'  => $data['interest']  ?? 0,
                'fine'      => $data['fine']      ?? 0,
                'discount'  => $data['discount']  ?? 0,

                'reference' => $data['reference'] ?? null,
                'notes'     => $data['notes']     ?? null,
            ]);

            // marca a fatura como paga
            $invoice->status = 'paid';
            // se você tiver colunas paid_at ou paid_amount, atualiza aqui:
            // $invoice->paid_at     = $data['paid_at'];
            // $invoice->paid_amount = $data['amount'];
            $invoice->save();

            return response()->json(['ok' => true]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Dashboard KPI "A receber neste mês"
    |--------------------------------------------------------------------------
    */
    public function monthDue()
    {
        $start = now()->startOfMonth()->toDateString();
        $end   = now()->endOfMonth()->toDateString();

        // faturas (invoice) pendentes/atrasadas no mês
        $sumInv = Invoice::whereIn('status', ['pending','overdue'])
            ->whereBetween('due_date', [$start, $end])
            ->sum('amount');

        // recorrências ativas com vencimento dentro do mês
        $sumSub = Subscription::where('active', true)
            ->whereBetween('next_due_date', [$start, $end])
            ->sum('amount');

        return response()->json([
            'value' => (float) ($sumInv + $sumSub),
            'start' => $start,
            'end'   => $end,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    // pega primeira descrição útil da invoice pra exibir na tabela
    protected function firstDescriptionFromInvoiceRow(array $row)
    {
        if (!empty($row['items'][0]['description'])) {
            return $row['items'][0]['description'];
        }
        if (!empty($row['number'])) {
            return $row['number'];
        }
        return '—';
    }

    // traduz período pra badge
    protected function periodPtBr(?string $p): string
    {
        return match ($p) {
            'monthly' => 'Mensal',
            'yearly'  => 'Anual',
            default   => '',
        };
    }

    // label da coluna "Parcelas" para invoices normais
    protected function buildParcelLabelFromInvoiceRow(array $row): string
    {
        // invoice marcada como recorrente
        if (!empty($row['is_recurring'])) {
            return $this->periodPtBr($row['recurring_period'] ?? null);
        }

        // tenta descrever ("Sinal", "Parcela 1", etc)
        if (!empty($row['items'][0]['description'])) {
            return $row['items'][0]['description'];
        }

        // fallback tosco
        if (!empty($row['installments'])) {
            return 'Parcela '.$row['installments'].'x';
        }

        return '';
    }

    // label da coluna "Parcelas" para subscriptions
    protected function buildParcelLabelFromSubscriptionRow(array $row): string
    {
        // row['recurring_period'] vai vir de Subscription->period
        $lbl = $this->periodPtBr($row['recurring_period'] ?? null);
        return $lbl !== '' ? $lbl : 'Recorrência';
    }

    // monta os dados da subscription num formato parecido com invoice
    protected function mapSubscriptionToRow(Subscription $s): array
    {
        // cuidado: campos reais do model Subscription
        // pelos prints, Subscription tem:
        // id, customer_id, budget_id, name, amount, period ('monthly'|'yearly'),
        // day_of_month, month_of_year, next_due_date, active, ...
        return [
            'id'               => $s->id,
            'name'             => $s->name,
            'due_date' => $s->next_due_date
                ? Carbon::parse($s->next_due_date)->toDateString()
                : null,
            'amount'           => (float) $s->amount,
            'status'           => $s->status ?? 'pending', // se não existir status na tabela, fica sempre 'pending'
            'recurring_period' => $s->period,               // 'monthly' | 'yearly'

            'customer' => [
                'id'   => $s->customer->id ?? null,
                'name' => $s->customer->name ?? null,
            ],

            'budget' => [
                'id'   => $s->budget->id ?? null,
                'code' => $s->budget->budget_code
                    ? ('#' . str_pad((string)$s->budget->budget_code, 6, '0', STR_PAD_LEFT))
                    : null,
            ],
        ];
    }

    // calcula a próxima data de vencimento da assinatura
    // baseando no ciclo atual (currentDue), não na data paga
    private function bumpSubscriptionNextDueDate(Subscription $sub, Carbon $currentDue): ?Carbon
    {
        // monthly -> soma 1 mês mantendo o dia configurado
        if ($sub->period === 'monthly') {
            $next = $currentDue
                ->copy()
                ->addMonthNoOverflow();

            if (!empty($sub->day_of_month)) {
                $next->day($sub->day_of_month);
            }

            return $next;
        }

        // yearly -> soma 1 ano mantendo mês/dia configurado
        if ($sub->period === 'yearly') {
            $next = $currentDue
                ->copy()
                ->addYearNoOverflow();

            if (!empty($sub->month_of_year)) {
                $next->month($sub->month_of_year);
            }
            if (!empty($sub->day_of_month)) {
                $next->day($sub->day_of_month);
            }

            return $next;
        }

        // outro tipo -> nada
        return null;
    }
}
