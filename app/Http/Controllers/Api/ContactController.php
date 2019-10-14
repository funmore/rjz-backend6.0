<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\ProgramTeamRole;
use App\Models\ProgramTeamRoleTask;
use App\Models\Pvlog;
use App\Models\Pvstate;
use App\Models\Token;
use App\Models\Node;
use App\Models\DailyNote;
use App\Models\Contact;
use App\Models\Program;


class ContactController extends Controller
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

        $contacts=Contact::where('is_public',(int)filter_var(    ($listQuery['is_public']), FILTER_VALIDATE_BOOLEAN))
            ->where('is_12s',(int)filter_var(    ($listQuery['is_12s']), FILTER_VALIDATE_BOOLEAN))->get();
        $contactsToArray=$contacts->map(function($contact){
            return collect($contact->toArray())->only(['id','organ','type','name','tele'])->all();
        });


        $ret['items']=$contactsToArray;
        $ret['total']=sizeof($contactsToArray);

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
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null,'isOkay'=>true);

        $postData=$request->all();

        $program=Program::find($postData['programId']);
        if($program==null){
            $ret['isOkay']=false;
            $ret['note']='无此项目';
            return json_encode($ret);
        }

        $contacts=$postData['data'];

        foreach($contacts as $member){
            $memberRole = new Contact(array(        'is_12s'   => $member['is_12s'],
                                                    'type'     => $member['type'],
                                                    'organ'    => $member['organ'],
                                                    'name'     => $member['name'],
                                                    'tele'     => $member['tele']
                                                ));
            $program->Contact()->save($memberRole);
        }


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
        $contacts=$program->Contact;
        if(sizeof($contacts)==0){
                $ret['isOkay']=false;
                $ret['note']='此项目无联系人';
                return json_encode($ret);
        }
        $contacts=$contacts->map(function($member){
            return collect($member->toArray())->only([
                'id',
                'is_12s',
                'type',
                'organ',
                'name',
                'tele'])->all();
        });



        $ret['item']=$contacts;      
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
        $token = $request->header('AdminToken');
        $employee =Token::where('token',$token)->first()->Employee;
        $ret = array('success'=>0, 'note'=>null,'total'=>0,'items'=>null,'isOkay'=>true);

        $postData=$request->all();

        $program=Program::find($postData['programId']);
        if($program==null){
            $ret['isOkay']=false;
            $ret['note']='无此项目';
            return json_encode($ret);
        }

        
        $contacts=$postData['data'];
        if(sizeof($program->Contact)!=0){
            foreach($contacts as $member){
                $memberRole=null;
                if(array_key_exists('id',$member)){
                    $memberRole=Contact::find($member['id']);
                    $memberRole->is_12s=$member['is_12s'];
                    $memberRole->type=$member['type'];
                    $memberRole->organ=$member['organ'];
                    $memberRole->name=$member['name'];
                    $memberRole->tele=$member['tele'];
                    $memberRole->save();
                }
            }
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
