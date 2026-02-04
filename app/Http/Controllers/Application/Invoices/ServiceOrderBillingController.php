<?php

namespace App\Http\Controllers\Application\Invoices;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrders\ServiceOrder;
use App\Models\ServiceOrders\ServiceOrderInvoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

    public function generate(Request $request, ServiceOrder $serviceOrder)
    {
        $data = $request->validate([
            'first_due_date' => ['required', 'date'], // ou date_format:Y-m-d se quiser travar
            'payment_method' => ['required', 'string', 'max:50'],

            'use_down_payment' => ['nullable', 'in:0,1'],

            // quando usa sinal
            'down_payment_percent'   => ['required_if:use_down_payment,1', 'nullable', 'numeric', 'min:1', 'max:99'],
            'remaining_installments' => ['required_if:use_down_payment,1', 'nullable', 'integer', 'min:1', 'max:120'],

            // quando NÃO usa sinal
            'installments' => ['required_if:use_down_payment,0', 'nullable', 'integer', 'min:1', 'max:120'],
        ]);

        $useDown = ($data['use_down_payment'] ?? '0') === '1';

        $rawDate = trim((string) $data['first_due_date']);

        try {
            // aceita "2026-02-04"
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
                $firstDue = Carbon::createFromFormat('Y-m-d', $rawDate)->startOfDay();

                // aceita "04/02/2026"
            } elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $rawDate)) {
                $firstDue = Carbon::createFromFormat('d/m/Y', $rawDate)->startOfDay();

            } else {
                // tenta um parse genérico (último recurso)
                $firstDue = Carbon::parse($rawDate)->startOfDay();
            }
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Data de vencimento inválida.',
            ], 422);
        }

        return DB::transaction(function () use ($serviceOrder, $data, $firstDue) {

            if ($serviceOrder->status === 'nf_emitida') {
                return response()->json(['ok' => false, 'message' => 'NF já emitida para esta OS.'], 409);
            }

            $tenantId   = $serviceOrder->customer_sistapp_id;
            $customerId = $serviceOrder->secondary_customer_id ?? null;

            $total = round((float) ($serviceOrder->grand_total ?? 0), 2);
            if ($total <= 0) {
                return response()->json(['ok' => false, 'message' => 'Total da OS inválido.'], 422);
            }

            $firstDue = Carbon::parse($data['first_due_date'])->startOfDay();

            $makeNumber = function () {
                return 'NF' . now()->format('ymd') . '-' . strtoupper(Str::random(6));
            };

            $createdIds = [];

            $useDown = (string)($data['use_down_payment'] ?? '0') === '1';

            if ($useDown) {
                $pct = (int) $data['down_payment_percent'];
                $n   = (int) $data['remaining_installments'];

                $signal    = round($total * ($pct / 100), 2);
                $remaining = round($total - $signal, 2);

                // SINAL (vence no primeiro vencimento)
                $signalInv = ServiceOrderInvoice::create([
                    'customer_sistapp_id'  => $tenantId,
                    'service_order_id'     => $serviceOrder->id,
                    'customer_id'          => $customerId,
                    'number'               => $makeNumber(),
                    'due_date'             => $firstDue->toDateString(),
                    'amount'               => $signal,
                    'type'                 => 'signal',
                    'installment'          => 0,
                    'installments_total'   => $n,
                    'status'               => 'pending',
                    'payment_method'       => $data['payment_method'],
                ]);

                $createdIds[] = $signalInv->id;

                // PARCELAS do restante (mensal a partir do mês seguinte)
                $per = $n > 0 ? round($remaining / $n, 2) : 0;
                $acc = 0;

                for ($i = 1; $i <= $n; $i++) {
                    $amt = ($i === $n) ? round($remaining - $acc, 2) : $per;
                    $acc = round($acc + $amt, 2);

                    $inv = ServiceOrderInvoice::create([
                        'customer_sistapp_id' => $tenantId,
                        'service_order_id'    => $serviceOrder->id,
                        'customer_id'         => $customerId,
                        'number'              => $makeNumber(),
                        'due_date'            => $firstDue->copy()->addMonthsNoOverflow($i)->toDateString(),
                        'amount'              => $amt,
                        'type'                => 'parcel',
                        'installment'         => $i,
                        'installments_total'  => $n,
                        'status'              => 'pending',
                        'payment_method'      => $data['payment_method'],
                    ]);
                    $createdIds[] = $inv->id;
                }
            } else {
                $n = max(1, (int) $data['installments']);

                if ($n === 1) {
                    $inv = ServiceOrderInvoice::create([
                        'customer_sistapp_id' => $tenantId,
                        'service_order_id'    => $serviceOrder->id,
                        'customer_id'         => $customerId,
                        'number'              => $makeNumber(),
                        'due_date'            => $firstDue->toDateString(),
                        'amount'              => $total,
                        'type'                => 'single',
                        'installment'         => 1,
                        'installments_total'  => 1,
                        'status'              => 'pending',
                        'payment_method'      => $data['payment_method'],
                    ]);
                    $createdIds[] = $inv->id;
                } else {
                    $per = round($total / $n, 2);
                    $acc = 0;

                    for ($i = 1; $i <= $n; $i++) {
                        $amt = ($i === $n) ? round($total - $acc, 2) : $per;
                        $acc = round($acc + $amt, 2);

                        $inv = ServiceOrderInvoice::create([
                            'customer_sistapp_id' => $tenantId,
                            'service_order_id'    => $serviceOrder->id,
                            'customer_id'         => $customerId,
                            'number'              => $makeNumber(),
                            'due_date'            => $firstDue->copy()->addMonthsNoOverflow($i - 1)->toDateString(),
                            'amount'              => $amt,
                            'type'                => 'parcel',
                            'installment'         => $i,
                            'installments_total'  => $n,
                            'status'              => 'pending',
                            'payment_method'      => $data['payment_method'],
                        ]);
                        $createdIds[] = $inv->id;
                    }
                }
            }

            $serviceOrder->status = 'nf_emitida';
            $serviceOrder->save();

            return response()->json(['ok' => true, 'created' => $createdIds]);
        });
    }
}
