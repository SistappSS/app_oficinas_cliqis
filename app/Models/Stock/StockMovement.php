<?php

namespace App\Models\Stock;

use App\Models\Entities\Users\User;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasUuids, HasCustomerScope;

    public $incrementing = false;
    protected $keyType = 'string';

    public function reason() { return $this->belongsTo(StockMovementReason::class, 'reason_id'); }
    public function items() { return $this->hasMany(StockMovementItem::class, 'movement_id'); }

    public function user()  { return $this->belongsTo(User::class, 'user_id'); }
}
