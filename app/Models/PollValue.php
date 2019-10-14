<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class PollValue extends Model
{
    //
    protected $table = 'poll_value';

    protected $fillable = [ 'poll_column_id',
                            'poll_fill_id',
                            'value'
                            ];



    public function PollColumn(){
    	return $this->belongsTo('App\Models\PollColumn','poll_column_id','id');
    }
    public function PollFill(){
        return $this->belongsTo('App\Models\PollFill','poll_fill_id','id');
    }
}
