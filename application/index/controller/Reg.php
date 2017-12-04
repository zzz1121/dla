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
    }
    public function index()
    {
        $merchant_id=input('merchant_id');
        $user=model('user')
            ->where('user_id',$merchant_id)
            ->find();

        session('merchant_id',$merchant_id);
        $merchant=substr($merchant_id,0,3)."****".substr($merchant_id,-4);
        session('recommend',$merchant_id);
        $this->assign('merchant_id',$merchant_id);
        $this->assign('merchant',$merchant);
        $this->assign('user',$user);
        return $this->fetch();
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
