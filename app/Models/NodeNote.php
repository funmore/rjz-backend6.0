<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NodeNote extends Model
{
        //
    protected $table = 'node_note';

    protected $fillable = [ 'employee_id', 
                            'node_id', 
                            'note',
                            'state',
                            'is_up',
                            'done_day'];

    public function Node()
    {
        return $this->belongsTo('App\Models\Node','node_id','id');
    }
    public function Employee()
    {
        return $this->belongsTo('App\Models\Employee','employee_id','id');
    }

}
