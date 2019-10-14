<?php

namespace App\Http\Controllers\Api;

use App\Models\Department;
use App\Models\Employee;
use App\Models\UserInfo;
use App\Models\Team;
use App\Models\Token;
use Faker\Provider\Company;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;




use Illuminate\Database\Eloquent\Collection;



class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null );
        $employees=null;
        $employeesToArray=null;
        $listQuery=$request->all();
        //查找型号负责人的单独逻辑
        if(array_key_exists('checkPM',$listQuery)&&$listQuery['checkPM']!=''){
                $employees=Employee::where('is_p_principal','1')->get();
                $employeesToArray=$employees->map(function($employee){
                return collect($employee->toArray())->only(['id','name'])->all();
            });

        }
        //查找所有员工的简单逻辑   用于创建项目时的项目组配置使用
        if(array_key_exists('checkALL',$listQuery)&&filter_var($listQuery['checkALL'], FILTER_VALIDATE_BOOLEAN)==true) {
            $employees=Employee::all();

            $employees=$employees->sortBy(function($employee)
            {
                return $employee->id;
            });

            $employeesToArray=$employees->map(function($employee){
                return collect($employee->toArray())->only(['id','name'])->all();
            });
        }

        //查找所有员工的复杂逻辑   用于打开员工管理页面使用
        if(array_key_exists('checkCOMPLEX',$listQuery)&&filter_var($listQuery['checkCOMPLEX'], FILTER_VALIDATE_BOOLEAN)==true) {
            $employees=Employee::all();

            $employees=$employees->sortBy(function($employee)
            {
                return $employee->id;
            });

            $employeesToArray=$employees->map(function($employee){
                $userInfo=$employee->UserInfo;
                if($userInfo==null) {
                    $userInfo = new UserInfo(array('username' => $employee->name,
                        'password' => 'AB12345678c'
                    ));
                    $employee->UserInfo()->save($userInfo);
                }
                $username=$userInfo->username;
                $password=$userInfo->password;
                return collect($employee->toArray())->only([
                     'id',
                     'name',
                     'age',
                     'sex',
                     'mobilephone',
                     'team_id',
                     'is_director',
                     'is_v_director',
                     'is_chiefdesigner',
                     'is_v_chiefdesigner',
                     'is_teamleader',
                     'is_p_leader',
                     'is_p_principal',
                     'is_qa',
                     'is_cm',
                     'is_bd',
                     'is_tester',
                     'is_admin'])
                      ->put('username',$username)
                      ->put('password',$password)->all();
            });
        }

         //查找所有员工的复杂逻辑   用于打开员工管理页面使用
        if(array_key_exists('checkForSelect',$listQuery)&&filter_var($listQuery['checkForSelect'], FILTER_VALIDATE_BOOLEAN)==true) {
            $employees=Employee::all();

            $employees=$employees->sortBy(function($employee)
            {
                return $employee->id;
            });

            $employeesToArray=$employees->map(function($employee){

                $team['name']=Team::find($employee->team_id)->name;
                $team['id']=$employee->team_id;
                return collect($employee->toArray())->only([
                     'id',
                     'name',
                     'team_id'
                     ])
                      ->put('team',$team)->all();
            });
        }


        $ret['items']=$employeesToArray;
        $ret['total']=sizeof($employeesToArray);
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
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null );
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;

        $postData=$request->all();

        $employeeOne = new Employee(array(          'name'      => $postData['name'],
                                                    'age'      => $postData['age'],
                                                    'sex'      => $postData['sex'],
                                                    'mobilephone'      => $postData['mobilephone'],
                                                    'team_id'      => $postData['team_id'],
                                                    'is_director'      => $postData['is_director'],
                                                    'is_v_director'      => $postData['is_v_director'],
                                                    'is_chiefdesigner'      => $postData['is_chiefdesigner'],
                                                    'is_v_chiefdesigner'      => $postData['is_v_chiefdesigner'],
                                                    'is_teamleader'      => $postData['is_teamleader'],
                                                    'is_p_leader'      => $postData['is_p_leader'],
                                                    'is_p_principal'      => $postData['is_p_principal'],
                                                    'is_qa'      => $postData['is_qa'],
                                                    'is_cm'      => $postData['is_cm'],
                                                    'is_bd'      => $postData['is_bd'],
                                                    'is_tester'      => $postData['is_tester'],
                                                    'is_admin'      => $postData['is_admin']
                                            ));
        $employeeOne->save();

        $userInfo= new UserInfo(array(          'username'      => $postData['name'],
                                                'password'      =>'AB12345678c'
        ));
        $employeeOne->UserInfo()->save($userInfo);

        $ret['items']=$employeeOne->id;


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

        $employee=Employee::find($postData['id']);
        $employee->name=$postData['name'];
        $employee->age=$postData['age'];
        $employee->sex=$postData['sex'];
        $employee->mobilephone=$postData['mobilephone'];
        $employee->team_id=$postData['team_id'];
        $employee->is_director=$postData['is_director'];
        $employee->is_v_director=$postData['is_v_director'];
        $employee->is_chiefdesigner=$postData['is_chiefdesigner'];
        $employee->is_v_chiefdesigner=$postData['is_v_chiefdesigner'];
        $employee->is_teamleader=$postData['is_teamleader'];
        $employee->is_p_leader=$postData['is_p_leader'];
        $employee->is_p_principal=$postData['is_p_principal'];
        $employee->is_qa=$postData['is_qa'];
        $employee->is_cm=$postData['is_cm'];
        $employee->is_bd=$postData['is_bd'];
        $employee->is_tester=$postData['is_tester'];
        $employee->is_admin=$postData['is_admin'];
        $employee->save();

        $userInfo=$employee->UserInfo;
        if($userInfo==null){
            $userInfo= new UserInfo(array(          'username'      => $postData['username'],
                'password'      =>'AB12345678c'
            ));
            $employee->UserInfo()->save($userInfo);
        }else{
            $userInfo->username=$postData['username'];
            $userInfo->password=$postData['password'];
            $userInfo->save();
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
