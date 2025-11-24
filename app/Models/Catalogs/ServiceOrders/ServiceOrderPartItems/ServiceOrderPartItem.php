<?php

namespace App\Models\Catalogs\ServiceOrders\ServiceOrderPartItems;

use App\Models\Catalogs\Parts\Part;
use App\Models\Catalogs\ServiceOrders\ServiceOrder;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ServiceOrderPartItem extends Model
{
    use HasUuids;
    use HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }
}
