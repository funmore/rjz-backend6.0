<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Favor extends Model
{
    protected $table = 'favor';
    protected $fillable = [ 
                                 'employee_id',
                                 'type',
                                 'alias',
                                 'value',
                                 'system',
                                 'default'];
    protected $casts = [
        'value' => 'array',
    ];
    public function Employee(){
        return $this->belongsTo('App\Models\Employee','employee_id','id');
    }
}
