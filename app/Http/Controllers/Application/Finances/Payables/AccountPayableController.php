<?php

namespace App\Http\Controllers\Application\Finances\Payables;

use App\Http\Controllers\Controller;
use App\Models\Finances\AccountPayable;
use App\Models\Finances\AccountPayablePayment;
use App\Models\Finances\AccountPayableRecurrence;
use App\Support\Audit\Payables\PayablesAudit;
use App\Traits\RoleCheckTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountPayableController extends Controller
{
    use RoleCheckTrait;

    public function view()
    {
        return view('app.finances.payables.payable_index');
    }

    public function index(Request $r)
    {
        $tenantId = $this->customerSistappID();

        $status  = $r->get('status', 'all');
        $term    = $r->get('q');

        $grouped = $r->boolean('grouped'); // ✅ NOVO

        $start = $r->get('start');
        $end   = $r->get('end');

        // ✅ default 12 meses só no DESAGRUPADO
        if (!$grouped && (!$start || !$end)) {
            $start = now()->startOfMonth()->toDateString();
            $end   = now()->copy()->addMonthsNoOverflow(12)->endOfMonth()->toDateString();
        }

        // ✅ KPI sempre tem período (se não vier, cai no default 12 meses)
        $kpiStart = $r->get('kpi_start') ?? $start;
        $kpiEnd   = $r->get('kpi_end')   ?? $end;

        if (!$kpiStart || !$kpiEnd) {
            $kpiStart = now()->startOfMonth()->toDateString();
            $kpiEnd   = now()->copy()->addMonthsNoOverflow(12)->endOfMonth()->toDateString();
        }

        $q = AccountPayableRecurrence::query()
            ->where('customer_sistapp_id', $tenantId)
            ->with(['accountPayable', 'payments'])
            ->when($status !== 'all', function ($qq) use ($status) {
                if ($status === 'overdue') {
                    $qq->where('status', 'pending')
                        ->where('due_date', '<', now()->toDateString());
                } else {
                    $qq->where('status', $status);
                }
            })
            ->when($term, function ($qq) use ($term) {
                $qq->whereHas('accountPayable', fn($aq) =>
                $aq->where('description', 'like', "%{$term}%")
                );
            })
            ->when($start && $end, fn($qq) => $qq->whereBetween('due_date', [$start, $end]))
            ->orderBy('due_date', 'asc')
            ->orderBy('created_at', 'asc');

        if ($grouped) {
            $items = $q->get();
        } else {
            $rows  = $q->paginate(100);
            $items = collect($rows->items());
        }

        $base = AccountPayableRecurrence::query()
            ->where('customer_sistapp_id', $tenantId)
            ->where('status', 'pending')
            ->when($kpiStart && $kpiEnd, fn($qq) => $qq->whereBetween('due_date', [$kpiStart, $kpiEnd]))
            ->with('payments');

        $pendente = $base->get()->reduce(function ($s, AccountPayableRecurrence $rec) {
            $paid = $rec->payments->sum('amount');
            return $s + max(0, (float)$rec->amount - $paid);
        }, 0.0);

        $pagosPeriodo = AccountPayablePayment::query()
            ->where('customer_sistapp_id', $tenantId)
            ->when($kpiStart && $kpiEnd, fn($qq) => $qq->whereBetween('paid_at', [$kpiStart, $kpiEnd]))
            ->sum('amount');

        $data = $items->map(function (AccountPayableRecurrence $rec) {
            $ap        = $rec->accountPayable;
            $paidTotal = (float)$rec->payments->sum('amount');
            $lastPay   = $rec->payments->sortByDesc('paid_at')->first();
            $lastPaidAt = $lastPay?->paid_at?->toDateString();

            $overdue = $rec->status === 'pending'
                && $rec->due_date->toDateString() < now()->toDateString();

            return [
                'id'           => $rec->id,
                'date'         => $rec->due_date->toDateString(),
                'price'        => (float)$rec->amount,
                'status'       => $rec->status,
                'amount_paid'  => $paidTotal,
                'paid_total'   => $paidTotal,
                'last_paid_at' => $lastPaidAt,
                'overdue'      => $overdue,
                'origin' => [
                    'payable_id'         => $ap->id,
                    'description'        => $ap->description,
                    'type'               => $ap->recurrence,
                    'recurrence'         => $rec->recurrence_number,
                    'total_recurrences'  => $ap->times,
                ],
            ];
        })->values();

        $groupMeta = null;

        if ($grouped) {
            $paidAgg = DB::table('account_payable_payments')
                ->select('payable_recurrence_id', DB::raw('SUM(amount) as paid_sum'))
                ->where('customer_sistapp_id', $tenantId)
                ->groupBy('payable_recurrence_id');

            $today = now()->toDateString();

            $g = DB::table('account_payable_recurrences as r')
                ->join('account_payables as ap', 'ap.id', '=', 'r.account_payable_id')
                ->leftJoinSub($paidAgg, 'pay', function ($j) {
                    $j->on('pay.payable_recurrence_id', '=', 'r.id');
                })
                ->where('r.customer_sistapp_id', $tenantId)
                ->when($status !== 'all', function ($qq) use ($status, $today) {
                    if ($status === 'overdue') {
                        $qq->where('r.status', 'pending')
                            ->where('r.due_date', '<', $today);
                    } else {
                        $qq->where('r.status', $status);
                    }
                })
                ->when($term, function ($qq) use ($term) {
                    $qq->where('ap.description', 'like', "%{$term}%");
                })
                ->groupBy('r.account_payable_id')
                ->selectRaw('r.account_payable_id as payable_id')
                ->selectRaw('MIN(r.due_date) as first_due')
                ->selectRaw("SUM(CASE WHEN r.status='pending' THEN GREATEST(0, r.amount - COALESCE(pay.paid_sum,0)) ELSE 0 END) as pending_total")
                ->selectRaw("MAX(CASE WHEN r.status='pending' THEN 1 ELSE 0 END) as has_pending")
                ->selectRaw("MAX(CASE WHEN r.status='pending' AND r.due_date < ? THEN 1 ELSE 0 END) as has_overdue", [$today])
                ->get();

            $groupMeta = $g->keyBy('payable_id')->map(function ($row) {
                return [
                    'pending_total' => (float) $row->pending_total,
                    'first_due'     => $row->first_due,
                    'has_pending'   => (int) $row->has_pending,
                    'has_overdue'   => (int) $row->has_overdue,
                ];
            });
        }

        return response()->json([
            'data' => $data,
            'group_meta' => $groupMeta,
            'kpis' => [
                'pending_sum' => round($pendente, 2),
                'paid_sum'    => round($pagosPeriodo, 2),
                'net_outflow' => round(0 - $pagosPeriodo, 2),
            ],
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'description'    => ['required', 'string', 'max:255'],
            'first_payment'  => ['required', 'date'],
            'recurrence'     => ['required', 'in:yearly,monthly,variable'],
            'default_amount' => ['required', 'numeric', 'min:0.01'],
            'times'          => ['nullable', 'integer', 'min:1'],
            'end_recurrence' => ['nullable', 'date', 'after_or_equal:first_payment'],
        ]);

        $tenantId = auth()->user()->customerLogin->customer_sistapp_id
            ?? auth()->user()->customer_sistapp_id;
        $userId   = auth()->id();

        return DB::transaction(function () use ($data, $tenantId, $userId) {
            $ap = AccountPayable::create([
                'customer_sistapp_id' => $tenantId,
                'user_id'             => $userId,
                'description'         => $data['description'],
                'default_amount'      => $data['default_amount'],
                'first_payment'       => $data['first_payment'],
                'recurrence'          => $data['recurrence'],
                'end_recurrence'      => $data['end_recurrence'] ?? null,
                'times'               => $data['times'] ?? null,
                'status'              => 'open',
            ]);

            $first = Carbon::parse($data['first_payment']);

            $addRecurrence = function (Carbon $d, int $i, float $amount) use ($ap, $tenantId, $userId) {
                AccountPayableRecurrence::create([
                    'customer_sistapp_id' => $tenantId,
                    'user_id'             => $userId,
                    'account_payable_id'  => $ap->id,
                    'recurrence_number'   => $i + 1,
                    'due_date'            => $d->toDateString(),
                    'amount'              => $amount,
                    'status'              => 'pending',
                ]);
            };

            // variável = parcelado ou único
            if ($data['recurrence'] === 'variable') {
                $n = (int) ($data['times'] ?? 1);

                if ($n <= 1) {
                    $addRecurrence($first->copy(), 0, (float) $data['default_amount']);
                } else {
                    // divide em centavos, distribuindo resto
                    $totalCents = (int) round($data['default_amount'] * 100);
                    $base = intdiv($totalCents, $n);
                    $rem  = $totalCents % $n;

                    for ($i = 0; $i < $n; $i++) {
                        $amtCents = $base + ($i < $rem ? 1 : 0);
                        $amount   = $amtCents / 100;
                        $dueDate  = $first->copy()->addMonths($i);

                        $addRecurrence($dueDate, $i, $amount);
                    }
                }
            }
            // mensal
            elseif ($data['recurrence'] === 'monthly') {
                $end = !empty($data['end_recurrence'])
                    ? Carbon::parse($data['end_recurrence'])
                    : $first->copy(); // se não tiver fim, gera só a primeira (energia/internet/etc)

                $i = 0;
                for ($d = $first->copy(); $d->lte($end); $d->addMonth(), $i++) {
                    $addRecurrence($d, $i, (float) $data['default_amount']);
                }
            }
            // anual
            else {
                $end = !empty($data['end_recurrence'])
                    ? Carbon::parse($data['end_recurrence'])
                    : $first->copy();

                $i = 0;
                for ($d = $first->copy(); $d->lte($end); $d->addYear(), $i++) {
                    $addRecurrence($d, $i, (float) $data['default_amount']);
                }
            }

            $recs = AccountPayableRecurrence::query()
                ->where('customer_sistapp_id', $tenantId)
                ->where('account_payable_id', $ap->id)
                ->orderBy('due_date', 'asc')
                ->get(['id','due_date','amount','status','recurrence_number']);

            $after = [
                'payable' => [
                    'id' => (string) $ap->id,
                    'description' => $ap->description,
                    'recurrence' => $ap->recurrence,
                    'default_amount' => (float) $ap->default_amount,
                    'first_payment' => (string) $ap->first_payment,
                    'end_recurrence' => $ap->end_recurrence,
                    'times' => $ap->times,
                    'status' => $ap->status,
                ],
                'recurrences' => [
                    'count' => $recs->count(),
                    'first_due' => optional($recs->first())->due_date,
                    'last_due'  => optional($recs->last())->due_date,
                    'sample' => $recs->take(6)->map(fn($x) => [
                        'id' => (string) $x->id,
                        'n' => (int) $x->recurrence_number,
                        'due_date' => (string) $x->due_date,
                        'amount' => (float) $x->amount,
                        'status' => (string) $x->status,
                    ])->values(),
                ],
            ];

            PayablesAudit::log(
                $tenantId,
                (string) $userId,
                'payable',
                (string) $ap->id,
                'created',
                null,
                $after
            );

            return response()->json(['ok' => true, 'id' => $ap->id]);
        });
    }

    public function pay(Request $r, $recurrenceId)
    {
        $data = $r->validate([
            'paid_at' => ['required', 'date'],
            'amount'  => ['required', 'numeric', 'min:0.01'],
            'notes'   => ['nullable', 'string'],

            'adjustments' => ['nullable', 'array'],
            'adjustments.*.custom_field_id' => ['required', 'string'],
            'adjustments.*.value' => ['required', 'numeric', 'min:0.01'],

            'amount_base'     => ['nullable', 'numeric'],
            'amount_original' => ['nullable', 'numeric'],
            'amount_final'    => ['nullable', 'numeric'],
        ]);

        return DB::transaction(function () use ($recurrenceId, $data) {
            $rec = AccountPayableRecurrence::lockForUpdate()
                ->with(['accountPayable', 'payments'])
                ->findOrFail($recurrenceId);

            $base = (float) $rec->amount;

            $adjInput = collect($data['adjustments'] ?? [])
                ->filter(fn($a) => !empty($a['custom_field_id']) && (float)$a['value'] > 0)
                ->values();

            $delta = 0.0;
            $fieldsById = collect();

            if ($adjInput->isNotEmpty()) {
                $ids = $adjInput->pluck('custom_field_id')->unique()->values();

                $fieldsById = DB::table('payable_custom_fields')
                    ->where('customer_sistapp_id', $rec->customer_sistapp_id)
                    ->whereIn('id', $ids)
                    ->where('active', 1)
                    ->get()
                    ->keyBy('id');

                foreach ($ids as $id) {
                    if (!isset($fieldsById[$id])) {
                        return response()->json([
                            'message' => 'Campo adicional inválido/inativo: '.$id
                        ], 422);
                    }
                }

                foreach ($adjInput as $a) {
                    $field = $fieldsById[$a['custom_field_id']];
                    $type = (string) $field->type; // deduct|add
                    $val  = round(abs((float)$a['value']), 2);

                    $delta += ($type === 'deduct') ? -$val : +$val;
                }
            }

            $dueAfter = round($base + $delta, 2);

            if ($dueAfter < 0.01) {
                return response()->json([
                    'message' => 'Ajustes inválidos: o valor final não pode ser menor que 0,01.'
                ], 422);
            }

            $before = [
                'recurrence' => [
                    'id' => $rec->id,
                    'status' => $rec->status,
                    'amount' => (float) $rec->amount,
                    'amount_paid' => (float) $rec->amount_paid,
                    'paid_at' => optional($rec->paid_at)->toDateString(),
                ],
                'payments_cash_sum' => (float) $rec->payments->sum('amount'),
            ];

            $cash = round((float)$data['amount'], 2);

            if (abs($cash - $dueAfter) > 0.02) {
                return response()->json([
                    'message' => 'Valor pago não confere com os ajustes calculados.'
                ], 422);
            }

            $settled = round($cash - $delta, 2);

            if (abs($settled - $base) > 0.02) {
                return response()->json([
                    'message' => 'Falha de consistência: base/ajustes não conferem.'
                ], 422);
            }

            $rec->payments()->delete();

            $payment = AccountPayablePayment::create([
                'customer_sistapp_id'   => $rec->customer_sistapp_id,
                'user_id'               => auth()->id(),
                'payable_recurrence_id' => $rec->id,
                'paid_at'               => $data['paid_at'],
                'amount'                => $cash, // ✅ cash
                'notes'                 => $data['notes'] ?? null,
                'meta'                  => [
                    'amount_base'       => $base,
                    'amount_settled'    => $settled,   // ✅ quitado
                    'cash_amount'       => $cash,      // ✅ cash
                    'delta_total'       => $delta,
                    'amount_due'        => $dueAfter,  // = cash
                    'adjustments_count' => $adjInput->count(),
                ],
            ]);

            foreach ($adjInput as $a) {
                $field = $fieldsById[$a['custom_field_id']];
                $type = (string) $field->type; // deduct|add
                $val  = round(abs((float)$a['value']), 2);

                DB::table('account_payable_payment_adjustments')->insert([
                    'id'                  => (string) \Illuminate\Support\Str::uuid(),
                    'customer_sistapp_id'  => $rec->customer_sistapp_id,
                    'user_id'              => auth()->id(),
                    'payable_recurrence_id'=> $rec->id,
                    'payment_id'           => $payment->id,
                    'custom_field_id'      => $a['custom_field_id'],
                    'type_snapshot'        => $type,
                    'value'                => $val,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
            }

            $rec->amount_paid = $settled;

            if ($settled + 0.0001 >= (float)$rec->amount) {
                $rec->status  = 'paid';
                $rec->paid_at = $data['paid_at'];
            } else {
                $rec->status  = 'pending';
                $rec->paid_at = null;
            }

            $rec->save();

            $ap = $rec->accountPayable()->lockForUpdate()->first();

            $isInfiniteRecurring = in_array($ap->recurrence, ['monthly', 'yearly'], true)
                && is_null($ap->end_recurrence);

            if ($isInfiniteRecurring && $rec->status === 'paid') {
                $hasFuture = $ap->recurrences()
                    ->where('due_date', '>', $rec->due_date)
                    ->exists();

                if (! $hasFuture) {
                    $lastNumber = (int)$ap->recurrences()->max('recurrence_number');
                    $nextNumber = $lastNumber + 1;

                    $nextDate = Carbon::parse($rec->due_date);
                    $ap->recurrence === 'monthly' ? $nextDate->addMonth() : $nextDate->addYear();

                    AccountPayableRecurrence::create([
                        'customer_sistapp_id' => $ap->customer_sistapp_id,
                        'user_id'             => auth()->id(),
                        'account_payable_id'  => $ap->id,
                        'recurrence_number'   => $nextNumber,
                        'due_date'            => $nextDate->toDateString(),
                        'amount'              => (float)$ap->default_amount,
                        'status'              => 'pending',
                        'amount_paid'         => 0,
                    ]);
                }
            }

            if (! $isInfiniteRecurring) {
                $hasOpen = $ap->recurrences()->where('status', 'pending')->exists();
                if (! $hasOpen) $ap->update(['status' => 'closed']);
            }

            $after = [
                'recurrence' => [
                    'id' => $rec->id,
                    'status' => $rec->status,
                    'amount' => (float) $rec->amount,
                    'amount_paid' => (float) $rec->amount_paid, // settled
                    'paid_at' => optional($rec->paid_at)->toDateString(),
                ],
                'payment' => [
                    'id' => $payment->id,
                    'cash' => (float) $payment->amount,
                    'meta' => $payment->meta,
                ],
                'adjustments' => $adjInput->map(fn($a) => [
                    'custom_field_id' => $a['custom_field_id'],
                    'value' => round((float)$a['value'], 2),
                ])->values(),
            ];

            PayablesAudit::log(
                $rec->customer_sistapp_id,
                (string) auth()->id(),
                'recurrence',
                (string) $rec->id,
                'paid',
                $before,
                $after
            );

            return response()->json(['ok' => true]);
        });
    }

    public function payments($recurrenceId)
    {
        $rec = AccountPayableRecurrence::with('payments')->findOrFail($recurrenceId);

        $payments = $rec->payments()
            ->orderByDesc('paid_at')
            ->get()
            ->map(function (AccountPayablePayment $p) {
                return [
                    'id'      => $p->id,
                    'paid_at' => $p->paid_at->toDateString(),
                    'amount'  => (float) $p->amount,
                    'notes'   => $p->notes,
                ];
            })->values();

        return response()->json($payments);
    }

    public function updateParcelAmount(Request $r, $recurrenceId)
    {
        $data = $r->validate(['amount' => ['required', 'numeric', 'min:0.01']]);
        $tenantId = $this->customerSistappID();

        return DB::transaction(function () use ($tenantId, $recurrenceId, $data) {
            $rec = AccountPayableRecurrence::lockForUpdate()
                ->where('customer_sistapp_id', $tenantId)
                ->with('payments')
                ->findOrFail($recurrenceId);

            $before = [
                'recurrence' => [
                    'id' => (string) $rec->id,
                    'status' => (string) $rec->status,
                    'amount' => (float) $rec->amount,
                    'amount_paid' => (float) $rec->amount_paid,
                    'due_date' => $rec->due_date->toDateString(),
                ],
                'payments_cash_sum' => (float) $rec->payments->sum('amount'),
            ];

            $rec->update(['amount' => round((float)$data['amount'], 2)]);

            $after = [
                'recurrence' => [
                    'id' => (string) $rec->id,
                    'status' => (string) $rec->status,
                    'amount' => (float) $rec->amount,
                    'amount_paid' => (float) $rec->amount_paid,
                    'due_date' => $rec->due_date->toDateString(),
                ],
            ];

            PayablesAudit::log(
                $tenantId,
                (string) auth()->id(),
                'recurrence',
                (string) $rec->id,
                'amount_changed',
                $before,
                $after
            );

            return response()->json(['ok' => true]);
        });
    }

    public function cancelParcel($recurrenceId)
    {
        $tenantId = $this->customerSistappID();

        return DB::transaction(function () use ($tenantId, $recurrenceId) {
            $rec = AccountPayableRecurrence::lockForUpdate()
                ->where('customer_sistapp_id', $tenantId)
                ->with('payments')
                ->findOrFail($recurrenceId);

            $paid = (float) $rec->payments->sum('amount');

            if ($rec->status === 'paid' || $paid > 0) {
                return response()->json([
                    'message' => 'Não é possível cancelar: parcela já possui pagamento. Use estorno (se aplicar).'
                ], 422);
            }

            $before = [
                'recurrence' => [
                    'id' => (string) $rec->id,
                    'status' => (string) $rec->status,
                    'amount' => (float) $rec->amount,
                    'amount_paid' => (float) $rec->amount_paid,
                    'due_date' => $rec->due_date->toDateString(),
                ],
                'payments_cash_sum' => $paid,
            ];

            $rec->update(['status' => 'canceled']);

            $after = [
                'recurrence' => [
                    'id' => (string) $rec->id,
                    'status' => (string) $rec->status,
                    'amount' => (float) $rec->amount,
                    'amount_paid' => (float) $rec->amount_paid,
                    'due_date' => $rec->due_date->toDateString(),
                ],
            ];

            PayablesAudit::log(
                $tenantId,
                (string) auth()->id(),
                'recurrence',
                (string) $rec->id,
                'canceled',
                $before,
                $after
            );

            return response()->json(['ok' => true]);
        });
    }
}
