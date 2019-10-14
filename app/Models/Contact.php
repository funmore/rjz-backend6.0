<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $table = 'contact';

    protected $fillable = [ 
                        'is_12s',
                        'type',
                        'organ',
                        'name',
                        'tele'];

    public function Program()
    {
        return $this->belongs('App\Models\Program','program_id','id');
    }
    
}
