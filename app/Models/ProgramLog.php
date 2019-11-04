<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class ProgramLog extends Model
{
    protected $table = 'program_log';
    protected $fillable = [ 
                                 'program_id',
                                 'value'];
    protected $casts = [
        'value' => 'array',
    ];
    public function Program(){
        return $this->belongsTo('App\Models\Program','program_id','id');
    }

}
