<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;

class AuditPartOrderSetting extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id','customer_sistapp_id','actor_user_id','action','entity_type','entity_id',
        'success','meta','ip','user_agent'
    ];
    protected $casts = [
        'success' => 'boolean',
        'meta' => 'array',
    ];
}
