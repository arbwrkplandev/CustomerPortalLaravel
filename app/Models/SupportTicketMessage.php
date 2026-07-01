<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SupportTicketMessage extends Model {
    protected $fillable = ['ticket_id','sender_id','sender_type','message','attachments','is_internal','read_at'];
    protected $casts = ['attachments'=>'array','is_internal'=>'boolean','read_at'=>'datetime'];
    public function ticket(){ return $this->belongsTo(SupportTicket::class,'ticket_id'); }
    public function sender(){ return $this->belongsTo(User::class,'sender_id'); }
}
