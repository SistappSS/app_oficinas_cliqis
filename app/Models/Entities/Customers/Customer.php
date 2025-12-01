<?php

namespace App\Models\Entities\Customers;

use App\Models\Entities\Users\CustomerUserLogin;
use App\Models\Finances\Payables\AccountPayable;
use App\Models\Retails\Branch;
use App\Models\Retails\SaleRetail;
use App\Models\Sales\Contracts\Contract;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Customer extends Model
{
    use HasFactory;
    use Notifiable;
    use HasUuids;
    use HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';

//    public function customerLogin()
//    {
//        return $this->hasOne(CustomerUserLogin::class, 'user_id');
//    }

    public function logins() // todos os logins do mesmo sistapp (master e não-master)
    {
        return $this->hasMany(
            \App\Models\Entities\Users\CustomerUserLogin::class,
            'customer_sistapp_id',   // FK em customer_user_logins
            'customer_sistapp_id'    // chave local em customers
        );
    }

    public function masterLogin()
    {
        return $this->hasOne(
            \App\Models\Entities\Users\CustomerUserLogin::class,
            'customer_sistapp_id',
            'customer_sistapp_id'
        )->where('is_master_customer', 1);
    }

    public function customerSistappLogin()
    {
        return $this->hasOne(CustomerUserLogin::class,
            'customer_sistapp_id',
            'customer_sistapp_id'
        );
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function accountPlayable()
    {
        return $this->belongsTo(AccountPayable::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function sales()
    {
        return $this->hasMany(SaleRetail::class);
    }
}
