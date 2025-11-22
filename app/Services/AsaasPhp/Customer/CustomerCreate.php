<?php

namespace App\Services\AsaasPhp\Customer;

use App\Services\AsaasPhp\Concerns\AsaasClient;
use App\Services\AsaasPhp\Contracts\AsaasPaymentInterface;
use Illuminate\Support\Facades\Http;

class CustomerCreate implements AsaasPaymentInterface
{
    use AsaasClient;

    public function handle(): array
    {
//    dd($this->data);
        try {
            return Http::withOptions(['verify' => false]) // REMOVER EM PRODUÇÃO
                ->withHeaders(['access_token' => $this->token])
                ->post("{$this->url}/customer", $this->data)
                ->throw()
                ->json();

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
