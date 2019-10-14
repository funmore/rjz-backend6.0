<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

use App\Http\Requests;
use App\Models\UserInfo;
use App\Models\Token;
use App\Models\Employee;
use App\Models\ProgramTeamRole;
use App\Models\Program;
use App\Models\SoftwareInfo;
use App\Models\Workflow;
use App\Models\Node;
use App\Models\Pvlog;
use App\Models\Pvstate;
use App\Models\Contact;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;

class StatisticPeopleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null );
        $listQuery=$request->all();
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;

        $programs=Collection::make();

        $employees=Employee::all();

        //根据队伍号选择分组
        if(array_key_exists('team',$listQuery)&&$listQuery['team']!=''){
            $team_id=Team::where('name',$listQuery['team'])->first()->id;
            $employees = $employees->filter(function ($value) use ($team_id){
                    return $value->team_id==$team_id;
                });
        }

         $peopleInfoToArray=$employees->map(function($employee){
              //查询每个人有多少项目 start
               $programsOneRelated=Collection::make();
               $programTeamRoles=ProgramTeamRole::where('employee_id',$employee->id)->get();
               $noDuplicates = array();
               foreach ($programTeamRoles as $v) {
                   if (isset($noDuplicates[$v['program_id']])) {
                       continue;
                   }
                   $noDuplicates[$v['program_id']] = $v;
               }
               $ProgramTeamRoleNoDuplicates = array_values($noDuplicates);

               if(sizeof($ProgramTeamRoleNoDuplicates)!=0){
                   foreach ($ProgramTeamRoleNoDuplicates as $programTeamRole) {
                       $programToAdd = Program::find($programTeamRole->program_id);
                       $programsOneRelated = $programsOneRelated->push($programToAdd);   //push用来添加单个item  merge用来合并两个集合
                   }
               }
              //查询每个人有多少项目 end

             $employee=collect($employee->toArray())->only([
                 'id',
                 'name',
                 'team_id'])
                 ->put('program_count',sizeof($programsOneRelated))
                 ->all();
             return $employee;
         });


        $ret['items']=$peopleInfoToArray->toArray();
        $ret['total']=sizeof($peopleInfoToArray);
        return json_encode($ret);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id,Request $request)
    {
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null );

            $tasks=Collection::make();
          //查询每个人有多少项目 start
           $programsOneRelated=Collection::make();
           $programTeamRoles=ProgramTeamRole::where('employee_id',$id)->get();
           $noDuplicates = array();
           foreach ($programTeamRoles as $v) {

               if (isset($noDuplicates[$v['program_id']])) {
                   continue;
               }
               $noDuplicates[$v['program_id']] = $v;
           }
           $ProgramTeamRoleNoDuplicates = array_values($noDuplicates);

           if(sizeof($ProgramTeamRoleNoDuplicates)!=0){
               foreach ($ProgramTeamRoleNoDuplicates as $programTeamRole) {
                   $programToAdd = Program::find($programTeamRole->program_id);
                   $programsOneRelated = $programsOneRelated->push($programToAdd);   //push用来添加单个item  merge用来合并两个集合
               }
           }
          //查询每个人有多少项目 end
         $programsToArray=$programsOneRelated->map(function($program)use($id){
             //获取角色数组 start
             $roles=$program->ProgramTeamRole->filter(function($teamrole)use($id){
                 return $teamrole->employee_id==$id;
                                               })
                 ->map(function($value){
                     $value=collect($value->toArray())->only([
                         'id',
                         'role']);
                     return $value;
                 })->values()->all();
             //获取角色数组 end

             $program=collect($program->toArray())->only([
                 'id',
                 'name',
                 'program_identity',
                 'model',
                 'program_type',
                 'classification',
                 'manager_id'])
                 ->put('roles',$roles)
                 ->all();
             return $program;
         });



        $ret['items']=$programsToArray->toArray();
        $ret['total']=sizeof($programsToArray);
        return json_encode($ret);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
            }
}
