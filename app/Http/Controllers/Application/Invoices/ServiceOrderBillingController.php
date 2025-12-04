<?php

namespace App\Http\Controllers\Application\Invoices;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DB;
use App\Models\ServiceOrders\ServiceOrder;
use App\Models\ServiceOrders\ServiceOrderInvoice;
use Illuminate\Http\Request;

class ServiceOrderBillingController extends Controller
{
    public function index()
    {
        // View da tela de cobranças das OS
        return view('app.service_orders.billing.service_order_billing_index');
    }

    /**
     * API para tela de cobranças.
     * Lista OS aprovadas + info de invoice (se existir).
     */
    public function list(Request $request)
    {
        $term = trim((string)$request->get('q', ''));

        $query = ServiceOrder::query()
            ->with(['secondaryCustomer', 'invoice']) // ajusta o nome da relação do cliente se for outro
            ->where('status', 'approved');

        if ($term !== '') {
            $like = "%{$term}%";

            $query->where(function ($q) use ($like) {
                $q->where('order_number', 'like', $like)
                    ->orWhere('ticket_number', 'like', $like)
                    ->orWhereHas('secondaryCustomer', function ($qq) use ($like) {
                        $qq->where('name', 'like', $like);
                    });
            });
        }

        $orders = $query
            ->orderByDesc('created_at')
            ->get()
            ->map(function (ServiceOrder $os) {
                return [
                    'id' => $os->id,
                    'order_number' => $os->order_number,
                    'order_date' => $os->order_date,   // deixa string/Carbon como está
                    'ticket_number' => $os->ticket_number,
                    'grand_total' => (float)$os->grand_total,
                    'status' => $os->status,
                    'secondary_customer' => $os->secondaryCustomer
                        ? [
                            'id' => $os->secondaryCustomer->id,
                            'name' => $os->secondaryCustomer->name,
                        ]
                        : null,
                    'invoice' => $os->invoice
                        ? [
                            'id' => $os->invoice->id,
                            'number' => $os->invoice->number,
                            'amount' => (float)$os->invoice->amount,
                            'payment_date' => $os->invoice->payment_date,
                            'payment_method' => $os->invoice->payment_method,
                            'installments' => (int)$os->invoice->installments,
                            'status' => $os->invoice->status,
                        ]
                        : null,
                ];
            });

        $kpiTotal = $orders->sum('grand_total');
        $kpiCount = $orders->count();

        return response()->json([
            'data' => $orders,
            'kpi_total' => $kpiTotal,
            'kpi_count' => $kpiCount,
        ]);
    }

    /**
     * Salva / atualiza invoice da OS a partir do modal "Gerar NF".
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'service_order_id' => ['required', 'uuid', 'exists:service_orders,id'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'max:50'],
            'installments' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        return DB::transaction(function () use ($data) {
            /** @var ServiceOrder $os */
            $os = ServiceOrder::where('status', 'approved')
                ->findOrFail($data['service_order_id']);

            // se já existir invoice dessa OS, atualiza; senão cria
            $invoice = ServiceOrderInvoice::updateOrCreate(
                ['service_order_id' => $os->id],
                [
                    'amount' => $data['amount'],
                    'payment_date' => $data['payment_date'],
                    'payment_method' => $data['payment_method'],
                    'installments' => $data['installments'],
                    'status' => 'open',
                ]
            );

            // aqui seria o ponto de "dar entrada em contas a receber"
            // por enquanto deixamos só o registro de invoice mesmo

            return response()->json([
                'ok' => true,
                'invoice' => $invoice,
            ]);
        });
    }
}
