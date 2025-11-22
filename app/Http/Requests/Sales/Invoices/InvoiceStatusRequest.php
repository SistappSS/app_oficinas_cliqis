<?php

namespace App\Http\Requests\Sales\Invoices;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceStatusRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['status' => ['required','in:pending,paid,overdue,canceled']];
    }
}
