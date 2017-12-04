<?php
namespace app\api\controller;
use \think\File;
use \think\Db;
use \think\Model;
use \think\Request;
class Restore extends Index
{

    public function index(){
        $res=Db::table('commission')
            ->group('user_id')
            ->field('user_id,sum(commission_money) as sum')
            ->select();
        $sum=0;
        foreach($res as $val){
            $res2=Db::table('user')
                ->where('user_id',$val['user_id'])
                ->update(['balance'=>$val['sum']]);
            $sum+=$res2;
        }
        $res_sum=Db::table('pay_orders')
            ->where('pay_status',"PAY_SUCCESS")
			->whereOr('pay_status','PAY_SUBMIT')
            ->field('user_id,sum(pay_money+pay_service) sum ')
            ->group('user_id')
            ->select();
        foreach($res_sum as $value){
            $res2=Db::table('user')
                ->where('user_id',$value['user_id'])
                ->setDec('balance',$value['sum']);
            $arr[]=$res2;
            $sum+=$res2;
        }
        return $arr;

    }
	public function add_comm(){
        $end=strtotime('2017-11-07 16:22:28');
        $start=strtotime('2017-11-07 00:22:28');
        db('commission')
            ->where('commission_time','<',$end)
            ->where('commission_time','>',$start)
            ->delete();
        $result=db('orders')
            ->where('order_time','<',$end)
            ->where('order_time','>',$start)
            ->where('order_status','SUCCESS')
            ->setField('order_status','PROCESSING');
        $result=db('orders')
            ->where('order_time','<',$end)
            ->where('order_time','>',$start)
            ->where('order_status','PROCESSING')
            ->select();
        foreach($result as $key=>$val){

            $user=model('user')
                ->where('user_id',$val['user_id'])
                ->find();
            $this->fenrun($val,$user);
            db('commission')
                ->where('order_id',$val['order_id'])
                ->setField('commission_time',$val['order_time']);
        }
        $result=db('orders')
            ->where('order_time','<',$end)
            ->where('order_time','>',$start)
            ->where('order_status','PROCESSING')
            ->setField('order_status','SUCCESS');
        //dump($result);
        die();
    }
	

    public function merchant(){
        $res=model('user')->field('user_id,merchant_id,underling')->select();
        foreach($res as $val){
            $user_id=$val['user_id'];
            $count=model('user')->where('merchant_id',$user_id)->count('user_id');
            $arr[]['sum']=$count;
            $arr[]['user_id']=$user_id;
            $res2=model('user')
                ->where('user_id',$user_id)
                ->setField('underling',$count);
            $arr[]['count']=$res2;
        }
        $res=model('user')->field('user_id,merchant_id,underling')->select();
        foreach($res as $val){
            $user_id=$val['user_id'];
            $count=model('user')->where('merchant_id',$user_id)->sum('underling');
            $arr[]['sum']=$count;
            $arr[]['user_id']=$user_id;
            $res2=model('user')
                ->where('user_id',$user_id)
                ->setField('indirect',$count);
            $arr[]['count']=$res2;
        }
        return $arr;
    }


