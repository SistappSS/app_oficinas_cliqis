<?php

namespace App\Enums;

enum RequestInputTranslateEnum: string
{
    case NAME = 'nome';
    case CPFCNPJ = 'CPF/CNPJ';
    case MOBILE_PHONE = 'número';
    case POSTAL_CODE = 'CEP';
    case ADDRESS = 'Endereço';
    case ADDRESS_NUMBER = 'número da residência';
    case PROVINCE = 'Bairro';
    case CITY_NAME = 'Cidade';
    case STATE = 'Estado';

    public static function getTranslation(string $field): string
    {
        return match ($field) {
            'name' => self::NAME->value,
            'cpfCnpj' => self::CPFCNPJ->value,
            'mobilePhone' => self::MOBILE_PHONE->value,
            'postalCode' => self::POSTAL_CODE->value,
            'address' => self::ADDRESS->value,
            'addressNumber' => self::ADDRESS_NUMBER->value,
            'province' => self::PROVINCE->value,
            'cityName' => self::CITY_NAME->value,
            'state' => self::STATE->value,
            default => $field,
        };
    }
}
