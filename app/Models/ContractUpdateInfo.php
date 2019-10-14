<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class ContractUpdateInfo extends Model
{
   protected $table = 'contract_update_info';

   public function Contract(){
        return $this->belongsTo('App\Models\Contract','contract_id','id');
    }
   public function Employee(){
        return $this->belongsTo('App\Models\Employee','info_typer_id','id');
    }
}
