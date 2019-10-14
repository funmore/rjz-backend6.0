<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Node extends Model
{
    //
    protected $table = 'node';

    protected $fillable = [ 'workflow_id',
                            'workflow_template_id',
                            'type',
                            'array_index',
                            'name'
                            ];

    public function Workflow(){
    	return $this->belongsTo('App\Models\Workflow','workflow_id','id');
    }

    public function WorkflowTemplate(){
    	return $this->belongsTo('App\Models\WorkflowTemplate','workflow_template_id','id');
    }

    public function NodeNote(){
        return $this->hasMany('App\Models\NodeNote','node_id','id');
    }
    public function ProgramTeamRoleTask(){
        return $this->hasMany('App\Models\ProgramTeamRoleTask','before_node_id','id');
    }
    public static function boot() {
        parent::boot();

        static::deleting(function($item) { // before delete() method call this
            $item->NodeNote()->delete();
            $item->ProgramTeamRoleTask()->delete();
        });
    }
}
