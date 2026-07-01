<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CustomerSubscription extends Model {
    protected $fillable = ['tenant_id','plan_id','billing_cycle','status','start_date','end_date','next_renewal_date','amount','currency','notes','created_by'];
    protected $casts = ['start_date'=>'date','end_date'=>'date','next_renewal_date'=>'date'];
    public function tenant(){ return $this->belongsTo(Tenant::class); }
    public function plan(){ return $this->belongsTo(Plan::class); }
    public function invoices(){ return $this->hasMany(Invoice::class,'subscription_id'); }
    public function isActive(): bool { return $this->status === 'active' && $this->end_date >= now()->toDateString(); }
}
