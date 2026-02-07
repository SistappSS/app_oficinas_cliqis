<?php

namespace App\Models\Stock;

use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StockPart extends Model
{
    use HasUuids, HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';

    public function balances()
    {
        return $this->hasMany(StockBalance::class, 'stock_part_id');
    }
}
