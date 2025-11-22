<?php

namespace App\Services\AsaasPhp\Customer;

use App\Services\AsaasPhp\Concerns\AsaasClient;
use App\Services\AsaasPhp\Contracts\AsaasPaymentInterface;
use Illuminate\Support\Facades\Http;

class CustomerList implements AsaasPaymentInterface
{
    use AsaasClient;

    public function handle(): array
    {
        try {
            return Http::withOptions(['verify' => false]) // REMOVER EM PRODUÇÃO
                ->withHeaders(['access_token' => $this->token])
                ->get("{$this->url}/customer")
                ->throw()
                ->json();

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
