<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Contract extends Model {
    use SoftDeletes;
    protected $fillable = ['tenant_id','contract_number','title','description','type','status','start_date','end_date','signed_at','sent_at','original_pdf_path','signed_pdf_path','signer_name','signer_email','signer_ip','html_content','created_by','assigned_by'];
    protected $casts = ['start_date'=>'date','end_date'=>'date','signed_at'=>'datetime','sent_at'=>'datetime'];
    public function tenant(){ return $this->belongsTo(Tenant::class); }
    public function signFields(){ return $this->hasMany(ContractSignField::class); }
    public function files(){ return $this->hasMany(ContractFile::class); }
    public function isSigned(): bool { return $this->status === 'signed'; }
}
