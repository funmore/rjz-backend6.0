<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollValue;
use App\Models\PollFill;
use App\Models\PollColumn;
use App\Models\UserInfo;
use App\Models\Token;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Collection;

class PollFillController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'structure'=>null,'list'=>null );
        $listQuery=$request->all();
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;



        $poll=Poll::find($listQuery['id']);
        if($poll==null)
            return json_encode($ret);


        //获取poll的结构
        $pollColumn=$poll->PollColumn;
        $pollColumn=$pollColumn->sortBy(function($item)
        {
            return $item->index;
        });

        $pollColumn=$pollColumn->map(function($item){
            return collect($item->toArray())->only([
                'id',
                'name',
                ])
                ->all();
        })->values();


        $structure['name']=$poll->name;
        $structure['pollColumn']=$pollColumn;
        //获取poll的结构 end

        //获取poll的填写结果
        $pollFills=$poll->PollFill;
        $pollFills=$pollFills->sortBy(function($item)
        {
            return $item->created_at;
        });
        $list=$pollFills->map(function($pollFill){
            $poll_value=$pollFill->PollValue->map(function($item){
                return collect($item->toArray())->only([
                    'id',
                    'poll_column_id',
                    'value'])
                    ->all();
            });
            $item=$poll_value->put('employee_name',Employee::find($pollFill->employee_id)->name)
                             ->put('poll_fill_id',$pollFill->id);
            return $item;
        });

        //获取poll的填写结果end


        $ret['structure']=$structure;
        $ret['list']=$list;
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
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'is_okay'=>true );
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;

        $postData=$request->all();

        $poll=Poll::find($postData['poll_id']);
        if($poll==null){
            $ret['is_okay']=false;
            $ret['note']='此投票不存在';
            return json_encode($ret);
        }
        $poll_fill['poll_id'] = $postData['poll_id'];
        $poll_fill['state'] = '已填写';
        $poll_fill['employee_id']  = $employee->id;

        $poll_fill=PollFill::create($poll_fill);
        $poll_fill->save();

        $RecPollValue=$postData['poll_value'];
        foreach($RecPollValue as $key=>$member){
            $memberRole = new PollValue(array(     'poll_column_id'      => $key,
                                                    'value'=> is_array($member)?implode("|",$member):$member
                                                ));
            $poll_fill->PollValue()->save($memberRole);
        }
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id,Request $request)
    {
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'is_okay'=>true );
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;

        $pollFill=PollFill::find($id);
        $poll=$pollFill->Poll;
        if($poll!=null){
            if($poll->employee_id!=$employee->id){
                $ret['is_okay']=false;
                return json_encode($ret);
            }
        }
        if($pollFill!=null){
            $pollFill->PollValue()->delete();
            $pollFill->delete();

        }
        return json_encode($ret);


    }
}
