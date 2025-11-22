<?php

namespace App\Services;

use App\Models\Sales\Budgets\Budget;
use App\Models\Sales\Budgets\BudgetConfig;
use App\Traits\RoleCheckTrait;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BudgetPdfService
{
    use RoleCheckTrait;

    /** Gera binário do PDF a partir do orçamento */
    public function make(Budget $budget): string
    {
        $data = $this->buildViewData($budget);

        // garante remote/data-uri habilitado
        $pdf = Pdf::loadView('layouts.templates.mail.budget_pdf', $data)
            ->setPaper('a4')
            ->setOptions(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);

        return $pdf->output();
    }

    /** Monta um data-uri correto a partir da base64 crua */
    private function toDataUri(?string $raw): ?string
    {
        if (!$raw) return null;
        $raw = trim($raw);
        if (Str::startsWith($raw, 'data:image')) return $raw;

        // heurística por prefixo
        $mime = 'image/png';
        if (Str::startsWith($raw, '/9j/')) $mime = 'image/jpeg';
        elseif (Str::startsWith($raw, 'iVBOR')) $mime = 'image/png';
        elseif (Str::startsWith($raw, 'R0lGOD')) $mime = 'image/gif';

        return "data:{$mime};base64,{$raw}";
    }

    /** Prepara os dados para a view do PDF */
    public function buildViewData(Budget $budget): array
    {
        // Relations básicas
        $budget->loadMissing(['customer.masterLogin.user', 'items.service', 'installments']);

        // ---------- Usuário/empresa (dinâmico) ----------
        $user               = Auth::user();
        $customerSistappId  = $this->customerSistappID();

        // Defaults para evitar "undefined index"
        $org             = [];
        $representative  = [];
        $services_txt    = null;
        $payment_txt     = null;
        $logo            = null;

        $cfg = BudgetConfig::where('customer_sistapp_id', $customerSistappId)->first();
        if ($cfg) {
            $orgDefaults = [
                'name'     => null,
                'document' => null,
                'email'    => null,
                'phone'    => null,
                'city'     => null,
                'state'    => null,
            ];
            $repDefaults = [
                'name'     => null,
                'document' => null,
                'email'    => null,
                'phone'    => null,
                'city'     => null,
                'state'    => null,
            ];

            $org            = array_merge($orgDefaults, (array)($cfg->org ?? []));
            $representative = array_merge($repDefaults, (array)($cfg->representative ?? []));
            $services_txt   = $cfg->texts['services'] ?? null;
            $payment_txt    = $cfg->texts['payment'] ?? null;
            $logo           = $this->toDataUri($cfg->logo['data'] ?? null);
        }

        // ---------- Cabeçalho ----------
        $budgetNumber = $budget->budget_code;
        $createDate   = optional($budget->created_at)->format('d/m/Y') ?? now()->format('d/m/Y');

        // ---------- Cliente / Representante ----------
        $cust = $budget->customer;

        $customerName = $cust->company_name ?? $cust->name ?? 'Cliente';
        $cnpj         = $cust->cpfCnpj ?? null;
        $state        = $cust->cityName ?? null;
        $uf           = $cust->state ?? null;
        $phone        = $cust->mobilePhone ?? null;

        // ---------- Itens (3 tabelas por budget_id) ----------
        // Únicos: pode haver item_price (único) ou price (fallback)
        $uniqueDB = ($budget->items ?? collect())->loadMissing('service');

        // Mensais/anuais: buscam por budget_id diretamente
        $monthlyDB = \App\Models\Sales\Budgets\BudgetMonthlyItem::where('budget_id', $budget->id)
            ->with('service')->get();
        $yearlyDB  = \App\Models\Sales\Budgets\BudgetYearlyItem::where('budget_id', $budget->id)
            ->with('service')->get();

        $mapUnique = collect($uniqueDB)->map(function ($it) {
            $srv  = $it->service;
            $base = (float)($it->item_price ?? $it->price ?? 0);
            $disc = (float)($it->discount_price ?? 0); // %
            $with = $disc > 0 ? round($base * (1 - $disc / 100), 2) : $base;

            return (object)[
                'service'             => $srv,
                'discount_price'      => $disc,   // %
                'item_price'          => $base,   // único
                'price'               => 0.0,
                'price_with_discount' => $with,
                'type'                => 'payment_unique',
            ];
        });

        $mapMonthly = collect($monthlyDB)->map(function ($it) {
            $srv  = $it->service;
            $base = (float)($it->price ?? 0);     // valor mensal
            $disc = (float)($it->discount_price ?? 0);
            $with = $disc > 0 ? round($base * (1 - $disc / 100), 2) : $base;

            return (object)[
                'service'             => $srv,
                'discount_price'      => $disc,
                'item_price'          => 0.0,
                'price'               => $base,   // mensal
                'price_with_discount' => $with,
                'type'                => 'monthly',
            ];
        });

        $mapYearly = collect($yearlyDB)->map(function ($it) {
            $srv  = $it->service;
            $base = (float)($it->price ?? 0);     // valor do ano
            $disc = (float)($it->discount_price ?? 0);
            $with = $disc > 0 ? round($base * (1 - $disc / 100), 2) : $base;

            return (object)[
                'service'             => $srv,
                'discount_price'      => $disc,
                'item_price'          => 0.0,
                'price'               => $base,   // anual
                'price_with_discount' => $with,
                'type'                => 'yearly',
            ];
        });

        // Coleção final para a view
        $items = collect()
            ->concat($mapUnique)
            ->concat($mapMonthly)
            ->concat($mapYearly);

        // ---------- Totais (corrigidos) ----------
        $sumUnique  = $items->where('type', 'payment_unique')->sum('price_with_discount');
        $sumMonthly = $items->where('type', 'monthly')->sum('price_with_discount');
        $sumYearly  = $items->where('type', 'yearly')->sum('price_with_discount');
        $grand      = $sumUnique + $sumMonthly + $sumYearly;

        // Subtotal base para sinal/restante (somente itens únicos)
        $subtotal    = (float)($budget->subtotal_price ?? $sumUnique);
        $signalPct   = (float)($budget->signal ?? 0);
        $signalPrice = round($subtotal * ($signalPct / 100), 2);
        $remainPct   = max(0, 100 - $signalPct);
        $remainPrice = max(0.0, round($subtotal - $signalPrice, 2));

        // ---------- Datas / cronograma ----------
        $payDate  = $budget->payment_date ? Carbon::parse($budget->payment_date) : now();
        $deadline = (int)($budget->deadline ?? 0);
        $firstDue = (clone $payDate)->addDays($deadline);

        // Captura parcelas gravadas
        $rawInstallments = collect($budget->installments ?? [])->map(function ($i) {
            $date = $i->payment_date ?? $i['payment_date'] ?? $i->date ?? $i['date'] ?? null;
            return [
                'date'   => $date ? Carbon::parse($date)->format('d/m/Y') : null,
                'label'  => $i->description ?? $i['description'] ?? 'Parcela',
                'amount' => (float)($i->price ?? $i['price'] ?? 0),
                'number' => $i->installment_number ?? $i['installment_number'] ?? null,
            ];
        });

        // Normaliza/rotula parcelas, detecta se alguma é o sinal
        $signalDate               = $payDate->format('d/m/Y');
        $epsilon                  = 0.01;
        $hasSignalInInstallments  = false;

        $installments = $rawInstallments->map(function ($row) use ($signalDate, $signalPrice, $epsilon, &$hasSignalInInstallments) {
            $isSignalRow = $signalPrice > 0
                && $row['date'] === $signalDate
                && abs($row['amount'] - $signalPrice) < $epsilon;

            if ($isSignalRow) {
                $hasSignalInInstallments = true;
                $row['label'] = 'Sinal';
            } else {
                if (stripos($row['label'], 'Parcela') === false) {
                    $row['label'] = 'Parcela' . ($row['number'] ? ' ' . $row['number'] : '');
                }
            }
            return $row;
        });

        // Se NÃO existir parcela que represente o sinal, adiciona a linha do sinal
        if (!$hasSignalInInstallments && $signalPct > 0 && $signalPrice > 0) {
            $installments->prepend([
                'date'   => $signalDate,
                'label'  => 'Sinal',
                'amount' => $signalPrice,
                'number' => null,
            ]);
        }

        // Se NÃO houver nenhuma parcela e existir restante, mostra "Restante na entrega"
        $hasAnyParcel = $installments->first(fn($r) => stripos($r['label'], 'Parcela') === 0);
        if (!$hasAnyParcel && $remainPrice > 0) {
            $installments->push([
                'date'   => $firstDue->format('d/m/Y'),
                'label'  => 'Restante na entrega',
                'amount' => $remainPrice,
                'number' => null,
            ]);
        }

        // Projeções recorrentes
        if ($sumMonthly > 0) {
            $installments->push([
                'date'   => (clone $firstDue)->addMonth()->format('d/m/Y'),
                'label'  => '1ª recorrência (mensal)',
                'amount' => $sumMonthly,
                'number' => null,
            ]);
        }
        if ($sumYearly > 0) {
            $installments->push([
                'date'   => (clone $firstDue)->addYear()->format('d/m/Y'),
                'label'  => '1ª recorrência (anual)',
                'amount' => $sumYearly,
                'number' => null,
            ]);
        }

        // Ordena por data
        $schedule = $installments->sortBy(function ($r) {
            return $r['date'] ? Carbon::createFromFormat('d/m/Y', $r['date']) : Carbon::create(1900, 1, 1);
        })->values();

        // ---------- Payload para a view ----------
        return [
            // Header
            'budget_number' => $budgetNumber,
            'create_date'   => $createDate,

            // Org (usuário autenticado)
            'org'            => $org ?: null,
            'representative' => $representative ?: null,
            'logo'           => $logo ?? null,
            'services_txt'   => $services_txt ?? null,
            'payment_txt'    => $payment_txt ?? null,

            // Cliente
            'customer' => $customerName,
            'cnpj'     => $cnpj,
            'state'    => $state,
            'uf'       => $uf,
            'phone'    => $phone,

            // Itens
            'items' => $items,

            // Totais
            'totals' => [
                'unique'  => $sumUnique,
                'monthly' => $sumMonthly,
                'yearly'  => $sumYearly,
                'grand'   => $grand,
            ],

            // Condições / pagamento
            'deadline'         => $deadline,
            'payment_date'     => $budget->payment_date ?? null,
            'percent_signal'   => $signalPct,
            'signal_price'     => $signalPrice,
            'percent_remaining'=> $remainPct,
            'remaining_price'  => $remainPrice,

            // Cronograma
            'schedule' => $schedule,
        ];
    }
}
