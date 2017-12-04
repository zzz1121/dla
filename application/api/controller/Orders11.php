<?php
namespace app\api\controller;
use \think\File;
use \think\Db;
use \think\Model;
use \think\Request;
class Orders extends Online
{
    //生成订单
    public function index(){
		$hour=date('H:i:s',time());

        if(empty($this->online['debit_card'])){
            $this->returnMsg['message']='尚未绑定收款借记卡,无法进行提现';
            return $this->returnMsg;
        }
        $md_card=input('card_id');
        if(empty($md_card)){
            $this->returnMsg['message']='尚未选择信用卡';
            return $this->returnMsg;
        }
        $card_data=model('user_card')
            ->where('md_card',$md_card)
            ->where('user_id',$this->online['user_id'])
            ->find();
        if(empty($card_data)){
            $this->returnMsg['message']='尚未绑定该卡,无法使用';
            return $this->returnMsg;
        }
        $money=input('money/d')*100;
        $account_time=input('account_time',0);
       
        if($account_time==1){
            $rate_data=Db::table('rate')
                ->where('id',2)
                ->find();
            $url=$this->sbt_url.'/gate/epay/sapply';
			$order_type=2;

        }else{
            $rate_data=Db::table('rate')
                ->where('id',1)
                ->find();
            $url=$this->sbt_url.'/gate/epay/sapply';
			$order_type=1;
        }
        if($hour<$rate_data['start_time'] || $hour>$rate_data['end_time']){
            $this->returnMsg['message']="请在 ".$rate_data['start_time']." ~ ".$rate_data['end_time']."时间段内进行提现操作";
            return $this->returnMsg;
        }
        $this->sye_rate=$rate_data;
        if($rate_data['pay_prot_id']==1){   //上福订单请求

	
			 if($money<5000){
				$this->returnMsg['message']='提现金额不能低于50元';
				return $this->returnMsg;
			}
			if($money>=1000000){
				$this->returnMsg['message']='单笔提现金额不能大于10000元';
				return $this->returnMsg;
			}
            $change_settle_rate=$this->change_settle_rate();//上福刷新用户汇率
            if($change_settle_rate!==200){
                return $this->returnMsg;
            }
            $service=$money*$this->settle_rate;
            if($service<$this->sye_rate['min_charge']){
                $service=$this->sye_rate['min_charge'];
            }
            if($this->sye_rate['extra_rate_type']=='RATE'){
                $extra_rate=$money*$this->sye_rate['extra_rate'];
            }else{
                $extra_rate=$this->sye_rate['extra_rate'];
            }
            $order_id=date('YmdHis');
            $arrival_amount= round( $money- $service- $extra_rate)/100;
            $order_time=time();
            $orders_model=model('orders');
            $orders_model['order_id']=$order_id;
            $orders_model['user_id']=$this->online['user_id'];
            $orders_model['from_card']=$card_data['card_id'];
            $orders_model['to_card']=$this->online['debit_card'];
            $orders_model['order_time']=$order_time;

            $orders_model['order_money']=$money/100;
            $orders_model['arrival_amount']=$arrival_amount;
            $orders_model['cover_charge']=$service/100;
            $orders_model['service_charge']=$extra_rate/100;
            $orders_model['order_type']=$order_type;
            $orders_model['pay_prot_id']=$rate_data['pay_prot_id'];



            $post_data=[
                'sp_id'=>config('sp_id'),
                'mch_id'=>$this->online['mcht_no_1'],
                'out_trade_no'=>$order_id,
                'total_fee'=>$money,
                'body'=>'电子',
                'notify_url'=>config('notify_url'),
                'id_type'=>'01',
                'acc_bank_name'=>$card_data['bank_name'],
                'acct_type'=>'CREDIT',
                'acc_name'=>$this->online['name'],
                'acc_no'=>$card_data['card_id'],
                'mobile'=>$card_data['card_phone'],
                'id_no'=>$this->online['number'],
                'bank_code'=>$card_data['bank_no'],
                'expire_date'=>$card_data['card_end'],
                'cvv'=>$card_data['card_cvv'],
                'nonce_str'=>$this->random(4,1)
            ];
            $this->returnMsg['data2']=$post_data;
            $bodys=$this->sbt_sign($post_data,$this->online['secretKey_1'])['data'];
            $result=$this->curl_allinfo($url,false,$bodys);
            $this->log_write('card_log',$post_data);
            $this->log_write('card_log',$result);

            if(empty($result)){
                $orders_model['order_status']='FAIL';
                $orders_model->save();
                $this->returnMsg['message'] = '订单生成失败';
                return $this->returnMsg;
            }

            if ($result->status !== 'SUCCESS') {
                $orders_model['order_status']='FAIL';
                $orders_model->save();
                $this->returnMsg['message'] = $result->message."错误1";
                return $this->returnMsg;
            }

            if($result->result_code!=="SUCCESS" ){
                //return $this->returnMsg;
                if(empty($result->err_msg)){
                    $this->returnMsg['message'] = '订单生成失败,请确认订单信息';
                }else{
                    $this->returnMsg['message'] = $result->err_msg.',错误2';
                }
                $orders_model['order_status']='FAIL';
                $orders_model->save();
                return $this->returnMsg;

            }
            $orders_model['order_status']='NOTPAY';
            $orders_model->save();



            $return_data=[
                'order_id'=>$order_id,
                'money'=>$money/100,
                'arrival_amount'=>$arrival_amount,
                'charge'=>$service/100,
                'extra_rate'=>$extra_rate/100,
                'time'=>date("Y-m-d H:i:s",$order_time)
            ];
            $this->returnMsg['data']=$return_data;
            $this->returnMsg['message']='已将验证码发送到预留手机号';
            $this->returnMsg['status']=200;
        }else{
			
			if($money<10000){
				$this->returnMsg['message']='提现金额不能低于100元';
				return $this->returnMsg;
			}
			if($money>=1000000){
				$this->returnMsg['message']='单笔提现金额不能大于10000元';
				return $this->returnMsg;
			}
            //汇享支付平台
            $debit_card=Db::table('user_card')
                ->where('card_id',$this->online['debit_card'])
                ->find();

            Vendor('hxpay.huixiangPay');
            $obj=new \PayAction();
            $this->online['settle_rate']=$this->settle_rate;
		
            if(empty($this->online['mcht_no_2'])){
                $res=$obj->reg($this->online,$debit_card,$this->sye_rate);
                if($res['resp_code']!=='000000'){
                    $this->returnMsg['message']="汇享支付通道开通失败。";
                    return $this->returnMsg;
                }
                $user_model = model('user');
                $update_data['secretKey_2'] = $res['data']->third_merchant_code;
                $update_data['mcht_no_2'] = $res['mcht_no_2'];
                $update_res = $user_model
                    ->where('user_id', $this->online['user_id'])
                    ->update($update_data);
                if ($update_res==0) {
                    $this->returnMsg['message'] = '汇享支付通道开通失败';
                    return $this->returnMsg;
                }
				$this->online['mcht_no_2'] = $res['mcht_no_2'];
				$this->online['secretKey_2']=$res['data']->third_merchant_code;
            }else{
				
                $res=$obj->update_status($this->online,$debit_card,$this->sye_rate,"M");
                $this->returnMsg['dd']=$res;
                if($res['resp_code']!=='000000'){
                    $this->returnMsg['message']="汇享支付费率更新失败。";
                    return $this->returnMsg;
                }
				
            }


			
            $order_data['service']= round($money * $this->settle_rate);

            if($this->sye_rate['extra_rate_type']=='RATE'){
                $order_data['extra_rate']=$money*$this->sye_rate['extra_rate'];
            }else{
                $order_data['extra_rate']=$this->sye_rate['extra_rate'];
            }


            $order_data['arrival_amount']= $money- $order_data['service']- $order_data['extra_rate'];
            $order_data['money']=$money;

            //请求汇享支付接口
            $pay_res= $obj->hxpay($this->online,$card_data,$debit_card,$order_data,$this->sye_rate);


            if($pay_res['resp_code']!=='000000'){
                $this->returnMsg['message']="汇享支付订单生成失败。";
                return $this->returnMsg;
            }

            //订单保存
            $orders_model=model('orders');
            $orders_model['order_id']=$pay_res['client_trans_id'];
            $orders_model['user_id']=$this->online['user_id'];
            $orders_model['from_card']=$card_data['card_id'];
            $orders_model['to_card']=$this->online['debit_card'];
            $orders_model['order_time']=$pay_res['trans_timestamp'];
            $orders_model['order_status']='NOTPAY';
            $orders_model['order_money']=$money/100;
            $orders_model['arrival_amount']=$order_data['arrival_amount']/100;
            $orders_model['cover_charge']=$order_data['service']/100;
            $orders_model['service_charge']=$order_data['extra_rate']/100;
            $orders_model['order_type']=$order_type;
			$orders_model['pay_prot_id']=$rate_data['pay_prot_id'];
            $orders_model->save();
            $return_data=[
                'order_id'=>$pay_res['client_trans_id'],
                'money'=>$money/100,
                'arrival_amount'=>$order_data['arrival_amount']/100,
                'charge'=>$order_data['service']/100,
                'extra_rate'=>$order_data['extra_rate'],
                'time'=>date("Y-m-d H:i:s",$pay_res['trans_timestamp']),
                'url'=>$pay_res['data']->page_content
            ];
            $this->returnMsg['data']=$return_data;
            $this->returnMsg['message']='已将验证码发送到预留手机号';
            $this->returnMsg['status']=200;
        }


        return $this->returnMsg;
    }


