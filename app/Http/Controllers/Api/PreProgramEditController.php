<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use App\Libraries\PV;
use App\Libraries\PERMISSION;


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
use Illuminate\Database\Eloquent\Collection;
use App\Models\FlightModel;


class PreProgramEditController extends Controller
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


        if(array_key_exists('title',$listQuery)&&$listQuery['title']!=''){
            if(strpos($listQuery['title'], '*') !== false) {
                $programsTitle = Program::where('name', 'LIKE', str_replace('*', '%', $listQuery['title']))->get();
            }else{
                $programsTitle = Program::where('name', 'LIKE', '%'.$listQuery['title'].'%')->get();
            }
            if($programs->isEmpty()){
                $programs=$programs->merge($programsTitle);
            }else{
                $programs=$programs->intersect($programsTitle);
            }
        }
        if(array_key_exists('model_id',$listQuery)&&$listQuery['model_id']!=''){
            $programsModel = Program::where('model_id', $listQuery['model_id'])->get();
            if($programs->isEmpty()){
                $programs=$programs->merge($programsModel);
            }else{
                $programs=$programs->intersect($programsModel);
            }
        }

        if(array_key_exists('classification',$listQuery)&&$listQuery['classification']!=''){
            $programsClassification = Program::where('classification', $listQuery['classification'])->get();
            if($programs->isEmpty()){
                $programs=$programs->merge($programsClassification);
            }else{
                $programs=$programs->intersect($programsClassification);
            }
        }

        if(array_key_exists('program_type',$listQuery)&&$listQuery['program_type']!=''){
            $programsProgramType = Program::where('program_type', $listQuery['program_type'])->get();
            if($programs->isEmpty()){
                $programs=$programs->merge($programsProgramType);
            }else{
                $programs=$programs->intersect($programsProgramType);
            }
        }

        if(array_key_exists('manager',$listQuery)&&$listQuery['manager']!=''){
            $programsManager = Program::where('manager_id', (int)$listQuery['manager'])->get();
            if($programs->isEmpty()){
                $programs=$programs->merge($programsManager);
            }else{
                $programs=$programs->intersect($programsManager);
            }
        }
        if(array_key_exists('first',$listQuery)&&$listQuery['first']=='true'){
            $programs=null;
            $programs=Program::All();
        }



        if($employee->is_director!=true&&$employee->is_v_director!=true&&$employee->is_admin!=true){
            $permission = new PERMISSION();
            $programs=$programs->filter(function($program)use($employee,$permission){
                $ret=$permission->checkPermission($program,$employee);
                return $ret;
            });
        }


        //将programs按照创建时间的降序排列
        $programs=$programs->filter(function($program){
          return $program->state=='意向项目'
               ||$program->state=='预备项目';
        })->sortBy(function($program)
        {
            return $program->created_at;
        })->reverse();
        $ret['total']=sizeof($programs);
        $programs=$programs->forPage($listQuery['page'], $listQuery['limit']);

         $programsToArray=$programs->map(function($program){
             $create_step=['项目的信息','联系人配置'];
             if(sizeof($program->SoftwareInfo)==0){

             }else if($program->Workflow==null){
                 $create_step[2]='被测件信息';
             }else if(sizeof($program->ProgramTeamRole)==0){
                 $create_step[2]='被测件信息';
                 $create_step[3]='工作流配置';
             }else{
                 $create_step[2]='被测件信息';
                 $create_step[3]='工作流配置';
                 $create_step[4]='项目组配置';
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
                 ->put('create_step',$create_step)
                 ->all();
             return $program;
         });

        $ret['items']=$programsToArray->toArray();
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
        //1.programBasic
        //2.contact
        //3.softwareInfo
        //4.workflow
        //5.programTeamRole
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'id'=>0 );
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;

        $postData=$request->all();
        $programBasic=$postData['programBasic'];

        $program['plan_start_time'] = $programBasic['plan_start_time'];
        $program['plan_end_time']   = $programBasic['plan_end_time'];
        $program['name']            = $programBasic['name'];
        $program['type']            = $programBasic['type'];
        $program['ref']             = $programBasic['ref'];
        $program['program_source']  = $programBasic['program_source'];
        $program['state']           = '正式项目';
        $program['program_identity']= $programBasic['program_identity'];
        $program['model_id']        = $programBasic['model_id'];
        $program['program_type']    = $programBasic['program_type'];
        $program['classification']  = $programBasic['classification'];
        $program['program_stage']   = $programBasic['program_stage'];
        $program['dev_type']        = $programBasic['dev_type'];
        $program['creator_id']      = $employee->id;
        $program['manager_id']      = $programBasic['manager']['id'];

        $program=Program::create($program);
        $program->save();

        $contacts=$postData['contact'];

        foreach($contacts as $member){
            $memberRole = new Contact(array(        'is_12s'   => $member['is_12s'],
                                                    'type'     => $member['type'],
                                                    'organ'    => $member['organ'],
                                                    'name'     => $member['name'],
                                                    'tele'     => $member['tele']
                                                ));
            $program->Contact()->save($memberRole);
        }
        if(array_key_exists('softwareInfo',$postData)){
            $softInfo=$postData['softwareInfo'];
            foreach($softInfo as $member){
                $softwareInfo = new SoftwareInfo(array( 'name'             => $member['name'],
                                                        'version_id'       => $member['version_id'],
                                                        'complier'         => $member['complier'],
                                                        'runtime'          => $member['runtime'],
                                                        'size'             => $member['size'],
                                                        'reduced_code_size'=> $member['reduced_code_size'],
                                                        'reduced_reason'   => $member['reduced_reason'],
                                                        'software_cate'    => $member['software_cate'],
                                                        'software_sub_cate'=> $member['software_sub_cate'],
                                                        'cpu_type'         => $member['cpu_type'],
                                                        'code_langu'       => $member['code_langu'],
                                                        'software_usage'   => $member['software_usage'],
                                                        'software_type'    => $member['software_type'],
                                                        'info_typer_id'    =>$employee->id  
                                                        ));
                $program->SoftwareInfo()->save($softwareInfo);
            }
        }


            if(array_key_exists('workflow',$postData)){
                $workflowInfo=$postData['workflow'];
                $workflow = Workflow::create([          'workflow_name'  => $workflowInfo['workflow_name'],
                                                        'active'=>      0,
                                                        'workflow_template_id'  =>      1
                                                        ]);
                $program->Workflow()->associate($workflow);
                $program->save();


                $workflowArray=$workflowInfo['workflowArray'];

                foreach($workflowArray as $key=>$workflowNode){
                    $node = new Node(array(     'workflow_template_id'      => 1,
                                                'type'=> $workflowNode['type'],
                                                'plan_day'=> $workflowNode['plan_day'],                                        
                                                'name'  => $workflowNode['name'],
                                                'array_index'=>  $key
                                                ));
                    $program->Workflow->Node()->save($node);
                }
            }

            if(array_key_exists('programTeamRole',$postData)) {
                $programTeamRoles = $postData['programTeamRole'];
                foreach ($programTeamRoles as $member) {
                    $memberRole = new ProgramTeamRole(array('role' => $member['role'],
                        'workload_note' => $member['workload_note'],
                        'plan_workload' => $member['plan_workload'],
                        'actual_workload' => $member['actual_workload'],
                        'employee_id' => $member['employee_id']
                    ));
                    $program->ProgramTeamRole()->save($memberRole);
                }

            $pv = new PV();
            $ret['noticeArray']=$pv->storePvState($program,$employee);
            }else{
            $program['state']='预备项目';
            $program->save();
        }





        $ret['id']=$program->id;
        return json_encode($ret);
        //return json_encode($request->header('Cookie'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id,Request $request)
    {
        $token = $request->header('AdminToken');

        $ret = array('success'=>0, 'note'=>null,'items'=>null,'is_leader'=>false );

        $program = Program::find($id);

        $softwareInfoCol=null;
        if(sizeof($program->SoftwareInfo)!=0) {
            $softwareInfoCol = $program->SoftwareInfo;
            $softwareInfoCol = $softwareInfoCol->map(function ($softwareInfo) {
                return collect($softwareInfo->toArray())->only([
                    'id',
                    'name',
                    'version_id',
                    'complier',
                    'runtime',
                    'size',
                    'reduced_code_size',
                    'reduced_reason',
                    'software_cate',
                    'software_sub_cate',
                    'cpu_type',
                    'code_langu',
                    'software_usage',
                    'software_type'])->all();
            });
            $item['softwareInfo']=$softwareInfoCol;
        }
        $workflow=null;
        if($program->Workflow!=null) {
            $workflow = array('id' => null, 'workflow_name' => null, 'active' => null, 'workflowArray' => null);
            $workflow['id'] = $program->Workflow->id;
            $workflow['workflow_name'] = $program->Workflow->workflow_name;
            $workflow['active'] = $program->Workflow->active;

            $workflow['workflowArray'] = $program->Workflow->Node->map(function ($node) {
                return collect($node->toArray())->only([
                    'id',
                    'plan_day',
                    'actual_day',
                    'array_index',
                    'name',
                    'type'])->all();
            })->sortBy('array_index');
            $item['workflow'] =$workflow;
        }
        $programTeamRoles=null;
        if(sizeof($program->ProgramTeamRole)!=0) {
                $programTeamRoles = $program->ProgramTeamRole;
                $programTeamRoles = $programTeamRoles->map(function ($programTeamRole) {
                    return collect($programTeamRole->toArray())->only([
                        'id',
                        'role',
                        'workload_note',
                        'plan_workload',
                        'actual_workload',
                        'employee_id'])->put('employee_name', Employee::find($programTeamRole->employee_id)->name)->all();
                });
                $item['programTeamRole']=$programTeamRoles;
            }

        $contacts=$program->Contact;
        $contacts=$contacts->map(function($member){
            return collect($member->toArray())->only([
                'id',
                'is_12s',
                'type',
                'organ',
                'name',
                'tele'])->all();
        });

        $managerSelect=Employee::find($program->manager_id);
         if($managerSelect!=null)
        $manager = array('id'=>$managerSelect->id, 'name'=>$managerSelect->name );

        $programToArray=collect($program->toArray())->only([
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
                'dev_type'])->put('manager',$manager)->all();


        $item['programBasic']=$programToArray;
        $item['contact']=$contacts;



        $ret['items']=$item;



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
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'id'=>0 );
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;

        $postData=$request->all();
        $programBasic=$postData['programBasic'];
        $program=Program::find($programBasic['id']);

        $program->plan_start_time = $programBasic['plan_start_time'];
        $program->plan_end_time   = $programBasic['plan_end_time'];
        $program->name            = $programBasic['name'];
        $program->type            = $programBasic['type'];
        $program->ref            = $programBasic['ref'];
        $program->program_source  = $programBasic['program_source'];
        $program->state  = '预备项目';
        $program->program_identity= $programBasic['program_identity'];
        $program->model_id           = $programBasic['model_id'];
        $program->program_type    = $programBasic['program_type'];
        $program->classification  = $programBasic['classification'];
        $program->program_stage   = $programBasic['program_stage'];
        $program->dev_type        = $programBasic['dev_type'];
        $program->creator_id      = $employee->id;
        $program->manager_id      = $programBasic['manager']['id'];

        $program->save();

        if(array_key_exists('contact',$postData)){
            $contacts=$postData['contact'];
            foreach($contacts as $member){
                $memberRole=null;
                if(array_key_exists('id',$member)){
                    $memberRole=Contact::find($member['id']);
                    $memberRole->is_12s=$member['is_12s'];
                    $memberRole->type=$member['type'];
                    $memberRole->organ=$member['organ'];
                    $memberRole->name=$member['name'];
                    $memberRole->tele=$member['tele'];
                    $memberRole->save();
                }else {
                    $memberRole = new Contact(array('is_12s' => $member['is_12s'],
                        'type' => $member['type'],
                        'organ' => $member['organ'],
                        'name' => $member['name'],
                        'tele' => $member['tele']
                    ));
                    $program->Contact()->save($memberRole);

                }
            }
        }





        if(array_key_exists('softwareInfo',$postData)){
            $softInfo=$postData['softwareInfo'];
            foreach($softInfo as $member){
                $softwareInfo=null;
                if(array_key_exists('id',$member)){
                    $softwareInfo=SoftwareInfo::find($member['id']);
                    $softwareInfo->name=$member['name'];
                    $softwareInfo->version_id=$member['version_id'];
                    $softwareInfo->complier=$member['complier'];
                    $softwareInfo->runtime=$member['runtime'];
                    $softwareInfo->size=$member['size'];
                    $softwareInfo->reduced_code_size=$member['reduced_code_size'];
                    $softwareInfo->reduced_reason=$member['reduced_reason'];
                    $softwareInfo->software_cate=$member['software_cate'];
                    $softwareInfo->software_sub_cate=$member['software_sub_cate'];
                    $softwareInfo->cpu_type=$member['cpu_type'];
                    $softwareInfo->code_langu=$member['code_langu'];
                    $softwareInfo->software_usage=$member['software_usage'];
                    $softwareInfo->software_type=$member['software_type'];
                    $softwareInfo->info_typer_id=$employee->id;
                    $softwareInfo->save();
                }else {
                    $softwareInfo = new SoftwareInfo(array( 'name'      => $member['name'],
                        'version_id'=> $member['version_id'],
                        'complier'  => $member['complier'],
                        'runtime'  => $member['runtime'],
                        'size'     => $member['size'],
                        'reduced_code_size'  => $member['reduced_code_size'],
                        'reduced_reason'  => $member['reduced_reason'],
                        'software_cate'  => $member['software_cate'],
                        'software_sub_cate'  => $member['software_sub_cate'],
                        'cpu_type'  => $member['cpu_type'],
                        'code_langu'  => $member['code_langu'],
                        'software_usage'  => $member['software_usage'],
                        'software_type'  => $member['software_type'],
                        'info_typer_id'   =>$employee->id
                    ));
                    $program->SoftwareInfo()->save($softwareInfo);
                }
            }
        }

        if(array_key_exists('workflow',$postData)){
            $member=$postData['workflow'];
            $workflow=null;
            if(array_key_exists('id',$member)){
                $workflow=workflow::find($member['id']);
                $workflow->workflow_name=$member['workflow_name'];
                $workflow->save();

                $workflowArray=$member['workflowArray'];
                foreach($workflowArray as $key=>$workflowNode){
                    $node=Node::find($workflowNode['id']);
                    $node->type=$workflowNode['type'];
                    $node->plan_day=$workflowNode['plan_day'];
                    $node->name=$workflowNode['name'];
                    $node->array_index=$key;
                    $node->save();
                }

            }else {
                $workflow = Workflow::create([          
                            'workflow_name'  => $member['workflow_name'],
                            'active'=>      0,
                            'workflow_template_id'  =>      1
                        ]);
                $program->Workflow()->associate($workflow);
                $program->save();


                $workflowArray=$member['workflowArray'];

                foreach($workflowArray as $key=>$workflowNode){
                    $node = new Node(array(     'workflow_template_id'      => 1,
                        'type'=> $workflowNode['type'],
                        'plan_day'=> $workflowNode['plan_day'],
                        'name'  => $workflowNode['name'],
                        'array_index'=>  $key
                    ));
                    $program->Workflow->Node()->save($node);
                }
            }
        }

        if(array_key_exists('programTeamRole',$postData)){
            $programTeamRoles=$postData['programTeamRole'];
            foreach($programTeamRoles as $member){
                $memberRole=null;
                if(array_key_exists('id',$member)){
                    $memberRole=ProgramTeamRole::find($member['id']);
                    $memberRole->role=$member['role'];
                    $memberRole->workload_note=$member['workload_note'];
                    $memberRole->plan_workload=$member['plan_workload'];
                    $memberRole->actual_workload=$member['actual_workload'];
                    $memberRole->employee_id=$member['employee_id'];
                    $memberRole->save();
                }else {
                    $memberRole = new ProgramTeamRole(array('role' => $member['role'],
                        'workload_note' => $member['workload_note'],
                        'plan_workload' => $member['plan_workload'],
                        'actual_workload' => $member['actual_workload'],
                        'employee_id' => $member['employee_id']
                    ));
                    $program->ProgramTeamRole()->save($memberRole);

                }
            }
            $program->state='正式项目';
            $program->save();



            $pv = new PV();
            $ret['noticeArray']=$pv->storePvState($program,$employee);
        }

        $ret['id']=$program->id;
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
        return json_encode($id);
    }
}
