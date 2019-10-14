<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\FlightModel;
use App\Models\Employee;
use App\Models\UserInfo;
use App\Models\Token;

class ModelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null );

        $models=null;
        $models=FlightModel::all();
        if(sizeof($models)==0) {
            return json_encode($ret);
        }

        $modelsToArray=$models->map(function($model){
             return collect($model->toArray())->only([
                 'id',
                 'model_name',
                 'employee_id',
                 'created_at'])
                ->put('manager_name',Employee::find($model->employee_id)->name)
                 ->all();
         })->sortBy('created_at');
         $ret['items']=$modelsToArray;
         $ret['total']=sizeof($modelsToArray);
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

        $postData=$request->all();

        $model['model_name'] = $postData['model_name'];
        $model['employee_id']   = $postData['employee_id'];

        $model=FlightModel::create($model);
        $model->save();

        $ret['id']=$model->id;
        $ret['created_at']=$model->created_at;
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

        $model=FlightModel::find($id);
        $model['model_name'] = $postData['model_name'];
        $model['employee_id']= $postData['employee_id'];
        $model->save();

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

        if($employee->is_admin==false){
            $ret['is_okay']=false;
            return json_encode($ret);
        }
        $model=FlightModel::find($id);
        $model->delete();

        return json_encode($ret);
    }
}
