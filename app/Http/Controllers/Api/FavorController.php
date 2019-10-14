<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Favor;
use App\Models\Token;
use App\Models\UserInfo;
use Illuminate\Database\Eloquent\Collection;



class FavorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null,'isOkay'=>true );
        $listQuery=$request->all();
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;
        
        $favors=Collection::make();

        
        if(array_key_exists('type',$listQuery)&&$listQuery['type']!=''){
            $favors=Favor::where('type',$listQuery['type'])->where('system',"是")->get();
            $favorsPersonal=Favor::where('type',$listQuery['type'])->where('employee_id',$employee->id)->get();
            $favors=$favors->merge($favorsPersonal);
        }else{
            $ret['note']='未指定类型';
            $ret['isOkay']=false;
            return json_encode($ret);
        }
        if(sizeof($favors)==0) {
            return json_encode($ret);
        }

        $favorsToArray=$favors->map(function($favor){
             return collect($favor->toArray())->only([
                 'id',
                 'default',
                 'alias',
                 'value',
                 'system',
                 'created_at'])
                 ->all();
         })->sortBy('system')->sortBy('default');
         $ret['items']=$favorsToArray;
         $ret['total']=sizeof($favorsToArray);
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
        if($postData['default']=='是'){
            $favorsPersonals=Favor::where('type',$postData['type'])->where('employee_id',$employee->id)->where('default','是')->get();
            if(sizeof($favorsPersonals)!=0){
                foreach($favorsPersonals as $one){
                    $one->default='否';
                    $one->save();
                }
            }
        }
        $favor['employee_id']=$employee->id;
        $favor['type'] = $postData['type'];
        $favor['value']   = $postData['value'];
        $favor['alias']   = $postData['alias'];
        $favor['default']   = $postData['default'];
        $favor['system']   = $postData['system'];

        $favor=Favor::create($favor);
        $favor->save();

        

        $ret['id']=$favor->id;
        $ret['created_at']=$favor->created_at;
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
        if($postData['default']=='是'){
            $favorsPersonals=Favor::where('type',$postData['type'])->where('employee_id',$employee->id)->where('default','是')->get();
            if(sizeof($favorsPersonals)!=0){
                foreach($favorsPersonals as $one){
                    $one->default='否';
                    $one->save();
                }
            }
        }
        $favor=Favor::find($id);
        $favor['default'] = $postData['default'];
        $favor['alias'] = $postData['alias'];
        $favor['value']= $postData['value'];
        $favor['system']= $postData['system'];
        $favor->save();

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

        $favor=Favor::find($id);
        if($favor==null){
            $ret['is_okay']=false;
            $ret['note']='不存在';
            return json_encode($ret);
        }
        if($employee->id!=$favor->employee_id){
            $ret['is_okay']=false;
            $ret['note']='非本人操作';
            return json_encode($ret);
        }
        $favor->delete();

        return json_encode($ret);
    }
}
