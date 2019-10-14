<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class FileProgram extends Model
{
    protected $table = 'file_program';
    protected $fillable = [ 
                                 'program_id',
                                 'employee_id',
                                 'version',
                                 'category',
                                 'name',
                                 'path',
                                 'review_state'];
    public function Program(){
    	return $this->belongsTo('App\Models\Program','program_id','id');
    }

    public function Employee(){
    	return $this->belongsTo('App\Models\Employee','employee_id','id');
    }
    public function FileReview(){
        return $this->hasMany('App\Models\FileReview','file_program_id','id');
    }
}
