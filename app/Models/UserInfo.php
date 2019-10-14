<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserInfo extends Model
{
    //
    protected $table = 'user_info';
    protected $fillable = [
        'username',
        'password',
        'imagepath',
        'employee_id'];

    public function Employee(){
    	return $this->belongsTo('App\Models\Employee','employee_id','id');
    }
}
