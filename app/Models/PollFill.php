<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class PollFill extends Model
{
    //
    protected $table = 'poll_fill';

    protected $fillable = [ 'poll_id',
                            'state',
                            'employee_id'
                            ];

    public function Poll(){
    	return $this->belongsTo('App\Models\Poll','poll_id','id');
    }
    public function Employee(){
    	return $this->belongsTo('App\Models\Employee','employee_id','id');
    }
    public function PollValue(){
    	return $this->hasMany('App\Models\PollValue','poll_fill_id','id');
    }

}
