<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyNote extends Model
{
    protected $table = 'daily_note';

    protected $fillable = [ 'ptr_note_id',
                            'assist_name',
                            'plan_work',
                            'actual_work',
                            'output'
                        ];

    public function ProgramTeamRoleTask(){
        return $this->belongsTo('App\Models\ProgramTeamRoleTask','ptr_note_id','id');
    }
}
