<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\UserInfo;
use App\Models\Token;
use App\Models\Employee;
use App\Models\Team;

class TeamController extends Controller
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
            case 'all':
                $teams=Team::All();
                break;

            default:
                ;
        }

        $teams=$teams->sortBy(function($team)
        {
            return $team->created_at;
        })->reverse();

         $teamsToArray=$teams->map(function($team){
             $team=collect($team->toArray())->only([
                 'id',
                 'name'])
                 ->all();
             return $team;
         });

        $ret['items']=$teamsToArray->toArray();
        $ret['total']=sizeof($teamsToArray);
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
        //
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
