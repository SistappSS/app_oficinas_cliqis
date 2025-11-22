<?php

namespace App\Http\Controllers\APP\Sales\Budgets;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\Budgets\StoreBudgetRequest;
use App\Mail\BudgetPdfMail;
use App\Models\Sales\Budgets\Budget;
use App\Models\Sales\Budgets\BudgetInstallment;
use App\Models\Sales\Budgets\BudgetItem;
use App\Models\Sales\Budgets\BudgetMonthlyItem;
use App\Models\Sales\Budgets\BudgetYearlyItem;
use App\Models\Sales\Budgets\Subscriptions\Subscription;
use App\Models\Sales\Invoices\Invoice;
use App\Services\BudgetBuilderService;
use App\Services\BudgetPdfService;
use App\Services\BudgetToBillingService;
use App\Traits\RoleCheckTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class BudgetController extends Controller
{
    use RoleCheckTrait;

    public function view()
    {
        return view('app.sales.budgets.budget_index');
    }

    public function create()
    {
        return view('app.sales.budgets.budget_create');
    }

    public function index(Request $r)
    {
        $q = Budget::with(['customer', 'user'])
            ->latest('id')->take(200)->get();

        $data = $q->map(function ($b) {
            $installments = BudgetInstallment::where('budget_id', $b->id)->get();
            $parc = $installments->where('installment_number', '>', 0);
            $parcCount = $parc->count();
            $parcSum   = (float)$parc->sum('price');
            $parcEach  = $parcCount ? round($parcSum / $parcCount, 2) : 0.0;

            $sumMonthly = (float) BudgetMonthlyItem::where('budget_id', $b->id)->sum('price_with_discount');
            $sumYearly  = (float) BudgetYearlyItem::where('budget_id', $b->id)->sum('price');

            $hasMonthly = $sumMonthly > 0;
            $hasYearly  = $sumYearly  > 0;
            $escopo = ($hasMonthly || $hasYearly) ? 'recorrente' : 'unico';

            $totalContrato = (float)$b->signal_price + $parcSum;

            return [
                'id'        => $b->id,
                'code'      => $b->budget_code,
                'cliente'   => $b->customer?->name ?? '-',
                'criado_em' => optional($b->created_at)->format('d/m/Y') ?? '-',
                'status'    => $b->status, // open | approved | rejected
                'escopo'    => $escopo,

                // números para exibir
                'totais' => [
                    'contrato' => $totalContrato,
                    'sinal'    => (float)$b->signal_price,
                    'parcelas' => ['qtd' => $parcCount, 'soma' => $parcSum, 'cada' => $parcEach],
                    'recorr'   => ['mensal' => $sumMonthly, 'anual' => $sumYearly],
                ],
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function store(StoreBudgetRequest $request, BudgetBuilderService $builder)
    {
        $request->validated();
        $budget = $builder->create($request->all());

        return response()->json(['ok' => true, 'id' => $budget->id, 'code' => $budget->budget_code]);
    }

    public function approve($id, BudgetToBillingService $billing)
    {
        DB::transaction(function () use ($id, $billing) {
            $budget = Budget::lockForUpdate()->findOrFail($id);

            if ($budget->status !== 'approved') {
                $budget->update(['status' => 'approved', 'approved_at' => now()]);
            }

            $hasAnything =
                Invoice::where('budget_id', $budget->id)->exists() ||
                Subscription::where('budget_id', $budget->id)->exists();

            if (!$hasAnything) {
                $billing->convert($budget->fresh()); // sem transação interna
            }
        });

        return response()->json(['ok' => true]);
    }

    public function reject($id)
    {
        $budget = Budget::findOrFail($id);
        $budget->update(['status' => 'rejected', 'updated_at' => now()]);
        return response()->json(['ok' => true]);
    }

    public function sendEmail($id, Request $request, BudgetPdfService $pdfService)
    {
        $budget = Budget::with(['customer.masterLogin.user','items.service','installments'])
            ->findOrFail($id);

        $to = trim($request->input('to', ''));
        if (!$to) {
            $to = $budget->customer->company_email ?? $budget->customer->email ?? '';
        }
        abort_unless(filter_var($to, FILTER_VALIDATE_EMAIL), 422, 'Cliente sem e-mail válido.');

        $pdfBinary = $pdfService->make($budget);

        Mail::to($to)->send(new BudgetPdfMail($budget, $pdfBinary));

        return response()->json(['ok' => true]);
    }

    public function viewPdf($id, Request $request, BudgetPdfService $pdfService)
    {

        // carrega o orçamento + relações necessárias
        $budget = Budget::with(['customer.masterLogin.user','items.service','installments'])
            ->findOrFail($id);

        // gera o binário do PDF (mesmo PDF que você manda por e-mail)
        $pdfBinary = $pdfService->make($budget);

        $filename = 'orcamento-' . ($budget->code ?? $budget->id) . '.pdf';

        // retorna o PDF inline pra abrir no navegador
        return response($pdfBinary, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {

            // carrega o orçamento + itens relacionados
            $budget = Budget::with([
                'items',          // BudgetItem
                'monthlyItems',   // BudgetMonthlyItem
                'yearlyItems',    // BudgetYearlyItem
            ])->findOrFail($id);

            foreach ($budget->items as $item) {
                BudgetInstallment::where('budget_item_id', $item->id)->delete();
            }

            BudgetItem::where('budget_id', $budget->id)->delete();

            BudgetMonthlyItem::where('budget_id', $budget->id)->delete();

            BudgetYearlyItem::where('budget_id', $budget->id)->delete();

            Invoice::where('budget_id', $budget->id)->delete();

            Subscription::where('budget_id', $budget->id)->delete();

            // 6. Finalmente deletar o orçamento em si
            $budget->delete();
        });

        return response()->json(['ok' => true]);
    }

    public function json($id)
    {
        // carrega tudo que precisamos pra remontar o orçamento na tela
        $budget = Budget::with([
            'customer',
            'items',         // itens únicos (BudgetItem)
            'monthlyItems',  // itens mensais (BudgetMonthlyItem)
            'yearlyItems',   // itens anuais (BudgetYearlyItem)
            'installments',  // parcelas (BudgetInstallment via hasManyThrough)
        ])->findOrFail($id);

        // tenta deduzir nº de parcelas dos itens únicos:
        // se você já salva isso direto em alguma coluna tipo $budget->install, use ela.
        $installCount = max(
            1,
            $budget->installments->groupBy('budget_item_id')->map->count()->max() ?? 1
        );

        // monta resposta enxuta pro front
        $data = [
            'customer' => [
                'id'    => $budget->customer?->id,
                'name'  => $budget->customer?->name,
                'email' => $budget->customer?->company_email ?? $budget->customer?->email,
            ],

            // descontos e escopo
            'discount_percent' => $budget->discount_percent ?? 0,
            'discount_scope'   => $budget->discount_scope  ?? 'all', // 'all' | 'one'

            // cobrança recorrente antecipada ou não
            'rec_upfront' => (bool)($budget->rec_upfront ?? false),   // true/false
            'rec_mode'    => $budget->rec_mode ?? 'installment',      // 'signal' | 'installment'

            // condições de pagamento / cronograma base
            'cond' => [
                'paydate'  => optional($budget->payment_date)->format('Y-m-d'),
                'deadline' => $budget->deadline ?? 0,
                'signal'   => $budget->signal ?? 0,            // %
                'install'  => $installCount,                  // nº parcelas dos únicos
            ],

            // serviços selecionados
            'items_unique'   => $budget->items->map(fn($i) => [
                'service_id' => $i->service_id,
            ])->values(),

            'items_monthly'  => $budget->monthlyItems->map(fn($i) => [
                'service_id' => $i->service_id,
            ])->values(),

            'items_yearly'   => $budget->yearlyItems->map(fn($i) => [
                'service_id' => $i->service_id,
            ])->values(),
        ];

        return response()->json([
            'ok'   => true,
            'data' => $data,
        ]);
    }
}
