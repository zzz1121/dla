<?php
namespace app\index2\controller;
use think\Model;
use \think\Session;
use think\Db;
class Publics extends Core
{

    public function index()
    {
        if(!empty(session('merchant_id'))){
            $this->redirect('personal/index');
        }
        if(request()->isPost()){
            $user_id=input('post.username');
            $password=input('post.password');
            $code=input('post.code');
            $this->returnMsg['action']='login';
            if(empty($code)){
                $this->returnMsg['message']='验证码不能为空';
                return $this->returnMsg;
            }
            if(!captcha_check($code)){
                $this->returnMsg['message']='验证码错误';
                return $this->returnMsg;
            };
            $user_data=model('user')
                ->where('user_id',$user_id)
                ->find();
            if(empty($user_data)){
                $this->returnMsg['message']='账户密码有误';
                return $this->returnMsg;
            }
            if($user_data['is_merchant']!==2){
                $this->returnMsg['message']='抱歉,你的账号权限无法登陆管理系统';
                return $this->returnMsg;
            }

            if($user_data['password']!==md5($password)){
                $this->returnMsg['message']='密码有误';
                return $this->returnMsg;
            }

            $role_data=Db::table('role')
                ->where(['role_id'=>array('>','0')])
                ->order('role_id')
                ->select();
            session('role_data',$role_data);
            session('merchant_id',$user_data['user_id']);
            session('role_id',$user_data['role_id']);
            $this->returnMsg['url']=url('personal/index');
            $this->returnMsg['message']='登陆成功,页面跳转中';
            $this->returnMsg['status']=200;
            return $this->returnMsg;
        }
        return $this->fetch();

    }


    //退出登录
    public function logout(){
        session(null);
        if(!empty(session('merchant_id'))){
            $this->redirect('personal/index');
        }
        $this->redirect('publics/index');
    }


    // 锁屏解锁
    public function unlocked(){
        $password=input('post.password');
        $user_id=session('user_id');
        $this->returnMsg['data']=$user_id;
        $user_data=model('merchant')
            ->where('merchant_id',$user_id)
            ->find();
        if($user_data['password']!==md5($password)){
            $this->returnMsg['data']=$user_data;
            $this->returnMsg['ddd']=md5($password);
            return $this->returnMsg;
        }
        $this->returnMsg['status']=200;
        $this->returnMsg['message']='解锁成功';
        return $this->returnMsg;
    }


	
}
