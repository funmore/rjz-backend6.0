<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowNote extends Model
{
    //
    protected $table = 'workflow_note';

    protected $fillable = [ 'workflow_id',
	                        'employee_id',
	                        'from_node_id',
	                        'to_node_id',
	                        'note',
	                        'note_type'
	                        ];

    public function Workflow(){
    	return $this->belongsTo('App\Models\Workflow','workflow_id','id');
    }
    public function Employee(){
        return $this->belongsTo('App\Models\Employee','employee_id','id');
    }
}
