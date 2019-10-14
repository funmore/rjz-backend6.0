<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class PollColumn extends Model
{
    //
    protected $table = 'poll_column';

    protected $fillable = [ 'poll_id',
                            'index',
                            'name',
                            'valid_value',
                            'type'
                            ];

    public function Poll(){
    	return $this->belongsTo('App\Models\Poll','poll_id','id');
    }

    public function PollValue(){
    	return $this->hasMany('App\Models\PollValue','poll_column_id','id');
    }
}
