<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePartOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $ufs = ["AC","AL","AM","AP","BA","CE","DF","ES","GO","MA","MG","MS","MT","PA","PB","PE","PI","PR","RJ","RN","RO","RR","RS","SC","SE","SP","TO"];

        return [
            'title'        => ['required','string','max:120'],
            'billing_cnpj' => ['required','string', function($attr,$value,$fail){
                $digits = preg_replace('/\D+/', '', (string)$value);
                if (strlen($digits) !== 14) $fail('CNPJ inválido.');
            }],
            'billing_uf'   => ['required','string', Rule::in($ufs)],
            'order_date'   => ['required','date_format:Y-m-d'],

            'status'       => ['required','string', Rule::in(['draft','pending'])],
            'icms_rate'    => ['nullable','numeric','min:0','max:100'],

            'supplier_id'  => ['nullable','uuid'], // tenant check no controller/service
            'items'        => ['required','array','min:1'],

            'items.*.id'            => ['nullable','uuid'],
            'items.*.part_id'       => ['nullable','uuid'], // tenant check no controller/service
            'items.*.code'          => ['nullable','string','max:40'],
            'items.*.description'   => ['nullable','string','max:255'],
            'items.*.ncm'           => ['nullable','string','max:20'],

            'items.*.unit_price'    => ['required','numeric','min:0'],
            'items.*.ipi_rate'      => ['nullable','numeric','min:0','max:100'],
            'items.*.quantity'      => ['required','numeric','min:1'],
            'items.*.discount_rate' => ['nullable','numeric','min:0','max:100'],
            'items.*.position'      => ['nullable','integer','min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // normaliza cnpj/uf aqui se quiser
        if ($this->has('billing_uf')) {
            $this->merge(['billing_uf' => strtoupper((string)$this->billing_uf)]);
        }
    }
}
