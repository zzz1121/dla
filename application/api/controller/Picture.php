<?php
namespace app\api\controller;
use think\Db;
use \think\File;
class Picture extends Online
{
    public function index(){
        $file=input('picture');
        $pic_path=$this->base_img_upload($file,'picture');

        if(!$pic_path && empty($_FILES)){
            return $this->returnMsg;
        }
        if(!empty($_FILES)){

            $file=$_FILES['file'];
            $pic_path=$this->load_img_save($file);
        }
        $response=Db::table('user')
            ->where('user_id',$this->online['user_id'])
            ->setField('picture',$pic_path);
        if(!$response){
            $this->returnMsg['图片保存失败,请稍后再试'];
            return $this->returnMsg;
        }
        $this->returnMsg['message']='更新成功;';
        $this->returnMsg['status']='200';
        $this->returnMsg['data']['picture']=$pic_path;
        return $this->returnMsg;
    }


}
