<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AuthSessionMap extends Model {
    protected $table = 'auth_session_map';
    protected $fillable = ['user_id','session_token','provider','payload','ip_address','user_agent','expires_at'];
    protected $casts = ['payload'=>'array','expires_at'=>'datetime'];
    public function user(){ return $this->belongsTo(User::class); }
    public function isExpired(): bool { return $this->expires_at && $this->expires_at < now(); }
}
