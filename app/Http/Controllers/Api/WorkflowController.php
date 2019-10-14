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
        }

        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;

        $pv = new PV();
        if($pv->isPVStateExist($program)) {
            $pv->storePvlog($program, $employee, '创建工作流程');
        }
        
        // $pvstates= Pvstate::where('program_id',$program->id)->where('employee_id','!=',$employee->id)->get();
        // if(sizeof($pvstates)!=0) {
        //     foreach ($pvstates as $pvstate) {
        //         $pvstate->is_read = 0;
        //         $pvstate->save();
        //     }
        // }
        // $pvlog = new Pvlog(array( 'changer_id'      => $employee->id,
        //                           'change_note'=> '创建工作流程'
        // ));
        // $program->Pvlog()->save($pvlog);



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
