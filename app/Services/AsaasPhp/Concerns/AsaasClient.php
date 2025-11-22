<?php

namespace App\Services\AsaasPhp\Concerns;

trait AsaasClient
{
    public function __construct(protected ?string $env = null, protected ?string $url = null, protected ?string $token = null, protected array $data = [])
    {
        $this->env = app()->isLocal() ? 'sandbox' : 'production';
        $this->token = config("asaas.{$this->env}.token");
        $this->url = config("asaas.{$this->env}.url");
    }
}
