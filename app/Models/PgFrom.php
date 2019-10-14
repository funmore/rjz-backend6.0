<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class PgFrom extends Model
{
    protected $table = 'pgfrom';

    protected $fillable = [ 'id',
                            'type',
                            'item_id',
                            'from_id',
                            'from_note'
                        ];


    public function Employee(){
        return $this->belongsTo('App\Models\Employee','from_id','id');
    }
    public function Item(){
        switch ($this->type) {
            case 'poll':
                return $this->belongsTo('App\Models\Poll','poll_id','id');
                break;
            default:
                return null;
                break;
        }
    }
}
