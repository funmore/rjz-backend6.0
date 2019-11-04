<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ProgramLog;
use App\Models\Program;

class ProgramLogController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>array(),'is_okay'=>true );


        $program=Program::find($_REQUEST['id']);
        if($program==null){
            $ret['is_okay']=false;
            $ret['note']='无此项目';
            return json_encode($ret);
        }
        $program_logs=$program->ProgramLog;
        if(sizeof($program_logs)==0) {
            return json_encode($ret);
        }
        $program_logToArray=$program_logs
            ->map(function($program_log){
            return collect($program_log->toArray())->only([
                'id',
                'value',
                'created_at'])->all();
         })->sortBy('created_at')->reverse();


         $ret['items']=$program_logToArray;
         $ret['total']=sizeof($program_logToArray);
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
