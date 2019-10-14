<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class PostProgram extends Model
{
    //
    protected $table = 'post_program';

    protected $fillable = [ 
                            'program_id',
                            'test_round',
                            'problem_num',
                            'code_problem_num',
                            'class12_problem_num', 
                            'plan_type', 
                            'plan_complete_type',
                            'cmtool_info',
                        	'dec817', 
                            'is_cut'];

    public function Program(){
    	return $this->belongsTo('App\Models\Program','program_id','id');
	}
}
