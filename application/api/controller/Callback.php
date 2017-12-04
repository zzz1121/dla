<?php
/**
 * @author qianwang-zlq
 * @version 2017-06-04
 *  api 操作判定父类
 */

namespace app\api\controller;
use think\Db;
class Callback extends Index
{
//   protected $sbt_url="http://testapi.shangfudata.com";

    public function index(){

        if(request()->isPost()){
            $post_data=input('post.');
            $this->log_write('shangfu_log',$post_data);

            $mcht_data=db('user_pay_data')
                ->where('mcht_no',$post_data['mch_id'])
                ->find();
            $user=model('user')
                ->where('user_id',$mcht_data['user_id'])
                ->find();
            //$this->returnMsg['messageaaa']=$user;
            //$this->returnMsg['data']=$post_data;
            //$this->returnMsg['da12ta']=strtolower($this->sbt_sign($post_data,$user['secretKey'])['sign']);
            // 写入的字符




            //echo fwrite($fh, $sign);    // 输出：6

            if(empty($user)){
                $this->returnMsg['message']="用户错误";
                $this->log_write('shangfu_log','用户错误');
                return $this->returnMsg;
            }
			vendor('shangfupay.shangfupay');
            $pay=new \Shangfupay();
			$sign=$pay->sbt_sign($post_data,$mcht_data['secret_key']);
			

            if ( empty($post_data) || empty($post_data['sign']) || strtoupper($post_data['sign']) !== $sign['sign']) {
                $this->returnMsg['message']="签名错误";
                $this->log_write('shangfu_log','签名错误');
                return $this->returnMsg;
            }


            $total_fell=$post_data['total_fee']/100;
            $order=model('orders')
                ->alias('a')
                ->join('user b','a.user_id=b.user_id')
                ->where('a.order_id',$post_data['out_trade_no'])

                //->where('a.order_money',$total_fell)
                //->fetchSql(true)
                ->find();

            if(empty($order)){
                $this->returnMsg['message']="订单未找到";
                $this->log_write('shangfu_log','订单未找到');
                return $this->returnMsg;
            }
            $res=db('commission')
                ->where('order_id',$order['order_id'])
                ->find();
            if(!empty($res)){
                $this->returnMsg['message']="订单已处理";
                $this->log_write('shangfu_log','订单已处理');
                return 'SUCCESS';
            }
            if( $order['order_status']!=="PROCESSING" || $order['order_status']=="SUCCESS"){
                $this->returnMsg['message']="订单已处理";
                $this->log_write('shangfu_log','订单已处理');
                return 'SUCCESS';
            }


            if($post_data['trade_state']=="SUCCESS"){
//                return $user;
//                return $this->fenrun($order['order_id'],$user['user_id']);

                $res=$this->fenrun($order,$user);
                if(!$res){
                    $this->returnMsg['message']="写入失败";
                    $this->log_write('shangfu_log','写入失败');
                    return $this->returnMsg;
                }
            }
            $update_data['order_status']=$post_data['trade_state'];
            $update_data['out_trade_no']=$post_data['trade_no'];

            $res=model('orders')
                ->where('order_id',$post_data['out_trade_no'])
                ->update($update_data);
            if($res==0){
                $this->returnMsg['message']="写入失败";
                $this->log_write('shangfu_log','写入失败');
                return $this->returnMsg;
            }


            $this->log_write('shangfu_log','写入成功');

            $this->returnMsg['message']="写入成功";
            $this->returnMsg['status']=200;

            return 'SUCCESS';

        }
    }



    //汇享支付后台回调
    public function pxback()
    {
        $post_data=input('post.');
        $this->log_write('px_back',$post_data);

        Vendor('hxpay.huixiangPay');
        $obj=new \PayAction();
        $res=$obj->notify($post_data);
        if($res!==200){
            $this->log_write('px_back','签名失败');
            return;
        }
        if($post_data['resp_code']=='PAY_SUCCESS'){
            $status="SUCCESS";
        }elseif($post_data['resp_code']=='PAY_FAILURE'){
            $status="FAIL";
        }
        $order=Db::table('orders')
            ->where('order_id',$post_data['client_trans_id'])
            ->find();
        if($order['order_status']=="SUCCESS" || $order['order_status']=="FAIL"){
            $this->log_write('px_back','订单状态已修改');
            return "订单状态已修改";
        }
        $user=model('user')
            ->where('user_id',$order['user_id'])
            ->find();
        if($status=="SUCCESS"){
            $res=$this->fenrun($order,$user);
            if(!$res){
                $this->returnMsg['message']="写入失败";
                $this->log_write('shangfu_log','写入失败');
                return $this->returnMsg;
            }
        }
        $res=model('orders')
            ->where('order_id',$order['order_id'])
            ->setField('order_status',$status);

        $commission=Db::table('commission')
            ->where('order_id',$order['order_id'])
            ->find();
        if(!empty($commission)){
            $this->log_write('px_back','已入库');
            return 'SUCCESS';
        }






       return 'SUCCESS';
    }


    //汇享代付订单请求更新
    public function pay_update(){
        $list=Db::table('pay_orders')
            ->where('pay_status',"PAY_SUBMIT")
            ->select();
        Vendor('hxpay.huixiangPay');
        $obj=new \PayAction();
        foreach($list as $val){
            $res=$obj->get_order_status($val['pay_order_id']);
            $this->log_write('call_hx_back',$res);

            $db_res=Db::table('pay_orders')
                ->where('id',$val['id'])
                ->update(['pay_status'=>$res['data']['resp_code']]);
            if($db_res && $res['data']['resp_code']=="PAY_FAILURE"){
                Db::startTrans();
                $balance=model('user')
                    ->where('user_id',$val['user_id'])
                    ->value('balance');
                $user_res=model('user')
                    ->where('user_id',$val['user_id'])
                    ->update(['balance'=>$balance+$val['pay_money']+2]);
                if(!$user_res){
                    Db::rollback();
                }
                Db::commit();
            }


        }
        $this->returnMsg['message']="请求".count($list).'条';
        $this->returnMsg['status']=200;
        return $this->returnMsg;

    }

}
