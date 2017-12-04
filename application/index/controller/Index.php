<?php
namespace app\index\controller;
use think\Model;
use \think\Session;
class Index extends Core
{
    public function index()
    {
        if(request()->isGet()){
            return $this->fetch();
        }
    }
    public function login(){
        $user_id=input('post.acount');
        $password=input('post.password');
        $code=input('post.code');
        if(empty($code)){
            $this->returnMsg['message']='验证码不能为空';
            return $this->returnMsg;
        }
        if(!captcha_check($code)){
            $this->returnMsg['message']='验证码错误';
            return $this->returnMsg;
        };
        $user_data=model('merchant')
            ->alias('a')
            //->join('role b',"a.role_id=b.role_id")
            //->field('a.merchant_id,a.role_id,b.role_name')
            ->where('a.merchant_id',$user_id)
            ->where('a.password',md5($password))
            ->find();

        if(empty($user_data)){
            $this->returnMsg['message']='账号密码有误';
            return $this->returnMsg;
        }
        if($user_data['role_id']!==0){
            $this->returnMsg['message']='抱歉,你的账号权限无法登陆管理系统';
            return $this->returnMsg;
        }
        session('user_id',$user_data['merchant_id']);
        session('role_id',$user_data['role_id']);
        $this->returnMsg['message']='登陆成功';
        $this->returnMsg['status']=200;
        return $this->returnMsg;
    }
	public function reg(){
	    $merchant_id=input('merchant_id');
        $this->assign('merchant_id',$merchant_id);
		return $this->fetch('index/reg');
	}
	
	
}
