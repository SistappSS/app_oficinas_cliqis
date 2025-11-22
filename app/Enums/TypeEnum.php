<?php

namespace App\Enums;

enum TypeEnum: string
{
    case PAYMENT_UNIQUE = "payment_unique";
    case MONTHLY = "monthly";
    case YEARLY = "yearly";
}
