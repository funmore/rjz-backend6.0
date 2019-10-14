<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowTemplateEditLog extends Model
{
    //
    protected $table = 'workflow_template_edit_log';

    public function WorkflowTemplate(){
    	return $this->belongsTo('App\Models\WorkflowTemplate','workflow_template_id','id');
    }
}
