<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowEditLog extends Model
{
    //
    protected $table = 'workflow_edit_log';

    public function Workflow(){
    	return $this->belongsTo('App\Models\Workflow','workflow_id','id');
    }
}