    public function bank_card(){


        if($this->online['card_status']!=1){
            $this->returnMsg['message']='尚未实名认证,无法绑定银行卡';
            return $this->returnMsg;
        }

        $user_name=$this->online['name'];//持有人
        $user_card=$this->online['number'];//持有人
        if(empty($user_card)){
            $this->returnMsg['message']='上传信息不全,请重新输入';
            return $this->returnMsg;
        }
        if($user_card!==$this->online['number']){
            $this->returnMsg['message']='只能绑定本人银行卡';
            return $this->returnMsg;
        }




        $card_id=input('card_number');//银行卡


        $card_type=input('card_type');//银行卡

        if(empty($card_id)){
            $this->returnMsg['message']='请输入银行卡号';
            return $this->returnMsg;
        }

        if(empty($card_type)){
            $this->returnMsg['message']='请确认绑定的银行卡类型';
            return $this->returnMsg;
        }
        $card_phone=input('card_phone');//预留手机号
        $card_end=input('card_end','');
        $card_cvv=input('card_cvv','');//银行卡照片
        $card_data=$this->card_id_data($card_id);



        if($card_data['cardtype']=='银联借记卡'){
            $card_pic_type='DEBIT';
        }elseif($card_data['cardtype']=='银联贷记卡'){
            $card_pic_type='CREDIT';
        }else{
            $card_pic_type='';
        }
        if($card_type!==$card_pic_type){
            $this->returnMsg['message']='上传卡片与所选银行卡类型不符';

            return $this->returnMsg;
        }

        $model=model('user_card');
        $is_have=$model
            ->where('card_id',$card_id)
            ->field("card_id")
            ->find();
        if(!empty($is_have)){
            $this->returnMsg['message']='该卡已绑定,请勿重复绑定';
            return $this->returnMsg;
        }

        if($card_type=="CREDIT"){
            (string)$res=$card_data['bankname'];
            $card_data['bankno']='';

            $bank_no_credit=config("bank_no_credit");
            foreach($bank_no_credit as $val){
                if($val['bank_name']==$card_data['bankname']){
                    $card_data['bankno']=$val['bank_no'];
                    break;
                }
            }
        }elseif($card_type=="DEBIT"){

            $card_data['bankno']='';
            $bank_no_debit=config('bank_no_debit');
            foreach($bank_no_debit as $val){
                if($val['bank_name']==$card_data['bankname']){
                    $card_data['bankno']=$val['bank_no'];
                    break;
                }
            }
        }


        if(empty($card_data['bankno'])){
            $this->returnMsg['message']="暂不支持该行银行卡，请更换卡片";
            return $this->returnMsg;
        }

        // 银行卡4要素认证
        $bank_4_res=$this->bank_card_4($card_id,$user_card,$user_name,$card_phone);
        if($bank_4_res>200){
            return $this->returnMsg;
        }

        $card_data=$this->object_to_array($card_data);
        if($card_type=='DEBIT' && empty($this->online['debit_card']) ) {//新用户商户注册

            $card_data['bank_name']=$card_data['bankname'];
            $card_data['bank_no']=$card_data['bankno'];
            $card_data['card_phone']=$card_phone;
            $card_data['card_id']=$card_id;
            $reg_shangfu_res=$this->reg_shangfu_pay($card_data);
            if($reg_shangfu_res!==200){
                return $this->returnMsg;
            }

        }


        $model['user_id']=$this->online['user_id'];
        $model['card_id']=$card_id;
        $model['card_name']=$user_name;
        $model['card_phone']=$card_phone;
        $model['card_type']=$card_type;
        $model['bank_no']=$card_data['bankno'];
        $model['bank_name']=$card_data['bankname'];
        $model['md_card']=substr($card_id,0,4)."**********".substr($card_id,-4);
        if($card_type=='CREDIT'){
            $model['card_end']=$card_end;
            $model['card_cvv']=$card_cvv;
        }
        $insert_result=$model->save();
        if(!$insert_result){
            $this->returnMsg['message']='系统错误,请稍后再试';
            return $this->returnMsg;
        }


        $this->returnMsg['data']['card_data']=$this->get_my_card();
        //
        $this->returnMsg['message']='认证成功';
        $this->returnMsg['status']='200';
        return $this->returnMsg;
    }

    public function card_id_data($card_id){
        $url="http://aliyun.apistore.cn/7?bankcard=".$card_id;
        $response=$this->aliyun_curl($url,"GET",'','35788ff82d204eeb9fddf4463056a072');
        $response=$this->object_to_array($response);
        if($response['error_code']!==0){
            $this->returnMsg['message']='卡号输入有误，请检查';
            return false;
        }
        $card_data=$this->object_to_array($response['result']);
        return $card_data;
    }





    //银行卡识别
    public function get_card_data($url,$pic_path,$type=null){
        $postData="bas64String=".$this->img_to_base($pic_path); //post数据拼接
        if(!empty($type)){
            $url.="?typeid=".$type;
        }
        return $this->aliyun_curl($url,"POST",$postData,'2fdab480b57848a29f0f39f101ae345f');
    }

