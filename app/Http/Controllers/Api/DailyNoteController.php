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
use App\Libraries\PV;





class DailyNoteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null );


        $ptr_note=ProgramTeamRoleTask::find($_REQUEST['id']);
        $daily_notes=$ptr_note->DailyNote;
        if(sizeof($daily_notes)==0) {
            return json_encode($ret);
        }

        $daily_notesToArray=$daily_notes->map(function($daily_note){
             return collect($daily_note->toArray())->only([
                 'id',
                 'plan_work',
                 'actual_work',
                 'assist_name',
                 'output',
                 'created_at',
                 'updated_at'])->all();
         })->sortBy('created_at');

         $ret['items']=$daily_notesToArray;
         $ret['total']=sizeof($daily_notesToArray);
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
        $daily_note = new DailyNote(array(          'plan_work'      => $postData['plan_work'],
                                                    'actual_work'=> $postData['actual_work'],
                                                    'assist_name'  => $postData['assist_name'],
                                                    'output'    =>$postData['output']
                                            ));
        $ptr_note->DailyNote()->save($daily_note);
        $daily_note=collect($daily_note->toArray())->only([
             'id',
             'plan_work',
             'actual_work',
             'assist_name',
             'output',
             'created_at',
             'updated_at'])->all();
        $ret['items']=$daily_note;
        $ret['total']=1;

        $program=$ptr_note->ProgramTeamRole->Program;
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;


        $pv = new PV();
        $pv->storePvlog($program,$employee,'新增每日工作日志');

        // $pvstates= Pvstate::where('program_id',$program->id)->where('employee_id','!=',$employee->id)->get();
        // if(sizeof($pvstates)!=0) {
        //     foreach ($pvstates as $pvstate) {
        //         $pvstate->is_read = 0;
        //         $pvstate->save();
        //     }
        // }
        // $pvlog = new Pvlog(array( 'changer_id'      => $employee->id,
        //                           'change_note'=> '新增每日工作日志'
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

        $daily_note=DailyNote::find($id);
        $daily_note->plan_work=$postData['plan_work'];
        $daily_note->actual_work=$postData['actual_work'];
        $daily_note->assist_name=$postData['assist_name'];
        $daily_note->output=$postData['output'];
        $daily_note->save();

        $program=$daily_note->ProgramTeamRoleTask->ProgramTeamRole->Program;
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
