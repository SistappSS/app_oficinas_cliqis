<?php

namespace App\Services\AsaasPhp\Contracts;

interface AsaasPaymentInterface
{
    public function handle(): array;
}
