<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DelayApply extends Model
{
    protected $table = 'delay_apply';

    protected $fillable = [ 'ptr_note_id',
                            'delay_day',
                            'delay_reason',
                            'is_approved'
                        ];

    public function ProgramTeamRoleTask(){
        return $this->belongsTo('App\Models\ProgramTeamRoleTask','ptr_note_id','id');
    }

}
