<?php

namespace App\Traits;

use App\Services\PaymentGateway\Connectors\AsaasConnector;
use App\Services\PaymentGateway\Gateway;

trait CreateCustomerAsaas
{
    private function createCustomerIfAdmin(array $data)
    {
        if ($this->userHasRole('admin')) {
            $adapter = new AsaasConnector;
            $gateway = new Gateway($adapter);

            $customer = $gateway->customer()->create($data);

            if (!isset($customer['id'])) {
                throw new \Exception('Erro ao criar cliente no Asaas.');
            }

            return $customer['id'];
        }

        return null;
    }

    private function createCustomer(array $data)
    {
        $adapter = new AsaasConnector;
        $gateway = new Gateway($adapter);

        $customer = $gateway->customer()->create($data);

        if (!isset($customer['id'])) {
            throw new \Exception('Erro ao criar cliente no Asaas.');
        }

        return $customer['id'];
    }
}
