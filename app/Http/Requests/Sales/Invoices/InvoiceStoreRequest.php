<?php

namespace App\Http\Requests\Sales\Invoices;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customer_id'      => ['required','exists:customers,id'],
            'due_date'         => ['required','date'],
            'amount'           => ['required','numeric','min:0.01'],
            'installments'     => ['nullable','integer','min:1'],
            'is_recurring'     => ['boolean'],
            'recurring_period' => ['nullable','in:monthly,yearly'],
            'auto_reminder'    => ['boolean'],
            'items'            => ['array'],
            'items.*.service_id'  => ['nullable','exists:services,id'],
            'items.*.description' => ['required','string','max:255'],
            'items.*.qty'         => ['nullable','integer','min:1'],
            'items.*.unit_amount' => ['required','numeric','min:0'],
            'items.*.type'        => ['nullable','in:one_time,subscription'],
        ];
    }
}
