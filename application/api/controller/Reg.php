<?php
namespace app\api\controller;
use think\Validate;
use think\Db;
use \think\Request;
use alisms\sms\Smsdemo;

class Reg extends Core
{
    public function index(){
        $user_id=input('phone/s','');
        $pwd=input('password/s','');
        $merchant_id=input('recommond','');
        $code=input('code');

        $reg_cod=db('code')->where('user_id',$user_id)->find();
        if(empty($reg_cod['reg_code'])){
            $this->returnMsg['message']='请先获取验证码';
            return $this->returnMsg;
        }
        if($reg_cod['code_end']<time()){
            $this->returnMsg['message']='验证码已过期';
            return $this->returnMsg;
        }
        if(md5($code)!==$reg_cod['reg_code']){
            $this->returnMsg['message']='验证码错误';
            return $this->returnMsg;
        }


        if(!empty($merchant_id)){
            $merchant=model('user')
                ->where('user_id',$merchant_id)
                ->find();
            if(empty($merchant)){
                $this->returnMsg['message']='推荐人不存在';
                return $this->returnMsg;
            }
        }
        $user=model('user')
            ->where('user_id',$user_id)
            ->find();
        if(!empty($user)){
            $this->returnMsg['message']='用户已注册，请勿重复注册';
            return $this->returnMsg;
        }



        if(strlen($pwd)<6 || strlen($pwd)>16){
            $this->returnMsg['message']='密码长度在6~16位之间';
            return $this->returnMsg;
        }
        if(!preg_match("/^(?![\d]+$)(?![a-zA-Z]+$)(?![^\da-zA-Z]+$).{6,16}$/",$pwd)){
            $this->returnMsg['message']='密码必须含有数字，字母，特殊符号中的两种';
            return $this->returnMsg;
        }
        $insert_data['user_id']=$user_id;
        $insert_data['password']=md5($pwd);
        $insert_data['reg_time']=time();

        $res=model('user')
            ->insert($insert_data);
        if(!$res){
            $this->returnMsg['message']='用户注册失败';
            return $this->returnMsg;
        }
        if(!empty($merchant_id)){
            $res=$this->bind_recommend($user_id,$merchant_id);
            if($res['status']!==200){
                $this->returnMsg['message']='推荐人绑定失败';
                return $this->returnMsg;
            }
        }
        $this->returnMsg['message']='用户注册成功';
        $this->returnMsg['status']=200;
        return $this->returnMsg;

    }

    public function change(){
        $user_id=input('phone/s','');
        $pwd=input('password/s','');
        $code=input('code/s','');
        $user=model('user')
            ->where('user_id',$user_id)
            ->find();

        if($user['code']!==md5($code)){
            $this->returnMsg['message']='验证码有误，请重新输入';
            return $this->returnMsg;
        }
        if(empty($user)){
            $this->returnMsg['message']='用户不存在';
            return $this->returnMsg;
        }
        if(strlen($pwd)<6 || strlen($pwd)>16){
            $this->returnMsg['message']='密码长度在6~16位之间';
            return $this->returnMsg;
        }
        if(!preg_match("/^(?![\d]+$)(?![a-zA-Z]+$)(?![^\da-zA-Z]+$).{6,16}$/",$pwd)){
            $this->returnMsg['message']='密码必须含有数字，字母，特殊符号中的两种';
            return $this->returnMsg;
        }

        $res=model('user')
            ->where('user_id',$user_id)
            ->setField('password',md5($pwd));
			
        if(!$res){
            $this->returnMsg['message']='密码设定失败';
            return $this->returnMsg;
        }
        $this->returnMsg['message']='密码设定成功';
        $this->returnMsg['status']=200;
        return $this->returnMsg;

    }

    public function reg_sms(){
        $mobile = input('phone','');
        $code = input('code');

        if (!preg_match("/^1[34578]{1}\d{9}$/", $mobile)) {
            $this->returnMsg['message']="请输入正确的手机号";
            return $this->returnMsg;
        }
        $user=model('user')->where('user_id',$mobile)->find();
        if(!empty($user)){
            $this->returnMsg['message']='用户已注册，请勿重复注册';
            return $this->returnMsg;
        }
        $code = $this->random(4, 1);
        $send_res=$this->sms_hy($mobile,$code);
        if(!$send_res){
            return $this->returnMsg;
        }
        $userData['user_id']=$mobile;
        $userData['reg_code']=md5($code);
        $userData['code_end']=time()+60;
        $code=db('code')
            ->where('user_id',$mobile)
            ->find();
        if(empty($code)){
            $res=db('code')->insert($userData);
            if(!$res){
                $this->returnMsg['message']='验证码生成失败';
                return $this->returnMsg;
            }
        }else{
            $res=db('code')
                ->where('user_id',$mobile)
                ->update($userData);
        }
        $this->returnMsg['message']='发送成功';
        $this->returnMsg['status']='200';
        return $this->returnMsg;
    }

