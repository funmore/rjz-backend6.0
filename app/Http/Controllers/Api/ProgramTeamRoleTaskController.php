<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\ProgramTeamRole;
use App\Models\ProgramTeamRoleTask;
use App\Models\Pvlog;
use App\Models\Pvstate;
use App\Models\Token;
use App\Models\Node;
use Illuminate\Database\Eloquent\Collection;
use App\Libraries\PV;
use App\Models\Employee;
use App\Models\Program;



class ProgramTeamRoleTaskController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>array() );

        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;
        $listQuery=$request->all();

        if(array_key_exists('isOne',$listQuery)&&filter_var($listQuery['isOne'], FILTER_VALIDATE_BOOLEAN)==true){
            $ptr=ProgramTeamRole::find($_REQUEST['id']);
            $ptr_notes=$ptr->ProgramTeamRoleTask;
            if(sizeof($ptr_notes)==0) {
                return json_encode($ret);
            }

            $ptr_notesToArray=$ptr_notes->map(function($ptr_note){
                return collect($ptr_note->toArray())->only([
                    'id',
                    'task',
                    'before_node_id',
                    'due_day',
                    'overdue_reason',
                    'state',
                    'note',
                    'ratio',
                    'score',
                    'created_at',
                    'updated_at'])->put('before_node_name',Node::find($ptr_note->before_node_id)->name)->all();
            })->sortBy('updated_at')->reverse();

            $ret['items']=$ptr_notesToArray;
            $ret['total']=sizeof($ptr_notesToArray);
            return json_encode($ret);
        }else if(array_key_exists('type',$listQuery)&&$listQuery['type']!=''){
            $node=Node::find($listQuery['node_id']);
            if($node==null){
                return json_encode($ret);
            }
            $node_tasks=$node->ProgramTeamRoleTask;
            if(sizeof($node_tasks)==0){
                return json_encode($ret);
            }
            $node_tasksToArray=$node_tasks->map(function($node_task){
                $employee_name=Employee::find($node_task->ProgramTeamRole->employee_id)->name;
                return collect($node_task->toArray())->only([
                    'id',
                    'task',
                    'before_node_id',
                    'due_day',
                    'overdue_reason',
                    'state',
                    'note',
                    'ratio',
                    'score',
                    'created_at',
                    'updated_at'])
                    ->put('employee_name',$employee_name)->all();
            })->sortBy('state')->sortBy('employee_id')->reverse();
            $ret['items']=$node_tasksToArray;
            $ret['total']=sizeof($node_tasksToArray);
            return json_encode($ret);

        }{
            foreach($listQuery['id'] as $id){
                $ptr=ProgramTeamRole::find($id);
                $ptr_notes=$ptr->ProgramTeamRoleTask;
                if(sizeof($ptr_notes)==0) {
                    continue;
                }

                $ptr_notesToArray=$ptr_notes->map(function($ptr_note){
                    return collect($ptr_note->toArray())->only([
                        'id',
                        'task',
                        'before_node_id',
                        'due_day',
                        'overdue_reason',
                        'state',
                        'note',
                        'ratio',
                        'score',
                        'created_at',
                        'updated_at'])->put('before_node_name',Node::find($ptr_note->before_node_id)->name)->all();
                })->sortBy('updated_at')->reverse()->toArray();

                $ret['items']=array_merge($ret['items'],$ptr_notesToArray);
                $ret['total']=$ret['total']+sizeof($ptr_notesToArray);
            }
            return json_encode($ret);
        }


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
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null,'isOkay'=>true );

        $postData=$request->all();

        if($postData['programBasicId']==null){
            $ret['isOkay']=false;
            $ret['note']='无此项目';
            return json_encode($ret);
        }
        $program=Program::find($postData['programBasicId']);
        if($program==null){
            $ret['isOkay']=false;
            $ret['note']='无此项目';
            return json_encode($ret);
        }
        $ptr_note = new ProgramTeamRoleTask();
        foreach($ptr_note->getFillable() as $key => $value){
            if(array_key_exists($value,$postData)&&$postData[$value]!=null){
                $ptr_note[$value]=$postData[$value];
            }
        }
        if($ptr_note['before_node_id']==null){
            $ret['isOkay']=false;
            $ret['note']='未指定所属节点';
            return json_encode($ret);
        }
        $ptr_note->save();
        $ptr_note=collect($ptr_note->toArray())->only([
             'id',
             'task',
             'employee_id',
             'role_type',
             'before_node_id',
             'due_day',
             'overdue_reason',
             'state',
             'note',
             'ratio',
             'score',
             'created_at',
             'updated_at'])
            ->put('before_node_name',Node::find($ptr_note->before_node_id)->name)
            ->put('employee_name',Employee::find($ptr_note->employee_id)!=null?Employee::find($ptr_note->employee_id)->name:null)
            ->all();
        $ret['items']=$ptr_note;
        $ret['total']=1;

        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;

        $pv = new PV();
        $pv->storePvlog($program,$employee,'新增任务');

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
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null );



        $ptr=ProgramTeamRole::find($id);


        $ptr_notes=$ptr->ProgramTeamRoleTask;
        if(sizeof($ptr_notes)==0) {
            return json_encode($ret);
        }

        $ptr_notesToArray=$ptr_notes->map(function($ptr_note){
            return collect($ptr_note->toArray())->only([
                'id',
                'task',
                'before_node_id',
                'due_day',
                'overdue_reason',
                'state',
                'note',
                'ratio',
                'score',
                'created_at',
                'updated_at'])->put('before_node_name',Node::find($ptr_note->before_node_id)->name)->all();
        })->sortBy('updated_at')->reverse();

        $ret['items']=$ptr_notesToArray;
        $ret['total']=sizeof($ptr_notesToArray);
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
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null );
        $update_log=array();
        $postData=$request->all();

        // if(array_key_exists('plan_start_time',$postData)&&$postData['plan_start_time']!=''){
        //     $program['plan_start_time'] = $postData['plan_start_time'];
        // }
        // if(array_key_exists('plan_end_time',$postData)&&$postData['plan_end_time']!=''){
        //         $program['plan_end_time'] = $postData['plan_end_time'];
        // }
        // $program->save();

        $ptr_note=ProgramTeamRoleTask::find($id);

        foreach($ptr_note->getFillable() as $key => $value){
            if(array_key_exists($value,$postData)&&$ptr_note[$value]!=$postData[$value]){
                $update_log[$value]['old']=$postData[$value];
                $ptr_note[$value]=$postData[$value];
                $update_log[$value]['new']=$postData[$value];
            }
        }
        $ptr_note->save();


        $program=$ptr_note->ProgramTeamRole->Program;
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;
        
        $pv = new PV();
        $pv->storePvlog($program,$employee,'修改任务');

        // $pvstates= Pvstate::where('program_id',$program->id)->where('employee_id','!=',$employee->id)->get();
        // if(sizeof($pvstates)!=0) {
        //     foreach ($pvstates as $pvstate) {
        //         $pvstate->is_read = 0;
        //         $pvstate->save();
        //     }
        // }
        // $pvlog = new Pvlog(array( 'changer_id'      => $employee->id,
        //                           'change_note'=> '修改任务'
        // ));
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
