<?php
/**
 * Created by PhpStorm.
 * User: mr_z
 * Date: 2017/9/21
 * Time: 下午4:15
 *///代理商汇率修改
class Test extends Core{


    public function role_rate(){
    $settle_rate=input('post.settle_rate');
    $role_id=input('post.role_id');
    $save_data=input('post.');
    if($settle_rate>$this->sye_rate){
        $this->returnMsg['message']='输入汇率不能高于平台最高汇率';
        return $this->returnMsg;
    }
    $result=Db::table('role')
        ->where("role_id",$role_id)
        ->update($save_data);

    if(!$result){
        $this->returnMsg['message']='更新失败';
        return $this->returnMsg;
    }
    $this->returnMsg['message']='更新成功';
    $this->returnMsg['status']=200;
    return $this->returnMsg;
    }

    public function get_mcht(){
    $user_list=Db::table("user")
        ->field("mcht_no,secretKey")
        ->where("role_id",'5')
        ->where("merchant_id",'0')
        ->select();
    dump($user_list);
    foreach($user_list as $val){
        $reg_data = [
            'sp_id' => config('sp_id'),
            'mcht_no' => $val['mcht_no'],
            'busi_type' => 'EPAYS',
            'settle_type' => 'REAL_PAY',
            'settle_rate' => 0.005,
            'extra_rate_type' => 'AMOUNT',
            'extra_rate' => 200,
            'nonce_str' => $this->random(4, 1)
        ];
        $reg_data = $this->sbt_sign($reg_data,config('secretKey'));
        $url = config('sbt_api_url'). '/gate/msvr/busiratemodify';
        $result_reg = $this->curl_allinfo($url, false, $reg_data['data']);

        if(empty($result_reg)){
            $this->returnMsg['message'] = '认证失败';
            return $this->returnMsg;
        }
        if ($result_reg->status !== 'SUCCESS') {
            $this->returnMsg['message'] = $result_reg->message;
            return $this->returnMsg;
        }
        if($result_reg->result_code!=="SUCCESS"){
            $this->returnMsg['message'] = $result_reg->err_msg;
            return $this->returnMsg;
        }
        $this->returnMsg['status']=200;
        return $this->returnMsg;
    }
    }
    public function seek_mcht(){
    $reg_data = [
        'sp_id' => config('sp_id'),
        'mcht_no' => 108650000000006,
        'nonce_str' => $this->random(4, 1)
    ];
    $reg_data = $this->sbt_sign($reg_data,config('secretKey'));
    $url = config('sbt_api_url'). '/gate/msvr/mchtbaseqry';
    $result_reg = $this->curl_allinfo($url, false, $reg_data['data']);
    dump($reg_data);
    dump($result_reg);
    }
}