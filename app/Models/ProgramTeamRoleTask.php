<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramTeamRoleTask extends Model
{
    //
    protected $table = 'programteamrole_task';

    protected $fillable = [ 'before_node_id',
                            'task',
                            'due_day',
                            'overdue_reason',
                            'state',
                            'note',
                            'ratio',
                            'score'
                        ];

    public function ProgramTeamRole(){
        return $this->belongsTo('App\Models\ProgramTeamRole','programteamrole_id','id');
    }
    public function Node(){
        return $this->belongsTo('App\Models\Node','before_node_id','id');
    }
    public function DailyNote(){
        return $this->hasMany('App\Models\DailyNote','ptr_note_id','id');
    }
    public function DelayApply(){
        return $this->hasMany('App\Models\DelayApply','ptr_note_id','id');
    }
    public static function boot() {
        parent::boot();

        static::deleting(function($item) { // before delete() method call this
             $item->DailyNote()->delete();
             $item->DelayApply()->delete();
        });
    }
}
