<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\ProgramTeamRole;


use App\Models\Employee;
use App\Models\UserInfo;
use App\Models\Token;
use App\Models\Workflow;
use App\Models\Program;

use App\Models\Node;
use App\Models\Pvlog;
use App\Models\Pvstate;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Collection;
use App\Libraries\PV;



class ProgramTeamRoleController extends Controller
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
        $programTeamRoles=$postData['data'];
            foreach ($programTeamRoles as $member) {
                $memberRole = new ProgramTeamRole(array('role' => $member['role'],
                    'workload_note' => $member['workload_note'],
                    'plan_workload' => $member['plan_workload'],
                    'actual_workload' => $member['actual_workload'],
                    'employee_id' => $member['employee_id']
                ));
                $program->ProgramTeamRole()->save($memberRole);
            }
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;

        
        $pv = new PV();
        if($pv->isPVStateExist($program)) {
            $pv->storePvlog($program,$employee,'创建工作流程');
        }

        $programTeamRoles=$program->ProgramTeamRole;
        $programTeamRoles=$programTeamRoles->map(function($programTeamRole){
            $employee_name=Employee::find($programTeamRole->employee_id)!=null?Employee::find($programTeamRole->employee_id)->name:null;
            return collect($programTeamRole->toArray())->only([
                'id',
                'role',
                'workload_note',
                'plan_workload',
                'actual_workload',
                'employee_id'])->put('employee_name',$employee_name)->all();
        });


        $ret['items']=$programTeamRoles;
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
        $ret = array('success'=>0, 'note'=>null,'item'=>null,'isOkay'=>true );


        $program=Program::find($id);
        if($program==null){
            $ret['isOkay']=false;
            $ret['note']='无此项目';
            return json_encode($ret);
        }
        $programTeamRole=$program->ProgramTeamRole;
        if(sizeof($programTeamRole)==0){
            $ret['isOkay']=false;
            $ret['note']='此项目无项目组';
            return json_encode($ret);
        }
        $programTeamRoles=null;
        $programTeamRoles = $program->ProgramTeamRole;
        $programTeamRoles = $programTeamRoles->map(function ($programTeamRole) {
            $employee_name=Employee::find($programTeamRole->employee_id)!=null?Employee::find($programTeamRole->employee_id)->name:null;
            return collect($programTeamRole->toArray())->only([
                'id',
                'role',
                'workload_note',
                'plan_workload',
                'actual_workload',
                'employee_id'])->put('employee_name', $employee_name)->all();
        });




        $ret['item']=$programTeamRoles;
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
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null,'isOkay'=>true);


        $programTeamRole=ProgramTeamRole::find($id);
        
        $postData=$request->all();
        if(array_key_exists('programId',$postData)&&$postData['programId']!=''){

            //删除不在postData中的人员 start
            $program=Program::find($postData['programId']);
            if($program!=null&&sizeof($program->ProgramTeamRole)!=0&&sizeof($postData['data'])!=0) {
                    $postDataIdCol = Collection::make($postData['data'])->values()->pluck('id');
                    $ProgramTeamRoleForDelete=$program->ProgramTeamRole->filter(function($item)use($postDataIdCol){
                        return !is_numeric($postDataIdCol->search($item->id));
                    });
                    foreach($ProgramTeamRoleForDelete as $item){
                        $item->delete();
                }
            }
            //删除不在postData中的人员 end


            $data=$postData['data'];
            foreach($data as $member){
                $memberRole=null;
                if(array_key_exists('id',$member)){
                    $memberRole=ProgramTeamRole::find($member['id']);
                    $memberRole->role=$member['role'];
                    $memberRole->workload_note=$member['workload_note'];
                    $memberRole->plan_workload=$member['plan_workload'];
                    $memberRole->actual_workload=$member['actual_workload'];
                    $memberRole->employee_id=$member['employee_id'];
                    $memberRole->save();
                }else {
                    $memberRole = new ProgramTeamRole(array('role' => $member['role'],
                        'workload_note' => $member['workload_note'],
                        'plan_workload' => $member['plan_workload'],
                        'actual_workload' => $member['actual_workload'],
                        'employee_id' => $member['employee_id']
                    ));
                    $program->ProgramTeamRole()->save($memberRole);

                }
            }

        }else{
            $programTeamRole->plan_workload  = $postData['plan_workload'];
            $programTeamRole->workload_note= $postData['workload_note'];
            $programTeamRole->actual_workload  = $postData['actual_workload'];      
            $programTeamRole->save();
        }





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
