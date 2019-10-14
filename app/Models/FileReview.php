<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class FileReview extends Model
{
    protected $table = 'file_review';
    protected $fillable = [ 
                                 'file_program_id',
                                 'employee_id',
                                 'category',
                                 'phase',
                                 'version',
                                 'name',
                                 'path',
                             	 'state'];
    public function FileProgram(){
    	return $this->belongsTo('App\Models\FileProgram','file_program_id','id');
    }

    public function Employee(){
    	return $this->belongsTo('App\Models\Employee','employee_id','id');
    }
}
