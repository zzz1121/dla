<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\api;
class Callback extends Controller
{


    public function index()
    {
        $post_data=input('post.');
        $order_id=$post_data['orderNo'];
        $order=model('orders')
            ->where('order_id',$order_id)
            ->find();
        if($order=="NOTPAY"){
            $res=model('orders')
                ->where('order_id',$order_id)
                ->update(['order_status'=>'PROCESSING']);
            $order_status='PROCESSING';
        }else{
            $order_status=$order['order_status'];
        }


        $from_bank_name=model('user_card')
            ->where('card_id',$order['from_card'])
            ->field('bank_name')
            ->find()['bank_name'];
        $bank_name=model('user_card')
            ->where('card_id',$order['to_card'])
            ->field('bank_name')
            ->find()['bank_name'];
        $data=[
            'from_card'=>substr($order['from_card'],-4),
            'from_bank'=>$from_bank_name,
            'to_card'=>substr($order['to_card'],-4),
            'to_bank'=>$bank_name,
			'order_money'=>$order['order_money'],
			'arrival_amount'=>$order['arrival_amount'],
            'order_status'=>$order_status,
            'time'=>date("Y-m-d H:i:s",$order['order_time'])
        ];


        $this->assign('data',$data);
        return $this->fetch();
    }
    public function index2(){
        $post_data=input('post.');
        dump($post_data);
        return $this->fetch();
    }



}
