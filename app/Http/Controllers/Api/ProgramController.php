<?php

namespace App\Http\Controllers\API;

use App\Models\Program;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Pvlog;
use App\Models\Pvstate;
use App\Models\Token;
use App\Libraries\PV;
use App\Models\Employee;
use App\Models\ProgramTeamRole;
use App\Models\FlightModel;



class ProgramController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;

        $ret = array('success'=>0, 'note'=>null,'total'=>0,'item'=>null,'isOkay'=>true);
        $postData=$request->all();

        $programBasic=$postData['data'];
        $program['plan_start_time'] = $programBasic['plan_start_time'];
        $program['plan_end_time']   = $programBasic['plan_end_time'];
        $program['actual_start_time'] = $programBasic['actual_start_time'];
        $program['actual_end_time']   = $programBasic['actual_end_time'];
        $program['contract_id']   = 0;
        $program['workflow_id']   = 0;
        $program['note']   = '';
        $program['name']            = $programBasic['name'];
        $program['type']            = $programBasic['type'];
        $program['ref']             = $programBasic['ref'];
        $program['program_source']  = $programBasic['program_source'];
        $program['state']           = '意向项目';
        $program['program_identity']= $programBasic['program_identity'];
        $program['model_id']        = $programBasic['model_id'];
        $program['program_type']    = $programBasic['program_type'];
        $program['classification']  = $programBasic['classification'];
        $program['program_stage']   = $programBasic['program_stage'];
        $program['dev_type']        = $programBasic['dev_type'];
        $program['overdue_reason']  = $programBasic['overdue_reason'];
        $program['creator_id']      = $employee->id;
        $program['manager_id']      = FlightModel::find($programBasic['model_id'])->employee_id;

        $program=Program::create($program);
        $program->save();


        
        $pv = new PV();
        if($pv->isPVStateExist($program)) {
            $pv->storePvlog($program,$employee,'创建项目');
        }


        $manager=$program->FlightModel==null?null:Employee::find($program->FlightModel->employee_id);
        $program=collect($program->toArray())->only([
                'id',
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
                'state',
                'creator_id',
                'note'])
                ->put('manager',$manager)
                ->all();


        $ret['item']=$program;
        return json_encode($ret);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $ret = array('success'=>0, 'note'=>null,'item'=>null,'isOkay'=>true );

        if($id==null){
                $ret['isOkay']=false;
                $ret['note']='无此项目';
                return json_encode($ret);  
        }
        $program=Program::find($id);
        if($program==null){
                $ret['isOkay']=false;
                $ret['note']='无此项目';
                return json_encode($ret);
        }

        $program=collect($program->toArray())->only([
                'id',
                'ref',
                'type',
                'program_source',
                'state',
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
                'manager_id'])->all();



        $ret['item']=$program;      
        return json_encode($ret);
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function role($id,Request $request)
    {
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;

        $ret = array('success'=>0, 'note'=>null,'program_role'=>null,'isOkay'=>true );

        $program = Program::find($id);
        if($program==null){
                $ret['note']="项目不存在";
                $ret['isOkay']=false;
                return json_encode($ret);
        }

        $programRole=array();
        if(sizeof($program->ProgramTeamRole)!=0){
        $programTeamRolesForRoleAdd=$program->ProgramTeamRole;
        foreach($programTeamRolesForRoleAdd as $one){
                if($one->employee_id==$employee->id) {
                array_push($programRole, $one->role);
                }
        }
        }
        if($program->FlightModel->Employee->id==$employee->id){
        array_push($programRole, '型号负责人');
        }
        if(sizeof($programRole)==0){
        array_push($programRole, '只读');
        }
        $ret['program_role']=$programRole;

        return json_encode($ret);


    }

     /**
     * 获取项目组的成员
     *
     * @param 项目id int  $id  
     * @return \Illuminate\Http\Response
     * @return     s_team:[],   //short for strict_teamrole 只局限于项目组长和项目组员
     * @return     g_team:[],    //short for general_teamrole 标准项目组  项目组长&项目组员&质量&配置管理&监督人员
     * @return     w_team:[],   //short for wide_teamrole  项目组长&项目组员&质量&配置管理&监督人员  型号负责人&项目组长
     * @return     d_team:[]    //short for dynamic_teamrole 用户自定义项目组长 for future use
     */
    public function team($id,Request $request)
    {
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;

        $ret = array('success'=>0, 'note'=>null,'team'=>null,'isOkay'=>true );

        $program = Program::find($id);
        if($program==null){
                $ret['note']="项目不存在";
                $ret['isOkay']=false;
                return json_encode($ret);
        }

        $s_team=array();
        $g_team=array();
        $w_team=array();
        $d_team=array();
        if(sizeof($program->ProgramTeamRole)!=0){
                $g_team=$program->ProgramTeamRole->map(function($programteamrole){
                        $employee_name=Employee::find($programteamrole->employee_id)==null? null:Employee::find($programteamrole->employee_id)->name;
                        $employee_id=$programteamrole->employee_id;

                        $item=collect($programteamrole->toArray())->only([
                                'role'])
                                ->put('employee_id',$employee_id)
                                ->put('employee_name',$employee_name)
                                ->all();
                        return $item;
                        })->toArray();
                $s_team=$program->ProgramTeamRole
                                ->filter(function($programteamrole){
                                        return $programteamrole->role=='项目组长'|| $programteamrole->role=='项目组员';
                                })
                                ->map(function($programteamrole){
                                        $employee_name=Employee::find($programteamrole->employee_id)==null? null:Employee::find($programteamrole->employee_id)->name;
                                        $employee_id=$programteamrole->employee_id;
                                        $item=collect($programteamrole->toArray())->only([
                                                'role'])
                                                ->put('employee_id',$employee_id)
                                                ->put('employee_name',$employee_name)
                                                ->all();
                                        return $item;
                                        })->toArray();

        }
        $w_team=$g_team;


        $manager=null;
        $manager['role']='型号负责人';
        $manager['employee_id']= $program->FlightModel->Employee->id;
        $manager['employee_name']=$program->FlightModel->Employee->name;
        array_push($w_team, $manager);

        $teamleader_array=Employee::where('team_id',$program->FlightModel->Employee->team_id)
                                  ->where('is_teamleader',1)->get();
        if(sizeof($teamleader_array)!=0){
                $teamleader_array=$teamleader_array->map(function($teamleader){
                                $teamleader=null;
                                $teamleader['role']='工程组长';
                                $teamleader['employee_id']= $teamleader->id;
                                $teamleader['employee_name']=$teamleader->name;
                                return $teamleader;
                                })->toArray();
                $w_team=array_merge($w_team,$teamleader_array);
        }else{
             //do nothing   
        }

        $ret['team']['s_team']=$s_team;
        $ret['team']['g_team']=$g_team;
        $ret['team']['w_team']=$w_team;
        $ret['team']['d_team']=$d_team;

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
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null,'isOkay'=>true );
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;

        $postData=$request->all();

        $program=Program::find($id);
        $pv = new PV();
        $pv_isset=false;
        if(array_key_exists('plan_start_time',$postData)&&$postData['plan_start_time']!=''){
                $program['plan_start_time'] = $postData['plan_start_time'];
        }
        if(array_key_exists('plan_end_time',$postData)&&$postData['plan_end_time']!=''){
                $program['plan_end_time'] = $postData['plan_end_time'];
        }
        if(array_key_exists('actual_start_time',$postData)&&$postData['actual_start_time']!=''){
                $program['actual_start_time'] = $postData['actual_start_time'];
        }
        if(array_key_exists('actual_end_time',$postData)&&$postData['actual_end_time']!=''){
                $program['actual_end_time'] = $postData['actual_end_time'];
        }
        if(array_key_exists('name',$postData)&&$postData['name']!=''){
                $program['name'] = $postData['name'];
        }
        if(array_key_exists('program_identity',$postData)&&$postData['program_identity']!=''){
                $program['program_identity'] = $postData['program_identity'];
        }
        if(array_key_exists('model_id',$postData)&&$postData['model_id']!=''){
                $program['model_id'] = $postData['model_id'];
        }
        if(array_key_exists('program_type',$postData)&&$postData['program_type']!=''){
                $program['program_type'] = $postData['program_type'];
        }
        if(array_key_exists('classification',$postData)&&$postData['classification']!=''){
                $program['classification'] = $postData['classification'];
        }
        if(array_key_exists('program_stage',$postData)&&$postData['program_stage']!=''){
                $program['program_stage'] = $postData['program_stage'];
        }
        if(array_key_exists('dev_type',$postData)&&$postData['dev_type']!=''){
                $program['dev_type'] = $postData['dev_type'];
        }
        if(array_key_exists('overdue_reason',$postData)&&$postData['overdue_reason']!=''){
                $program['overdue_reason'] = $postData['overdue_reason'];
        }
        if(array_key_exists('note',$postData)&&$postData['note']!=''){
                $program['note'] = $postData['note'];
        }
        if(array_key_exists('state',$postData)&&$postData['state']!=''){
                if($program['state']!="正式项目"&&$postData['state']=="正式项目"){
                    $ret['noticeArray']=$pv->storePvState($program,$employee);
                }else{
                    $pv->storePvlog($program,$employee,'项目信息变更');
                    $pv_isset==true;
                }
                $program['state'] = $postData['state'];
        }
        if(array_key_exists('type',$postData)&&$postData['type']!=''){
                $program['type'] = $postData['type'];
        }
        if(array_key_exists('ref',$postData)&&$postData['ref']!=''){
                $program['ref'] = $postData['ref'];
        }
        $program->save();

        if($pv_isset==false){
            $pv->storePvlog($program,$employee,'项目信息变更');
        }

        // $pvstates= Pvstate::where('program_id',$program->id)->where('employee_id','!=',$employee->id)->get();
        // if(sizeof($pvstates)!=0) {
        //     foreach ($pvstates as $pvstate) {
        //         $pvstate->is_read = 0;
        //         $pvstate->save();
        //     }
        // }

        // $pvlog = new Pvlog(array( 'changer_id'      => $employee->id,
        //                            'change_note'=> '项目信息变更'
        //                         ));
        // $program->Pvlog()->save($pvlog);


        return json_encode($ret);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
