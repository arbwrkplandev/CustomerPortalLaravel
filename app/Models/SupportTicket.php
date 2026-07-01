<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class SupportTicket extends Model {
    use SoftDeletes;
    protected $fillable = ['tenant_id','created_by','assigned_to','ticket_number','subject','description','priority','status','category','resolved_at','first_response_at'];
    protected $casts = ['resolved_at'=>'datetime','first_response_at'=>'datetime'];
    public function tenant(){ return $this->belongsTo(Tenant::class); }
    public function creator(){ return $this->belongsTo(User::class,'created_by'); }
    public function assignee(){ return $this->belongsTo(User::class,'assigned_to'); }
    public function messages(){ return $this->hasMany(SupportTicketMessage::class,'ticket_id'); }
    public function isResolved(): bool { return in_array($this->status,['resolved','closed']); }
}