    //获取订单信息
    public function get_orders(){
        if(!empty(input('order_id'))){
            $order_id=input('order_id');
            $reg_data = [
                'sp_id' => config('sp_id'),
                'mch_id' => $this->online['mcht_no_1'],
                'out_trade_no' => $order_id,
                'nonce_str' => $this->random(4, 1)
            ];
            $reg_data = $this->sbt_sign($reg_data);
            $url = $this->sbt_url . '/gate/spsvr/trade/qry';
            $result_reg = $this->curl_allinfo($url, false, $reg_data['data']);
            $this->returnMsg['data']=$result_reg;
            return $this->returnMsg;
        }else{
            $page=!empty(input('page'))?input('page'):1;
            $start_count=($page-1)*config('api_page_count');
            $order_data=model('orders')
                ->where('user_id',$this->online['user_id'])
                ->field('order_id,order_time,from_card,to_card,order_status,order_money,arrival_amount,cover_charge,service_charge')
                ->order('order_time desc')
                ->limit($start_count,config('api_page_count'))
                ->select();
            foreach($order_data as $key=>$val){
                $order_data[$key]['order_time']=date("Y-m-d H:i:s",$val['order_time']);
				$order_data[$key]['from_card']=substr($val['from_card'],-4);
				$order_data[$key]['to_card']=substr($val['to_card'],-4);
            }
			$this->returnMsg['data']['page']=$page;
			$this->returnMsg['end_page']=0;
			if(count($order_data)<config('api_page_count')){
				$this->returnMsg['end_page']=1;
			}
			//if(empty($order_data)){
				//$this->returnMsg['message']='没有数据了';
				//return $this->returnMsg;
			//}
            $this->returnMsg['status']=200;
            $this->returnMsg['message']='请求成功';
            $this->returnMsg['data']['order_data']=$order_data;
            return $this->returnMsg;
        }

    }
	
	
	

