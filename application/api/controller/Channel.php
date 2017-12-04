<?php
namespace app\api\controller;
use think\Validate;
use think\Db;
use \think\Request;
use alisms\sms\Smsdemo;
class Channel extends Online
{
    public function index()
    {
        if($this->online['user_type']==1 && $this->online['role_id']>1){
            $data=Db::table('role_settle')
                ->alias('a')
                ->join('rate b','a.pay_id=b.pay_prot_id')
                ->where('b.status',1)
                ->where('a.role_id',$this->online['role_id'])
                ->field('a.settle_rate,a.extra_rate,b.pay_prot_id,b.rate_type,b.pay_name,b.min_charge,b.start_time,b.end_time,b.min_money,b.max_money')
                ->select();
        }elseif($this->online['user_type']==1 && $this->online['role_id']==1 ){
            $data = Db::table('rate')
                ->field('pay_prot_id,rate_type,pay_name,settle_rate,extra_rate,min_charge,start_time,end_time,min_money,max_money')
                ->where('status',1)
                ->select();

        }elseif($this->online['is_merchant']==2){
            $data=Db::table('group_settle')
                ->alias('a')
                ->join('rate b','a.pay_id=b.pay_prot_id')
                ->where('group_id',$this->online['user_id'])
                ->where('user_lv',1)
                ->where('b.status',1)
                ->field('a.settle_rate,a.extra_rate,b.pay_prot_id,b.rate_type,b.pay_name,b.min_charge,b.start_time,b.end_time,b.min_money,b.max_money')
                ->select();
        }elseif($this->online['user_type']==2 && $this->online['group_up']==$this->online['group_id']){
            $data=Db::table('group_settle')
                ->alias('a')
                ->join('rate b','a.pay_id=b.pay_prot_id')
                ->where('group_id',$this->online['group_id'])
                ->where('user_lv',2)
                ->where('b.status',1)
                ->field('a.settle_rate,a.extra_rate,b.pay_prot_id,b.rate_type,b.pay_name,b.min_charge,b.start_time,b.end_time,b.min_money,b.max_money')
                ->select();
        }elseif($this->online['user_type']==2 ){
            $data=Db::table('group_settle')
                ->alias('a')
                ->join('rate b','a.pay_id=b.pay_prot_id')
                ->where('group_id',$this->online['group_id'])
                ->where('user_lv',3)
                ->where('b.status',1)
                ->field('a.settle_rate,a.extra_rate,b.pay_prot_id,b.rate_type,b.pay_name,b.min_charge,b.start_time,b.end_time,b.min_money,b.max_money')
                ->select();
        }

        if(empty($data)){
            $this->returnMsg['message']='通道为空';
            return $data;
        }
        foreach($data as $key=>$val){
            $rate=db('user_settle')
                ->where('user_id',$this->online['user_id'])
                ->where('pay_id',$val['pay_prot_id'])
                ->find();
            if(!empty($rate)){
                $data[$key]['settle_rate']=$rate['settle_rate'];
                $data[$key]['extra_rate']=$rate['extra_rate'];
            }
        }
		
		//$change_data[]=$data[1];
		//$change_data[]=$data[2];
		//$change_data[]=$data[0];
		$change_data=$data;


        $this->returnMsg['status']=200;
        $this->returnMsg['message']='请求成功';
        $this->returnMsg['data']=$change_data;
        return $this->returnMsg;
    }


}