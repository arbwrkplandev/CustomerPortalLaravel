<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Payment extends Model {
    protected $fillable = ['tenant_id','invoice_id','subscription_id','payment_reference','amount','currency','payment_mode','status','payment_date','transaction_id','gateway','gateway_response','notes','recorded_by'];
    protected $casts = ['gateway_response'=>'array','payment_date'=>'date'];
    public function tenant(){ return $this->belongsTo(Tenant::class); }
    public function invoice(){ return $this->belongsTo(Invoice::class); }
}
