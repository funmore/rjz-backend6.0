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


class NodeController extends Controller
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

        $program=Program::find($postData['programId']);
        $workflow=Workflow::find($postData['workflowId']);
        $index =$postData['index'];
        $data = $postData['data'];

        $nodes=$workflow->Node->sortBy(function($item)
        {
           return $item->array_index;
        });
        foreach($nodes as $node){
            if($node->array_index>$index){
                $node->array_index=$node->array_index +1;
                $node->save();
            }
        }
        $node = new Node(array(     'workflow_template_id'      => 1,
                                    'type'=> $data['type'],
                                    'plan_day'=> $data['plan_day'],                                        
                                    'name'  => $data['name'],
                                    'array_index'=>  ($index+1)
        ));
        $workflow->Node()->save($node);


        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;

        $pv = new PV();
        if($pv->isPVStateExist($program)) {
            $pv->storePvlog($program, $employee, '创建工作更新节点');
        }
        
        $node=collect($node->toArray())->only([
            'id',
            'plan_day',
            'actual_day',
            'array_index',
            'name',
            'type',
            'created_at'])
            ->all();

        $ret['items']=$node;

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
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null,'isOkay'=>true);

        $postData=$request->all();

        $program=Program::find($postData['programId']);
        $workflow=Workflow::find($postData['workflowId']);
        $index =$postData['index'];
        $data = $postData['data'];


        $node =Node::find($data['id']);
        $node->name=$data['name'];
        $node->type=$data['type'];
        $node->plan_day=$data['plan_day'];
        $node->save();



        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;

        $pv = new PV();
        if($pv->isPVStateExist($program)) {
            $pv->storePvlog($program, $employee, '更新工作更新节点');
        }
        
        $node=collect($node->toArray())->only([
            'id',
            'plan_day',
            'actual_day',
            'array_index',
            'name',
            'type',
            'created_at'])
            ->all();

        $ret['items']=$node;

        return json_encode($ret);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id,Request $request)
    {
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null,'isOkay'=>true);


        $node=Node::find($id);
        $workflow=Workflow::find($node->Workflow->id);
        $program=Program::find($workflow->Program->id);
        $index =$node->array_index;

        $nodes=$workflow->Node->sortBy(function($item)
        {
           return $item->array_index;
        });
        $node->delete();
        foreach($nodes as $node){
            if($node->array_index>$index){
                $node->array_index=$node->array_index -1;
                $node->save();
            }
        }
        //如果是删除节点操作，需要保证节点的数量大于等于active值
        //貌似Laravel是异步删除node的机制导致代码运行到这的sizeof($workflow->Node)的值为删除前的数值
        if(sizeof($workflow->Node)<=$workflow->active+1){
            $workflow->active =sizeof($workflow->Node)-2;
            $workflow->save();
        }
        
        


        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;

        $pv = new PV();
        if($pv->isPVStateExist($program)) {
            $pv->storePvlog($program, $employee, '删除工作流更新节点');
        }
        

        return json_encode($ret);
    }
}
