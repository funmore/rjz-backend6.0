<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\SoftwareInfo;
use App\Models\Pvlog;
use App\Models\Pvstate;
use App\Models\Token;
use App\Models\Employee;
use App\Models\UserInfo;

use App\Models\Program;
use App\Libraries\PV;



class SoftwareInfoController extends Controller
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
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null,'isOkay'=>true);

        $postData=$request->all();

        $program=Program::find($postData['programId']);
        $softInfo=$postData['data'];
        $softwareInfo = new SoftwareInfo(array( 'name'      => $softInfo['name'],
                                                'version_id'=> $softInfo['version_id'],
                                                'complier'  => $softInfo['complier'],
                                                'runtime'  => $softInfo['runtime'],
                                                'size'     => $softInfo['size'],
                                                'reduced_code_size'  => $softInfo['reduced_code_size'],
                                                'reduced_reason'  => $softInfo['reduced_reason'],
                                                'software_cate'  => $softInfo['software_cate'],
                                                'software_sub_cate'  => $softInfo['software_sub_cate'],
                                                'cpu_type'  => $softInfo['cpu_type'],
                                                'code_langu'  => $softInfo['code_langu'],
                                                'software_usage'  => $softInfo['software_usage'],
                                                'software_type'  => $softInfo['software_type'],
                                                'info_typer_id'   =>$employee->id  
                                                ));
        $program->SoftwareInfo()->save($softwareInfo);

        $pv = new PV();
        if($pv->isPVStateExist($program)){
            $pv->storePvlog($program,$employee,'新增被测件信息');
        }


        $softwareInfoCol=$program->SoftwareInfo;
        $softwareInfoCol=$softwareInfoCol->map(function($softwareInfo){
            return collect($softwareInfo->toArray())->only([
                'id',
                'name',
                'version_id',
                'complier',
                'runtime',
                'size',
                'reduced_code_size',
                'reduced_reason',
                'software_cate',
                'software_sub_cate',
                'cpu_type',
                'code_langu',
                'software_usage',
                'software_type'])->all();
            });
        

        $ret['items']=$softwareInfoCol;
        $log=array('program_id'=>$program->id,'employee_id'=>$employee,'employee_name'=>$employee->name,'name'=>'被测件信息','type'=>'新增','instance_name'=>$softwareInfo['name'],'content'=>array());
        $request->attributes->add(['log' => $log]);

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
        $softwareInfo=$program->SoftwareInfo;
        if(sizeof($softwareInfo)==0){
                $ret['isOkay']=false;
                $ret['note']='此项目无软件信息';
                return json_encode($ret);
        }
        $softwareInfo=collect($softwareInfo[0]->toArray())->only([
            'id',
            'name',
            'version_id',
            'complier',
            'runtime',
            'size',
            'reduced_code_size',
            'reduced_reason',
            'software_cate',
            'software_sub_cate',
            'cpu_type',
            'code_langu',
            'software_usage',
            'software_type'])->all();
      



        $ret['item']=$softwareInfo;      
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
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null ,'isOkay'=>true);
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;


        $log=null;
        $postData=$request->all();
        if(array_key_exists('programId',$postData)&&$postData['programId']!=''){
            $member=$postData['data'];
            if(array_key_exists('id',$member)){
                $softwareInfo=SoftwareInfo::find($member['id']);
                $log=array('program_id'=>$postData['programId'],'employee_id'=>$employee,'employee_name'=>$employee->name,'name'=>'被测件信息','type'=>'更新','instance_name'=>$softwareInfo['name'],'content'=>array());
                $log['instance_name']=$softwareInfo['name'];
                foreach($softwareInfo->getFillable() as $key => $value){
                    if(array_key_exists($value,$member)&&$softwareInfo[$value]!=$member[$value]){
                        $log['content'][$value]['old']=$member[$value];
                        $softwareInfo[$value]=$member[$value];
                        $log['content'][$value]['new']=$member[$value];
                    }
                }
                $softwareInfo->save();
            }

        }
            $program=$softwareInfo->Program;
            $token = $request->header('AdminToken');
            $employee =Token::where('token',$token)->first()->Employee;

            $pv = new PV();
            if($pv->isPVStateExist($program)){
                $pv->storePvlog($program,$employee,'被测件信息变更');
            }
        if(sizeof($log['content'])!=0) {
            $request->attributes->add(['log' => $log]);
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
