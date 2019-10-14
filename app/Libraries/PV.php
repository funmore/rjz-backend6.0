<?php
namespace App\Libraries;
use App\Models\Program;
use App\Models\ProgramTeamRole;
use App\Models\Pvstate;
use App\Models\Pvlog;
use App\Models\Employee;
class PV {

  public function __construct() {

  }

  public function isPVStateExist($program){
    $pvstates= Pvstate::where('program_id',$program->id)->get();
    if(sizeof($pvstates)!=0){
        return true;
    }else{
        return false;
    }
  }
  //创建新项目使用
  public function storePvState($program,$employee){
        $programTeamRoles=$program->ProgramTeamRole;
        if(sizeof($programTeamRoles)==0) return false;

        $noDuplicates = array();
        foreach ($programTeamRoles as $v) {
            if (isset($noDuplicates[$v['employee_id']])) {
                continue;
            }
            $noDuplicates[$v['employee_id']] = $v;
        }
        $ProgramTeamRoleNoDuplicates = array_values($noDuplicates);
        foreach ($ProgramTeamRoleNoDuplicates as $member) {
            $pvstate = new Pvstate(array(
                'employee_id' => $member['employee_id'],
                'is_read' => '0'
            ));
            if ($member['employee_id'] == $employee->id) {
                $pvstate->is_read = '1';
            }
            $program->Pvstate()->save($pvstate);
        }


        $pvlog = new Pvlog(array('changer_id' => $employee->id,
            'change_note' => '创建了新项目',
        ));
        $program->Pvlog()->save($pvlog);
        return true;
  }

  //获取针对指定用户的所有与其相关项目的其他项目组员的任务动态
  public function getPvlog($employee) {
    $programTeamRoles=ProgramTeamRole::where('employee_id',$employee->id)->get();
    $noDuplicates = array();
    foreach ($programTeamRoles as $v) {
        if (isset($noDuplicates[$v['program_id']])) {
            continue;
        }
        $noDuplicates[$v['program_id']] = $v;
    }
    $ProgramTeamRoleNoDuplicates = array_values($noDuplicates);
    $noticeArray=array();
    foreach($ProgramTeamRoleNoDuplicates as $member){
        $program = $member->Program;
        $pvstate =Pvstate::where('program_id',$program->id)->where('employee_id',$employee->id)->first();
        if($pvstate==null) continue;
        $is_read= $pvstate->is_read;
        if($is_read==1) continue;
        $pvlogs  = Pvlog::where('program_id',$program->id)
                        ->where('changer_id','!=',$employee->id)
                        ->where('updated_at','>=',$pvstate->updated_at)->get();
        if(sizeof($pvlogs)==0) continue;

        foreach ($pvlogs as $pvlog) {
            $singlenotice['id']=$program->id;
            $singlenotice['name']=$program->name;
            $singlenotice['changer']=Employee::find($pvlog->changer_id)->name;
            $singlenotice['change_note']=$pvlog->change_note;
            array_push($noticeArray, $singlenotice);
        }
    }
    return $noticeArray;
  }

  //设置针对某项目某用户的操作而对其项目其他成员的任务提示状态的更新
  public function storePvlog($program,$employee,$note){
        $pvstates= Pvstate::where('program_id',$program->id)->where('employee_id','!=',$employee->id)->get();
        if(sizeof($pvstates)!=0) {
            foreach ($pvstates as $pvstate) {
                $pvstate->is_read = 0;
                $pvstate->save();
            }
        }else{
            return;
        }
        
        $pvlog = new Pvlog(array( 'changer_id'      => $employee->id,
                                  'change_note'=> $note
        ));
        $program->Pvlog()->save($pvlog);
  }


  
}

