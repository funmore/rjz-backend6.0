<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowTemplate extends Model
{
    //
    protected $table = 'workflow_template';

    public function Node(){
    	return $this->hasMany('App\Models\Node');
    }

    public function Workflow(){
    	return $this->hasMany('App\Models\Workflow');
    }
    public function WorkflowEditLog(){
    	return $this->hasMany('App\Models\WorkflowEditLog')
    }
}
