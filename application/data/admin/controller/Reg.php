<?php
namespace app\index\controller;
use think\Model;
use \think\Session;
class Reg extends Core
{
    public function index()
    {
        $merchant_id=input('merchant_id');
        session('recommend',$merchant_id);
        $this->assign('merchant_id',$merchant_id);
		return $this->fetch();
    }

    public function reg(){
        $user_id=input('phone');
        $merchant_id=session('recommend');
        $code=input('code');
        if (!preg_match("/^1[34578]{1}\d{9}$/", $user_id)) {
            $this->returnMsg['message']="请输入有效手机号";
            return $this->returnMsg;
        }
        if(empty($code)){
            $this->returnMsg['message']="请输入验证码";
            return $this->returnMsg;
        }
        $user_data=model('user')
            ->where('user_id',$user_id)
            ->find();
        $this->returnMsg['datt']=$user_data;
        if(empty($user_data) || $user_data['code_end']<time()){
            $this->returnMsg['message']="验证码已失效,请重新发送";
            return $this->returnMsg;
        }
        if($user_data['code']!==md5($code)){
            $this->returnMsg['message']="验证码错误";
            return $this->returnMsg;
        }
        $this->returnMsg['data']=$user_data;
        if(!empty($user_data['merchant_id'])){
            $this->returnMsg['message']='抱歉,您已绑定过推荐人';
            return $this->returnMsg;
        }
        $merchant_data=model('user')
            ->where('user_id',$merchant_id)
            ->find();
        if(empty($merchant_data)){
            $this->returnMsg['message']="推荐人不存在";
            return $this->returnMsg;
        }
        if($merchant_data['merchant_id']==$user_id){
            $this->returnMsg['message']="不能绑定下属商户为自身推荐人";
            return $this->returnMsg;
        }
        $result=model('user')
            ->where('user_id',$user_id)
            ->update(
                ['merchant_id'=>$merchant_id]
            );
        if($result==0){
            $this->returnMsg['message']='抱歉,绑定失败,请重试';
            return $this->returnMsg;
        }
        $this->returnMsg['message']="绑定成功";
        $this->returnMsg['status']=200;
        return $this->returnMsg;
    }

}
