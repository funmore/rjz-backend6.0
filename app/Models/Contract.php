<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    //
    protected $table = 'contract';

    public function Program(){
        return $this->hasOne('App\Models\Program');
    }

    public function Workflow(){
        return $this->belongsTo('App\Models\Workflow','workflow_id','id');
    }
}
