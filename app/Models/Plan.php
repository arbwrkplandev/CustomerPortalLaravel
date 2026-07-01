<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Plan extends Model {
    protected $fillable = ['name','slug','description','monthly_price','quarterly_price','annual_price','features','max_users','is_active','sort_order'];
    protected $casts = ['features'=>'array','is_active'=>'boolean'];
    public function subscriptions(){ return $this->hasMany(CustomerSubscription::class); }
    public function getPriceForCycle(string $cycle): float {
        return match($cycle){
            'monthly'   => (float)$this->monthly_price,
            'quarterly' => (float)$this->quarterly_price,
            'annual'    => (float)$this->annual_price,
            default     => (float)$this->monthly_price,
        };
    }
}
