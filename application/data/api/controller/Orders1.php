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
		
        if(empty($this->online['debit_card'])){
            $this->returnMsg['message']='尚未绑定收款借记卡,无法进行提现';
            return $this->returnMsg;
        }
        $md_card=input('card_id');
		//$this->returnMsg['message']=$md_card;
		//return $this->returnMsg;
        if(empty($md_card)){
            $this->returnMsg['message']='尚未选择信用卡';
            return $this->returnMsg;
        }
        $card_data=model('user_card')
            ->where('md_card',$md_card)
            ->where('user_id',$this->online['user_id'])
            ->find();
		//$this->returnMsg['data']=$card_data;	
        if(empty($card_data)){
            $this->returnMsg['message']='尚未绑定该卡,无法使用';
            return $this->returnMsg;
        }
        $money=input('money/d');
        if($money<6){
            $this->returnMsg['message']='提现金额不能低于6元';
            return $this->returnMsg;
        }
		$change_settle_rate=$this->change_settle_rate();//刷新用户汇率

		if($change_settle_rate!==200){
			return $this->returnMsg;
		}
        $order_id=md5(md5(time()+$this->random(4)));
        $post_data=[
            'sp_id'=>config('sp_id'),
            'mch_id'=>$this->online['mcht_no'],
            'out_trade_no'=>$order_id,
            'total_fee'=>$money*100,
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
        $bodys=$this->sbt_sign($post_data)['data'];
        $url=$this->sbt_url.'/gate/epay/sapply';
        $result=$this->curl_allinfo($url,false,$bodys);
        if(empty($result)){
            $this->returnMsg['message'] = '订单生成失败';
            return $this->returnMsg;
        }
        if ($result->status !== 'SUCCESS') {
            $this->returnMsg['message'] = $result->message;
            return $this->returnMsg;
        }
        if($result->result_code!=="SUCCESS"){
            $this->returnMsg['message'] = $result->err_msg;
            return $this->returnMsg;
        }
        //$this->returnMsg['data2']=$result;
		//return $this->returnMsg;
		 //$balance=$money*($this->sye_rate-$this->settle_rate);
		 //$this->returnMsg['balance']=$balance;
		$service=$money*$this->settle_rate;
		if($service<3){
			$service=3;
		}
		$arrival_amount= round( ($money-$service-2) *100)/100;
		$commission=$money*($this->settle_rate-$this->merchant_rate);
		$order_time=time();
        $orders_model=model('orders');
        $orders_model['order_id']=$order_id;
        $orders_model['user_id']=$this->online['user_id'];
        $orders_model['from_card']=$card_data['card_id'];
        $orders_model['to_card']=$this->online['debit_card'];
        $orders_model['order_time']=$order_time;
       // $orders_model['out_trade_no']=$result->out_trade_no;
        $orders_model['order_status']='NOTPAY';
        $orders_model['order_money']=$money;
        $orders_model['arrival_amount']=$arrival_amount;
        $orders_model['commission']=$commission;
        $orders_model['commission_rate']=$this->sye_rate-$this->settle_rate;
        $orders_model->save();
		//$this->returnMsg['rate']=$this->settle_rate;
		
		//$this->returnMsg['sql']=$res;
        $return_data=[
          'order_id'=>$order_id,
           'money'=>$money,
          'arrival_amount'=>$arrival_amount,
		  'time'=>date("Y-m-d H:i:s",$order_time)
        ];
        $this->returnMsg['data']=$return_data;
        $this->returnMsg['message']='已将验证码发送到预留手机号';
        $this->returnMsg['status']=200;
        return $this->returnMsg;
    }

    //获取订单信息
    public function get_orders(){
        if(!empty(input('order_id'))){
            $order_id=input('order_id');
            $reg_data = [
                'sp_id' => '1000',
                'mch_id' => $this->online['mcht_no'],
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
            $start_count=($page-1)*5;
            $order_data=model('orders')
                ->where('user_id',$this->online['user_id'])
				->field('order_id,order_time,from_card,order_status,order_money,arrival_amount')
                ->order('order_time desc')
                ->limit($start_count,5)
                ->select();
			foreach($order_data as $key=>$val){
				$order_data[$key]['order_time']=date("Y-m-d H:i:s",$val['order_time']);
			}
            $this->returnMsg['status']=200;
			$this->returnMsg['message']='请求成功';
            $this->returnMsg['data']['order_data']=$order_data;
			$this->returnMsg['data']['page']=$page;
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
        $reg_data = [
            'sp_id' => config('sp_id'),
            'mch_id' => $this->online['mcht_no'],
            'out_trade_no' => $order_id,
            'password' => $password,
            'nonce_str' => $this->random(4, 1)
        ];
//
        $reg_data = $this->sbt_sign($reg_data);
        $url = $this->sbt_url . '/gate/epay/submit';
        $result= $this->curl_allinfo($url, false, $reg_data['data']);
        //$this->returnMsg['data2']=$result;
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
        }else if($result->result_code=="PROCESSING"){
            $this->returnMsg['message'] = $result->err_msg.",付款完成，正在转帐中";
            return $this->returnMsg;
        }
        if($result->result_code=="SUCCESS" && !empty($this->online['merchant_id']) ){
			$merchant_balance=Db::table('merchant')
				->where('merchant_id',$this->online['merchant_id'])
				->field('balance')
				->find()['balance'];
            $balance=$order['money']*($this->sye_rate-$this->settle_rate);
            $res=Db::table('merchant')
                ->where("merchant_id",$this->online['merchant_id'])
                ->update(['balance'=>$merchant_balance+$balance]);

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
            'bank_name'=>$bank_name,
			'time'=>date("Y-m-d H:i:s",$order['order_time'])
        ];
        return $this->returnMsg;
    }
	
}
