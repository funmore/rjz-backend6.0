<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\UserInfo;
use App\Models\Token;
use App\Models\Employee;
use App\Models\ProgramTeamRole;
use App\Models\Program;
use App\Models\SoftwareInfo;
use App\Models\Workflow;
use App\Models\Node;
use App\Models\Pvlog;
use App\Models\Pvstate;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Collection;
use App\Models\FlightModel;
use App\Models\NoTestWork;
use Carbon\Carbon;


class NoTestWorkController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null );
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;
        $postData=$request->all();

        $noTestWorks=null;
        $noTestWorks=$employee->NoTestWork;
        if($noTestWorks==null){
            return json_encode($ret);
        }
        $noTestWorks=$noTestWorks->filter(function($value)use($postData){
                     return   $value->date==$postData['date'];
                 });

        if(sizeof($noTestWorks)==0) {
            return json_encode($ret);
        }
        $noTestWorksToArray=$noTestWorks->map(function($noTestWork){
             return collect($noTestWork->toArray())->only([
                 'id',
                 'date',
                 'range_start',
                 'range_end',
                 'type',
                 'assist_name',
                 'output',
                 'note',
                 'created_at'])
                 ->all();
         })->sortBy('created_at');
         $ret['items']=$noTestWorksToArray;
         $ret['total']=sizeof($noTestWorksToArray);
        return json_encode($ret);
    }
    /**
     * Display very day log  counts of a particular month
     *return a list contain a month days noteswork log counts
     * @return \Illuminate\Http\Response
     */
    public function month(Request $request)
    {
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>array() );
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;
        $postData=$request->all();
        $datesArr=$postData['datesArr'];
        $noTestWorks=null;
        $noTestWorks=$employee->NoTestWork;
        if($noTestWorks==null){
            $ret['items']=$datesArr;
            return json_encode($ret);
        }

        foreach($datesArr as $date) {
            $noTestWorksOnedayCol = $noTestWorks->filter(function ($value) use ($date) {
                return $value->date == $date;
            });
            $toPush['date'] = $date;
            $toPush['data'] = null;
            $toPush['data']=sizeof($noTestWorksOnedayCol);
            array_push($ret['items'], $toPush);
            // if (sizeof($noTestWorksOnedayCol); != 0) {
            //     //获取详细内容
            //     $toPush['data'] = $noTestWorksOnedayCol->map(function ($noTestWork) {
            //         return collect($noTestWork->toArray())->only([
            //             'id',
            //             'range_start',
            //             'range_end',
            //             'type',
            //             'assist_name',
            //             'output',
            //             'note',
            //             'created_at'])
            //             ->all();
            //     })->sortBy('created_at')->values()->toArray();

            //     array_push($ret['items'], $toPush);
            // } else {
            //     $toPush['data']=array();
            //     array_push($ret['items'], $toPush);
            // }
        }
        $ret['total']=sizeof($ret['items']);
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
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'id'=>0,'created_at'=>null );
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;
        $postData=$request->all();
        $noTestWork['date'] = $postData['date'];
        $noTestWork['range_start']   = $postData['range_start'];
        $noTestWork['range_end'] = $postData['range_end'];
        $noTestWork['type']   = $postData['type'];
        $noTestWork['assist_name'] = $postData['assist_name'];
        $noTestWork['output']   = $postData['output'];
        $noTestWork['note'] = $postData['note'];
        $noTestWork['employee_id']   = $employee->id;

        $noTestWork=NoTestWork::create($noTestWork);
        $noTestWork->save();

        $ret['id']=$noTestWork->id;
        $ret['created_at']=$noTestWork->created_at;
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
        $ret = array('success'=>0, 'note'=>'update','total'=>0,'items'=>null,'is_okay'=>true );
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;

        $postData=$request->all();

        $noTestWork=NoTestWork::find($id);
        if($noTestWork==null){
            $ret['is_okay']=false;
            $ret['note']='该工作日志不存在';
            return json_encode($ret);
        }
        if($noTestWork->Employee->id!=$employee->id){
            $ret['is_okay']=false;
            $ret['note']='非本人操作';
            return json_encode($ret);
        }

        $noTestWork['range_start'] = $postData['range_start'];
        $noTestWork['range_end']= $postData['range_end'];
        $noTestWork['type']= $postData['type'];
        $noTestWork['assist_name']= $postData['assist_name'];
        $noTestWork['output']= $postData['output'];
        $noTestWork['note']= $postData['note'];
        $noTestWork->save();

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
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'is_okay'=>true );
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;

        $noTestWork=NoTestWork::find($id);
        if($noTestWork==null){
            $ret['is_okay']=false;
            $ret['note']='该工作日志不存在';
            return json_encode($ret);
        }
         if($noTestWork->Employee->id!=$employee->id){
            $ret['is_okay']=false;
            $ret['note']='非本人操作';
            return json_encode($ret);
        }
        
        $noTestWork->delete();

        return json_encode($ret);
    }
}
