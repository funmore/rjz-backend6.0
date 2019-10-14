<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pvlog extends Model
{
    protected $table = 'pvlog';

    protected $fillable = [ 'program_id',
                            'changer_id',
                            'change_note'
                        ];


    public function Employee(){
        return $this->belongsTo('App\Models\Employee','changer_id','id');
    }
    public function Program(){
        return $this->belongsTo('App\Models\Program','program_id','id');
    }

}
