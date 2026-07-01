<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ContractFile extends Model {
    protected $fillable = ['contract_id','tenant_id','file_type','file_path','file_name','mime_type','file_size','uploaded_by'];
    public function contract(){ return $this->belongsTo(Contract::class); }
    public function tenant(){ return $this->belongsTo(Tenant::class); }
}
