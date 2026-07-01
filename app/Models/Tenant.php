<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Tenant extends Model {
    use SoftDeletes;
    protected $fillable = ['company_name','slug','contact_name','contact_email','contact_phone','address','city','country','timezone','logo','status','trial_ends_at','settings'];
    protected $casts = ['settings'=>'array','trial_ends_at'=>'datetime'];

    /** Convenience accessor so views can use $tenant->name or $tenant->email */
    public function getNameAttribute(): string { return $this->company_name; }
    public function getEmailAttribute(): string { return $this->contact_email; }
    public function getPhoneAttribute(): ?string { return $this->contact_phone; }
    public function setNameAttribute(string $v): void { $this->attributes['company_name'] = $v; }

    public function users(){ return $this->hasMany(User::class); }
    public function subscriptions(){ return $this->hasMany(CustomerSubscription::class); }
    public function activeSubscription(){ return $this->hasOne(CustomerSubscription::class)->where('status','active')->latest(); }
    public function contracts(){ return $this->hasMany(Contract::class); }
    public function invoices(){ return $this->hasMany(Invoice::class); }
    public function supportTickets(){ return $this->hasMany(SupportTicket::class); }
}
