<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ContractSignField extends Model {
    protected $fillable = ['contract_id','field_type','label','page_number','x_position','y_position','width','height','required','value'];
    protected $casts = ['required'=>'boolean'];
    public function contract(){ return $this->belongsTo(Contract::class); }
}
