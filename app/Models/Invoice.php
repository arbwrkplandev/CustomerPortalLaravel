<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Invoice extends Model {
    protected $fillable = ['tenant_id','subscription_id','invoice_number','status','issue_date','due_date','paid_date','subtotal','tax_amount','discount_amount','total_amount','currency','line_items','notes','pdf_path','created_by'];
    protected $casts = ['line_items'=>'array','issue_date'=>'date','due_date'=>'date','paid_date'=>'date'];
    public function tenant(){ return $this->belongsTo(Tenant::class); }
    public function subscription(){ return $this->belongsTo(CustomerSubscription::class); }
    public function payments(){ return $this->hasMany(Payment::class); }
}
