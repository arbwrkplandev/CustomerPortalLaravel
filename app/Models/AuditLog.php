<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AuditLog extends Model {
    protected $fillable = ['user_id','tenant_id','action','module','entity_id','entity_type','old_values','new_values','ip_address','user_agent','description'];
    protected $casts = ['old_values'=>'array','new_values'=>'array'];
    const UPDATED_AT = null;
    public function user(){ return $this->belongsTo(User::class); }
    public function tenant(){ return $this->belongsTo(Tenant::class); }
}
