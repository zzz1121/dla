<?php
namespace app\api\controller;
use \think\File;
use \think\Db;
use \think\Model;
use \think\Request;
class Orders extends Online
{


    //生成订单
    public function index()
    {
        $hour = date('H:i:s', time());

        if (empty($this->online['debit_card'])) {
            $this->returnMsg['message'] = '尚未绑定收款借记卡,无法进行提现';
            return $this->returnMsg;
        }
        $md_card = input('card_id');
        if (empty($md_card)) {
            $this->returnMsg['message'] = '尚未选择信用卡';
            return $this->returnMsg;
        }
        $card_data = model('user_card')
            ->where('md_card', $md_card)
            ->where('user_id', $this->online['user_id'])
            ->find();
        if (empty($card_data)) {
            $this->returnMsg['message'] = '尚未绑定该卡,无法使用';
            return $this->returnMsg;
        }
        $money = (int)(input('money') * 100);
        $account_time = input('account_time', '');
        $pay_prot_id = input('pay_prot_id', 2);
        if (!empty($account_time)) {
			 $pay_prot_id=2;
			if($account_time==1){
				$pay_prot_id=3;
			}
           
        }
        $rate_data = $this->get_settle($pay_prot_id, $this->online['user_id']);

        $this->settle_rate = $rate_data['settle_rate'];

        $order_type = $rate_data['rate_type'];

        if ($money/100 < $rate_data['min_money']) {
            $this->returnMsg['message'] = '提现金额不能低于元'.$rate_data['min_money'];
            return $this->returnMsg;
        }
        if ($money/100 >= $rate_data['max_money']) {
            $this->returnMsg['message'] = '单笔提现金额不能大于'.$rate_data['max_money'];
            return $this->returnMsg;
        }

        if ($hour < $rate_data['start_time'] || $hour > $rate_data['end_time']) {
            $this->returnMsg['message'] = "请在 " . $rate_data['start_time'] . " ~ " . $rate_data['end_time'] . "时间段内进行提现操作";
            return $this->returnMsg;
        }
//        if($pay_prot_id==1){
//            $pay_prot_id=2;
//        }elseif($pay_prot_id==2){
//            $pay_prot_id=3;
//        }else{
//            $pay_prot_id=1;
//        }



        $mcht_data=db('user_pay_data')  //通道账号
        ->where('user_id',$this->online['user_id'])
            ->where('pay_id',$pay_prot_id)
            ->find();
        $debit_card = Db::table('user_card')//绑定卡
        ->where('card_id', $this->online['debit_card'])
            ->find();


        if ($pay_prot_id == 1 || $pay_prot_id==3) {   //上福订单请求
            if($pay_prot_id==1){
                $sp_id="1086-2";
            }else{
                $sp_id="1086-1";
            }
            vendor('shangfupay.shangfupay');
            $pay=new \Shangfupay();
            if(empty($mcht_data)){
                $data=array_merge($this->online,$debit_card);
                $data=array_merge($data,$rate_data);
                $res=$this->reg_shangfu_pay($data,$pay_prot_id);
                if($res>200){
                    $this->returnMsg['message']='支付通道开通失败';
                    return $this->returnMsg;
                }
                $mcht_data=db('user_pay_data')  //通道账号
                ->where('user_id',$this->online['user_id'])
                    ->where('pay_id',$pay_prot_id)
                    ->find();
            }else{
                $updata_data=$debit_card;
                $updata_data['mcht_no']=$mcht_data['mcht_no'];
                $change_card=$pay->change_card($updata_data,$pay_prot_id);
                if ($change_card !== 200) {
                    return $this->returnMsg;
                }
                $updata_data=array_merge($this->online,$rate_data);
                $updata_data['mcht_no']=$mcht_data['mcht_no'];
                $change_settle_rate = $pay->update_status($updata_data,$pay_prot_id);//上福刷新用户汇率
                if ($change_settle_rate !== 200) {
                    return $this->returnMsg;
                }
            }



            $service = $money * $this->settle_rate;
            if ($service < $rate_data['min_charge']) {
                $service = $rate_data['min_charge'];
            }

            $extra_rate = $rate_data['extra_rate'];

            $order_id = date('YmdHis');
            $arrival_amount = round($money - $service - $extra_rate) / 100;
            $order_time = time();
            $orders_model = model('orders');
            $orders_model['order_id'] = $order_id;
            $orders_model['user_id'] = $this->online['user_id'];
            $orders_model['from_card'] = $card_data['card_id'];
            $orders_model['to_card'] = $this->online['debit_card'];
            $orders_model['order_time'] = $order_time;

            $orders_model['order_money'] = $money / 100;
            $orders_model['arrival_amount'] = $arrival_amount;
            $orders_model['cover_charge'] = $service / 100;
            $orders_model['service_charge'] = $extra_rate / 100;
            $orders_model['sye_cost'] = ceil($money*$rate_data['costing'])/100;
            $orders_model['order_type'] = $order_type;
            $orders_model['pay_prot_id'] = $rate_data['pay_prot_id'];


            $post_data = [
                'sp_id' => $sp_id,
                'mcht_no' => $mcht_data['mcht_no'],
                'order_id' => $order_id,
                'money' => $money,
                'body' => '电子',
                'notify_url' => config('notify_url'),
                'id_type' => '01',
                'bank_name' => $card_data['bank_name'],
                'acct_type' => 'CREDIT',
                'name' => $this->online['name'],
                'card_id' => $card_data['card_id'],
                'card_phone' => $card_data['card_phone'],
                'number' => $this->online['number'],
                'bank_no' => $card_data['bank_no'],
                'card_end' => $card_data['card_end'],
                'card_cvv' => $card_data['card_cvv'],
                'nonce_str' => $this->random(4, 1),
                'secret_key'=>$mcht_data['secret_key']
            ];
//            $this->returnMsg['data2'] = $post_data;
//            $bodys = $this->sbt_sign($post_data, $this->online['secretKey_1'])['data'];
//            $result = $this->curl_allinfo($url, false, $bodys);

            $result=$pay->order($post_data);
            $this->log_write('card_log', $result);
            $this->log_write('card_log', $result);

            if (empty($result)) {
                $orders_model['order_status'] = 'FAIL';
                $orders_model->save();
                $this->returnMsg['message'] = '订单生成失败';
                return $this->returnMsg;
            }

            if ($result->status !== 'SUCCESS') {
                $orders_model['order_status'] = 'FAIL';
                $orders_model->save();
                $this->returnMsg['message'] = $result->message . "错误";
                return $this->returnMsg;
            }

            if ($result->result_code !== "SUCCESS") {
                //return $this->returnMsg;
                if (empty($result->err_msg)) {
                    $this->returnMsg['message'] = '订单生成失败,请确认订单信息';
                } else {
                    $this->returnMsg['message'] = $result->err_msg;
                }
                $orders_model['order_status'] = 'FAIL';
                $orders_model->save();
                return $this->returnMsg;

            }
            $orders_model['order_status'] = 'NOTPAY';
            $orders_model->save();


            $return_data = [
                'order_id' => $order_id,
                'money' => $money / 100,
                'arrival_amount' => $arrival_amount,
                'charge' => $service / 100,
                'extra_rate' => $extra_rate / 100,
                'time' => date("Y-m-d H:i:s", $order_time)
            ];
            $this->returnMsg['data'] = $return_data;
            $this->returnMsg['message'] = '已将验证码发送到预留手机号';
            $this->returnMsg['status'] = 200;
        } else {


            //汇享支付平台


            Vendor('hxpay.huixiangPay');
            $obj = new \PayAction();
            $this->online['settle_rate'] = $this->settle_rate;




            $order_data['service'] = ceil( $money * $this->settle_rate );


            $order_data['extra_rate'] = $rate_data['extra_rate'];

            if($order_data['service']<$rate_data['min_charge']){
                $rate_data['extra_rate']+=(int)($rate_data['min_charge']-$order_data['service']);

                $order_data['extra_rate']+=(int)($rate_data['min_charge']-$order_data['service']);
            }


            if (empty($mcht_data)) {
                $res = $obj->reg($this->online, $debit_card, $rate_data);
                if ($res['resp_code'] !== '000000') {
                    $this->returnMsg['message'] = "支付通道开通失败。";
                    return $this->returnMsg;
                }
                $update_res = db('user_pay_data')
                    ->insert([
                        'user_id'=>$this->online['user_id'],
                        'mcht_no'=>$res['mcht_no_2'],
                        'secret_key'=>$res['data']->third_merchant_code,
                        'pay_id'=>$pay_prot_id
                    ]);

                if ($update_res == 0) {
                    $this->returnMsg['message'] = '支付通道开通失败';
                    return $this->returnMsg;
                }
                $this->online['mcht_no_2'] = $res['mcht_no_2'];
                $this->online['secretKey_2'] = $res['data']->third_merchant_code;
            } else {
                $this->online['mcht_no_2']=$mcht_data['mcht_no'];
                $this->online['secretKey_2']=$mcht_data['secret_key'];
                $res = $obj->update_status($this->online, $debit_card, $rate_data, "M");
                //$this->returnMsg['dd'] = $res;
                $this->log_write('hx_update',$res);
                if ($res['resp_code'] !== '000000') {
                    $this->returnMsg['message'] = "支付费率更新失败。";
                    return $this->returnMsg;
                }

            }





            $order_data['arrival_amount'] = $money - $order_data['service'] - $order_data['extra_rate'];
            $order_data['money'] = $money;

            //请求汇享支付接口
            $pay_res = $obj->hxpay($this->online, $card_data, $debit_card, $order_data, $rate_data);

            //return $pay_res;
			
            if (empty($pay_res) || $pay_res['resp_code'] !== '000000') {
                $this->returnMsg['message'] = $pay_res['resp_msg'];
                return $this->returnMsg;
            }

            $order_data['service'] = ceil( $money * $this->settle_rate );



            if($order_data['service']<$rate_data['min_charge']){
                $rate_data['extra_rate']-=(int)($rate_data['min_charge']-$order_data['service']);

                $order_data['service']=$rate_data['min_charge'];
            }
            $order_data['extra_rate'] = $rate_data['extra_rate'];




            //订单保存
            $orders_model = model('orders');
            $orders_model['order_id'] = $pay_res['client_trans_id'];
            $orders_model['user_id'] = $this->online['user_id'];
            $orders_model['from_card'] = $card_data['card_id'];
            $orders_model['to_card'] = $this->online['debit_card'];
            $orders_model['order_time'] = $pay_res['trans_timestamp'];
            $orders_model['order_status'] = 'NOTPAY';
            $orders_model['order_money'] = $money / 100;
            $orders_model['sye_cost'] = ceil($money*$rate_data['costing'])/100;
            $orders_model['arrival_amount'] = $order_data['arrival_amount'] / 100;
            $orders_model['cover_charge'] = $order_data['service'] / 100;
            $orders_model['service_charge'] = $order_data['extra_rate'] / 100;
            $orders_model['order_type'] = $order_type;
            $orders_model['pay_prot_id'] = $rate_data['pay_prot_id'];
            $orders_model->save();
            $return_data = [
                'order_id' => $pay_res['client_trans_id'],
                'money' => $money / 100,
                'arrival_amount' => $order_data['arrival_amount'] / 100,
                'charge' => $order_data['service'] / 100,
                'extra_rate' => $order_data['extra_rate'],
                'time' => date("Y-m-d H:i:s", $pay_res['trans_timestamp']),
                'url' => $pay_res['data']->page_content
            ];
            $this->returnMsg['data'] = $return_data;
            $this->returnMsg['message'] = '已将验证码发送到预留手机号';
            $this->returnMsg['status'] = 200;
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
        $order=db('orders')
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
        $mcht_data=db('user_pay_data')
            ->where('user_id',$this->online['user_id'])
            ->where('pay_id',$order['pay_prot_id'])
            ->find();
        //
        vendor('shangfupay.shangfupay');
        $pay=new \Shangfupay();
        $data=array_merge($order,$mcht_data);
        $data['password']=$password;
        $result=$pay->sub_order($data,$order['pay_prot_id']);




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

            $res=model('orders')
                ->where('order_id',$order_id)
                ->update(['order_status'=>$result->status]);
            $this->returnMsg['message'] = $result->message.",请重新下单";
            return $this->returnMsg;
        }
        $order_status=$result->result_code;

        $res=model('orders')
            ->where('order_id',$order_id)
            ->update(['order_status'=>$result->result_code]);
        $bank_name=model('user_card')
            ->where('card_id',$order['to_card'])
            ->field('bank_name')
            ->find()['bank_name'];

        if($result->result_code=="SUCCESS" ){
            $user=$this->online;
            // 代理商抽成提取
            $res=$this->fenrun($order,$user);
            if(!$res){
                $res=model('orders')
                    ->where('order_id',$order_id)
                    ->update(['order_status'=>'PROCESSING']);
                $this->log_write('shangfu_log','写入失败');
                $this->returnMsg['message']='支付成功';
                $this->returnMsg['status']=200;
                $this->returnMsg['data']=[
                    'to_card'=>substr($order['to_card'],-4),
                    'order_status'=>'PROCESSING',
                    'to_bank'=>$bank_name,
                    'time'=>date("Y-m-d H:i:s",$order['order_time'])
                ];
                return $this->returnMsg;
            }
            $this->returnMsg['message']='支付成功';
            $this->returnMsg['status']=200;
            $this->returnMsg['data']=[
                'to_card'=>substr($order['to_card'],-4),
                'order_status'=>'SUCCESS',
                'to_bank'=>$bank_name,
                'time'=>date("Y-m-d H:i:s",$order['order_time'])
            ];
            return $this->returnMsg;


        }

        if($result->result_code=="FAIL"){
            $this->returnMsg['message'] = $result->err_msg.",请重新下单";
            return $this->returnMsg;
        }



        //        $this->returnMsg['result']=$result;


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

    //上福支付通道注册
    public function reg_shangfu_pay($data,$pay_id='1'){
        if($pay_id==1){
            $sp_id='1086-2';
        }else{
            $sp_id='1086-1';
        }
        vendor('shangfupay.shangfupay');
        $pay=new \Shangfupay();


        $result = $pay->reg(array_merge($data),$sp_id);//上福商户注册
        //return $result;
        if ( empty($result) || $result->status !== 'SUCCESS' ||!strtolower($result->sign) == $this->sbt_sign($result)['sign'] ) {
            //$this->returnMsg['message'] = '商户注册失败,请稍后再试';
            $this->returnMsg['message'] = $result->message.",银行卡绑定失败";
            return 400;
        }
        $result=$this->object_to_array($result);
        $data=array_merge($data,$result);
        $result_reg = $pay->busiopen($data,$sp_id);
        if(empty($result_reg)){
            $this->returnMsg['message'] = '认证失败';
            return 400;
        }
        if ($result_reg->status !== 'SUCCESS') {
            $this->returnMsg['message'] = $result->message;
            return 400;
        }
        if($result_reg->result_code!=="SUCCESS"){
            $this->returnMsg['message'] = $result->err_msg;
            return 400;
        }
        $insert_res = db('user_pay_data')
            ->insert([
                'user_id'=>$this->online['user_id'],
                'mcht_no'=>$result['mcht_no'],
                'secret_key'=>$result['secretKey'],
                'pay_id'=>$pay_id
            ]);
        if (!$insert_res) {
            $this->returnMsg['message'] = '支付通道开通失败,请重试';
            return 400;
        }

        return 200;
    }

}
