<?php

namespace App\Http\Requests\Sales\Budgets\BudgetConfig;

use Illuminate\Foundation\Http\FormRequest;

class StoreBudgetConfigRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // ORG
            'org'                 => ['required','array'],
            'org.name'            => ['required','string','max:120'],
            'org.document'        => ['nullable','string','max:32'],
            'org.email'           => ['nullable','email','max:120'],
            'org.phone'           => ['nullable','string','max:30'],
            'org.city'            => ['nullable','string','max:80'],
            'org.state'           => ['nullable','string','max:40'],
            'org.country'         => ['nullable','string','max:60'],

            // REPRESENTANTE
            'representative'          => ['nullable','array'],
            'representative.name'     => ['nullable','string','max:120'],
            'representative.document' => ['nullable','string','max:32'],
            'representative.email'    => ['nullable','email','max:120'],
            'representative.phone'    => ['nullable','string','max:30'],
            'representative.city'     => ['nullable','string','max:80'],
            'representative.state'    => ['nullable','string','max:40'],
            'representative.country'  => ['nullable','string','max:60'],

            // TEXTOS (HTML permitido)
            'texts'             => ['nullable','array'],
            'texts.services'    => ['nullable','string'],
            'texts.payment'     => ['nullable','string'],

            // LOGO
            'logo'              => ['nullable','array'],
            'logo.data'         => ['nullable','string'], // pode ser data-uri ou base64 cru
            'logo.mime'         => ['nullable','string','max:40'],
            'logo.max_height'   => ['nullable','integer','min:24','max:200'],

            // Upload opcional (convertemos para base64 no controller)
            'logo_file'         => ['nullable','image','max:1024'],
        ];
    }
}
