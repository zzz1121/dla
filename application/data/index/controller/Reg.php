<?php
namespace app\index\controller;
use think\Model;
use \think\Session;
use think\Db;
class Reg extends Core
{
    public $ios_load_url="itms-services://?action=download-manifest&url=https://downloadpkg.apicloud.com:443/zip/56/45/564509f00756a734b382ca490e33ffee.plist";
    public $android_url="http://downloadpkg.apicloud.com/app/download?path=http://7yz1kb.com1.z0.glb.clouddn.com/314de47fc30ce884db94202b744a3558_d";
    //public $android_url="http://shouji.baidu.com/software/22475414.html";

    public function load(){

        $ios_load_url=$this->ios_load_url;
        $android_url=$this->android_url;
        $this->assign('ios_load_url',$ios_load_url);
        $this->assign('android_url',$android_url);
        return $this->fetch();
        return $this->fetch();
    }
    public function index()
    {
        $merchant_id=input('merchant_id');
        $user=model('user')
            ->where('user_id',$merchant_id)
            ->find();

        session('merchant_id',$merchant_id);
        $ios_load_url=$this->ios_load_url;
        $android_url=$this->android_url;
        $ios_logo="./public/static/images/ios_logo.jpeg";

        $ios_img=$this->getQrcode($ios_load_url,'ios',$ios_logo);
        $android_logo="./public/static/images/android_logo.jpeg";

        $android_img=$this->getQrcode($android_url,'android',$android_logo);
        $merchant_id=substr($merchant_id,0,3)."****".substr($merchant_id,-4);
        session('recommend',$merchant_id);
        $this->assign('merchant_id',$merchant_id);
        $this->assign('ios_img',$ios_img);
        $this->assign('android_img',$android_img);
        $this->assign('user',$user);
        return $this->fetch();
    }
    public function app_load(){
        $user_id=session('reg_result');
        //if(empty($user_id)){
        //$this->redirect('reg/index');
        //}

        $ios_load_url=$this->ios_load_url;
        $android_url=$this->android_url;
        $ios_logo="./public/static/images/ios_logo.jpeg";

        $ios_img=$this->getQrcode($ios_load_url,'ios',$ios_logo);
        $android_logo="./public/static/images/android_logo.jpeg";

        $android_img=$this->getQrcode($android_url,'android',$android_logo);


        $this->assign('ios_load_url',$ios_load_url);
        $this->assign('android_url',$android_url);
        $this->assign('ios_img',$ios_img);
        $this->assign('android_img',$android_img);
        return $this->fetch();
    }

    public function reg(){
        $user_id=input('phone');
        $merchant_id=session('merchant_id');

        if($user_id==$merchant_id){
            $this->returnMsg['message']="无法绑定自身手机号";
            return $this->returnMsg;
        }
        $code=input('code');
        if (!preg_match("/^1[34578]{1}\d{9}$/", $user_id)) {
            $this->returnMsg['message']="您输入的本人手机号有误，请重新输入";
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
        if (preg_match("/^1[34578]{1}\d{9}$/", $merchant_id)) {
            $merchant_data=model('user')
                ->where('user_id',$merchant_id)
                ->find();
        }else{
            $merchant_data=model('user')
                ->where('generalize',$merchant_id)
                ->find();
        }

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

        $user_update=model('user')
            ->where('user_id',$user_id)
            ->update(['merchant_id'=>$merchant_id]);
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

    public function getQrcode($url,$filename,$logo=null){
        if(empty($logo)){
            $level=3;
            $size=4;

            Vendor('Phpqrcode.phpqrcode');
            $errorCorrectionLevel =intval($level) ;//容错级别
            $matrixPointSize = intval($size);//生成图片大小
            //生成二维码图片
            $object = new \QRcode();
            $file_path="./public/qrcode/".$filename.".png";
            $object->png($url, $file_path, $errorCorrectionLevel, $matrixPointSize, 2);
            return $file_path;
        }
        //带LOGO
        $level=3;
        $size=4;
        Vendor('Phpqrcode.phpqrcode');
        $errorCorrectionLevel =intval($level) ;//容错级别
        $matrixPointSize = intval($size);//生成图片大小
        //生成二维码图片
        $object = new \QRcode();
        $file_path="./public/qrcode/".$filename.".png";
        $ad = $file_path;
        $object->png($url, $ad, $errorCorrectionLevel, $matrixPointSize, 2);
        $logo = $logo;//准备好的logo图片
        $QR = $file_path;

        if ($logo !== FALSE) {
            $QR = imagecreatefromstring(file_get_contents($QR));
            $logo = imagecreatefromstring(file_get_contents($logo));
            $QR_width = imagesx($QR);//二维码图片宽度
            $QR_height = imagesy($QR);//二维码图片高度
            $logo_width = imagesx($logo);//logo图片宽度
            $logo_height = imagesy($logo);//logo图片高度
            $logo_qr_width = $QR_width / 5;
            $scale = $logo_width/$logo_qr_width;
            $logo_qr_height = $logo_height/$scale;
            $from_width = ($QR_width - $logo_qr_width) / 2;
            //重新组合图片并调整大小
            imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
                $logo_qr_height, $logo_width, $logo_height);
        }
        //输出图片  带logo图片
        $logo_file="./public/qrcode/".$filename."_logo.png";
        imagepng($QR, $logo_file);
        return $logo_file;
    }
}
