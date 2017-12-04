<?php
namespace app\api\controller;
use \think\File;
use \think\Db;
use \think\Model;
use \think\Request;
class Describe extends Index
{
   


    //代理商户信息
    public function index(){


        $card_pic=input('post.question_pic');

        $pic_path=$this->base_img_upload($card_pic,'question');



        $add_data['question_id']=time().$this->online['user_id'];
        $add_data['question_body']=input('post.question_body');
        $add_data['question_pic']=$pic_path;
        $add_data['user_qq']=input('post.user_qq');
        $add_data['mobile']=input('post.phone');
        $add_data['question_time']=time();

        if (!preg_match("/^1[34578]{1}\d{9}$/", $add_data['mobile'])) {
            $this->returnMsg['message']="请输入有效手机号";
            return $this->returnMsg;
        }


        if(empty($add_data['question_body'])){
            $this->returnMsg['message']='反馈描述不能为空';
            return $this->returnMsg;
        }
        if(empty($add_data['mobile'])){
            $this->returnMsg['message']='联系电话不能为空';
            return $this->returnMsg;
        }
        if(empty($add_data['question_pic'])){
            $this->returnMsg['message']='请选择问题截图';
            return $this->returnMsg;
        }
        //return $this->returnMsg;
        $res=model('question')
            ->insert($add_data);
        if(!$res){
            $this->returnMsg['message']='反馈保存失败,请重试';
            return $this->returnMsg;
        }
        $this->returnMsg['message']='反馈成功';
        $this->returnMsg['status']=200;
        return $this->returnMsg;
    }
}
