<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class FlightModel extends Model
{
    //
    protected $table = 'model';

    protected $fillable = [ 'model_name',
                            'employee_id'
                            ];


	public function Program(){
    	return $this->belongsTo('App\Models\Program','employee_id','id');
    }
    public function Employee(){
    	return $this->belongsTo('App\Models\Employee','employee_id','id');
    }
}
