<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class PgTo extends Model
{
    protected $table = 'pvstate';

    protected $fillable = [ 'id',
                            'type',
                            'item_id',
                            'to_id',
                            'is_read'
                        ];

    public function Employee(){
        return $this->belongsTo('App\Models\Employee','to_id','id');
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
