<?php

namespace App\Console\Commands;

use App\Models\Entities\Customers\Customer;
use App\Services\PaymentGateway\Connectors\AsaasConnector;
use App\Services\PaymentGateway\Gateway;
use Illuminate\Console\Command;

class NormalizeCustomerIdCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'normalize:customer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $customers = Customer::query()
            ->whereNull('customerId')
            ->orWhere('customerId', '')
            ->get();

        $adapter = new AsaasConnector;
        $gateway = new Gateway($adapter);

        $customers->each(function (Customer $customer) use ($gateway) {

            $data = [
                'name' => $customer->name,
                'cpfCnpj' => $customer->cpfCnpj,
                'mobilePhone' => $customer->mobilePhone,
                'email' => $customer->email,
                'address' => $customer->address,
                'addressNumber' => $customer->addressNumber,
                'postalCode' => $customer->postalCode,
            ];

            $response = $gateway->customer()->create($data);

            if (!isset($response['id']) && is_string($response['error'])) {
                $this->line("Erro ao atualizar {$customer->name}: {$response['error']}");
                return true;
            }

            if (!isset($response['id']) && is_array($response['error'])) {
                $error = $response['error'][0]['description'] ?? 'Erro na integração!';
                $this->line("Erro ao atualizar {$customer->name}: {$error}");
                return true;
            }

                $customer->customerId = $response['id'];
                $customer->save();
                return true;
        });

        return 0;
    }
}
