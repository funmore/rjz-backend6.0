<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    //
    protected $table = 'program';

        protected $fillable = [ 
                                'state',
                                'ref',
                                'program_source',
                                'type',
                                'overdue_reason', 
                                'plan_start_time', 
                                'plan_end_time',
                                'actual_start_time',
                                'actual_end_time',
                                'contract_id',
                                'workflow_id',
                                'name',
                                'program_identity',
                                'model_id',
                                'program_type',
                                'classification',
                                'program_stage',
                                'dev_type',
                                'creator_id',
                                'manager_id',
                                'note'];

    public function SoftwareInfo(){
    	return $this->hasMany('App\Models\SoftwareInfo');
    }
    public function ProgramTeamRole(){
    	return $this->hasMany('App\Models\ProgramTeamRole');
    }
    public function Pvstate(){
        return $this->hasMany('App\Models\Pvstate','program_id','id');
    }
    public function Pvlog(){
        return $this->hasMany('App\Models\Pvlog','program_id','id');
    }
    public function PostProgram(){
        return $this->hasOne('App\Models\PostProgram','program_id','id');
    }
    public function Workflow(){
        return $this->belongsTo('App\Models\Workflow','workflow_id','id');
    }
    public function FlightModel(){
        return $this->belongsTo('App\Models\FlightModel','model_id','id');
    }
    public function Contract(){
        return $this->belongsTo('App\Models\Contract','contract_id','id');
    }
    public function Contact()
    {
        return $this->hasMany('App\Models\Contact','program_id','id');
    }
    public function Creator()
    {
        return $this->belongsTo('App\Models\Employee','creator_id','id');
    }
    public function FileProgram()
    {
        return $this->hasMany('App\Models\FileProgram','program_id','id');
    }
    public static function boot() {
        parent::boot();

        static::deleting(function($item) { // before delete() method call this
             $item->Contact()->delete();
             $item->SoftwareInfo()->delete();
             $item->Workflow()->delete();
             $item->ProgramTeamRole()->delete();

             $item->Pvstate()->delete();
             $item->Pvlog()->delete();

             $item->PostProgram()->delete();
        });
    }

}
