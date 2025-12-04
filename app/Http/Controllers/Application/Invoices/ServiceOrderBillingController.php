<?php

namespace App\Http\Controllers\Application\Invoices;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrders\ServiceOrder;
use App\Models\ServiceOrders\ServiceOrderInvoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceOrderBillingController extends Controller
{
    public function index()
    {
        // View da tela de cobranças das OS
        return view('app.service_orders.billing.service_order_billing_index');
    }

    public function generateFromServiceOrder(Request $request, ServiceOrder $serviceOrder)
    {
        // valida
        $data = $request->validate([
            'first_due_date'        => ['required', 'date'],   // data de vencimento do sinal ou 1ª parcela
            'payment_method'        => ['nullable', 'string', 'max:50'],

            'use_down_payment'      => ['required', 'boolean'],

            // se tiver sinal
            'down_payment_percent'  => ['nullable', 'numeric', 'min:1', 'max:99'],
            'remaining_installments'=> ['nullable', 'integer', 'min:1', 'max:120'],

            // se não tiver sinal (tudo em X parcelas)
            'installments'          => ['nullable', 'integer', 'min:1', 'max:120'],
        ]);

        if (! $serviceOrder->isApproved()) { // ajusta para teu status real
            return response()->json([
                'ok'    => false,
                'error' => 'OS não está aprovada.',
            ], 422);
        }

        $total = (float) $serviceOrder->grand_total; // campo que você já usa no JS
        if ($total <= 0) {
            return response()->json([
                'ok'    => false,
                'error' => 'Total da OS inválido.',
            ], 422);
        }

        return DB::transaction(function () use ($data, $serviceOrder, $total) {
            $baseDate = Carbon::parse($data['first_due_date'])->startOfDay();
            $paymentMethod = $data['payment_method'] ?? null;

            $useDown = (bool) $data['use_down_payment'];

            $created = [];

            if ($useDown) {
                // 30% de sinal, restante em X parcelas
                $percent = (float) ($data['down_payment_percent'] ?? 0);
                $qtdRest = (int) ($data['remaining_installments'] ?? 0);

                if ($percent <= 0 || $qtdRest < 1) {
                    return response()->json([
                        'ok'    => false,
                        'error' => 'Percentual de sinal ou número de parcelas inválido.',
                    ], 422);
                }

                $signalAmount    = round($total * ($percent / 100), 2);
                $remainingAmount = max(0, $total - $signalAmount);

                if ($signalAmount <= 0 || $remainingAmount <= 0) {
                    return response()->json([
                        'ok'    => false,
                        'error' => 'Configuração de sinal/parcelas gera valores inválidos.',
                    ], 422);
                }

                // total de "documentos": 1 sinal + N parcelas
                $totalDocs = 1 + $qtdRest;

                // 1) cria invoice de SINAL
                $created[] = $this->createInvoiceForServiceOrder(
                    serviceOrder: $serviceOrder,
                    type: 'signal',
                    amount: $signalAmount,
                    dueDate: $baseDate->copy(),
                    installment: 1,                // podemos considerar sinal como 1/totalDocs
                    installmentsTotal: $totalDocs,
                    paymentMethod: $paymentMethod
                );

                // 2) cria invoices de PARCELAS do restante
                $parcelBase = $remainingAmount / $qtdRest;

                // para evitar problema de arredondamento, ajusta a última
                $parcelAmounts = [];
                for ($i = 1; $i <= $qtdRest; $i++) {
                    $parcelAmounts[$i] = round($parcelBase, 2);
                }
                $diff = $remainingAmount - array_sum($parcelAmounts);
                if (abs($diff) >= 0.01) {
                    // joga diferença na última parcela
                    $parcelAmounts[$qtdRest] = round($parcelAmounts[$qtdRest] + $diff, 2);
                }

                for ($i = 1; $i <= $qtdRest; $i++) {
                    $due = $baseDate->copy()->addMonths($i); // cada parcela mês a mês
                    $created[] = $this->createInvoiceForServiceOrder(
                        serviceOrder: $serviceOrder,
                        type: 'parcel',
                        amount: $parcelAmounts[$i],
                        dueDate: $due,
                        installment: 1 + $i,       // ex: sinal 1/4, parcelas 2/4,3/4,4/4
                        installmentsTotal: $totalDocs,
                        paymentMethod: $paymentMethod
                    );
                }

            } else {
                // Sem sinal: tudo em X parcelas iguais
                $qtd = (int) ($data['installments'] ?? 1);
                if ($qtd < 1) $qtd = 1;

                $parcelBase = $total / $qtd;
                $parcelAmounts = [];
                for ($i = 1; $i <= $qtd; $i++) {
                    $parcelAmounts[$i] = round($parcelBase, 2);
                }
                $diff = $total - array_sum($parcelAmounts);
                if (abs($diff) >= 0.01) {
                    $parcelAmounts[$qtd] = round($parcelAmounts[$qtd] + $diff, 2);
                }

                for ($i = 1; $i <= $qtd; $i++) {
                    $due = $baseDate->copy()->addMonths($i - 1);
                    $created[] = $this->createInvoiceForServiceOrder(
                        serviceOrder: $serviceOrder,
                        type: $qtd === 1 ? 'single' : 'parcel',
                        amount: $parcelAmounts[$i],
                        dueDate: $due,
                        installment: $i,
                        installmentsTotal: $qtd,
                        paymentMethod: $paymentMethod
                    );
                }
            }

            return response()->json([
                'ok'       => true,
                'invoices' => $created,
            ]);
        });
    }

    private function createInvoiceForServiceOrder(
        ServiceOrder $serviceOrder,
        string $type,
        float $amount,
        Carbon $dueDate,
        int $installment,
        int $installmentsTotal,
        ?string $paymentMethod = null
    ): ServiceOrderInvoice {
        // simples: pega próximo id pra gerar número com padding
        $seq    = (int) (ServiceOrderInvoice::lockForUpdate()->max('id') ?? 0) + 1;
        $number = sprintf('#%06d', $seq);

        return ServiceOrderInvoice::create([
            'service_order_id'   => $serviceOrder->id,
            'customer_id'        => $serviceOrder->customer_id ?? null,
            'number'             => $number,
            'due_date'           => $dueDate->toDateString(),
            'amount'             => $amount,
            'installment'        => $installment,
            'installments_total' => $installmentsTotal,
            'type'               => $type,
            'status'             => 'pending',
            'payment_method'     => $paymentMethod,
        ]);
    }
}
