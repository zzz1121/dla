<?php
namespace app\admin\controller;
use think\Controller;
class Personal extends Online
{
    public function index()
    {
        if(request()->isGet()){
            $this->assign('role_id',$this->role_id);
            return $this->fetch();
        }
    }
    public function info(){
        $data=[];
        $this->assign('data',$data);
        return $this->fetch();
    }

    public function update_pwd(){
        if(request()->isPost()){
            $password=input('post.password');
            $last_pwd=input('post.last_password');
            $last_pwd2=input('post.last_password2');
            if(empty($password) || empty($last_pwd) || empty($last_pwd2)){
                $this->returnMsg['message']='输入不能为空,请检查';
                return $this->returnMsg;
            }
            $pwd_result=model('admin')
                ->where('account',$this->user_id)
                ->where('password',md5($password))
                ->find();
            if(empty($pwd_result)){
                $this->returnMsg['message']='当前密码输入有误,请重试';
                return $this->returnMsg;
            }
            if (strlen($last_pwd)>30 || strlen($last_pwd)<6){
                $this->returnMsg['message']='密码必须为6-30位的字符串';
                return $this->returnMsg;
            }
            if(!preg_match("/^[a-z\d]*$/i",$last_pwd))
            {
                $this->returnMsg['message']='密码只能包含数字和字母';
                return $this->returnMsg;
            }
            if($last_pwd!==$last_pwd2){
                $this->returnMsg['message']='新密码两次输入不同';
                return $this->returnMsg;
            }
            $model=model('admin');
            $res=$model
                ->where('account',$this->user_id)
                ->update(['password'=>md5($last_pwd)]);
            if($res==0){
                $this->returnMsg['message']='新密码与原密码相同,请重新输入';
                return $this->returnMsg;
            }
            $this->returnMsg['message']='密码修改成功';
            $this->returnMsg['url']=url('personal/index');
            $this->returnMsg['status']=200;
            return $this->returnMsg;
        }
    }


    //退出登录
    public function quit_login(){
        session(null);
        if(!empty(session('user_id'))){
            $this->returnMsg['message']='退出失败';
            return $this->returnMsg;
        }
        $this->returnMsg['message']='退出成功';
        $this->returnMsg['status']=200;
        return $this->returnMsg;
    }
}
