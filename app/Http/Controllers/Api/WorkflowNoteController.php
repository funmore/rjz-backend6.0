<?php

namespace App\Http\Controllers\API;

use App\Models\Workflow;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Pvlog;
use App\Models\Pvstate;
use App\Models\Token;
use App\Models\WorkflowNote;
use App\Models\Node;
use App\Models\Employee;
use App\Libraries\PV;


class WorkflowNoteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null );


        $workflow=Workflow::find($_REQUEST['id']);

        $workflow_notes=$workflow->WorkflowNote;
        if(sizeof($workflow_notes)==0) {
            return json_encode($ret);
        }
        $workflow_noteToArray=$workflow_notes->map(function($workflow_note){

            return collect($workflow_note->toArray())->only([
                'id',
                'employee_id',
                'note_type',
                'note',
                'from_node_id',
                'to_node_id',
                'created_at'])->merge(array( 'employee_name'=>Employee::find($workflow_note->employee_id)->name,
                                                 'from_node_name'=>Node::find($workflow_note->from_node_id)->name,
                                                 'to_node_name'=>Node::find($workflow_note->to_node_id)->name))->all();
         })->sortBy('created_at')->reverse();


         $ret['items']=$workflow_noteToArray;
         $ret['total']=sizeof($workflow_noteToArray);
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
        $ret = array('success'=>0, 'note'=>null,'item'=>null );
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;

        $postData=$request->all();

        $workflow=Workflow::find($postData['workflow_id']);
        $workflow_note = new WorkflowNote(array(    'employee_id'      => $employee->id,
                                                    'from_node_id'=> $postData['from_node_id'],
                                                    'to_node_id'  => $postData['to_node_id'],
                                                    'note'  => $postData['note'],
                                                    'note_type' => $postData['note_type']
        ));
        $workflow->WorkflowNote()->save($workflow_note);
        $workflow->active=Node::find($postData['to_node_id'])->array_index;
        $workflow->save();


        $program=$workflow->Program;

        $pv = new PV();
        $pv->storePvlog($program,$employee,'工作流变更');

        // $pvstates= Pvstate::where('program_id',$program->id)->where('employee_id','!=',$employee->id)->get();
        // if(sizeof($pvstates)!=0) {
        //     foreach ($pvstates as $pvstate) {
        //         $pvstate->is_read = 0;
        //         $pvstate->save();
        //     }
        // }

        // $pvlog = new Pvlog(array( 'changer_id'      => $employee->id,
        //     'change_note'=> '工作流变更'
        // ));
        // $program->Pvlog()->save($pvlog);

        $workflow_note=collect($workflow_note->toArray())->only([
                'id',
                'employee_id',
                'note_type',
                'note',
                'from_node_id',
                'to_node_id',
                'created_at'])->all();
        $workflow_note['employee_name']=Employee::find($workflow_note['employee_id'])->name;
        $workflow_note['from_node_name'] =Node::find($workflow_note['from_node_id'])->name;
        $workflow_note['to_node_name'] =Node::find($workflow_note['to_node_id'])->name;

        $ret['item']=$workflow_note;

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
        //
    }
}
