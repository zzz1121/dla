<?php
namespace app\index2\controller;
use think\Controller;
use think\Db;
class Statement extends Online
{
    public function index()
    {
        $user=model('user')
            ->where('user_id',$this->user_id)
            ->find();

    //           交易总金额
        $condition_money['user_id'] = $this->user_id;
        $data['all_money']=model('commission')
            ->sum('commission_money');
        $order_total=model('commission')
            ->where('user_id',$this->user_id)
            ->sum('order_money');

        //总分润量
        $data['all_money']=model('commission')
            ->sum('commission_money');
        $comm_total=model('commission')
            ->where('user_id',$this->user_id)
            ->sum('commission_money');



        $this->assign('user',$user);
        $this->assign('order_total',$order_total);
        $this->assign('comm_total',$comm_total);
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
