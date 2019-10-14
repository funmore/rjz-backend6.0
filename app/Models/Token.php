<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    protected $table = 'token';

    public function Employee(){
    	return $this->belongsTo('App\Models\Employee','employee_id','id');
    }	
}
