<?php

namespace App\Http\Controllers\APP\Sales\Invoices\Billing;

use App\Http\Controllers\Controller;
use App\Http\Resources\Invoice\InvoiceResource;
use App\Mail\InvoiceReminderMail;
use App\Models\Sales\Budgets\Budget;
use App\Models\Sales\Budgets\BudgetItem;
use App\Models\Sales\Budgets\Subscriptions\Subscription;
use App\Models\Sales\Invoices\Invoice;
use App\Models\Sales\Invoices\ReminderInvoiceConfig;
use App\Models\Sales\Services\Service;
use App\Traits\RoleCheckTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;

class BillingController extends Controller
{
    use RoleCheckTrait;

    public function view()
    {
        return view('app.sales.invoice.invoice_index');
    }

    public function index(Request $r)
    {
        $term = $r->get('q'); // texto da busca
        $ids  = (array) $r->get('ids', []);

        // aba atual da UI:
        // all | recurring | single | pending | overdue
        // fallback pra 'status' só pra não quebrar enquanto ajusta o front
        $tab  = $r->get('tab', $r->get('status', 'all'));

        /*
         * CASO ESPECIAL: modal de edição chama
         * /invoices?ids[]=123
         * Nesse caso eu NÃO misturo subscription,
         * eu só devolvo a(s) invoices pedidas.
         */
        if (!empty($ids)) {
            $invQ = Invoice::with(['customer', 'items', 'budget'])
                ->whereIn('id', $ids);

            if ($term) {
                $invQ->search($term);
            }

            $invoices = $invQ->get()->map(function ($inv) use ($r) {
                return (new InvoiceResource($inv))->toArray($r);
            });

            return response()->json([
                'data'        => $invoices,
                'kpi_pending' => 0,
                'kpi_overdue' => 0,
            ]);
        }

        /*
         * LISTAGEM NORMAL DA TELA
         * 1. Carrega todas as faturas normais (Invoice)
         */
        $invQ = Invoice::with(['customer', 'items', 'budget']);

        if ($term) {
            $invQ->search($term);
        }

        $invoices = $invQ->get()->map(function ($inv) use ($r) {
            // mantém o formato atual do front
            $arr = (new InvoiceResource($inv))->toArray($r);
            $arr['kind'] = 'invoice'; // garantia
            return $arr;
        });

        /*
         * 2. Carrega subscriptions ativas (recorrentes mensal/anual)
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

        $subs = $subQ->get()->map(function ($s) {
            return $this->mapSubscriptionToRow($s);
        });

        /*
         * 3. Junta tudo num array só
         */
        $all = $invoices->concat($subs)->values();

        /*
         * 4. Normaliza status efetivo (pending / overdue / paid / canceled)
         *    Regras:
         *    - se já está paid/canceled mantém
         *    - senão: compara due_date com hoje -> overdue/pending
         */
        $today = Carbon::now()->startOfDay();

        $all = $all->map(function ($row) use ($today) {
            $status = $row['status'] ?? 'pending';

            if ($status === 'paid' || $status === 'canceled') {
                // mantém
            } else {
                $dueRaw = $row['due_date'] ?? null;
                if ($dueRaw) {
                    $due = Carbon::parse($dueRaw)->startOfDay();
                    $status = $due->lt($today) ? 'overdue' : 'pending';
                } else {
                    $status = 'pending';
                }
            }

            $row['status'] = $status;
            return $row;
        });

        // 4.1 – nesta tela só queremos pendentes/atrasados
        $all = $all->filter(function ($row) {
            $status = $row['status'] ?? 'pending';
            return in_array($status, ['pending', 'overdue'], true);
        })->values();

        /*
         * 5. KPIs (antes de filtrar por aba)
         * "A receber" = soma PENDING
         * "Em atraso" = soma OVERDUE
         * Isso tem que considerar invoice + subscription
         */
        $kpiPending = $all->filter(fn ($r) => $r['status'] === 'pending')
            ->sum(fn ($r) => (float) $r['amount']);

        $kpiOverdue = $all->filter(fn ($r) => $r['status'] === 'overdue')
            ->sum(fn ($r) => (float) $r['amount']);

        /*
         * 6. Filtro pela aba/tab escolhida
         * tabs novas:
         * - all        -> tudo
         * - recurring  -> só recorrentes
         * - single     -> só fatura única / parcela
         * - pending    -> só pendentes
         * - overdue    -> só atrasados
         *
         * Obs: 'overdue' = sua aba "Atrasados"
         */
        $filtered = $all->filter(function ($row) use ($tab) {
            $kind   = $row['kind']   ?? 'invoice';
            $status = $row['status'] ?? 'pending';

            return match ($tab) {
                'recurring' => ($kind === 'subscription'),
                'single'    => ($kind === 'invoice'),
                'pending'   => ($status === 'pending'),
                'overdue'   => ($status === 'overdue'),
                default     => true, // 'all'
            };
        })->values();

        /*
         * 7. Ordena por vencimento pra exibir
         */
        $filtered = $filtered->sortBy('due_date')->values();

        /*
         * 8. Retorno final pro front
         */
        return response()->json([
            'data'        => $filtered,
            'kpi_pending' => $kpiPending,
            'kpi_overdue' => $kpiOverdue,
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'customer_id'      => ['required','integer','exists:customers,id'],
            'service_id'       => ['required','integer','exists:services,id'],
            'due_date'         => ['required','date'],
            'amount'           => ['required','numeric','min:0.01'],
            'installments'     => ['nullable','integer','min:1'],
            'auto_reminder'    => ['boolean'],
            'is_recurring'     => ['boolean'],
            'recurring_period' => ['nullable','in:monthly,yearly'],
        ]);

        return DB::transaction(function () use ($data) {

            $due = Carbon::parse($data['due_date']); // vencimento informado
            $now = now();

            // 1) cria um orçamento só pra ter histórico e amarrar esse lançamento
            $nextCode = (int) DB::table('budgets')
                    ->lockForUpdate()
                    ->max('budget_code') + 1;

            $budget = Budget::create([
                'customer_sistapp_id'  => $this->customerSistappID(),
                'user_id'              => auth()->id(),
                'customer_id'          => (int)$data['customer_id'],

                'budget_code'          => $nextCode,
                'status'               => 'approved',

                'payment_date'         => $due->toDateString(),  // data base
                'deadline'             => 0,
                'signal'               => 0,
                'signal_price'         => 0,
                'remaining_price'      => (float)$data['amount'],
                'total_budget_price'   => (float)$data['amount'],
                'discount_percent'     => 0,
                'discount_scope'       => 'all',

                'created_at'           => $now,
                'updated_at'           => $now,
            ]);

            $serviceName = optional(Service::find($data['service_id']))->name ?? 'Serviço';

            BudgetItem::create([
                'customer_sistapp_id'  => $this->customerSistappID(),
                'user_id'              => auth()->id(),
                'budget_id'            => $budget->id,
                'service_id'           => (int)$data['service_id'],
                'item_price'           => (float)$data['amount'],
                'discount_price'       => 0,
                'price_with_discount'  => (float)$data['amount'],
                'payment_method'       => 3,
                'created_at'           => $now,
                'updated_at'           => $now,
            ]);

            // 2) caso NÃO seja recorrente -> gera Invoice normal
            if (empty($data['is_recurring']) || empty($data['recurring_period'])) {

                $seq    = (int) (Invoice::lockForUpdate()->max('id') ?? 0) + 1;
                $number = sprintf('#%06d', 230 + $seq);

                $inv = Invoice::create([
                    'customer_sistapp_id' => $this->customerSistappID(),
                    'user_id'             => auth()->id(),
                    'customer_id'         => (int)$data['customer_id'],
                    'budget_id'           => $budget->id,

                    'number'              => $number,
                    'due_date'            => $due->toDateString(),
                    'amount'              => (float)$data['amount'],
                    'installments'        => (int)($data['installments'] ?? 1),
                    'status'              => 'pending',

                    'auto_reminder'       => (bool)($data['auto_reminder'] ?? false),

                    'created_at'          => $now,
                    'updated_at'          => $now,
                ]);

                $inv->items()->create([
                    'description'  => $serviceName.' (Orçamento #'.str_pad($budget->budget_code, 6, '0', STR_PAD_LEFT).')',
                    'qty'          => 1,
                    'unit_amount'  => (float)$data['amount'],
                    'type'         => 'one_time',
                ]);

                return response()->json([
                    'ok'         => true,
                    'type'       => 'invoice',
                    'invoice_id' => $inv->id,
                ]);
            }

            // 3) se É recorrente -> cria Subscription
            $period = $data['recurring_period']; // monthly | yearly

            // opcional: salvar item mensal/anual no orçamento pra referência
            $monthlyItemId = null;
            $yearlyItemId  = null;

            if ($period === 'monthly') {
                $monthlyItemId = DB::table('budget_monthly_items')->insertGetId([
                    'customer_sistapp_id' => $this->customerSistappID(),
                    'user_id'             => auth()->id(),
                    'budget_id'           => $budget->id,
                    'service_id'          => (int)$data['service_id'],
                    'price'               => (float)$data['amount'],
                    'payment_day'         => $due->day,
                    'discount_price'      => 0,
                    'price_with_discount' => (float)$data['amount'],
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ]);
            } else { // yearly
                $yearlyItemId = DB::table('budget_yearly_items')->insertGetId([
                    'customer_sistapp_id' => $this->customerSistappID(),
                    'user_id'             => auth()->id(),
                    'budget_id'           => $budget->id,
                    'service_id'          => (int)$data['service_id'],
                    'price'               => (float)$data['amount'],
                    'payment_day'         => $due->day,
                    'payment_month'       => $due->month,
                    'discount_price'      => 0,
                    'price_with_discount' => (float)$data['amount'],
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ]);
            }

            $sub = Subscription::create([
                'customer_sistapp_id'     => $this->customerSistappID(),
                'user_id'                 => auth()->id(),
                'budget_id'               => $budget->id,
                'customer_id'             => (int)$data['customer_id'],

                'budget_monthly_item_id'  => $monthlyItemId,
                'budget_yearly_item_id'   => $yearlyItemId,

                'name'                    => $serviceName,
                'amount'                  => (float)$data['amount'],
                'period'                  => $period, // monthly | yearly
                'day_of_month'            => $due->day,
                'month_of_year'           => ($period === 'yearly') ? $due->month : null,
                'next_due_date'           => $due->toDateString(),

                'auto_reminder'           => (bool)($data['auto_reminder'] ?? false),
                'active'                  => true,

                'created_at'              => $now,
                'updated_at'              => $now,
            ]);

            return response()->json([
                'ok'           => true,
                'type'         => 'subscription',
                'subscription' => $sub->id,
            ]);
        });
    }

