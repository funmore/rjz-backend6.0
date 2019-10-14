<?php

namespace App\Http\Controllers\Api;

use App\Libraries\JSSDK;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\UserInfo;
use App\Models\Token;
use App\Models\Employee;
use App\Models\Pvlog;
use App\Models\Pvstate;
use App\Models\Program;
use App\Models\ProgramTeamRole;



//token: 0  success
//       1  false
class UserController extends Controller
{
    public function logout(Request $request){
        $token=$request->input('token');
        $ret = array('success'=>4, 'note'=>'loged out' );
        return json_encode($ret);
    }
    public function login(Request $request)
    {
        $name = $request->input('name');
        $pw = $request->input('password');
        $userinfo = UserInfo::where('username',$name)->first();

        $ret = array('success'=>0, 'token'=>null, 'note'=>null );
        if($userinfo==null){
            $ret['success'] = 1;
            $ret['note']    = '用户名不存在';
            return json_encode($ret);
        }

        if($userinfo->password==$pw){

            $token = new Token;
            $token->token = md5(uniqid().$pw.$name);
            
            $employee=$userinfo->Employee;
            $token->employee_id=$employee->id;
            if($employee==null)
                return;

            $token->save();
            $ret['token'] = $token->token;
        }else{
            $ret['success'] = 2;
            $ret['note']    = '密码错误';
        }
        return json_encode($ret);


    }

    public function getInfo(Request $request){
        $ret = array('success'=>0, 'note'=>null,'roles'=>null,'name'=>'' ,'noticeNum'=>null);

        $token = $request->input('token');
        $employee  =Token::where('token', $token)->first()->Employee;
        if($employee!=null){
                $ret['name']=$employee->name;
                $ret['roles']=array();
                if($employee->is_director==1){
                    array_push($ret['roles'], '主任');
                }
                if($employee->is_director==1){
                    array_push($ret['roles'], '副主任');
                }
                if($employee->is_v_director==1){
                    array_push($ret['roles'], '主任');
                }
                if($employee->is_chiefdesigner==1){
                    array_push($ret['roles'], '主任设计师');
                }
                if($employee->is_v_chiefdesigner==1){
                    array_push($ret['roles'], '副主任设计师');
                }
                if($employee->is_p_leader==1){
                    array_push($ret['roles'], '项目组长');
                }
                if($employee->is_p_principal==1){
                    array_push($ret['roles'], '型号负责人');
                }
                if($employee->is_qa==1){
                    array_push($ret['roles'], '质量保证人员');
                }
                if($employee->is_cm==1){
                    array_push($ret['roles'], '配置管理人员');
                }
                if($employee->is_bd==1){
                    array_push($ret['roles'], '市场人员');
                }
                if($employee->is_tester==1){
                    array_push($ret['roles'], '测试人员');
                }
                if($employee->is_admin==1){
                    array_push($ret['roles'], '管理员');
                }
                array_push($ret['roles'], '测试人员');
        }
        $programTeamRoles=ProgramTeamRole::where('employee_id',$employee->id)->get();
        $noDuplicates = array();
        foreach ($programTeamRoles as $v) {
            if (isset($noDuplicates[$v['program_id']])) {
                continue;
            }
            $noDuplicates[$v['program_id']] = $v;
        }
        $ProgramTeamRoleNoDuplicates = array_values($noDuplicates);
        $noticeNum=0;
        foreach($ProgramTeamRoleNoDuplicates as $member){
            $program = $member->Program;
            $pvstate =Pvstate::where('program_id',$program->id)->where('employee_id',$employee->id)->first();
            if($pvstate==null) continue;
            $is_read= $pvstate->is_read;
            if($is_read==1) continue;
            $pvlogs  = Pvlog::where('program_id',$program->id)
                            ->where('changer_id','!=',$employee->id)
                            ->where('updated_at','>=',$pvstate->updated_at)->get();
            $noticeNum=$noticeNum+sizeof($pvlogs);
        }
        $ret['noticeNum']=$noticeNum;
        return json_encode($ret);




    }


    public function grant(Request $request) {
        $token = $request->input('token');
        $code = $request->input('code');
        $nick = $request->input('nick');
        $wx = Token::where('token', $token)->first();
        $employee = Employee::where('openid', $code)->first();
        $msg = array(
            //"touser" => $leader->openid,
            "template_id" => config('yueche.BindMsg'),
            //"page" => "index",
            "form_id" => $request->input('formId'),
            "data" => array(
                "keyword3" => array(
                    "value" => $nick,
                    "color" => "#173177",
                ),
                "keyword4" => array(
                    "value" => '已绑定',
                    "color" => "#173177",
                )
            ),
            "emphasis_keyword" => "keyword4.DATA"
        );
        if ($employee) {
            $employee->openid = $wx->openid;
            $employee->save();
            $msg['data']['keyword1'] = array(
                "value" => '欢迎使用遥感约车',
                "color" => "#173177",
            );
            $msg['data']['keyword2'] = array(
                "value" => $employee->name,
                "color" => "#173177",
            );
            if ($employee->admin) {
                $ret['role'] = 'admin';
            }
            else {
                $ret['role'] = 'employee';
            }

            if($employee->privileges){
                $ret['privileges']=1;
            }else{
                $ret['privileges']=0;
            }
            if($employee->second_privileges){
                $ret['second_privileges']=1;
            }else{
                $ret['second_privileges']=0;
            }

        }
        else {
            $company = Company::where('openid', $code)->first();

            if ($company) {
                $company->openid = $wx->openid;
                $company->save();
                $msg['data']['keyword1'] = array(
                    "value" => $company->name,
                    "color" => "#173177",
                );
                $msg['data']['keyword2'] = array(
                    "value" => $company->name,
                    "color" => "#173177",
                );
                $ret['role'] = 'company';
            }
            else {
                $ret['role'] = 'noprivilege';
            }
        }
        $admins = Employee::where('admin', true)->get();
        $jssdk = new JSSDK(config('yueche.AppID'), config('yueche.AppSecret'));
        //foreach ($admins as $admin) {
            $msg['touser'] =$employee->openid;
            $jssdk->sendWxMsg($msg);



        return json_encode($ret);
    }
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
