<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
class Statement extends Online
{
    public function index()
    {
        $start_time=input('start_time','2017-01-01');
        $end_time=input('end_time',date('Y-m-d',time()+86400));
        if(empty($start_time)){
            $start_time='2017-01-01';
        }
        if(empty($end_time)){
            $end_time=date('Y-m-d',time()+86400);
        }

        $data['start_time']=$start_time;
        $data['end_time']=$end_time;
        if($end_time<$start_time){
            $time_res=$start_time;
            $start_time=$end_time;
            $end_time=$time_res;
            $data['start_time']=$end_time;
            $data['end_time']=$start_time;
        }


    //           交易总金额
        $condition_money['order_status'] = 'SUCCESS';
        $condition_money['order_time'] =  array('between',array(strtotime($start_time), strtotime($end_time) ));
        $data['all_money']=model('orders')
            ->where($condition_money)
            ->whereOr('order_status',"PROCESSING")
            ->sum('order_money');

        //平台成本
        $condition_money['order_status'] = 'SUCCESS';
        $condition_money['order_time'] =  array('between',array(strtotime($start_time), strtotime($end_time) ));
        $data['all_sye_cost']=model('orders')
            ->where($condition_money)
            ->whereOr('order_status',"PROCESSING")
            ->sum('sye_cost');


        //          到账总金额
        $condition_money['order_status'] = 'SUCCESS';
        $condition_money['order_time'] =  array('between',array(strtotime($start_time), strtotime($end_time) ));
        $data['all_arrival_amount']=model('orders')
            ->where($condition_money)
            ->whereOr('order_status',"PROCESSING")
            ->sum('arrival_amount');

        //手续费总额
        $condition_money['order_status'] = 'SUCCESS';
        $condition_money['order_time'] =  array('between',array(strtotime($start_time), strtotime($end_time) ));
        $data['all_cover_charge']=model('orders')
            ->where($condition_money)
            ->whereOr('order_status',"PROCESSING")
            ->sum('cover_charge');

        //服务费费总额
        $condition_money['order_status'] = 'SUCCESS';
        $condition_money['order_time'] =  array('between',array(strtotime($start_time), strtotime($end_time) ));
        $data['all_service_charge']=model('orders')
            ->where($condition_money)
            ->whereOr('order_status',"PROCESSING")
            ->sum('service_charge');

    //            交易总笔数
        $condition_num['order_status'] = "SUCCESS";
        $condition_num['order_time'] =  array('between',array(strtotime($start_time), strtotime($end_time) ));

        $data['all_num']=model('orders')
            ->where($condition_num)
            ->whereOr('order_status',"PROCESSING")
            ->count();





    //            分润总金额
        $commission['commission_time']=array('between',array(strtotime($start_time), strtotime($end_time) ));
        $data['all_comm']=model('commission')
            ->where($commission)
            ->field('sum(commission_money) as sum')
            ->sum('commission_money');
        $data['all_comm']=floor($data['all_comm']*100)/100;

        //已提现分润
        $pay_data['pay_time']=array('between',array(strtotime($start_time), strtotime($end_time) ));
        $pay_data['pay_status']="PAY_SUCCESS";
        $data['yet_comm']=Db::table('pay_orders')
            ->where($pay_data)
            ->sum('pay_money');

        //已提现分润
        $whree_data['pay_time']=array('between',array(strtotime($start_time), strtotime($end_time) ));
        $whree_data['pay_status']="PAY_SUBMIT";
        $data['being_comm']=Db::table('pay_orders')
            ->where($whree_data)
            ->sum('pay_money');

        //已提现分润手续费
        $data['service_comm']=Db::table('pay_orders')
            ->where($pay_data)
			->whereOr('pay_status','PAY_SUBMIT')
            ->sum('pay_service');

        //未提现分润
        $data['not_comm']=model('user')
//            ->where($condition_user)
            ->sum('balance');

        //已提现笔数
        $data['yet_count']=Db::table('pay_orders')
            ->where($pay_data)
            ->count();



        $condition_user['reg_time']=array('between',array(strtotime($start_time), strtotime($end_time) ));
        //           注册总人数
        $data['user_count']=model('user')
            ->where($condition_user)
            ->count();


        $condition_user['card_status']=1;
        //已认证用户
        $data['verify_count']=model('user')
            ->where($condition_user)
            ->count();

        $condition_user['card_status']=0;
        //未认证用户
        $data['not_verify_count']=model('user')
            ->where($condition_user)
            ->count();

        $dateStr = date('Y-m-d', time()); //当前时间
        $timestamp0 = strtotime($start_time); //0点时间戳
        $timestamp24 = strtotime($end_time) + 86400;//24点时间戳

    //           今日注册总人数
        $condition['reg_time'] = array('between',array($timestamp0, $timestamp24));
        $data['user_count_now']=model('user')->where($condition)->count();

    //           今日交易总金额
         $condition_money_now['order_status'] = 'SUCCESS';
         $condition_money_now['order_time'] =  array('between',array($timestamp0, $timestamp24));

        $all_money_now=0;
        $data['all_money_now']=model('orders')->where($condition_money_now)->field('sum(order_money) as sum')->sum('order_money');

        $condition_num_now['order_status'] = "SUCCESS";
        $condition_num_now['order_time'] =  array('between',array($timestamp0, $timestamp24));

        $all_num_now=0;
        $data['all_num_now']=model('orders')->where($condition_num_now)->count();


        //  今日分润总金额
        $condition_comm_now['commission_time'] = array('between',array($timestamp0, $timestamp24));
        $data['all_comm_now']=model('commission')->where($condition_comm_now)->field('sum(commission_money) as sum')->sum('commission_money');



        $this->assign('data',$data);
        return $this->fetch('index');
    }
    //判断两天是否是同一天
    function isDiffDays($last_date,$this_date){

        if(($last_date['year']===$this_date['year'])&&($this_date['yday']===$last_date['yday'])){
            return FALSE;
        }else{
            return TRUE;
        }
    }

}
