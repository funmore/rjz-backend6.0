<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoftwareInfo extends Model
{
    //
    protected $table = 'software_info';
    protected $fillable = [ 'version_id', 
                            'name', 
                            'size',
                            'complier',
                            'runtime',
                            'reduced_code_size',
                            'reduced_reason',
                            'info_typer_id',
                            'software_cate',
                            'software_sub_cate',
                            'cpu_type',
                            'code_langu',
                            'software_usage',
                            'software_type'];
    public function Program(){
        return $this->belongsTo('App\Models\Program','program_id','id');
    }
}
