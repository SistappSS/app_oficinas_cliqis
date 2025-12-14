<?php

namespace App\Http\Controllers\Application\Finances\Receivables;

use App\Http\Controllers\Controller;
use App\Models\Finances\AccountReceivablePayment;
use App\Models\ServiceOrders\ServiceOrderInvoice;
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
        $term      = $r->get('q');
        $tab       = $r->get('tab', 'all');           // all | paid | pending | overdue
        $startDate = $r->get('start_date');           // yyyy-mm-dd
        $endDate   = $r->get('end_date');             // yyyy-mm-dd

        $q = ServiceOrderInvoice::with('serviceOrder');

        if ($term) {
            $like = "%{$term}%";
            $q->where(function ($qq) use ($like) {
                $qq->where('number', 'like', $like)
                    ->orWhereHas('serviceOrder', function ($s) use ($like) {
                        $s->where('order_number', 'like', $like)
                            ->orWhere('client_name', 'like', $like);
                    });
            });
        }

        if ($startDate) {
            $q->whereDate('due_date', '>=', $startDate);
        }
        if ($endDate) {
            $q->whereDate('due_date', '<=', $endDate);
        }

        $rows = $q->get()->map(function (ServiceOrderInvoice $inv) {
            return [
                'id'                => $inv->id,
                'kind'              => 'invoice',
                'number'            => $inv->number,
                'date'              => $inv->due_date?->toDateString(),
                'price'             => (float) $inv->amount,
                'status_raw'        => $inv->status,
                'type'              => $inv->type, // signal | parcel | single
                'installment'       => $inv->installment,
                'installments_total'=> $inv->installments_total,
                'service_order'     => [
                    'id'           => $inv->serviceOrder?->id,
                    'order_number' => $inv->serviceOrder?->order_number,
                    'client_name'  => $inv->serviceOrder?->client_name,
                ],
            ];
        });

        $today = Carbon::now()->startOfDay();

        // normaliza status efetivo (pending/overdue/paid/canceled)
        $rows = $rows->map(function (array $row) use ($today) {
            $status = $row['status_raw'] ?? 'pending';

            if (in_array($status, ['paid', 'canceled'], true)) {
                // mantém
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
        });

        // filtro por aba
        $filtered = $rows->filter(function ($row) use ($tab) {
            $status = $row['status'] ?? 'pending';

            return match ($tab) {
                'paid'    => $status === 'paid',
                'pending' => $status === 'pending',
                'overdue' => $status === 'overdue',
                default   => true,
            };
        })->values();

        // ordenação
        $filtered = $filtered->sortBy('date')->values();

        // KPIs no período
        $valorPendente = $filtered
            ->where('status', 'pending')
            ->sum(fn ($x) => (float) $x['price']);

        $valorAtrasado = $filtered
            ->where('status', 'overdue')
            ->sum(fn ($x) => (float) $x['price']);

        // pagos vêm da tabela de pagamentos
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

    public function setPaid(Request $request, ServiceOrderInvoice $invoice)
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

        return DB::transaction(function () use ($invoice, $data) {
            if ($invoice->status === 'paid') {
                return response()->json(['ok' => true]);
            }

            AccountReceivablePayment::create([
                'customer_sistapp_id'       => $invoice->customer_sistapp_id,
                'service_order_invoice_id'  => $invoice->id,

                'paid_at'    => $data['paid_at'],
                'amount'     => $data['amount'],
                'interest'   => $data['interest']  ?? 0,
                'fine'       => $data['fine']      ?? 0,
                'discount'   => $data['discount']  ?? 0,
                'reference'  => $data['reference'] ?? null,
                'notes'      => $data['notes']     ?? null,
            ]);


            $invoice->status = 'paid';
            $invoice->save();

            return response()->json(['ok' => true]);
        });
    }
}