    //订单支付验证
    public function sub_order(){
        $password=input('post.order_code');
        $order_id=input('post.order_id');
        if(empty($password)){
            $this->returnMsg['message']='验证码不能为空';
            return $this->returnMsg;
        }
        if(empty($order_id)){
            $this->returnMsg['message']='订单号不能为空';
            return $this->returnMsg;
        }
        $orders_model=model('orders');
        $order=$orders_model
            ->where('order_id',$order_id)
            ->where('user_id',$this->online['user_id'])
            ->find();
        if(empty($order)){
            $this->returnMsg['message']='订单不存在,请重新提交订单';
            return $this->returnMsg;
        }
        $post_data = [
            'sp_id' => config('sp_id'),
            'mch_id' => $this->online['mcht_no_1'],
            'out_trade_no' => $order_id,
            'password' => $password,
            'nonce_str' => $this->random(4, 1)
        ];
//
        $post_data = $this->sbt_sign($post_data,$this->online['secretKey_1'])['data'];
        $url = $this->sbt_url . '/gate/epay/submit';
        $result= $this->curl_allinfo($url, false, $post_data);
		
		
		
		
		$path = ROOT_PATH .'public' . DS . 'log'.DS.date('Y-m-d',time()).DS;
		
		if(!is_dir($path)){
			mkdir($path);
		}
		
		$filename=$path = $path."sub_order_log.txt";
		$fh = fopen($filename, "a+");
		$post_txt="\n".date('Y--m-d H:i:s',time()).json_encode($post_data);
		$word = "\n".date('Y--m-d H:i:s',time()).json_encode($result);
		fwrite($fh, $post_txt);    // 输出：6
		fwrite($fh, $word);    // 输出：6
		fclose($fh);
		
        //order_code$this->returnMsg['data2']=$result;
        if(empty($result)){
            $this->returnMsg['message'] = '订单支付失败';
            return $this->returnMsg;
        }
        if ($result->status !== 'SUCCESS') {
            $this->returnMsg['message'] = $result->message.",请重新下单1";
            return $this->returnMsg;
        }
        $res=model('orders')
            ->where('order_id',$order_id)
            ->update(['order_status'=>$result->result_code]);
        if($result->result_code=="FAIL"){
            $this->returnMsg['message'] = $result->err_msg.",请重新下单";
            return $this->returnMsg;
        }
		if($result->result_code=="SUCCESS"){
			 $res=Db::table('user')
            ->where("user_id",$this->online['user_id'])
            ->update([
                'integral'=>$this->online['integral']+$order['order_money']
            ]);
			Db::table('commission')
				->insert([
					'order_id'=>$order_id,
					'commission_money'=>0,
					'order_money'=>$order['order_money'],
					'user_id'=>$this->online['user_id'],
					'commission_time'=>time()
				]); 
		}
		
		$order_status=$result->result_code;
        if($result->result_code=="SUCCESS" && !empty($this->online['merchant_id']) ){
			$user=$this->online;
             // 代理商抽成提取
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

//        $this->returnMsg['result']=$result;

        $bank_name=model('user_card')
            ->where('card_id',$order['to_card'])
            ->field('bank_name')
            ->find()['bank_name'];
        $this->returnMsg['message']='支付成功';
        $this->returnMsg['status']=200;
        $this->returnMsg['data']=[
            'to_card'=>substr($order['to_card'],-4),
			'order_status'=>$order_status,
            'to_bank'=>$bank_name,
            'time'=>date("Y-m-d H:i:s",$order['order_time'])
        ];
        return $this->returnMsg;
    }

}
