<?php

namespace App\Http\Controllers\APP\Sales\Invoices\Reminder;

use App\Http\Controllers\Controller;
use App\Models\Sales\Invoices\ReminderInvoiceConfig;
use App\Traits\RoleCheckTrait;
use Illuminate\Http\Request;

class ReminderInvoiceConfigController extends Controller
{
    use RoleCheckTrait;

    public function index()
    {
        $customerId = $this->customerSistappID();

        $configs = ReminderInvoiceConfig::where('customer_sistapp_id', $customerId)
            ->get()
            ->keyBy('trigger');

        $triggersLabels = [
            'before_3_days' => '3 dias antes do vencimento',
            'on_due_date'   => 'No dia do vencimento',
            'after_1_day'   => '1 dia após o vencimento',
            'manual'        => 'Envio manual (botão)',
        ];

        return view('app.sales.invoice.reminder.reminder_config', compact('configs', 'triggersLabels'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'trigger'   => ['required', 'in:before_3_days,on_due_date,after_1_day,manual'],
            'name'      => ['required', 'string', 'max:191'],
            'subject'   => ['required', 'string', 'max:191'],
            'body'      => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        ReminderInvoiceConfig::updateOrCreate(
            [
                'customer_sistapp_id' => $this->customerSistappID(),
                'trigger'             => $data['trigger'],
            ],
            [
                'user_id'   => auth()->id(),
                'name'      => $data['name'],
                'subject'   => $data['subject'],
                'body'      => $data['body'],
                'is_active' => (bool) ($data['is_active'] ?? false),
            ]
        );

        return redirect()
            ->back()
            ->with('success', 'Configuração salva.');
    }
}
