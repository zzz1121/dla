<?php
namespace app\api\controller;
use think\Validate;
use think\Db;
use \think\Request;
use alisms\sms\Smsdemo;
class Login extends Core
{
    public function index(){
        $user_id=input('phone');
        if(request()->isPost()){
            $code=input('post.code');
            if(empty($user_id) || empty($code)){
                $this->returnMsg['message']='数据错误';
                return $this->returnMsg;
            }
            if (!preg_match("/^1[34578]{1}\d{9}$/", $user_id)) {
                $this->returnMsg['message']='请输入有效手机号';
                return $this->returnMsg;
            }
            $user=Db::table('user')
                ->where('user_id',$user_id)
                //->field('user_id,picture,code,name,code_end,card_status,merchant_id,role_id,login_status,number,ids')
                ->find();
            if(empty($user)){
                $this->returnMsg['message']='手机号错误,请重新输入';
                return $this->returnMsg;
            }
            if($user['login_status']!==1){
                $this->returnMsg['message']='该手机号已经被锁定,请联系客服';
                return $this->returnMsg;
            }
            if($user['code']!==md5($code)){
                $this->returnMsg['message']='验证码错误,请重新输入';
                return $this->returnMsg;
            }
            if($user['code_end']<time()){
                $this->returnMsg['message']='验证码已失效,请重新发送';
                return $this->returnMsg;
            }
            $updateData['token']=$this->access_token($user_id);
            $updateData['token_end']=strtotime("+30 day");
            $result=Db::table('user')
                ->where('user_id',$user_id)
                ->update($updateData);
            if($result<=0){
                $this->returnMsg['message']='系统错误';
                $this->returnMsg['status']='500';
                return $this->returnMsg;
            }
            $this->online=$user;
            if(strlen($user['name'])>6){
                $user['name']=mb_substr($user['name'],0,1,'utf-8')."*".mb_substr($user['name'],-1,1,'utf-8');
            }else{
                $user['name']="*".mb_substr($user['name'],-1,1,'utf-8');
            }

            if(!empty($user['number'])){
                $user['number']=substr($user['number'],0,5)."*********".substr($user['number'],-1);
            }
            $this->returnMsg['data']=[
                'user_id'=>$user_id,
                'token'=>$updateData['token'],
                'picture'=>$user['picture'],
                'card_status'=>$user['card_status'],
                'name'=>$user['name'],
                'merchant_id'=>$user['merchant_id'],
                'role_id'=>$user['role_id'],
                'number'=>$user['number'],
                'is_merchant'=>1
            ];
            if($user_id=="17750775177"){
                $this->returnMsg['data']['is_merchant']=2;
            }
            $this->returnMsg['data']['first_pwd']=0;
            if(empty($user['password'])){
                $this->returnMsg['data']['first_pwd']=1;
            }
            $this->returnMsg['message']='登录成功';
            $this->returnMsg['status']=200;
            return $this->returnMsg;
        }else if(request()->isGet()){
            $token=input('token');
            $result=$this->token_check();
            if($result!==200){
                $this->returnMsg['status']=404;
                $this->returnMsg['message']='登录授权失败，请重新登录';
                return $this->returnMsg;
            }
            if($this->online['login_status']!==1){
                $this->returnMsg['message']='该手机号已经被锁定,请联系客服';
                return $this->returnMsg;
            }
            $user=$this->online;
            $this->returnMsg['name']=strlen($user['name']);
            if(strlen($user['name'])>6){
                $user['name']=mb_substr($user['name'],0,1,'utf-8')."*".mb_substr($user['name'],-1,1,'utf-8');
            }else{
                $user['name']="*".mb_substr($user['name'],-1,1,'utf-8');
            }
            if(!empty($user['number'])){
                $user['number']=substr($user['number'],0,5)."*********".substr($user['number'],-1);

            }
            $this->returnMsg['data']=[
                'user_id'=>$user_id,
                'token'=>$user['token'],
                'picture'=>$user['picture'],
                'card_status'=>$user['card_status'],
                'name'=>$user['name'],
                'merchant_id'=>$user['merchant_id'],
                'role_id'=>$user['role_id'],
                'number'=>$user['number'],
                'is_merchant'=>1
            ];
            if($user_id=="17750775177"){
                $this->returnMsg['data']['is_merchant']=2;
            }

            $this->returnMsg['message']='登录成功';
            $this->returnMsg['status']=200;
            return $this->returnMsg;
        }
    }

