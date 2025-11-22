<?php

namespace App\Models\Modules;

use App\Models\Entities\Customers\Customer;
use App\Models\Entities\Users\User;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuleTransaction extends Model
{
    use HasFactory;
    use HasCustomerScope;

    protected $guarded = [];

    protected $casts = [
        'module_ids'        => 'array',
        'selected_features' => 'array',
        'price_breakdown'   => 'array',
        'due_date'          => 'date',
        'payment_at'        => 'datetime',
    ];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments()
    {
        return $this->hasMany(ModuleTransactionPayment::class, 'transaction_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
