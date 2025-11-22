<?php

namespace App\Models\Sales\Invoices;

use App\Models\Entities\Customers\Customer;
use App\Models\Sales\Budgets\Budget;
use App\Traits\HasCustomerScope;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasCustomerScope;

    protected $casts = [
        'due_date'      => 'date',
        'auto_reminder' => 'bool',
        'is_recurring'  => 'bool',
        'last_sent_at'  => 'datetime', // <-- ADICIONADO
    ];

    public function items(){ return $this->hasMany(InvoiceItem::class); }
    public function customer(){ return $this->belongsTo(Customer::class); }
    public function budget(){ return $this->belongsTo(Budget::class); }

    public function scopeSearch($q,$term){
        $t = "%$term%";
        return $q->where(function($qq) use($t){
            $qq->where('number','like',$t)
                ->orWhereHas('customer',fn($c)=>$c
                    ->where('name','like',$t)
                    ->orWhere('company_email','like',$t)
                );
        });
    }

    public function getComputedStatusAttribute(){
        if($this->status==='paid') return 'paid';
        return $this->due_date->isPast() ? 'overdue' : 'pending';
    }
}
