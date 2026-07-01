<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Announcement extends Model {
    use SoftDeletes;
    protected $fillable = ['title','content','type','visibility','target_tenant_ids','target_plan_slugs','is_published','published_at','expires_at','created_by'];
    protected $casts = ['target_tenant_ids'=>'array','target_plan_slugs'=>'array','is_published'=>'boolean','published_at'=>'datetime','expires_at'=>'datetime'];
    public function isVisible(): bool { return $this->is_published && ($this->expires_at === null || $this->expires_at > now()); }
}
