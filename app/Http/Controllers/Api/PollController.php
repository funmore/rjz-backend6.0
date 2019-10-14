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
use Carbon\Carbon;


class PollController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null ,'isOkay'=>true);
        $listQuery=$request->all();
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;
        $e_id=$employee->id;

        $polls=null;

        if(!array_key_exists('type',$listQuery)){
            $ret['isOkay']=false;
            $ret['note']='未指定查询类型';
            return json_encode($ret);
        }
        switch ($listQuery['type']) {
            case 'canPoll':
                $polls=Poll::where('due_day','>',Carbon::now())->get();
                $polls=$polls->filter(function($poll)use($e_id){
                    if(is_numeric(array_search($e_id,explode('|',$poll->range)))==true){  //poll 表中有此用户
                        $pollFill=PollFill::where('poll_id',$poll->id)->where('employee_id',$e_id)->get();  //pollfill 表里无此用户
                        if(sizeof($pollFill)==0){
                            return true;
                        }
                    }
                   return  false;
                });
                break;
            case 'isPolled':
                $polls=Poll::where('due_day','>',Carbon::now())->get();
                $polls=$polls->filter(function($poll)use($e_id){
                    $pollFill=PollFill::where('poll_id',$poll->id)->where('employee_id',$e_id)->get();
                    if(sizeof($pollFill)!=0){
                        return true;
                    }else{
                        return false;
                    }
                });
                break;
            case 'isExpired':
                $polls=Poll::where('due_day','<',Carbon::now())->get();
                $polls=$polls->filter(function($poll)use($e_id){
                    if(is_numeric(array_search($e_id,explode('|',$poll->range)))==true){  //poll 表中有此用户
                        return true;
                    }
                });
                break;
            case 'processing':
                $polls=Poll::where('due_day','>',Carbon::now())->where('employee_id',$e_id)->get();
                break;
            case 'expired':
                $polls=Poll::where('due_day','<',Carbon::now())->where('employee_id',$e_id)->get();
                break;
            default:
                ;
        }

        //将polls按照创建时间的降序排列
        $polls=$polls->sortBy(function($poll)
        {
            return $poll->created_at;
        })->reverse();

         $pollsToArray=$polls->map(function($poll)use($employee){
             $pollFillsCount=sizeof($poll->PollFill);
             $isMePolled=sizeof(PollFill::where('poll_id',$poll->id)->where('employee_id',$employee->id)->get())==0?false:true;
             $employee_name=Employee::find($poll->employee_id)->name;
             $poll=collect($poll->toArray())->only([
                 'id',
                 'name',
                 'due_day',
                 'employee_id',
                 'is_multiple',
                 'range'])
                 ->put('poll_fill_count',$pollFillsCount)
                 ->put('is_me_polled',$isMePolled)
                 ->put('employee_name',$employee_name)
                 ->all();
             return $poll;
         });

        $ret['items']=$pollsToArray->toArray();
        $ret['total']=sizeof($pollsToArray);
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
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'id'=>0 );
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;

        $postData=$request->all();
        $RecPoll=$postData['poll'];

        $poll['name'] = $RecPoll['name'];
        $poll['due_day']   = $RecPoll['due_day'];
        $poll['employee_id']  = $employee->id;
        $poll['range']  =implode("|", $RecPoll['range']);
        $poll['is_multiple']   = $RecPoll['is_multiple'];

        $poll=Poll::create($poll);
        $poll->save();

        $RecPollColumn=$postData['poll_column'];
        foreach($RecPollColumn as $key=>$member){
            $memberRole = new PollColumn(array(     'name'      => $member['name'],
                                                    'type'=> $member['type'],
                                                    'valid_value'  => implode("|",$member['valid_value']),
                                                    'index'  => $key
                                                ));
            $poll->PollColumn()->save($memberRole);
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
        $ret = array('success'=>0, 'note'=>null,'items'=>null,'is_leader'=>false );

        $poll = Poll::find($id);
        $pollColumn=$poll->PollColumn;
        $pollColumn=$pollColumn->sortBy(function($item)
        {
            return $item->index;
        });

        $pollColumn=$pollColumn->map(function($item){
            return collect($item->toArray())->only([
                'id',
                'name',
                'type',
                'valid_value'])
                ->put('min','')
                ->put('max','')
                ->all();
        })->values();


        $item['name']=$poll->name;
        $item['pollColumn']=$pollColumn;
        $ret['items']=$item;



        return json_encode($ret);

    }
    
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showUnPollPeople($id)
    {

        $ret = array('success'=>0, 'note'=>null,'items'=>array(),'is_leader'=>false );

        $poll = Poll::find($id);
        $items=Collection::make();
        $employeIdCol=explode('|',$poll->range);
        foreach($employeIdCol as $employeId){
            $employee_name=Employee::find($employeId)!=null?Employee::find($employeId)->name:null;
            $items->push(array('employee_name'=>$employee_name,'count'=>sizeof(PollFill::where('poll_id',$poll->id)->where('employee_id',$employeId)->get())));
        }
        $items=$items->values()->sortBy(function($item){
            $item['count'];
        })->reverse();
        $ret['items']=$items;

        
        

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
        $poll=Poll::find($id);
        if($poll!=null){
            if($poll->employee_id!=$employee->id){
                $ret['is_okay']=false;
                return json_encode($ret);
            }
            $poll->PollColumn()->delete();
            if(sizeof($poll->PollFill)!=0){
                foreach($poll->PollFill as $item ){
                    $item->PollValue()->delete();
                    $item->delete();
                }
            }
            $poll->delete();

        }

        return json_encode($ret);
    }
}