    public function update($id, Request $r)
    {
        // se for "sub_x" → atualizar apenas auto_reminder na subscription
        if (Str::startsWith($id, 'sub_')) {
            $subId = (int) Str::after($id, 'sub_');
            $sub   = Subscription::findOrFail($subId);

            $data = $r->validate([
                'auto_reminder' => ['sometimes', 'boolean'],
            ]);

            $sub->fill($data);
            $sub->save();

            return response()->json([
                'ok'   => true,
                'type' => 'subscription',
            ]);
        }

        // fluxo normal para Invoice
        $inv = Invoice::with('items')->findOrFail($id);

        $data = $r->validate([
            'due_date'      => ['sometimes', 'date'],
            'amount'        => ['sometimes', 'numeric', 'min:0'],
            'auto_reminder' => ['sometimes', 'boolean'],
        ]);

        DB::transaction(function () use ($inv, $data) {
            $inv->update($data);

            if (array_key_exists('amount', $data)) {
                $firstItem = $inv->items()->orderBy('id')->first();
                if ($firstItem) {
                    $firstItem->update([
                        'unit_amount' => $data['amount'],
                    ]);
                }
            }
        });

        return new InvoiceResource(
            $inv->fresh('customer', 'items')
        );
    }

    public function destroy($id)
    {
        $inv = Invoice::findOrFail($id);
        $inv->delete();
        return response()->json(['ok' => true]);
    }

