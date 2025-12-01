<?php

namespace App\Models\Modules;

use App\Models\Entities\Users\User;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ModuleTransactionPayment extends Model
{
    use HasUuids;
    //use HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'paid_at'    => 'date',
        'expires_at' => 'date',
    ];

    // Relacionamento: cada pagamento pertence a uma transação
    public function transaction()
    {
        return $this->belongsTo(ModuleTransaction::class, 'transaction_id');
    }

    // Relacionamento com usuário
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