    public function check_sms(){


        $mobile = input('phone');
//        $mobile = "18649738701";


        if (!preg_match("/^1[34578]{1}\d{9}$/", $mobile)) {
            $this->returnMsg['message']="请输入正确的手机号";
            return $this->returnMsg;
        }
        $user=db('user')->where('user_id',$mobile)->find();
        if(empty($user)){
            $this->returnMsg['message']='用户尚未注册，无法操作';
            return $this->returnMsg;
        }
        if($user['code_end']>time() ){
            $this->returnMsg['message']='验证码尚未过期';
            return $this->returnMsg;
        }
        $mobile_code = $this->random(4, 1);
        $send_res=$this->sms_ali($mobile_code,$mobile);

        if(!$send_res){
            return $this->returnMsg;
        }
        $userData['user_id']=$mobile;
        $userData['code']=md5($mobile_code);
        $userData['code_end']=time()+60;
        $userData['role_id']=1;
        if(empty($user)){
            $userData['reg_time']=time();
            $res=db('user')->insert($userData);
            if(!$res){
                $this->returnMsg['message']='验证码保存失败';
                return $this->returnMsg;
            }
        }else{
            $res=db('user')
                ->where('user_id',$mobile)
                ->update($userData);
            $this->returnMsg['data']['first_login']=0;
        }
        $this->returnMsg['message']='发送成功';
        $this->returnMsg['status']='200';
        return $this->returnMsg;

    }

    //绑定推荐人
    protected function bind_recommend($user_id,$merchant){
        if(empty($user_id) || empty($merchant)){
            $this->returnMsg['message']='请求数据不能为空';
            return $this->returnMsg;
        }

        if (!preg_match("/^1[34578]{1}\d{9}$/", $user_id)) {
            $this->returnMsg['message']='请输入有效手机号';
            return $this->returnMsg;
        }


        if (!preg_match("/^1[34578]{1}\d{9}$/", $merchant)) {
            $this->returnMsg['message']='请输入有效推荐人手机号';
        }

        $user_data=model('user')
            ->where('user_id',$user_id)
            ->field('merchant_id')
            ->find();
        if(!empty($user_data['merchant_id'])){
            $this->returnMsg['message']='该用户已注册推荐人,无法重复绑定';
            return $this->returnMsg;
        }
        if($merchant==$user_id){
            $this->returnMsg['message']='请勿输入自身手机号';
            return $this->returnMsg;
        }
        $merchant_data=db('user')
            ->where('user_id',$merchant)
            ->find();
        if(empty($merchant_data)){
            $this->returnMsg['message']='推荐人不存在,请重新输入';
            return $this->returnMsg;
        }
        if($merchant_data['merchant_id']==$user_id){
            $this->returnMsg['message']='不能绑定自身推广商户为推广人';
            return $this->returnMsg;
        }

        // 启动事务
        Db::startTrans();

        $update_data['merchant_id']=$merchant;
        if($merchant_data['user_type']==2 && $merchant_data['is_merchant']==2){
            $update_data['settle_rate']=$merchant_data['settle_2'];
            $update_data['group_id']=$merchant;
            $update_data['group_up']=$merchant;
            $update_data['user_type']=2;
        }elseif($merchant_data['user_type']==2 && $merchant_data['group_id']==$merchant_data['group_up']){
            $settle_3=model('user')
                ->where('user_id',$merchant_data['group_id'])
                ->value('settle_3');
            $update_data['settle_rate']=$settle_3;
            $update_data['group_id']=$merchant_data['group_id'];
            $update_data['group_up']=$merchant;
            $update_data['user_type']=2;
        }elseif($merchant_data['user_type']==2 ){
            $settle_3=model('user')
                ->where('user_id',$merchant_data['group_id'])
                ->value('settle_3');
            $update_data['settle_rate']=$settle_3;
            $update_data['group_id']=$merchant_data['group_id'];
            $update_data['group_up']=$merchant_data['group_up'];
            $update_data['user_type']=2;
        }

        $user_update=model('user')
            ->where('user_id',$user_id)
            ->update($update_data);
        if($user_update==0){
            $this->returnMsg['message']='推荐人保存失败';
            return $this->returnMsg;
        }

        $result=model('user')
            ->where('user_id',$merchant)
            ->update(['underling'=>($merchant_data['underling']+1)]);
        if($result==0){
            $this->returnMsg['message']='推荐人保存失败';
            return $this->returnMsg;
        }

        if(!empty($merchant_data['merchant_id'])){
            $superior_data=model('user')
                ->where('user_id',$merchant_data['merchant_id'])
                ->find();
            $result2=model('user')
                ->where('user_id',$superior_data['user_id'])
                ->update(['indirect'=>($superior_data['indirect']+1)]);
        }
        if($result>0 && empty($merchant_data['merchant_id']) ||$result>0 && $result2>0 && !empty($merchant_data['merchant_id'])){
            Db::commit();

        }else{
            Db::rollback();
            $this->returnMsg['message']='保存失败,请重试';
            return $this->returnMsg;
        }


        $this->returnMsg['message']='推荐人保存成功';
        $this->returnMsg['status']=200;
        return $this->returnMsg;

    }




}