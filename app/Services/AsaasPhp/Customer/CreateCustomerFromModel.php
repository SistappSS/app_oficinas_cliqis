<?php

namespace App\Services\AsaasPhp\Customer;

use App\Services\PaymentGateway\Connectors\AsaasConnector;
use App\Services\PaymentGateway\Gateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class CreateCustomerFromModel
{
    public function __construct(protected Model $customer) {

    }

    public function send(): string
    {
        $adapter = new AsaasConnector();
        $gateway = new Gateway($adapter);

        $data = [
            'name',
            'cpfCnpj',
            'mobilePhone',
            'email',
            'address',
            'addressNumber',
            'postalCode'
        ];

        $response = $gateway->customer()->create($data);

        if (!isset($response['id']) && is_string($response['error'])) {
            Log::error("Erro ao atualizar {$this->customer->name}: {$response['error']}");
            return '';
        }


        if (!isset($response['id']) && is_array($response['error'])) {
            $error = $response['error'][0]['description'] ?? 'Erro de integração';
            Log::error("Erro ao atualizar {$this->customer->name}: {$error}");
            return '';
        }

        $this->customer->customer_id = $response['id'];
        $this->customer->save();

        dd($response['id']);

        return $response['id'];
    }
}