    //密码登录
    public function check(){
        $user_id=input('phone');
        $pwd=input('password');
        if(empty($user_id) || empty($pwd)){
            $this->returnMsg['message']='手机号、密码不能为空';
            return $this->returnMsg;
        }
        if (!preg_match("/^1[34578]{1}\d{9}$/", $user_id)) {
            $this->returnMsg['message']='手机号格式有误，请重新输入';
            return $this->returnMsg;
        }
        $user=model('user')
            ->where('user_id',$user_id)
            ->find();
        if(empty($user)){
            $this->returnMsg['message']='手机号尚未注册';
            return $this->returnMsg;
        }
        if(empty($user['password'])){
            $this->returnMsg['message']='尚未设定账号密码，请使用短信码校验登录';
            return $this->returnMsg;
        }
        if($user['password']!==md5($pwd)){
            $this->returnMsg['message']='手机号或密码错误';
            return $this->returnMsg;
        }

        $updateData['token']=$this->access_token($user_id);
        $updateData['token_end']=strtotime("+30 day");
        $result=Db::table('user')
            ->where('user_id',$user_id)
            ->update($updateData);
        if($result<=0){
            $this->returnMsg['message']='系统错误';
            $this->returnMsg['status']='500';
            return $this->returnMsg;
        }
        $this->online=$user;
        if(strlen($user['name'])>6){
            $user['name']=mb_substr($user['name'],0,1,'utf-8')."*".mb_substr($user['name'],-1,1,'utf-8');
        }else{
            $user['name']="*".mb_substr($user['name'],-1,1,'utf-8');
        }

        if(!empty($user['number'])){
            $user['number']=substr($user['number'],0,5)."*********".substr($user['number'],-1);

        }
        $this->returnMsg['data']=[
            'user_id'=>$user_id,
            'token'=>$updateData['token'],
            'picture'=>$user['picture'],
            'card_status'=>$user['card_status'],
            'name'=>$user['name'],
            'merchant_id'=>$user['merchant_id'],
            'role_id'=>$user['role_id'],
            'number'=>$user['number'],
            'is_merchant'=>1
        ];
        if($user_id=="17750775177"){
            $this->returnMsg['data']['is_merchant']=2;
        }

        $this->returnMsg['message']='登录成功';
        $this->returnMsg['status']=200;
        return $this->returnMsg;


    }


    private function access_token($user_id){
        $str=$user_id.time();
        $str.=$this->random(4,1);
        return md5($str);
    }
    //短信码发送
    public function sms_send(){
        //获取get信息
        $mobile = input('phone');
//        $mobile = "18649738701";

        if (empty($mobile)) {
            return json($this->returnMsg);
        }
        if (!preg_match("/^1[34578]{1}\d{9}$/", $mobile)) {
            $this->returnMsg['message']="请输入有效手机号";
            return $this->returnMsg;
        }
        $user=Db::table('user')->where('user_id',$mobile)->find();
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
            $res=Db::table('user')->insert($userData);
            $this->returnMsg['data']['first_login']=1;
            if(!$res){
                $this->returnMsg['message']='系统错误';
                return $this->returnMsg;
            }
        }else{
            $res=Db::table('user')
                ->where('user_id',$mobile)
                ->update($userData);
            $this->returnMsg['data']['first_login']=0;
        }
        $this->returnMsg['message']='发送成功';
        $this->returnMsg['status']='200';
        return $this->returnMsg;
    }
    public function sms_default($mobile_code,$mobile){
        $host = "http://smsapi.api51.cn";
        $path = "/code/";
        $method = "GET";
        $querys = "code=".$mobile_code."&mobile=".$mobile;
        $url = $host . $path . "?" . $querys;
        $response=$this->aliyun_curl($url);
        if(!$response->success){
            $this->returnMsg['message']='发送失败,请稍后再试';
            $this->returnMsg['status']='401';
            return false;
        }
        return true;
    }

    public function sms_ali($mobile_code="1233",$moblie="18649738701"){
        if(empty($mobile_code) || empty($moblie)){
            return false;
        }
//        header('Content-Type: text/plain; charset=utf-8');
        $demo = new Smsdemo(
            "LTAItTnVL6dKGHMg", // 请替换成您自己的AccessKeyId
            "eKzCbneB27Dhzs8wrgvPGbj1Pbv2OX" // 请替换成您自己的AccessKeySecret
        );
        $response = $demo->sendSms(
            "随便提", // 短信签名
            "SMS_105020005", // 短信模板编号
            (string)$moblie, // 短信接收者
            Array(  // 短信模板中字段的值
                "code"=>(string)$mobile_code
            )
        );
        if(empty($response)){
            $this->returnMsg['message']='短信发送失败';
            $this->returnMsg['status']='401';
            return false;
        }
        $result=$this->object_to_array($response);
        if($result['Code']=='isv.BUSINESS_LIMIT_CONTROL'){
            $this->returnMsg['message']='发送请求太过频繁，请稍后再试';
            $this->returnMsg['status']='401';
            return false;
        }elseif($result['Code']=='OK'){
            return true;
        }
        return false;
    }



}