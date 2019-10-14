<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;

use Storage;
use File;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Token;
use App\Models\FileProgram;
use App\Models\FileReview;
use App\Models\Program;
use App\Models\Employee;



class FileReviewController extends Controller
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
        $phase=$listQuery['phase'];
        $file_program=null;
        // if(array_key_exists('file_program_id',$listQuery)&&$listQuery['file_program_id']!=null){
        //     $file_program=FileProgram::find($listQuery['file_program_id']);
        // }else{
            
        // }
        $file_program=FileProgram::where('program_id',$listQuery['program_id'])
                                     ->where('category',$listQuery['category'])
                                     ->where('review_state','是')
                                     ->first();

        if($file_program==null){
            return json_encode($ret); 
        }
        $file_reviews=null;
        $file_reviews=$file_program->FileReview;
        if(sizeof($file_reviews)==0) {
            return json_encode($ret);
        }
        $file_reviews=$file_reviews->filter(function($value)use ($category,$phase){ 
            return $value->category==$category&&$value->phase==$phase;
        });

        $file_reviewsToArray=$file_reviews->map(function($file_review){
             return collect($file_review->toArray())->only([
                 'id',
                 'category',
                 'phase',
                 'version',
                 'name',
                 'state',
                 'created_at'])
                ->put('creator',Employee::find($file_review->employee_id)->name)
                 ->all();
         })->sortBy('created_at');
         $ret['items']=$file_reviewsToArray;
         $ret['total']=sizeof($file_reviewsToArray);
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
        $phase=$postData['phase'];
        $file_program_id=$file_program=FileProgram::where('program_id',$postData['program_id'])
                                     ->where('category',$postData['category'])
                                     ->where('review_state','是')
                                     ->first()->id;
        $storagePath='app/program/'.$postData['program_id'].'/'.''.'/'.$file_program_id.'/'.$category.'/'.$phase.'/'.$version;
        $uploadfile=$request->file();
        $recfile=reset($uploadfile);

        $file_review=null;
        if(array_key_exists('id',$postData)&&(is_numeric($postData['id'])==false)){
            //store a new
            $file_review['file_program_id']        = $file_program_id;
            $file_review['employee_id']            = $employee->id;
            $file_review['category'] = $category;
            $file_review['phase'] = $phase;
            $file_review['version'] = $version;
            $file_review['name']            = $recfile->getClientOriginalName();
            $file_review['path']            = $storagePath;
            $file_review['state']            = '待确认';

            $file_review=FileReview::create($file_review);
            $file_review->save();
        }else{
            //update one exist item
            $file_review=FileReview::find($postData['id']);
            $dirToDelete = $file_review->path;

            $file_review->employee_id=$employee->id;
            $file_review->version=$postData['version'];
            $file_review->name=$recfile->getClientOriginalName();
            $file_review->path=$storagePath;
            $file_review->save();
            File::deleteDirectory(storage_path($dirToDelete));
        }
        $recfile->move(storage_path($storagePath), $recfile->getClientOriginalName());

        $ret['items']=collect($file_review->toArray())->only([
             'id',
             'category',
             'phase',
             'version',
             'name',
             'state',
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
        $file_reivew=FileReview::find($id);
        $pathToFile=storage_path($file_reivew['path'].'/'.$file_reivew['name']);
        $headers=[
            'Content-Type' => mime_content_type( $pathToFile )
        ];
        return response()->download($pathToFile,$file_reivew['name'],$headers);
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
        if(sizeof($request->file())==null)
            return;

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