    //上福支付通道注册
    public function reg_shangfu_pay($card_data){
        if(empty($card_data)){
            return 400;
        }
        $post_data = [
            'sp_id' => config('sp_id'),
            'mcht_name' => '个体商户',
            'mcht_short_name' => $this->online['name'] . '的店铺',
            'address' => $this->online['address'],
            'leg_name' => $this->online['name'],
            'leg_phone' => $this->online['user_id'],
            'leg_email' => $this->online['user_id'] . '@163.com',
            'acc_no' => $card_data['card_id'],
            'acc_bank_name' => $card_data['cardname'],
            'acc_bank_no' => $card_data['bankno'],
            'service_tel' => $card_data['card_phone'],
            'id_type' => '01',
            'id_no' => $this->online['number'],
            'nonce_str' => $this->random(4, 1)
        ];

        $post_data = $this->sbt_sign($post_data,config('sbt_key'));
        $url = $this->sbt_url . '/gate/msvr/mbreg';
        $result = $this->curl_allinfo($url, false, $post_data['data']);


        if ( empty($result) || $result->status !== 'SUCCESS' ||!strtolower($result->sign) == $this->sbt_sign($result)['sign'] ) {
            //$this->returnMsg['message'] = '商户注册失败,请稍后再试';
            $this->returnMsg['message'] = $result->message.",银行卡绑定失败";
            return 400;
        }
        Db::startTrans();
        $user_model = model('user');
        $update_data['mcht_no_1'] = $result->mcht_no;
        $update_data['secretKey_1'] = $result->secretKey;
        $update_data['debit_card']=$card_data['card_id'];
        $res = $user_model
            ->where('user_id', $this->online['user_id'])
            ->update($update_data);
        if ($res==0) {
            $this->returnMsg['message'] = '支付通道开通失败,请重试';
            return 400;
        }
        $reg_data = [
            'sp_id' => config('sp_id'),
            'mcht_no' => $result->mcht_no,
            'busi_type' => 'EPAYS',
            'settle_type' => 'REAL_PAY',
            'settle_rate' => $this->sye_rate['settle_rate'],
            'extra_rate_type' => $this->sye_rate['extra_rate_type'],
            'extra_rate' => $this->sye_rate['extra_rate'],
            'nonce_str' => $this->random(4, 1)
        ];
        $reg_data = $this->sbt_sign($reg_data,config('sbt_key'));
        $url = $this->sbt_url . '/gate/msvr/busiopen';
        $result_reg = $this->curl_allinfo($url, false, $reg_data['data']);
        if(empty($result_reg)){
            $this->returnMsg['message'] = '认证失败';
            Db::rollback();
            return 400;
        }
        if ($result_reg->status !== 'SUCCESS') {
            $this->returnMsg['message'] = $result->message;
            Db::rollback();
            return 400;
        }
        if($result_reg->result_code!=="SUCCESS"){
            $this->returnMsg['message'] = $result->err_msg;
            Db::rollback();
            return 400;
        }
        Db::commit();
        return 200;
    }




    //阿里云四要素验证
    public function bank_card_4($card_id,$user_card,$user_name,$card_phone){
//        //银行卡实名认证
//        $url = "http://yhsys.market.alicloudapi.com/bank4";
//        $querys="bankCardNo=".$card_id."&identityNo=".$user_card."&name=".urlencode($user_name)."&mobileNo=".$card_phone;
//        $url .= "?" . $querys;
//        $response=$this->aliyun_curl($url,"GET");
//        //$this->returnMsg['sss']=$response;
//        $this->log_write('card_data',$response);
//        if(empty($response) || $response->code!=="0000"){
//            $this->returnMsg['message']="请求银行卡认证失败，请联系克服";
//            return 400;
//        }
        Vendor('hxpay.huixiangPay');
        $obj=new \PayAction();

        $response=$obj->bank_4($card_phone,$user_name,$user_card,$card_id);
//        $this->log_write('card_message',$querys);
        $this->log_write('card_message',$response);
        $response=$this->object_to_array($response);
        if($response['resp_code']!=="000000"){
            $this->returnMsg['message']="银行卡实名认证失败,或图片识别有误";
            return 400;
        }
        return 200;
    }




    public function get_my_card(){
        $card_type=input('card_type');//卡类型(1,借记卡;2,信用卡)
        $model=model('user_card');
        $card_arr=$model
            ->where('user_id',$this->online['user_id'])
            ->where('card_type',$card_type)
            ->field('card_id,bank_no,bank_name')
            ->select();
        if(!empty($card_arr)){
            foreach($card_arr as $key=>$val){
                $card_arr[$key]['debit_card']=0;
                if($val['card_id']==$this->online['debit_card']){
                    $card_arr[$key]['debit_card']=1;
                }
                $card_arr[$key]['card_id']=substr($val['card_id'],0,4)."**********".substr($val['card_id'],-4);
            }
        }
        return $card_arr;
    }

}