    private function mapSubscriptionToRow(Subscription $s)
    {
        // garantir formato limpo da data
        $dueDate = $s->next_due_date
            ? Carbon::parse($s->next_due_date)->toDateString()
            : null;

        return [
            'id'            => 'sub_' . $s->id,
            'kind'          => 'subscription',
            'period'        => $s->period, // <- importante (monthly / yearly)

            'number'        => null,
            'due_date'      => $dueDate,
            'amount'        => (float)$s->amount,
            'installments'  => 1,

            // isso vai ser recalculado depois no map() geral,
            // então pode deixar placeholder
            'status'        => 'pending',

            'auto_reminder' => (bool)$s->auto_reminder,

            'customer'      => [
                'id'   => $s->customer_id,
                'name' => optional($s->customer)->name ?? '—',
            ],

            'items'         => [[
                'id'           => null,
                'description'  => $s->name ?? 'Mensalidade',
                'qty'          => 1,
                'unit_amount'  => (float)$s->amount,
                'type'         => 'subscription',
                'service_id'   => $s->service_id ?? null,
            ]],

            'budget'        => [
                'id'          => $s->budget_id,
                'budget_code' => optional($s->budget)->budget_code ?? null,
                'created_at'  => null,
                'status'      => null,
            ],
        ];
    }

    private function buildInvoiceLikeFromSubscription(Subscription $s): Invoice
    {
        // monta um "Invoice" em memória só para reaproveitar o InvoiceReminderMail
        $inv = new Invoice([
            'customer_sistapp_id' => $s->customer_sistapp_id,
            'customer_id'         => $s->customer_id,
            'budget_id'           => $s->budget_id,
            'number'              => sprintf('#%06d', $s->id),
            'due_date'            => $s->next_due_date,
            'amount'              => (float) $s->amount,
            'installments'        => 1,
            'status'              => 'pending',
            'auto_reminder'       => (bool) $s->auto_reminder,
        ]);

        // não está salvo em banco
        $inv->exists = false;

        // relações básicas
        $inv->setRelation('customer', $s->customer);
        $inv->setRelation('budget', $s->budget);

        $item = new Fluent([
            'description' => $s->name ?? 'Assinatura recorrente',
            'qty'         => 1,
            'unit_amount' => (float) $s->amount,
            'type'        => 'subscription',
        ]);

        $inv->setRelation('items', collect([$item]));

        return $inv;
    }

