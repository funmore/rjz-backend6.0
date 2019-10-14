<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class NoTestWork extends Model
{
    protected $table = 'no_test_work';

    protected $fillable = [ 'employee_id', 
                            'date', 
                            'range_start',
                            'range_end',
                            'type',
                            'note',
                        	'assist_name',
                            'output'];


    public function Employee()
    {
        return $this->belongsTo('App\Models\Employee','employee_id','id');
    }

}
