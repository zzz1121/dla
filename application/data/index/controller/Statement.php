<?php
namespace app\index\controller;
use think\Controller;
class Statement extends Controller
{
    public function index()
    {
        if(request()->isGet()){
//           注册总人数
            $user_count=model('user')
                ->count();
//           交易总金额
            $condition_money['order_status'] = 'SUCCESS';
            $all_money=model('orders')->where($condition_money)->field('sum(order_money) as sum')->find();
//            交易总笔数
            $condition_num['order_status'] = "SUCCESS";
            $all_num=model('orders')->where($condition_num)->count();
//            分润总金额
            $all_comm=model('commission')->field('sum(commission_money) as sum')->find();

            $dateStr = date('Y-m-d', time()); //当前时间
            $timestamp0 = strtotime($dateStr); //0点时间戳
            $timestamp24 = strtotime($dateStr) + 86400;//24点时间戳

//           今日注册总人数
            $condition['reg_time'] = array('between',array($timestamp0, $timestamp24));
            $user_count_now=model('user')->where($condition)->count();

//           今日交易总金额
             $condition_money_now['order_status'] = 'SUCCESS';
             $condition_money_now['order_time'] =  array('between',array($timestamp0, $timestamp24));
             $all_money_now=model('orders')->where($condition_money_now)->field('sum(order_money) as sum')->find();

            $condition_num_now['order_status'] = "SUCCESS";
            $condition_num_now['order_time'] =  array('between',array($timestamp0, $timestamp24));
            $all_num_now=model('orders')->where($condition_num_now)->count();


            //  今日分润总金额
            $condition_comm_now['commission_time'] = array('between',array($timestamp0, $timestamp24));
            $all_comm_now=model('commission')->where($condition_comm_now)->field('sum(commission_money) as sum')->find();

            $this->assign('user_count',$user_count);
            $this->assign('all_money',$all_money['sum']);
            $this->assign('all_num',$all_num);
            $this->assign('all_comm',$all_comm['sum']);
            $this->assign('user_count_now',$user_count_now);
            $this->assign('all_money_now',$all_money_now['sum']);
            $this->assign('all_num_now',$all_num_now);
            $this->assign('all_comm_now',$all_comm_now['sum']);

            return $this->fetch('statistics');
        }
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