    public function sendReminder($id, Request $r)
    {
        // subscription: id vem como "sub_1"
        if (Str::startsWith($id, 'sub_')) {
            $subId = (int) Str::after($id, 'sub_');
            $sub   = Subscription::with('customer', 'budget')->findOrFail($subId);

            if (!$sub->customer) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'Cliente não vinculado à assinatura.',
                ], 422);
            }

            $email = $sub->customer->company_email
                ?? $sub->customer->email
                ?? null;

            if (!$email) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'Cliente sem e-mail cadastrado.',
                ], 422);
            }

            $config = ReminderInvoiceConfig::where('customer_sistapp_id', $sub->customer_sistapp_id)
                ->where('trigger', 'manual')
                ->where('is_active', true)
                ->first();

            $fakeInvoice = $this->buildInvoiceLikeFromSubscription($sub);

            Mail::to($email)->send(
                new InvoiceReminderMail($fakeInvoice, $config, 'manual')
            );

            // se quiser, pode depois adicionar campos sent_count/last_sent_at na tabela subscriptions
            return response()->json([
                'ok'      => true,
                'message' => 'Lembrete enviado.',
            ]);
        }

        // comportamento original para Invoice "normal"
        $inv = Invoice::with('customer')->findOrFail($id);

        if (!$inv->customer) {
            return response()->json([
                'ok'      => false,
                'message' => 'Cliente não vinculado à cobrança.',
            ], 422);
        }

        $email = $inv->customer->company_email
            ?? null;

        if (!$email) {
            return response()->json([
                'ok'      => false,
                'message' => 'Cliente sem e-mail cadastrado.',
            ], 422);
        }

        $config = ReminderInvoiceConfig::where('customer_sistapp_id', $inv->customer_sistapp_id)
            ->where('trigger', 'manual')
            ->where('is_active', true)
            ->first();

        Mail::to($email)->send(
            new InvoiceReminderMail($inv, $config, 'manual')
        );

        $inv->forceFill([
            'sent_count'   => $inv->sent_count + 1,
            'last_sent_at' => now(),
        ])->save();

        return response()->json([
            'ok'      => true,
            'message' => 'Lembrete enviado.',
        ]);
    }

    public function previewReminder($id, Request $r)
    {
        // se vier "sub_1" → subscription
        if (Str::startsWith($id, 'sub_')) {
            $subId = (int) Str::after($id, 'sub_');
            $sub   = Subscription::with('customer', 'budget')->findOrFail($subId);

            if (!$sub->customer) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'Cliente não vinculado à assinatura.',
                ], 422);
            }

            $email = $sub->customer->company_email
                ?? $sub->customer->email
                ?? null;

            if (!$email) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'Cliente sem e-mail cadastrado.',
                ], 422);
            }

            $config = ReminderInvoiceConfig::where('customer_sistapp_id', $sub->customer_sistapp_id)
                ->where('trigger', 'manual')
                ->where('is_active', true)
                ->first();

            $fakeInvoice = $this->buildInvoiceLikeFromSubscription($sub);

            $mail = new InvoiceReminderMail($fakeInvoice, $config, 'manual');
            [$subject, $body] = $mail->compose();

            return response()->json([
                'ok'      => true,
                'email'   => $email,
                'subject' => $subject,
                'body'    => $body,
            ]);
        }

        // comportamento original para Invoice "normal"
        $inv = Invoice::with('customer')->findOrFail($id);

        if (!$inv->customer) {
            return response()->json([
                'ok'      => false,
                'message' => 'Cliente não vinculado à cobrança.',
            ], 422);
        }

        $email = $inv->customer->company_email
            ?? $inv->customer->email
            ?? null;

        if (!$email) {
            return response()->json([
                'ok'      => false,
                'message' => 'Cliente sem e-mail cadastrado.',
            ], 422);
        }

        $config = ReminderInvoiceConfig::where('customer_sistapp_id', $inv->customer_sistapp_id)
            ->where('trigger', 'manual')
            ->where('is_active', true)
            ->first();

        $mail = new InvoiceReminderMail($inv, $config, 'manual');
        [$subject, $body] = $mail->compose();

        return response()->json([
            'ok'      => true,
            'email'   => $email,
            'subject' => $subject,
            'body'    => $body,
        ]);
    }
}
