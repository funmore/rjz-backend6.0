<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workflow extends Model
{
    //
    protected $table = 'workflow';

    protected $fillable = [ 'workflow_name',
                            'active',
                            'workflow_template_id'
                            ];

    public function Node(){
		return $this->hasMany('App\Models\Node');
	}

    public function WorkflowNote(){
        return $this->hasMany('App\Models\WorkflowNote');
    }

    public function WorkflowEditLog(){
		return $this->hasMany('App\Models\WorkflowEditLog');
	}

	public function Program(){
        return $this->hasOne('App\Models\Program');
    }
    public function Contract(){
        return $this->hasOne('App\Models\Contract');
    }

     public function WorkflowTemplate(){
    	return $this->belongsTo('App\Models\WorkflowTemplate','workflow_template_id','id');
    }
    public static function boot() {
        parent::boot();

        static::deleting(function($item) { // before delete() method call this
            $item->Node()->delete();
            $item->WorkflowNote()->delete();
        });
    }
}
