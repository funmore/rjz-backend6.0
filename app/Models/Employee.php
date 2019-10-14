<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employee';
    protected $fillable = [ 
                                 'name',
                                 'age',
                                 'sex',
                                 'mobilephone',
                                 'team_id',
                                 'is_director',
                                 'is_v_director',
                                 'is_chiefdesigner',
                                 'is_v_chiefdesigner',
                                 'is_teamleader',
                                 'is_p_leader',
                                 'is_p_principal',
                                 'is_qa',
                                 'is_cm',
                                 'is_bd',
                                 'is_tester',
                                 'is_admin'];

    public function UserInfo(){
    	return $this->hasOne('App\Models\UserInfo','employee_id','id');
    }
    public function Token(){
    	return $this->hasOne('App\Models\Token','employee_id','id');
    }
    public function CreatedProgram(){
    	return $this->hasMany('App\Models\Program','creator_id','id');
    }
    public function Pvstate(){
        return $this->hasMany('App\Models\Pvstate','employee_id','id');
    }
    public function Pvlog(){
        return $this->hasMany('App\Models\Pvlog','changer_id','id');
    }
    public function PgFrom(){
        return $this->hasMany('App\Models\PgFrom','from_id','id');
    }
    public function PgTo(){
        return $this->hasMany('App\Models\PgTo','to_id','id');
    }
    public function WorkflowNote(){
        return $this->hasMany('App\Models\WorkflowNote');
    }
    public function NodeNote(){
        return $this->hasMany('App\Models\NodeNote','employee_id','id');
    }
    public function FileProgram(){
        return $this->hasMany('App\Models\FileProgram','employee_id','id');
    }
    public function FileReview(){
        return $this->hasMany('App\Models\FileReview','employee_id','id');
    }
    public function NoTestWork(){
        return $this->hasMany('App\Models\NoTestWork','employee_id','id');
    }
}
