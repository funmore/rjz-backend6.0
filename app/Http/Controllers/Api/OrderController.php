<?php

namespace App\Http\Controllers\Api;

use App\Libraries\JSSDK;
use App\Models\Accept_log;
use App\Models\Company;
use App\Models\DestinationOfOrders;
use App\Models\Approve_log;
use App\Models\OtherInfoOfOrder;
use App\Models\PerformanceUse;
use App\Models\Order;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use App\Models\Token;
use App\Models\Employee;
use App\Models\OrderIsTakeProduct;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpSpec\Process\ReRunner\OptionalReRunner;

use App\Models\OrderNum;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        return 'order controller index';
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        $token = Input::get('token');
        $token = Token::where('token', $token)->orderBy('created_at')->first();
        $employee = Employee::where('openid', $token->openid)->first();

        $order['user_id'] = $employee->id;
        $order['usetime'] = \DateTime::createFromFormat('Y-m-d H:i:s',Input::get('usetime'));
//        $order['telephone'] = Input::get('telephone');
        $order['telephone'] = $employee->mobilephone;
        $order['isOld']= (int)Input::get('applyType');
        $order['type'] = (int)Input::get('type');
        if((int)Input::get('manager')==2000){
            $order['manager'] = $employee->id;
            $order['state'] = 10;
        }else{
            $order['manager'] = (int)Input::get('manager');
            $order['state'] = 8;
        }
        $order['reason'] = Input::get('reason');
        $order['passenger'] = Input::get('passenger');
        $order['mobilephone'] = Input::get('mobilephone');
        $order['isweekend'] = (int)Input::get('isweekend');
        $order['isreturn'] = (int)Input::get('isreturn');
        $order['workers'] = Input::get('workers');

        $order['remark'] = Input::get('remark');

        if($order['usetime']->format('Y-m') == '0000-00')    //时间不对
        {
            return 0;
        }else{
        $order = Order::create($order);
            $order->save();

        $origin=json_decode(Input::get('origin'));
            $originStr=implode('/',$origin);

        $dest  =json_decode(Input::get('dest'));
            $destStr=implode('/',$dest);

        $destinationOfOrders=new DestinationOfOrders(['origin'=>Input::get('origin'),'destination'=>Input::get('dest') ]);
        $destinationOfOrders=$order->destinationoforders()->save($destinationOfOrders);

        $otherInfoOfOrder=new OtherInfoOfOrder(['isLeader'=>(int)Input::get('isLeader'),'leaderInfo'=>Input::get('leaderInfo'),'isVan'=>(int)Input::get('isVan'),'vanType'=>Input::get('vanType')]);
            $order->otherInfoOfOrder()->save($otherInfoOfOrder);

            $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
            $orderSn = $yCode[intval(date('Y')) - 2011] . strtoupper(dechex(date('m'))) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
            $ordernum = new OrderNum(array('order_numb' => $orderSn));
            $order->ordernum()->save($ordernum);

            $orderistake = new OrderIsTakeProduct(array('order_istake' => (int)Input::get('istakeproduct')));
            $order->orderistakeproduct()->save($orderistake);


            $manager=null;
            if($order->type==1){
                $manager=Employee::find($order->manager);
                $managerName=$manager->name;
            }
            $employeeLeaders=Employee::where(['depart_id'=>$employee->depart_id,'privileges'=>1])->get();
            $employeeLeadersToArray= array();
            foreach($employeeLeaders as $leader){
                array_push($employeeLeadersToArray,$leader->name);
            }
            $employeeLeadersStr=implode('/', $employeeLeadersToArray);

            $keyword2=null;
            if($order->type==1) {
                $xinghao="型号：" . $managerName;
                $guanli=" 科室领导:" . $employeeLeadersStr;
                $zong=$xinghao.$guanli;
                $keyword2 = array(
                    "value" => $zong,
                    "color" => "#173177",
                );
            }else{
                $keyword2 = array(
                    "value" =>  "科室领导:" . $employeeLeadersStr,
                    "color" => "#173177",
                );
            }

        $jssdk = new JSSDK(config('yueche.AppID'), config('yueche.AppSecret'));



            $msg = array(
                "touser" =>$employee->openid,
                "template_id" => config('yueche.OrderMsg'),
                'page'=>"pages/order/order",
                "form_id" => Input::get('formId'),
                "data" => array(
                    "keyword1" => array(
                        "value" =>  "待审批",
                        "color" => "#173177",
                    ),
                    "keyword2" => $keyword2,
                    "keyword3" => array(

                        "value" => Input::get('usetime'),
                        "color" => "#173177",
                    ),
                    "keyword4" => array(
                        "value" =>$originStr,
                        "color" => "#173177",
                    ),
                    "keyword5" => array(
                        "value" =>$destStr,
                        "color" => "#173177",
                    )
                ),
                "emphasis_keyword" => "keyword1.DATA"
            );

            $jssdk->sendWxMsg($msg);
        return json_encode($order);

        }
        //foreach (Input::get['destinations'])
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
    public function show()
    {

        echo "{'success':true}";
        //小程序请求此用户的所有订单
        $token = Input::get('token');
        $token = Token::where('token', $token)->orderBy('created_at')->first();
        $employee = Employee::where('openid', $token->openid)->first();


        $orders=Order::where('user_id',$employee -> id)->get();







        //若order为null ,将此order提出数组
        $orders=$orders->reject(function($order)
        {
            return $order==null;
        });

        $orders=$orders->filter(function($order){
            $fromDate = Carbon::now()->subWeeks(2); // or ->format(..)
            return $order->created_at>=$fromDate;
        });

        //将ordrs按照创建时间的降序排列
        $orders=$orders->sortBy(function($order)
        {
            return $order->created_at;
        })->reverse();

        $ordersToArray=$orders->map(function($order){
            return collect($order->toArray())->only(['id','usetime','passenger','mobilephone','reason','state','origin','destination','selected'])->all();
        });
        //$ordersToJson=$ordersToArray->toJson();
        return json_encode($ordersToArray);
    }

    /**
     * Display one order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showOrderOne()
    {
        //小程序请求此用户的所有订单
        $token = Input::get('token');
        $token = Token::where('token', $token)->orderBy('created_at')->first();
        $employee = Employee::where('openid', $token->openid)->first();


        $order=Order::find(Input::get('id'));

        return json_encode($order);
    }

    /**
     * cancel the request order ids.
     *
     * @param  null
     * @return \Illuminate\Http\Response
     */
    public function cancel()
    {
        $token = Input::get('token');
        $token = Token::where('token', $token)->orderBy('created_at')->first();
        $employee = Employee::where('openid', $token->openid)->first();

        $orderIdToCancel=json_decode(Input::get('cancelId'));
        $order=null;
        foreach($orderIdToCancel as $orderId){
                $order=Order::where('id',$orderId)->first();
                if($order->state>=39){
                    $order->state=1;
                }else{
                    $order->state =0;
                }

                $order->save();
            }

        $origin=$order->getOriginAttribute();
        $dest=$order->getDestinationAttribute();
        $originStr=implode('/',$origin);
        $destStr=implode('/',$dest);
        $jssdk = new JSSDK(config('yueche.AppID'), config('yueche.AppSecret'));
        $msg = array(
            "touser" =>$employee->openid,
            "template_id" => config('yueche.OrderMsg'),
            'page'=>"pages/order/order",
            "form_id" => Input::get('formId'),
            "data" => array(
                "keyword1" => array(
                    "value" =>'已取消',
                    "color" => "#173177",
                ),
                "keyword2" => array(
                    "value" => $order->reason,
                    "color" => "#173177",
                ),
                "keyword3" => array(

                    "value" => $order->usetime,
                    "color" => "#173177",
                ),
                "keyword4" => array(
                    "value" =>$originStr,
                    "color" => "#173177",
                ),
                "keyword5" => array(
                    "value" =>$destStr,
                    "color" => "#173177",
                )
            ),
            "emphasis_keyword" => "keyword1.DATA"
        );

        $jssdk->sendWxMsg($msg);
        return 0;
    }

    /**
     * confirm the request order ids.
     *
     * @param  null
     * @return \Illuminate\Http\Response
     */
    public function confirm()
    {
        $token = Input::get('token');
        $token = Token::where('token', $token)->orderBy('created_at')->first();
        $employee = Employee::where('openid', $token->openid)->first();

        $orderIdToCancel=json_decode(Input::get('confirmId'));
        $order=null;
        foreach($orderIdToCancel as $orderId) {
            $performanceUse = new PerformanceUse(['orderID' => $orderId, 'reviewText' => '默认好评', 'score_of_order' => 5.0]);
            $performanceUse->save();
            $order = Order::where(['user_id' => $employee->id, 'id' => $orderId])->first();
            $company_id = ($order->acceptlog != null && $order->acceptlog->company != null) ? $order->acceptlog->company->id : null;
            if ($company_id == 1) {
                $order->state = 50;
            } else {
                $order->state = 41;
            }
            $order->save();
        }
        $origin=$order->getOriginAttribute();
        $dest=$order->getDestinationAttribute();
        $originStr=implode('/',$origin);
        $destStr=implode('/',$dest);
        $jssdk = new JSSDK(config('yueche.AppID'), config('yueche.AppSecret'));
        $msg = array(
            "touser" =>$employee->openid,
            "template_id" => config('yueche.OrderMsg'),
            'page'=>"pages/order/order",
            "form_id" => Input::get('formId'),
            "data" => array(
                "keyword1" => array(
                    "value" =>'已确认',
                    "color" => "#173177",
                ),
                "keyword2" => array(
                    "value" => $order->reason,
                    "color" => "#173177",
                ),
                "keyword3" => array(

                    "value" => $order->usetime,
                    "color" => "#173177",
                ),
                "keyword4" => array(
                    "value" =>$originStr,
                    "color" => "#173177",
                ),
                "keyword5" => array(
                    "value" =>$destStr,
                    "color" => "#173177",
                )
            ),
            "emphasis_keyword" => "keyword1.DATA"
        );

        $jssdk->sendWxMsg($msg);
        return 0;
    }

    /**
     * approval the request order ids.
     *
     * @param  null
     * @return \Illuminate\Http\Response
     */
    public function approval()
    {
        $token = Input::get('token');
        $token = Token::where('token', $token)->orderBy('created_at')->first();
        $employee = Employee::where('openid', $token->openid)->first();

        $orderId=json_decode(Input::get('approvalId'));

        $order=Order::where('id',$orderId)->first();

        if($order->state!=10&&$order->state!=9&&$order->state!=8){
            return 0;        //已经审批则返回0
        }else{


            //未审批则返回1
            $approval=json_decode(Input::get('approval'));
            $remark=null;
            if($approval==true) {
                        if($order->isOld==0){
                                    $nowTime = Carbon::now();
                                    $usetimeMysql = $order->usetime . '';
                                    $useIime = Carbon::parse($usetimeMysql);
                                    $timeInterval = $nowTime->diffInSeconds($useIime,false);

                                    if($timeInterval<3600) {
                                        return 2;
                                    }
                                }

                if ($order->state == 8) {
                    $applyerUserId=Order::where('id',$order->id)->first()->user_id;  //applyer id
                    $applyerDepartId=Employee::where('id',$applyerUserId)->first()->depart_id;
                    if($applyerDepartId==$employee->depart_id&&$employee->privileges==1&$employee->second_privileges==1){
                        $order->state = 20;
                    }else {
                        $order->state = 9;
                    }
                } else if ($order->state == 9 || $order->state == 10){
                    $order->state = 20;
                }
                    $order->save();

                    $opinion=true;
            }else{

                    $order->state =$order->state+43;  //审批拒绝   8->51 9->52 10->53
                    $order->save();
                    $remark=Input::get('remark');
                    $opinion=false;
            }

            $approveLog=new Approve_log(['order_id'=>$orderId,'approve_node'=>$order->state,'u_id'=>$employee->id,'opinion'=>$opinion,'remark'=>$remark]);
            $approveLog->save();

            $origin=$order->getOriginAttribute();
            $dest=$order->getDestinationAttribute();
            $originStr=implode('/',$origin);
            $destStr=implode('/',$dest);
            $jssdk = new JSSDK(config('yueche.AppID'), config('yueche.AppSecret'));

            $msg = array(
                "touser" => $employee->openid,
                "template_id" => config('yueche.ApprovalMsg'),
                'page'=>"pages/approval/approval",
                "form_id" => Input::get('formId'),    //what is formId


                "data" => array(
                    "keyword1" => array(
                        "value" =>'已审批',
                        "color" => "#173177",
                    ),
                    "keyword2" => array(
                        "value" => config('yueche.CarType')[(int)$order->type],
                        "color" => "#173177",
                    ),
                    "keyword3" => array(

                        "value" => $order->employee->name,
                        "color" => "#173177",
                    ),
                    "keyword4" => array(
                        "value" => $order->usetime,
                        "color" => "#173177",
                    ),
                    "keyword5" => array(
                        "value" => '出发地：'.$originStr .' 目的地：'.$destStr,
                        "color" => "#173177",
                    )
                ),
                "emphasis_keyword" => "keyword1.DATA"
            );

            $jssdk->sendWxMsg($msg);
            return 1;
        }
    }

    /**
     * lingdao order show  the request order ids.
     *
     * @param  null
     * @return \Illuminate\Http\Response
     */
    public function approvalShow()
    {
        $token = Input::get('token');
        $token = Token::where('token', $token)->orderBy('created_at')->first();
        $employee = Employee::where('openid', $token->openid)->first();

        $orders=Collection::make();
        if(($employee->privileges)&&($employee->second_privileges)) {


            $orders11 = Order::where(['manager' => $employee->id,  'state' => 8])->get();
            $orders = $orders->merge($orders11);  //型号订单（自己的订单 state 8->20）
            $employees = Employee::where('depart_id', $employee->depart_id)->get();
            foreach ($employees as $employeeChild) {

                $ordersToAdd = Order::where(['user_id' => $employeeChild->id, 'state' => 10])->where('type', '!=', 1)->get();    //待审批的订单 10->20
                $ordersToAdd1 = Order::where(['user_id' => $employeeChild->id, 'state' => 9])->get();  //型号订单                                 9->20

                $orders = $orders->merge($ordersToAdd);
                $orders = $orders->merge($ordersToAdd1);
            }
            //获取已审批订单（通过获取approve_logs里的u_id来获取order_id
            $approve_logs = Approve_log::where('u_id', $employee->id)->get();
            foreach ($approve_logs as $approve_log) {
                $ordersToAdd = Order::find($approve_log->order_id);
                $orders = $orders->push($ordersToAdd);   //push用来添加单个item  merge用来合并两个集合
            }


//            $orders11 = Order::where(['manager' => $employee->id,  'state' => 8])->get();
//
//            $orders = $orders->merge($orders11);  //型号订单（自己的订单 state 8->20）

        }elseif($employee->privileges){
            //获取待审批的订单（通过获取相同部门的员工的未审批订单来获得）

            $employees=Employee::where('depart_id',$employee->depart_id)->get();
            foreach($employees as $employeeChild){
//                if($employee->depart_id==14){   //科研市场处领导才可以看下属调度的订单
//                    $ordersToAdd = Order::where(['user_id' => $employeeChild->id, 'state' => 10])->get();    //待审批的订单
//                }else{            //非科研市场处
//                    $ordersToAdd = Order::where(['user_id' => $employeeChild->id, 'state' => 10])->where('type', '!=', 1)->get();    //待审批的订单
//                }

                $ordersToAdd = Order::where(['user_id' => $employeeChild->id, 'state' => 10])->where('type', '!=', 1)->get();    //待审批的订单
                $ordersToAdd1= Order::where(['user_id' => $employeeChild->id, 'state' => 9 ])->get();  //型号订单

                $orders=$orders->merge($ordersToAdd);
                $orders=$orders->merge($ordersToAdd1);
            }
            //获取已审批订单（通过获取approve_logs里的u_id来获取order_id
            $approve_logs=Approve_log::where('u_id',$employee->id)->get();
            foreach($approve_logs as $approve_log){
                $ordersToAdd=Order::find($approve_log->order_id);
                $orders=$orders->push($ordersToAdd);   //push用来添加单个item  merge用来合并两个集合
            }
        }elseif($employee->second_privileges){
            $orders=Order::where(['manager'=>$employee->id,'type'=>1])->get();
            //$orders=Order::where(['manager'=>$employee->id,'type'=>1])->where('user_id','!=',$employee->id)->get();
        }else{

        }





        //若order为null ,将此order提出数组
        $orders=$orders->reject(function($order)
        {
            return $order==null;
        });

        $orders=$orders->filter(function($order){
            $fromDate = Carbon::now()->subWeeks(2); // or ->format(..)
            return $order->created_at>=$fromDate;
        });

        //将ordrs按照创建时间的降序排列
        $orders=$orders->sortBy(function($order)
        {
           return $order->created_at;
        })->reverse();
       // $orders=$orders->where('state','!=',51)->where('state','!=',0);
       // $orders=$orders->where('state','0');
        $ordersToArray=$orders->map(function($order){
            return collect($order->toArray())->only(['id','usetime','passenger','mobilephone','reason','state','origin','destination','selected'])->all();
        });
        return json_encode($ordersToArray);
    }

    /**
     * review the request order ids.
     *
     * @param  null
     * @return \Illuminate\Http\Response
     */
    public function review()
    {
        $token = Input::get('token');
        $token = Token::where('token', $token)->orderBy('created_at')->first();
        $employee = Employee::where('openid', $token->openid)->first();


        $orderIdToReview=json_decode(Input::get('itemId'));
        $performanceUse=PerformanceUse::where('orderID',$orderIdToReview)->first();
        $performanceUse->reviewText=Input::get('remark');
        $performanceUse->score_of_order=Input::get('reviewScore');
        $performanceUse->save();

        $order=Order::find($orderIdToReview);
        $origin=$order->getOriginAttribute();
        $dest=$order->getDestinationAttribute();
        $originStr=implode('/',$origin);
        $destStr=implode('/',$dest);
        $jssdk = new JSSDK(config('yueche.AppID'), config('yueche.AppSecret'));
        $msg = array(
            "touser" =>$employee->openid,
            "template_id" => config('yueche.OrderMsg'),
            'page'=>"pages/order/order",
            "form_id" => Input::get('formId'),
            "data" => array(
                "keyword1" => array(
                    "value" =>'已评价',
                    "color" => "#173177",
                ),
                "keyword2" => array(
                    "value" => $order->reason,
                    "color" => "#173177",
                ),
                "keyword3" => array(

                    "value" => $order->usetime,
                    "color" => "#173177",
                ),
                "keyword4" => array(
                    "value" =>$originStr,
                    "color" => "#173177",
                ),
                "keyword5" => array(
                    "value" =>$destStr,
                    "color" => "#173177",
                )
            ),
            "emphasis_keyword" => "keyword1.DATA"
        );

        $jssdk->sendWxMsg($msg);
        return 0;
    }

    /**
     * admin order show  the request order ids.
     *
     * @param  null
     * @return \Illuminate\Http\Response
     */
    public function adminShow()
    {

        $a=1;
        $b=2;
        $c=3;
        $token = Input::get('token');
        $token = Token::where('token', $token)->orderBy('created_at')->first();
        $employee = Employee::where('openid', $token->openid)->first();
        $tabnum=(Int)Input::get('tabNum');

        $orders=Collection::make();
        if($employee->admin) {
           //$orders=Order::where('state','>=','20')->get();
            $fromDate = Carbon::now()->subWeeks(2); // or ->format(..)
            $tillDate = Carbon::now();
            //$orders=Order::selectRaw('date(created_at) as date, COUNT(*) as count')->whereBetween( DB::raw('date(created_at)'), [$fromDate, $tillDate] )->get();
            $orders0=Order::where('created_at','>=',$fromDate  )
                ->where('created_at','<=',$tillDate)
                ->where('state','>=','20')
                ->get();
            $orders1=Order::where('created_at','>=',$fromDate  )
                ->where('created_at','<=',$tillDate)
                ->where('state','1')
                ->get();
            $orders=$orders->merge($orders0);
            $orders=$orders->merge($orders1);
        }

        //若order为null ,将此order提出数组
        $orders=$orders->reject(function($order)
        {
            return $order==null;
        });

        $tabNums=array(0,0,0,0,0);
        foreach($orders as $key=>$order){
            if($order!=null){
                $state=$order->state;
                if($state==20){
                    $tabNums[0]++;
                    if($tabnum!=0){
                        unset($orders[$key]);
                    }
                }else if($state>20&&$state<39){
                    $tabNums[1]++;
                    if($tabnum!=1){
                        unset($orders[$key]);
                    }
                }else if($state>=39&&$state<=42){
                    $tabNums[2]++;
                    if($tabnum!=2){
                        unset($orders[$key]);
                    }
                }else if($state==43||$state==44){
                    $tabNums[3]++;
                    if($tabnum!=3){
                        unset($orders[$key]);
                    }
                }else if($state>44||$state==1) {
                    $tabNums[4]++;
                    if($tabnum!=4){
                        unset($orders[$key]);
                    }
                }else{
                }
            }
        }

//        //若order为null ,将此order提出数组
//        if($tabnum==0){
//            $orders=$orders->filter(function($order)
//            {
//                return $order->state==20;
//            });
//        }else if($tabnum==1){
//            $orders=$orders->filter(function($order)
//            {
//                return $order==null;
//            });
//        }else if($tabnum==2){
//            $orders=$orders->filter(function($order)
//            {
//                return $order==null;
//            });
//        }else if($tabnum==3){
//            $orders=$orders->filter(function($order)
//            {
//                return $order==null;
//            });
//        }else if($tabnum==4){
//            $orders=$orders->filter(function($order)
//            {
//                return $order==null;
//            });
//        }



        //将ordrs按照创建时间的降序排列
        $orders=$orders->sortBy(function($order)
        {
            return $order->updated_at;
        })->reverse();



   //     $ordersToArray = $orders->toArray();
            $ordersToArray=$orders->map(function($order){
            return collect($order->toArray())->only(['id','usetime','passenger','mobilephone','reason','state','origin','destination','selected','company_name','order_numb'])->all();
        });

            $data= array();
            $data['data']=$ordersToArray;


            $data['tabNums']=$tabNums;
        //$ordersToJson=$ordersToArray->toJson();
        return json_encode($data);



        return json_encode($ordersToArray);
    }

    /**
     * approval the request order ids.
     *
     * @param  null
     * @return \Illuminate\Http\Response
     */
    public function adminApproval()
    {
        $token = Input::get('token');
        $token = Token::where('token', $token)->orderBy('created_at')->first();
        $employee = Employee::where('openid', $token->openid)->first();

        $orderId=json_decode(Input::get('approvalId'));

        $order=Order::where('id',$orderId)->first();





        //未审批则返回1
        $approval=json_decode(Input::get('approval'));
        $remark=null;
        if($approval==true) {

            $order->state = 50;
            $order->save();
            $opinion=true;

        }else{

            $order->state =43;
            $order->save();
            $remark=Input::get('remark');
            $opinion=false;
        }
        //$approveLogs=Approve_log::where('order_id',$order->id)->where("approve_node",43)->get();
            $approveLog=Approve_log::where('order_id',$order->id)->where("approve_node",43)->first();


            if($approveLog!=null) {
                $approveLog->approve_node = $order->state;
                $approveLog->u_id = $employee->id;
                $approveLog->opinion = $opinion;
                $approveLog->remark = $remark;
                $approveLog->save();
            }else{
//                $approveLog = new Approve_log(['order_id' => $orderId, 'approve_node' => $order->state, 'u_id' => $employee->id, 'opinion' => $opinion, 'remark' => $remark]);
//                $approveLog->save();

                $approveLog = new Approve_log(array('approve_node' => $order->state, 'u_id' => $employee->id, 'opinion' => $opinion, 'remark' => $remark));
                $order->approvelog()->save($approveLog);


            }

//        if($order->state==43){
//            $approveLog=Approve_log::where('order_id',$order->id)->first();
//            $approveLog->approve_node = $order->state;
//            $approveLog->u_id          = $employee->id;
//            $approveLog->opinion          =$opinion;
//            $approveLog->remark        =$remark;
//            $approveLog->save();
//        }else if($order->state==44) {
//            $approveLog = new Approve_log(['order_id' => $orderId, 'approve_node' => $order->state, 'u_id' => $employee->id, 'opinion' => $opinion, 'remark' => $remark]);
//            $approveLog->save();
//        }



            $origin=$order->getOriginAttribute();
            $dest=$order->getDestinationAttribute();
            $originStr=implode('/',$origin);
            $destStr=implode('/',$dest);
            $jssdk = new JSSDK(config('yueche.AppID'), config('yueche.AppSecret'));

            $msg = array(
                "touser" => $employee->openid,
                "template_id" => config('yueche.ApprovalMsg'),
                'page'=>"pages/approval/approval",
                "form_id" => Input::get('formId'),    //what is formId


                "data" => array(
                    "keyword1" => array(
                        "value" =>'已审批',
                        "color" => "#173177",
                    ),
                    "keyword2" => array(
                        "value" => config('yueche.CarType')[(int)$order->type],
                        "color" => "#173177",
                    ),
                    "keyword3" => array(

                        "value" => $order->employee->name,
                        "color" => "#173177",
                    ),
                    "keyword4" => array(
                        "value" => $order->usetime,
                        "color" => "#173177",
                    ),
                    "keyword5" => array(
                        "value" => '出发地：'.$originStr .' 目的地：'.$destStr,
                        "color" => "#173177",
                    )
                ),
                "emphasis_keyword" => "keyword1.DATA"
            );

            $jssdk->sendWxMsg($msg);
            return 1;

    }

    /**
     * admin order show  the request order ids.
     *
     * @param  null
     * @return \Illuminate\Http\Response
     */
    public function adminAppoint()
    {
        $token = Input::get('token');
        $token = Token::where('token', $token)->orderBy('created_at')->first();
        $employee = Employee::where('openid', $token->openid)->first();

        $orderToAppoint=json_decode(Input::get('orderIdToSendStr'));
        $companyToAppoint=(int)(Input::get('companyIdToSend'));

        if(count($orderToAppoint)>0){
        foreach($orderToAppoint as $order){
            $order = Order::find($order);
            $order->state = 20 + $companyToAppoint;
            $order->save();
            }
        }
        return 0;
    }

    /**
     * admin order show  the request order ids.
     *
     * @param  null
     * @return \Illuminate\Http\Response
     */
    public function adminCompete()
    {
        $token = Input::get('token');
        $token = Token::where('token', $token)->orderBy('created_at')->first();
        $employee = Employee::where('openid', $token->openid)->first();

        $orderToAppoint=json_decode(Input::get('orderIdToSendStr'));
        if(count($orderToAppoint)>0){
            foreach($orderToAppoint as $order){
                $order = Order::find($order);
                $order->state = 35;
                $order->save();
            }
        }
        return 0;
    }

    /**
     * admin order show  the request order ids.
     *
     * @param  null
     * @return \Illuminate\Http\Response
     */
    public function adminRetreat()
    {
        $token = Input::get('token');
        $token = Token::where('token', $token)->orderBy('created_at')->first();
        $employee = Employee::where('openid', $token->openid)->first();

        $orderToAppoint=json_decode(Input::get('orderIdToSendStr'));
        if(count($orderToAppoint)>0){
            foreach($orderToAppoint as $order){
                $order = Order::find($order);
                $order->state = 20;
                $order->save();
            }
        }
        return 0;
    }

    /**
     * company order show  the request order ids.
     *
     * @param  null
     * @return \Illuminate\Http\Response
     */
    public function companyShow()
    {
        $token = Input::get('token');
        $token = Token::where('token', $token)->orderBy('created_at')->first();
        $company=Company::where('openid',$token->openid)->first();

        $orders=Collection::make();
        //获取指定订单
        $state2X=20 +$company->id;
        $orders2X=Order::where('state','=',$state2X)->get();
        $orders=$orders->merge($orders2X);

        //获取抢单
        $order35 = Order::where('state',35)->get();
        $orders = $orders->merge($order35);





        //获取所有接单之后的订单
        $ordersIdBelongsToCompanyAcceptLog=Accept_log::where('u_id',$company->id)->get();
        foreach($ordersIdBelongsToCompanyAcceptLog as $orderIdAcceptLog){
            $orderSingle=Order::find($orderIdAcceptLog->order_id);
            $orders->push($orderSingle);
        }


        //若order为null ,将此order提出数组
        $orders=$orders->reject(function($order)
        {
            return $order==null;
        });
        $orders=$orders->filter(function($order){
            $fromDate = Carbon::now()->subWeeks(2); // or ->format(..)
            return $order->created_at>=$fromDate;
        });
        //将ordrs按照创建时间的降序排列
        $orders=$orders->sortBy(function($order)
        {
            return $order->created_at;
        })->reverse();


        $ordersToArray=$orders->map(function($order){
            return collect($order->toArray())->only(['id','usetime','passenger','mobilephone','reason','state','origin','destination','selected','van_type'])->all();
        });
        return json_encode($ordersToArray);
    }

    /**
     * company order show  the request order ids.
     *
     * @param  null
     * @return \Illuminate\Http\Response
     */
    public function companyAdminValid()
    {
        $token = Input::get('token');
        $token = Token::where('token', $token)->orderBy('created_at')->first();
        $company=Company::where('openid',$token->openid)->first();
        $valid=true;
        if($company!=null){
            $valid=true;
        }else{
            $valid=false;
        }


        return json_encode($valid);
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
