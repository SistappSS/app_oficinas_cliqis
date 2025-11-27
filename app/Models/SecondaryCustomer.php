<?php

namespace App\Models;

use App\Models\ServiceOrders\ServiceOrder;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SecondaryCustomer extends Model
{
    use HasUuids;
    use HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected static function booted()
    {
        static::creating(function (SecondaryCustomer $customer) {
            if (empty($customer->signature_code)) {
                do {
                    $code = Str::random(32); // ou Str::ulid(), Str::uuid()
                } while (static::where('signature_code', $code)->exists());

                $customer->signature_code = $code;
            }
        });
    }

    public function serviceOrders()
    {
        return $this->hasMany(ServiceOrder::class, 'secondary_customer_id');
    }
}
