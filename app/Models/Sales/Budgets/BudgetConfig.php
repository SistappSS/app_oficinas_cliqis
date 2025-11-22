<?php

namespace App\Models\Sales\Budgets;

use Illuminate\Database\Eloquent\Model;

class BudgetConfig extends Model
{
    protected $guarded = [];

    protected $casts = [
        'org'            => 'array',
        'representative' => 'array',
        'texts'          => 'array',
        'logo'           => 'array'
    ];


}
