<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pvstate extends Model
{
    protected $table = 'pvstate';

    protected $fillable = [ 
    						'program_id',
                            'employee_id',    //登录人
                            'is_read'         //是否查看
                        ];

    public function Employee(){
        return $this->belongsTo('App\Models\Employee','employee_id','id');
    }
    public function Program(){
        return $this->belongsTo('App\Models\Program','program_id','id');
    }
}
