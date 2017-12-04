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

            $user=model('user')
                ->where('mcht_no_1',$post_data['mch_id'])
                ->find();

            //$this->returnMsg['messageaaa']=$user;
            //$this->returnMsg['data']=$post_data;
            //$this->returnMsg['da12ta']=strtolower($this->sbt_sign($post_data,$user['secretKey'])['sign']);
            // 写入的字符


            $sign="\n".strtolower($this->sbt_sign($post_data,config('sbt_key_1'))['sign']);


            //echo fwrite($fh, $sign);    // 输出：6


            if(empty($user)){
                $this->returnMsg['message']="用户错误";
                $this->log_write('shangfu_log','用户错误');
                return $this->returnMsg;
            }


            if ( empty($post_data) || empty($post_data['sign']) || $post_data['sign'] !== strtolower($this->sbt_sign($post_data,$user['secretKey_1'])['sign'])) {
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
            //$this->returnMsg['ssss']=$order;
            //return $this->returnMsg;
            if(empty($order)){
                $this->returnMsg['message']="订单未找到";
                $this->log_write('shangfu_log','订单未找到');
                return $this->returnMsg;
            }
            if($order['order_status']!=="PROCESSING"){
                $this->returnMsg['message']="订单已处理";
                $this->log_write('shangfu_log','订单已处理');
                return $this->returnMsg;
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

            if($post_data['trade_state']=="SUCCESS"){
                $order['order_money']=(int)$order['order_money'];
                $sye_rate=Db::table('rate')
                    ->where('id',1)
                    ->find();
                if(empty($sye_rate)){

                    $this->log_write('shangfu_log','系统费率未找到');
                    return 401;
                }
                $this->sye_rate=$sye_rate; //获取当前平台手续费率

                Db::table('commission')
                    ->insert([
                        'order_id'=>$order['order_id'],
                        'commission_money'=>0,
                        'order_money'=>$order['order_money'],
                        'user_id'=>$user['user_id'],
                        'commission_time'=>time()
                    ]);
                $res=Db::table('user')
                    ->where("user_id",$user['user_id'])
                    ->update([
                        'integral'=>$user['integral']+$order['order_money']
                    ]);

                if(!empty($user['merchant_id'])){
                    if($user['user_type']==2){
                        // 代理商抽成提取
                        $parent=Db::table('user')
                            ->alias('a')
                            ->where('a.user_id',$user['group_up'])
//                        ->field('a.balance,a.merchant_id,a.integral,user_id')
                            ->find();
                    }else{
                        // 代理商抽成提取
                        $parent=Db::table('user')
                            ->alias('a')
                            ->where('a.user_id',$user['merchant_id'])
//                        ->field('a.balance,a.merchant_id,a.integral,user_id')
                            ->find();
                    }



                    if($user['user_type']==2){

                        $balance=bcmul( $order['order_money'],($user['settle_rate']- $parent['settle_rate']),2);
                        $this->log_write('settel',$order['order_money']);
                        $this->log_write('settel',$user['settle_rate']- $parent['settle_rate']);
                        $this->log_write('settel',$balance);

                    }else{
                        $balance=bcmul( $order['order_money'], $this->sye_rate['parent'],2 );
                    }

//                    $balance=floor( $order['order_money'] * 100 * $this->sye_rate['parent'] )/100;
                    $res=Db::table('user')
                        ->where("user_id",$parent['user_id'])
                        ->update([
                            'balance'=>$parent['balance']+$balance,
                            'integral'=>$parent['integral']+$order['order_money']
                        ]);

                    //$this->returnMsg['parent']=$parent;
                    Db::table('commission')
                        ->insert([
                            'order_id'=>$order['order_id'],
                            'commission_money'=>$balance,
                            'order_money'=>$order['order_money'],
                            'user_id'=>$parent['user_id'],
                            'commission_time'=>time()
                        ]);


                    if(!empty($parent['merchant_id']) && $user['user_type']==1 || $user['group_up']!==$user['group_id'] && $user['user_type']==2){
                        if($user['user_type']==2 && $user['group_up']!==$user['group_id']){
                            $superior=Db::table('user')
                                ->where('user_id',$user['group_id'])
//                            ->field('balance,integral,user_id')
                                ->find();
                        }else{
                            $superior=Db::table('user')
                                ->where('user_id',$parent['merchant_id'])
//                            ->field('balance,integral,user_id')
                                ->find();
                        }



                        if($user['user_type']==2 ){
                            $superior_balance=bcmul( $order['order_money'],($parent['settle_rate']-$superior['settle_rate']),2);
                            $this->log_write('settel1',$order['order_money']);
                            $this->log_write('settel1',$parent['settle_rate']-$superior['settle_rate']);
                            $this->log_write('settel1',$superior_balance);
                        }else{
                            $superior_balance=bcmul( $order['order_money'], $this->sye_rate['parent'] ,2);
                        }
//                        $superior_balance=floor( $order['order_money'] * 100 * $this->sye_rate['superior'] )/100;

                        if(!empty($superior)){

                            $res=Db::table('user')
                                ->where("user_id",$superior['user_id'])
                                ->update([
                                    'balance'=>$superior['balance']+$superior_balance,
                                    'integral'=>$superior['integral']+$order['order_money']
                                ]);
                            //$this->returnMsg['superior22']=$superior;

                            Db::table('commission')
                                ->insert([
                                    'order_id'=>$order['order_id'],
                                    'commission_money'=>$superior_balance,
                                    'order_money'=>$order['order_money'],
                                    'user_id'=>$superior['user_id'],
                                    'commission_time'=>time()
                                ]);
                        }
                    }

                }
            }



            $this->log_write('shangfu_log','写入成功');

            $this->returnMsg['message']="写入成功";
            $this->returnMsg['status']=200;

            return $this->returnMsg;

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
            return;
        }

        $sye_rate=Db::table('rate')
            ->where('id',1)
            ->find();
        if(empty($sye_rate)){

            $this->log_write('px_back','系统费率未找到');
            return 401;
        }
        $this->sye_rate=$sye_rate; //获取当前平台手续费率

        $commission=Db::table('commission')
            ->where('order_id',$order['order_id'])
            ->find();
        if(!empty($commission)){
            $this->log_write('px_back','已入库');
            return 'error';
        }


        $user=Db::table('user')
            ->where('user_id',$order['user_id'])
            ->find();

        Db::table('commission')
            ->insert([
                'order_id'=>$order['order_id'],
                'commission_money'=>0,
                'order_money'=>$order['order_money'],
                'user_id'=>$order['user_id'],
                'commission_time'=>time()
            ]);
        $res=Db::table('user')
            ->where("user_id",$order['user_id'])
            ->update([
                'integral'=>$user['integral']+$order['order_money']
            ]);
        $res=Db::table('orders')
            ->where('order_id',$post_data['client_trans_id'])
            ->update(['order_status'=>$status]);
        if($res==0){
            $this->log_write('px_back','库写入失败');
            return 'error';
        }
        $this->log_write('px_back','库写入成功');
        if($post_data['resp_code']=='PAY_SUCCESS'){


            $this->log_write('px_back','分润写入');
            if(!empty($user['merchant_id'])){
                if($user['user_type']==2){
                    // 代理商抽成提取
                    $parent=Db::table('user')
                        ->alias('a')
                        ->where('a.user_id',$user['group_up'])
//                        ->field('a.balance,a.merchant_id,a.integral,user_id')
                        ->find();
                }else{
                    // 代理商抽成提取
                    $parent=Db::table('user')
                        ->alias('a')
                        ->where('a.user_id',$user['merchant_id'])
//                        ->field('a.balance,a.merchant_id,a.integral,user_id')
                        ->find();
                }



                if($user['user_type']==2){

                    $balance=bcmul( $order['order_money'],($user['settle_rate']- $parent['settle_rate']),2);
                    $this->log_write('settel',$order['order_money']);
                    $this->log_write('settel',$user['settle_rate']- $parent['settle_rate']);
                    $this->log_write('settel',$balance);

                }else{
                    $balance=bcmul( $order['order_money'], $this->sye_rate['parent'],2 );
                }

//                    $balance=floor( $order['order_money'] * 100 * $this->sye_rate['parent'] )/100;
                $res=Db::table('user')
                    ->where("user_id",$parent['user_id'])
                    ->update([
                        'balance'=>$parent['balance']+$balance,
                        'integral'=>$parent['integral']+$order['order_money']
                    ]);

                //$this->returnMsg['parent']=$parent;
                Db::table('commission')
                    ->insert([
                        'order_id'=>$order['order_id'],
                        'commission_money'=>$balance,
                        'order_money'=>$order['order_money'],
                        'user_id'=>$parent['user_id'],
                        'commission_time'=>time()
                    ]);


                if(!empty($parent['merchant_id']) && $user['user_type']==1 || $user['group_up']!==$user['group_id'] && $user['user_type']==2){
                    if($user['user_type']==2 && $user['group_up']!==$user['group_id']){
                        $superior=Db::table('user')
                            ->where('user_id',$user['group_id'])
//                            ->field('balance,integral,user_id')
                            ->find();
                    }else{
                        $superior=Db::table('user')
                            ->where('user_id',$parent['merchant_id'])
//                            ->field('balance,integral,user_id')
                            ->find();
                    }



                    if($user['user_type']==2 ){
                        $superior_balance=bcmul( $order['order_money'],($parent['settle_rate']-$superior['settle_rate']),2);
                        $this->log_write('settel1',$order['order_money']);
                        $this->log_write('settel1',$parent['settle_rate']-$superior['settle_rate']);
                        $this->log_write('settel1',$superior_balance);
                    }else{
                        $superior_balance=bcmul( $order['order_money'], $this->sye_rate['parent'] ,2);
                    }
//                        $superior_balance=floor( $order['order_money'] * 100 * $this->sye_rate['superior'] )/100;

                    if(!empty($superior)){

                        $res=Db::table('user')
                            ->where("user_id",$superior['user_id'])
                            ->update([
                                'balance'=>$superior['balance']+$superior_balance,
                                'integral'=>$superior['integral']+$order['order_money']
                            ]);
                        //$this->returnMsg['superior22']=$superior;

                        Db::table('commission')
                            ->insert([
                                'order_id'=>$order['order_id'],
                                'commission_money'=>$superior_balance,
                                'order_money'=>$order['order_money'],
                                'user_id'=>$superior['user_id'],
                                'commission_time'=>time()
                            ]);
                    }
                }

            }
        }

        return 'aaaaa';
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
