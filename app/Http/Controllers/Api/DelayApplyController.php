<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\ProgramTeamRole;
use App\Models\ProgramTeamRoleTask;
use App\Models\Pvlog;
use App\Models\Pvstate;
use App\Models\Token;
use App\Models\Node;
use App\Models\DailyNote;
use App\Models\DelayApply;
use App\Libraries\PV;



class DelayApplyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null );

        $ptr_note=ProgramTeamRoleTask::find($_REQUEST['id']);
        $delay_applys=$ptr_note->DelayApply;
        if(sizeof($delay_applys)==0) {
            return json_encode($ret);
        }

        $delay_applysToArray=$delay_applys->map(function($delay_apply){
             return collect($delay_apply->toArray())->only([
                 'id',
                 'delay_day',
                 'delay_reason',
                 'is_approved',
                 'created_at',
                 'updated_at'])->all();
         })->sortBy('created_at');
         $ret['items']=$delay_applysToArray;
         $ret['total']=sizeof($delay_applysToArray);
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
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null );

        $postData=$request->all();

        //根据ProgramTeamRole来定制各自的ProgramTeamRoleNote，而不是Employee!  因为Employee 有可能在ProgramTeamRole中重复！
        $ptr_note=ProgramTeamRoleTask::find($postData['ptrNoteId']);
        $delay_apply = new DelayApply(array(        'delay_day'      => $postData['delay_day'],
                                                    'delay_reason'=> $postData['delay_reason'],
                                                    'is_approved'  => '待处理'
                                            ));
        $ptr_note->DelayApply()->save($delay_apply);
        $delay_apply=collect($delay_apply->toArray())->only([
             'id',
             'delay_day',
             'delay_reason',
             'is_approved',
             'created_at',
             'updated_at'])->all();
        $ret['items']=$delay_apply;
        $ret['total']=1;

        $program=$ptr_note->ProgramTeamRole->Program;
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;
        
        $pv = new PV();
        $pv->storePvlog($program,$employee,'新增项目延期请求');

        // $pvstates= Pvstate::where('program_id',$program->id)->where('employee_id','!=',$employee->id)->get();
        // if(sizeof($pvstates)!=0) {
        //     foreach ($pvstates as $pvstate) {
        //         $pvstate->is_read = 0;
        //         $pvstate->save();
        //     }
        // }
        // $pvlog = new Pvlog(array( 'changer_id'      => $employee->id,
        //                           'change_note'=> '新增项目延期请求'
        // ));
        // $program->Pvlog()->save($pvlog);

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
        //
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
        
         $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null );

        $postData=$request->all();

        $delay_apply=DelayApply::find($id);
        $delay_apply->delay_day=$postData['delay_day'];
        $delay_apply->delay_reason=$postData['delay_reason'];
        $delay_apply->is_approved=$postData['is_approved'];
        $delay_apply->save();

        $program=$delay_apply->ProgramTeamRoleTask->ProgramTeamRole->Program;
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;

        $pv = new PV();
        $pv->storePvlog($program,$employee,'更新每日工作日志');
        
        // $pvstates= Pvstate::where('program_id',$program->id)->where('employee_id','!=',$employee->id)->get();
        // if(sizeof($pvstates)!=0) {
        //     foreach ($pvstates as $pvstate) {
        //         $pvstate->is_read = 0;
        //         $pvstate->save();
        //     }
        // }
        // $pvlog = new Pvlog(array( 'changer_id'      => $employee->id,
        //                           'change_note'=> '更新每日工作日志'
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
