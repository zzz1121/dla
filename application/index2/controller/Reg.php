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
        // 启动事务
            Db::startTrans();

        $update_data['merchant_id']=$merchant_id;
        if($merchant_data['user_type']==2 && $merchant_data['is_merchant']==2){
            $update_data['settle_rate']=$merchant_data['settle_2'];
            $update_data['group_id']=$merchant_id;
            $update_data['group_up']=$merchant_id;
            $update_data['user_type']=2;
        }elseif($merchant_data['user_type']==2 && $merchant_data['group_id']==$merchant_data['group_up']){
            $settle_3=model('user')
                ->where('user_id',$merchant_data['group_id'])
                ->value('settle_3');
            $update_data['settle_rate']=$settle_3;
            $update_data['group_id']=$merchant_data['group_id'];
            $update_data['group_up']=$merchant_id;
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
            ->where('user_id',$merchant_id)
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
