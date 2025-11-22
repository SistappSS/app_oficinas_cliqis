<?php

namespace App\Http\Controllers\APP\Entities\Customers\CustomerArea;

use App\Http\Controllers\Controller;
use App\Models\Entities\Customers\Customer;
use App\Models\Finances\Receivables\AccountReceivablePayment;
use App\Models\Sales\Budgets\Subscriptions\Subscription;
use App\Models\Sales\Invoices\Invoice;
use App\Traits\RoleCheckTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CustomerAreaController extends Controller
{
    use RoleCheckTrait;

    public function customerArea(Request $r, $id)
    {
        $customer = Customer::where('customer_sistapp_id', $this->customerSistappID())
            ->findOrFail($id);

        // Recorrências ativas
        $subs = Subscription::where('customer_sistapp_id', $this->customerSistappID())
            ->where('customer_id', $customer->id)
            ->where('active', true)
            ->orderBy('next_due_date')
            ->get();

        // Pagamentos de RECORRÊNCIA (tem subscription_id)
        $payments = AccountReceivablePayment::with('subscription')
            ->where('customer_sistapp_id', $this->customerSistappID())
            ->whereNotNull('subscription_id')
            ->whereHas('subscription', function ($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            })
            ->orderByDesc('paid_at')
            ->limit(12)
            ->get();

        // Invoices únicas (não recorrentes)
        $invoices = Invoice::where('customer_sistapp_id', $this->customerSistappID())
            ->where('customer_id', $customer->id)
            ->orderByDesc('due_date')
            ->limit(12)
            ->get();

        // Pagamentos ligados às INVOICES (payments.invoice_id)
        $invoicePaidMap = AccountReceivablePayment::where('customer_sistapp_id', $this->customerSistappID())
            ->whereNotNull('invoice_id')
            ->whereIn('invoice_id', $invoices->pluck('id'))
            ->get()
            ->groupBy('invoice_id')
            ->map(function ($rows) {
                // pega o último paid_at dessa invoice
                return $rows->max('paid_at');
            });

        // Monta timeline unificada (pagamentos + invoices)
        $charges = collect();

        // PAYMENTS de recorrência
        foreach ($payments as $p) {
            $refDate = $p->paid_at ? Carbon::parse($p->paid_at) : null;

            $charges->push([
                'type'      => 'payment',
                'date'      => $refDate,
                'title'     => optional($p->subscription)->name ?? 'Recorrência',
                'amount'    => $p->amount,
                'discount'  => $p->discount,
                'interest'  => $p->interest,
                'fine'      => $p->fine,
                'status'    => 'Pago',
                'is_paid'   => true,
            ]);
        }

        // INVOICES (usa paid_at vindo de payments.invoice_id)
        foreach ($invoices as $inv) {

            $paidAt = $invoicePaidMap[$inv->id] ?? null;
            $isPaid = $paidAt !== null || $inv->status === 'paid';

            if ($isPaid && $paidAt) {
                // aqui entra o paid_at da tabela payments
                $refDate = Carbon::parse($paidAt);
            } else {
                $baseDate = $inv->due_date ?: $inv->created_at;
                $refDate  = $baseDate ? Carbon::parse($baseDate) : null;
            }

            // label pt-BR
            switch ($inv->status) {
                case 'paid':
                    $statusLabel = 'Pago';
                    break;
                case 'pending':
                    $statusLabel = 'Pendente';
                    break;
                case 'overdue':
                    $statusLabel = 'Vencido';
                    break;
                case 'canceled':
                    $statusLabel = 'Cancelado';
                    break;
                default:
                    $statusLabel = $inv->status;
            }

            $charges->push([
                'type'     => 'invoice',
                'date'     => $refDate,
                'title'    => $inv->number
                    ? "Fatura {$inv->number}"
                    : 'Cobrança única',
                'amount'   => $inv->amount,
                'discount' => 0,
                'interest' => 0,
                'fine'     => 0,
                'status'   => $statusLabel,
                'is_paid'  => $isPaid,
            ]);
        }

        // ordena pela data (paid_at ou due_date) desc e limita 12
        $charges = $charges
            ->sortByDesc(fn($c) => $c['date'] ?? Carbon::minValue())
            ->values()
            ->take(12);

        $selectedSub = $r->query('sub');

        return view('app.entities.customer.customer_area.customer_area', [
            'customer'      => $customer,
            'subscriptions' => $subs,
            'charges'       => $charges,
            'selectedSub'   => $selectedSub,
        ]);
    }

    public function updateSubscription(Request $r, $subscriptionId)
    {
        $subscription = Subscription::findOrFail($subscriptionId);

        if ($subscription->customer_sistapp_id !== $this->customerSistappID()) {
            abort(403);
        }

        $data = $r->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'period' => ['required', 'in:monthly,yearly'],
            'day_of_month' => ['required', 'integer', 'between:1,31'],
            'month_of_year' => ['nullable', 'integer', 'between:1,12'],
            'auto_reminder' => ['boolean'],
        ]);

        $subscription->amount = $data['amount'];
        $subscription->period = $data['period'];
        $subscription->day_of_month = $data['day_of_month'];
        $subscription->month_of_year = $data['period'] === 'yearly'
            ? ($data['month_of_year'] ?? $subscription->month_of_year)
            : null;
        $subscription->auto_reminder = $r->boolean('auto_reminder');

        // Ajusta o próximo vencimento mantendo ano/mês atuais, mas com o novo dia
        if ($subscription->next_due_date) {
            $next = Carbon::parse($subscription->next_due_date);
            $next->day = $data['day_of_month'];

            if ($data['period'] === 'yearly' && !empty($data['month_of_year'])) {
                $next->month = $data['month_of_year'];
            }

            $subscription->next_due_date = $next->toDateString();
        }

        $subscription->save();

        return response()->json(['ok' => true]);
    }

    public function cancelSubscription(Request $r, $subscriptionId)
    {
        $subscription = Subscription::findOrFail($subscriptionId);

        if ($subscription->customer_sistapp_id !== $this->customerSistappID()) {
            abort(403);
        }

        $subscription->update([
            'active' => false,
        ]);

        return response()->json(['ok' => true]);
    }
}
