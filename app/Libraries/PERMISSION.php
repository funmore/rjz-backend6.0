<?php
namespace App\Libraries;
use App\Models\Program;
use App\Models\ProgramTeamRole;
use App\Models\Employee;
use App\Models\FlightModel;
class PERMISSION {

  public function __construct() {

  }

  //创建新项目使用
  public function checkPermission($program,$employee){
      if($program==null||$employee==null){
        return false;
      }
      $e_id=$employee->id;
      if($program->creator_id==$e_id||$program->manager_id==$e_id||$program->FlightModel->employee_id==$e_id){
          return true;
      }
      if(sizeof($program->ProgramTeamRole)!=0) {
        $ret=$program->ProgramTeamRole->search(function ($item)use($e_id) {
            return $item->employee_id==$e_id;
        });
        if(is_numeric($ret)==true){
            return true;
        }
      }
      return false;
  }


  
}

