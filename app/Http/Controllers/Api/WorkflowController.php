<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\UserInfo;
use App\Models\Token;
use App\Models\Workflow;
use App\Models\Program;

use App\Models\ProgramTeamRole;
use App\Models\ProgramTeamRoleTask;
use App\Models\Node;
use App\Models\Pvlog;
use App\Models\Pvstate;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Collection;
use App\Libraries\PV;


class WorkflowController extends Controller
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
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null,'isOkay'=>true);

        $postData=$request->all();

        //根据ProgramTeamRole来定制各自的ProgramTeamRoleNote，而不是Employee!  因为Employee 有可能在ProgramTeamRole中重复！
        $program=Program::find($postData['programId']);
        $workflowInfo=$postData['data'];
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
            switch ($node['type']) {
                case '软件测试条件准入审查阶段':
                    $node_task=[
                        '0'=>['task'=>'环境需求沟通','before_node_id'=>$node['id'],'is_must_choose'=>'否','is_must_complete'=>'否'],
                        '1'=>['task'=>'接受正式版文档','before_node_id'=>$node['id'],'is_must_choose'=>'是','is_must_complete'=>'否'],
                        '2'=>['task'=>'接受正式版程序','before_node_id'=>$node['id'],'is_must_choose'=>'是','is_must_complete'=>'否'],
                        '3'=>['task'=>'项目建立','before_node_id'=>$node['id'],'is_must_choose'=>'是','is_must_complete'=>'是'],
                        '4'=>['task'=>'任务书/需求评审/代码走查问题闭合确认','before_node_id'=>$node['id'],'is_must_choose'=>'否','is_must_complete'=>'否'],
                        '5'=>['task'=>'完成静态测试','before_node_id'=>$node['id'],'is_must_choose'=>'是','is_must_complete'=>'否'],
                        '6'=>['task'=>'需求文档齐套性/标准符合性/完整性(详细程度)','before_node_id'=>$node['id'],'is_must_choose'=>'是','is_must_complete'=>'否']
                    ];
                    foreach($node_task as $key=>$item){
                        $ptr_note = new ProgramTeamRoleTask();
                        foreach($ptr_note->getFillable() as $key => $value){
                            if(array_key_exists($value,$item)&&$item[$value]!=null){
                                $ptr_note[$value]=$item[$value];
                            }
                        }
                        $ptr_note->save();
                    }
                    break;
                case '测试设计阶段':
                    $node_task=[
                        '0'=>['task'=>'测试需求分解','before_node_id'=>$node['id'],'is_must_choose'=>'是','is_must_complete'=>'否'],
                        '1'=>['task'=>'测试用例设计','before_node_id'=>$node['id'],'is_must_choose'=>'是','is_must_complete'=>'是'],
                        '2'=>['task'=>'测试需求测试用例内容评审','before_node_id'=>$node['id'],'is_must_choose'=>'否','is_must_complete'=>'否'],
                        '3'=>['task'=>'测试需求测试用例正式评审','before_node_id'=>$node['id'],'is_must_choose'=>'是','is_must_complete'=>'否'],
                        '4'=>['task'=>'需求/用例正式评审意见闭合确认','before_node_id'=>$node['id'],'is_must_choose'=>'是','is_must_complete'=>'否'],
                        '5'=>['task'=>'全数字/仿真测试平台搭建','before_node_id'=>$node['id'],'is_must_choose'=>'否','is_must_complete'=>'否'],
                        '6'=>['task'=>'半实物测试环境准备','before_node_id'=>$node['id'],'is_must_choose'=>'否','is_must_complete'=>'否']
                    ];
                    foreach($node_task as $key=>$item){
                        $ptr_note = new ProgramTeamRoleTask();
                        foreach($ptr_note->getFillable() as $key => $value){
                            if(array_key_exists($value,$item)&&$item[$value]!=null){
                                $ptr_note[$value]=$item[$value];
                            }
                        }
                        $ptr_note->save();
                    }
                    break;
                case '测试执行阶段':
                    $node_task=[
                        '0'=>['task'=>'静态分析问题单(含代码审查)闭合','before_node_id'=>$node['id'],'is_must_choose'=>'否','is_must_complete'=>'否'],
                        '1'=>['task'=>'首轮测试完成','before_node_id'=>$node['id'],'is_must_choose'=>'是','is_must_complete'=>'否'],
                        '2'=>['task'=>'问题单确认','before_node_id'=>$node['id'],'is_must_choose'=>'是','is_must_complete'=>'否'],
                        '3'=>['task'=>'回归测试','before_node_id'=>$node['id'],'is_must_choose'=>'是','is_must_complete'=>'是']
                    ];
                    foreach($node_task as $key=>$item){
                        $ptr_note = new ProgramTeamRoleTask();
                        foreach($ptr_note->getFillable() as $key => $value){
                            if(array_key_exists($value,$item)&&$item[$value]!=null){
                                $ptr_note[$value]=$item[$value];
                            }
                        }
                        $ptr_note->save();
                    }
                    break;
                case '测试总结阶段':
                    $node_task=[
                        '0'=>['task'=>'测试总结','before_node_id'=>$node['id'],'is_must_choose'=>'是','is_must_complete'=>'否'],
                        '1'=>['task'=>'具备评审条件','before_node_id'=>$node['id'],'is_must_choose'=>'是','is_must_complete'=>'否'],
                        '2'=>['task'=>'测试报告正式评审','before_node_id'=>$node['id'],'is_must_choose'=>'是','is_must_complete'=>'否'],
                        '3'=>['task'=>'问题单签署','before_node_id'=>$node['id'],'is_must_choose'=>'是','is_must_complete'=>'否'],
                        '4'=>['task'=>'测试报告正式评审意见闭合确认','before_node_id'=>$node['id'],'is_must_choose'=>'是','is_must_complete'=>'否'],
                        '5'=>['task'=>'入库归档','before_node_id'=>$node['id'],'is_must_choose'=>'是','is_must_complete'=>'是']
                    ];
                    foreach($node_task as $key=>$item){
                        $ptr_note = new ProgramTeamRoleTask();
                        foreach($ptr_note->getFillable() as $key => $value){
                            if(array_key_exists($value,$item)&&$item[$value]!=null){
                                $ptr_note[$value]=$item[$value];
                            }
                        }
                        $ptr_note->save();
                    }
                    break;
                default:
                    # code...
                    break;
            }
        }

        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;

        $pv = new PV();
        if($pv->isPVStateExist($program)) {
            $pv->storePvlog($program, $employee, '创建工作流程');
        }
        



        $workflow = array('id'=>null,'workflow_name'=>null, 'active'=>null,'workflowArray'=>null );
        $workflow['id']=$program->Workflow->id;
        $workflow['workflow_name']=$program->Workflow->workflow_name;
        $workflow['active']=$program->Workflow->active;

        $workflow['workflowArray']=$program->Workflow->Node->map(function($node){
            return collect($node->toArray())->only([
                'id',
                'plan_day',
                'actual_day',
                'array_index',
                'name',
                'type'])->all();
        })->sortBy('array_index');

        $ret['items']=$workflow;

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


        $program=Program::find($id);
        if($program==null){
            $ret['isOkay']=false;
            $ret['note']='无此项目';
            return json_encode($ret);
        }
        $workflow=$program->Workflow;
        if($workflow==null){
            $ret['isOkay']=false;
            $ret['note']='此项目无流程';
            return json_encode($ret);
        }
        $workflow = array('id'=>null,'workflow_name'=>null, 'active'=>null,'workflowArray'=>null );
        $workflow['id']=$program->Workflow->id;
        $workflow['workflow_name']=$program->Workflow->workflow_name;
        $workflow['active']=$program->Workflow->active;

        $workflow['workflowArray']=$program->Workflow->Node->map(function($node){
            $undo_task_count=0;
            if(sizeof($node->ProgramTeamRoleTask)!=0){
                $undo_task_collection=$node->ProgramTeamRoleTask->filter(function ($value) {
                            return $value->state != '100';
                        });
                $undo_task_count=sizeof($undo_task_collection);
            }
            return collect($node->toArray())->only([
                'id',
                'plan_day',
                'actual_day',
                'array_index',
                'name',
                'type',
                'created_at'])
                ->put('undo_task_count',$undo_task_count)
                ->all();
        })->sortBy('array_index')->values()->toArray();




        $ret['item']=$workflow;
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
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null  ,'isOkay'=>true);



        $postData=$request->all();
        if(array_key_exists('programId',$postData)&&$postData['programId']!='') {
            $member = $postData['data'];
            $workflow = null;
            //删除
            if (array_key_exists('id', $member)) {
                $workflow = Workflow::find($member['id']);
                $workflow->delete();  //会自动触发删除node
            }

            //新建
            $program=Program::find($postData['programId']);
            $workflowInfo=$postData['data'];
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
