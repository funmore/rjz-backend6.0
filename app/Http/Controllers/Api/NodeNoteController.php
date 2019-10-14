<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\ProgramTeamRole;
use App\Models\NodeNote;
use App\Models\ProgramTeamRoleTask;
use App\Models\Pvlog;
use App\Models\Pvstate;
use App\Models\Token;
use App\Models\Node;
use App\Models\DailyNote;
use App\Models\DelayApply;
use App\Models\Employee;
use App\Libraries\PV;


class NodeNoteController extends Controller
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
        if(array_key_exists('id',$listQuery)&&$listQuery['id']!=null){
                $node=Node::find($_REQUEST['id']);


                $p_notes=$node->NodeNote;
                if(sizeof($p_notes)==0) {
                    return json_encode($ret);
                }

                $p_notesToArray=$p_notes->map(function($p_note){
                    $creator=$p_note->Employee->name;
                     return collect($p_note->toArray())->only([
                         'id',
                         'is_up',
                         'done_day',
                         'state',
                         'note',
                         'created_at',
                         'updated_at'])->put('creator',$creator)->all();
                 })->sortBy('updated_at')->reverse();

                 $ret['items']=$p_notesToArray;
                 $ret['total']=sizeof($p_notesToArray);
                return json_encode($ret);
            }else{
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
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null );
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;

        $postData=$request->all();

        $node=Node::find($postData['NodeId']);
        $p_note = new NodeNote(array(            'note'      => $postData['note'],
                                                    'state'  => $postData['state'],
                                                    'employee_id' =>  $employee->id,
                                                    'is_up'   =>$postData['is_up']
                                            ));
        $node->NodeNote()->save($p_note);
        $p_note=collect($p_note->toArray())->only([
             'id',
             'note',
             'state',
             'is_up',
             'created_at',
             'updated_at'])->put('creator',$employee->name)->all();
        $ret['items']=$p_note;
        $ret['total']=1;

        $program=$node->Workflow->Program;

        $pv = new PV();
        $pv->storePvlog($program,$employee,'新增项目待解决事项');

        // $pvstates= Pvstate::where('program_id',$program->id)->where('employee_id','!=',$employee->id)->get();
        // if(sizeof($pvstates)!=0) {
        //     foreach ($pvstates as $pvstate) {
        //         $pvstate->is_read = 0;
        //         $pvstate->save();
        //     }
        // }
        // $pvlog = new Pvlog(array( 'changer_id'      => $employee->id,
        //                           'change_note'=> '新增项目待解决事项'
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

        $p_note=NodeNote::find($id);
        $p_note->note=$postData['note'];
        $p_note->state=$postData['state'];
        $p_note->is_up=$postData['is_up'];
        $p_note->save();

        $program=$p_note->Node->Workflow->Program;
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;

        $pv = new PV();
        $pv->storePvlog($program,$employee,'更新待解决问题');
        
        // $pvstates= Pvstate::where('program_id',$program->id)->where('employee_id','!=',$employee->id)->get();
        // if(sizeof($pvstates)!=0) {
        //     foreach ($pvstates as $pvstate) {
        //         $pvstate->is_read = 0;
        //         $pvstate->save();
        //     }
        // }
        
        // $pvlog = new Pvlog(array( 'changer_id'      => $employee->id,
        //                           'change_note'=> '更新待解决问题'
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
