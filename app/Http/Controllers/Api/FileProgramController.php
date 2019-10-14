<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;

use Storage;
use File;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Token;
use App\Models\FileProgram;
use App\Models\Program;



class FileProgramController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null );
        $listQuery=$request->all();

        $category=$listQuery['category'];
        $program=Program::find($listQuery['program_id']);
        $file_programs=$program->FileProgram;
        if(sizeof($file_programs)==0) {
            return json_encode($ret);
        }
        $file_programs=$file_programs->filter(function($value)use ($category){ 
            return $value->category==$category;
        });

        $file_programsToArray=$file_programs->map(function($file_program){
             return collect($file_program->toArray())->only([
                 'id',
                 'version',
                 'name',
                 'category',
                 'review_state',
                 'created_at'])->all();
         })->sortBy('created_at');
         $ret['items']=$file_programsToArray;
         $ret['total']=sizeof($file_programsToArray);
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

        if(sizeof($request->file())==null)
            return;
        $postData=$request->all();
        
        $version=$postData['version'];
        $category=$postData['category'];
        
        $program_id=$postData['program_id'];
        $storagePath='app/program/'.$postData['program_id'].'/'.$postData['category'].'/'.$version;
        $uploadfile=$request->file();
        $recfile=reset($uploadfile);

        $file_program=null;
        if(array_key_exists('id',$postData)&&(is_numeric($postData['id'])==false)){
            //store a new
            $file_program['program_id']            = $program_id;
            $file_program['employee_id']            = $employee->id;
            $file_program['version'] = $version;
            $file_program['category']   = $category;
            $file_program['name']            = $recfile->getClientOriginalName();
            $file_program['path']            = $storagePath;
            $file_program['review_state']    = '否';

            $file_program=FileProgram::create($file_program);
            $file_program->save();
        }else{
            //update one exist item
            $file_program=FileProgram::find($postData['id']);
            $dirToDelete = $file_program->path;

            $file_program->employee_id=$employee->id;
            $file_program->version=$postData['version'];
            $file_program->name=$recfile->getClientOriginalName();
            $file_program->path=$storagePath;
            $file_program->save();
            File::deleteDirectory(storage_path($dirToDelete));
        }
        $recfile->move(storage_path($storagePath), $recfile->getClientOriginalName());

        $ret['items']=collect($file_program->toArray())->only([
                            'id',
                            'version',
                            'name',
                            'category',
                            'review_state',
                            'created_at'])->all();
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
        $file_program=FileProgram::find($id);
        $pathToFile=storage_path($file_program['path'].'/'.$file_program['name']);
//        $headers = array(
//            'Content-Type:' . mime_content_type( $pathToFile ),
//        );
        $headers=[
            'Content-Type' => mime_content_type( $pathToFile )
        ];
        return response()->downloadEx($pathToFile,$file_program['name'],$headers);
        //return response()->download($pathToFile,$file_program['name']);
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

       $token = $request->header('AdminToken');
       $employee =Token::where('token',$token)->first()->Employee;

       $postData=$request->all();

       $file_program=FileProgram::find($id);


       $program=$file_program->Program;
       $file_programs=$program->FileProgram;
       $file_programs->filter(function($value)use ($file_program){ return $value->category==$file_program->category;})->each(function ($item, $key) {
                $item->review_state='否';
                $item->save();
        });
       $file_program->review_state=$postData['review_state'];
       $file_program->save();




//        $pvstates= Pvstate::where('program_id',$program->id)->where('employee_id','!=',$employee->id)->get();
//        if(sizeof($pvstates)!=0) {
//            foreach ($pvstates as $pvstate) {
//                $pvstate->is_read = 0;
//                $pvstate->save();
//            }
//        }
//
//        $pvlog = new Pvlog(array( 'changer_id'      => $employee->id,
//            'change_note'=> '更新待解决问题'
//        ));
//        $program->Pvlog()->save($pvlog);

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
