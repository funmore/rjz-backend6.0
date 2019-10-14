<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use App\Libraries\PV;
use App\Libraries\TRIMTIME;



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
use App\Models\FlightModel;
use App\Models\PostProgram;
use Illuminate\Database\Eloquent\Collection;

class BatchImportController extends Controller
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
        $ret = array('success'=>0, 'note'=>[],'total'=>0 );
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;

        $trim = new TRIMTIME();
        $postDataArray=$request->all();
        foreach($postDataArray as $key=>$postData){
            if(is_array($postData)!=true){
                array_push($ret['note'], '序号'.(string)($key+1).' '.'的条目'.' 型号名称不在数据库');
                continue;
            }
            $programRawArray=null;
            if(array_key_exists('实际测试开始时间',$postData)&&$postData['实际测试开始时间']!=''){
                $programRawArray['actual_start_time'] = $trim->trim2TimeStampString($postData['实际测试开始时间']);
            }
            if(array_key_exists('（预测）测试结束时间',$postData)&&$postData['（预测）测试结束时间']!=''){
                $programRawArray['actual_end_time'] = $trim->trim2TimeStampString($postData['（预测）测试结束时间']);
            }

            if(array_key_exists('软件配置项名称',$postData)&&$postData['软件配置项名称']!=''){
                $programRawArray['name'] = $postData['软件配置项名称'];
            }
            if(array_key_exists('项目/型号',$postData)&&$postData['项目/型号']!=''){
                $model=FlightModel::where("model_name",$postData['项目/型号'])->first();
                if($model!=null){
                    $programRawArray['model_id'] = $model->id;
                    $programRawArray['manager_id'] = $model->employee_id;
                }else{
                    array_push($ret['note'], '序号'.(string)($key+1).' '.'的条目'.' 型号名称不在数据库');
                    continue;
                }
            }
            if(array_key_exists('是否回归测试',$postData)&&$postData['是否回归测试']!=''){
                if($postData['是否回归测试']=='是'||$postData['是否回归测试']=='Y'){
                    $programRawArray['program_type'] = '回归测试';
                }else if($postData['是否回归测试']=='否'||$postData['是否回归测试']=='N'){
                    if(strpos($postData['任务/批次'], '定型') !== false){
                        $programRawArray['program_type'] = '定型测试';
                    }else{
                        $programRawArray['program_type'] = '配置项测试';
                    }
                }
                
            }
            if(array_key_exists('任务/批次',$postData)&&$postData['任务/批次']!=''){
                $programRawArray['program_stage'] = $postData['任务/批次'];
            }
            if(array_key_exists('研制类型',$postData)&&$postData['研制类型']!=''){
                $programRawArray['dev_type'] = $postData['研制类型'];
            }
            if(array_key_exists('延期完成原因',$postData)&&$postData['延期完成原因']!=''){
                if(is_numeric($postData['延期完成原因'])&&array_key_exists((int)$postData['延期完成原因'],config('program.dueReason'))){
                    $programRawArray['overdue_reason'] = config('program.dueReason')[(int)$postData['延期完成原因']];
                }else{
                    $programRawArray['overdue_reason']=$postData['延期完成原因'];
                }
            }
            if(array_key_exists('项目状态',$postData)&&$postData['项目状态']!=''){
                $programRawArray['state'] = config('program.state')[(int)$postData['项目状态']];
            }else{
                $programRawArray['state']='意向项目';
            }
            if(array_key_exists('领域',$postData)&&$postData['领域']!=''){
                $programRawArray['type'] = $postData['领域'];
            }
            if(array_key_exists('参考基线',$postData)&&$postData['参考基线']!=''){
                $programRawArray['ref'] = $postData['参考基线'];
            }
            $programRawArray['creator_id'] = $employee->id;
            $program=Program::create($programRawArray);
            $program->save();

            $softwareInfoRawArray=null;
            if(array_key_exists('软件配置项名称',$postData)&&$postData['软件配置项名称']!=''){
                $softwareInfoRawArray['name'] = $postData['软件配置项名称'];
            }
            if(array_key_exists('软件产品领域',$postData)&&$postData['软件产品领域']!=''){
                $softwareInfoRawArray['software_sub_cate'] = $postData['软件产品领域'];
            }
            if(array_key_exists('软件类型',$postData)&&$postData['软件类型']!=''){
                $softwareInfoRawArray['software_cate'] = $postData['软件类型'];
            }
            if(array_key_exists('关键等级',$postData)&&$postData['关键等级']!=''){
                $softwareInfoRawArray['software_type'] = $postData['关键等级'];
            }
            if(array_key_exists('运行环境（芯片/操作系统）',$postData)&&$postData['运行环境（芯片/操作系统）']!=''){
                $softwareInfoRawArray['runtime'] = $postData['运行环境（芯片/操作系统）'];
            }
            if(array_key_exists('编程语言',$postData)&&$postData['编程语言']!=''){
                $softwareInfoRawArray['code_langu'] = $postData['编程语言'];
            }
            if(array_key_exists('代码量（KL）',$postData)&&$postData['代码量（KL）']!=''){
                $softwareInfoRawArray['reduced_code_size'] = $postData['代码量（KL）'];
            }
            $softwareInfoRawArray['info_typer_id'] = $employee->id;
            $softwareInfo=SoftwareInfo::create($softwareInfoRawArray);
            $program->SoftwareInfo()->save($softwareInfo);

            
            $memberRoleRawArray0=null;
            $memberRoleRawArray1=null;
            $memberRoleRawArray2=null;
            $memberRoleRawArray3=null;
            if(array_key_exists('项目组长',$postData)&&$postData['项目组长']!=''){
                if( Employee::where('name',$postData['项目组长'])->first()!=null){
                    $memberRoleRawArray0['employee_id'] =Employee::where('name',$postData['项目组长'])->first()->id;
                    $memberRoleRawArray0['role'] ='项目组长';
                }else{
                    array_push($ret['note'], '序号'.(string)($key+1).' '.'的条目'.' 项目组长不在数据库');
                    continue;
                }
                if(array_key_exists('工作占比0',$postData)&&$postData['工作占比0']!=''){
                $memberRoleRawArray0['actual_workload'] = $postData['工作占比0'];
                }
                $memberRole0=ProgramTeamRole::create($memberRoleRawArray0);
                $program->ProgramTeamRole()->save($memberRole0);
            }


            if(array_key_exists('项目成员1',$postData)&&$postData['项目成员1']!=''){
                if( Employee::where('name',$postData['项目成员1'])->first()!=null){
                    $memberRoleRawArray1['employee_id'] =Employee::where('name',$postData['项目成员1'])->first()->id;
                    $memberRoleRawArray1['role']='项目组员';
                }else{
                    array_push($ret['note'], '序号'.(string)($key+1).' '.'的条目'.' 项目成员1不在数据库');
                    continue;
                }
                if(array_key_exists('工作占比1',$postData)&&$postData['工作占比1']!=''){
                    $memberRoleRawArray1['actual_workload'] = $postData['工作占比1'];
                }
                $memberRole1=ProgramTeamRole::create($memberRoleRawArray1);
                $program->ProgramTeamRole()->save($memberRole1);
            }
            

            if(array_key_exists('项目成员2',$postData)&&$postData['项目成员2']!=''){
                if( Employee::where('name',$postData['项目成员2'])->first()!=null){
                    $memberRoleRawArray2['employee_id'] =Employee::where('name',$postData['项目成员2'])->first()->id;
                    $memberRoleRawArray2['role']='项目组员';
                }else{
                    array_push($ret['note'], '序号'.(string)($key+1).' '.'的条目'.' 项目成员2不在数据库');
                    continue;
                }
                if(array_key_exists('工作占比2',$postData)&&$postData['工作占比2']!=''){
                    $memberRoleRawArray2['actual_workload'] = $postData['工作占比2'];
                }
                $memberRole2=ProgramTeamRole::create($memberRoleRawArray2);
                $program->ProgramTeamRole()->save($memberRole2);
            }
            

            if(array_key_exists('项目成员3',$postData)&&$postData['项目成员3']!=''){
                if( Employee::where('name',$postData['项目成员3'])->first()!=null){
                    $memberRoleRawArray3['employee_id'] =Employee::where('name',$postData['项目成员3'])->first()->id;
                    $memberRoleRawArray3['role']='项目组员';
                }else{
                    array_push($ret['note'], '序号'.(string)($key+1).' '.'的条目'.' 项目成员3不在数据库');
                    continue;
                }
                if(array_key_exists('工作占比3',$postData)&&$postData['工作占比3']!=''){
                    $memberRoleRawArray3['actual_workload'] = $postData['工作占比3'];
                }
                $memberRole3=ProgramTeamRole::create($memberRoleRawArray3);
                $program->ProgramTeamRole()->save($memberRole3);
            }


            $postProgramRawArray=null;
            if(array_key_exists('测试轮数（被测件版本）',$postData)&&$postData['测试轮数（被测件版本）']!=''){
                $postProgramRawArray['test_round'] = $postData['测试轮数（被测件版本）'];
            }
            if(array_key_exists('测试发现问题总数',$postData)&&$postData['测试发现问题总数']!=''){
                $postProgramRawArray['problem_num'] = $postData['测试发现问题总数'];
            }
            if(array_key_exists('其中程序问题数',$postData)&&$postData['其中程序问题数']!=''){
                $postProgramRawArray['code_problem_num'] = $postData['其中程序问题数'];
            }
            if(array_key_exists('其中1，2级问题数',$postData)&&$postData['其中1，2级问题数']!=''){
                $postProgramRawArray['class12_problem_num'] = $postData['其中1，2级问题数'];
            }
            if(array_key_exists('计划种类',$postData)&&$postData['计划种类']!=''){

                if(is_numeric($postData['计划种类'])&&array_key_exists((int)$postData['计划种类'],config('postprogram.planType'))){
                    $postProgramRawArray['plan_type'] = config('postprogram.planType')[(int)$postData['计划种类']];
                }else{
                    $postProgramRawArray['plan_type']=$postData['计划种类'];
                }
            }
            if(array_key_exists('计划完成情况',$postData)&&$postData['计划完成情况']!=''){
                if(is_numeric($postData['计划完成情况'])&&array_key_exists((int)$postData['计划完成情况'],config('postprogram.planCompleteType'))){
                    $postProgramRawArray['plan_complete_type'] = config('postprogram.planCompleteType')[(int)$postData['计划完成情况']];
                }else{
                    $postProgramRawArray['plan_complete_type']=$postData['计划完成情况'];
                }
            }
            if(array_key_exists('CMTool',$postData)&&$postData['CMTool']!=''){
                $postProgramRawArray['cmtool_info'] = $postData['CMTool'];
            }
            if(array_key_exists('DeC817',$postData)&&$postData['DeC817']!=''){
                $postProgramRawArray['dec817'] = $postData['DeC817'];
            }
            if(array_key_exists('已裁剪',$postData)&&$postData['已裁剪']!=''){
                $postProgramRawArray['is_cut'] = $postData['已裁剪'];
            }
            $postProgram=PostProgram::create($postProgramRawArray);
            $program->PostProgram()->save($postProgram);
            
            $ret['total']=$ret['total']+1;
        }

        return json_encode($ret);
        //return json_encode($request->header('Cookie'));
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
