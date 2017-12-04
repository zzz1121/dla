<?php

namespace app\index\controller;

use think\Controller;
use think\Db;
use think\Session;
use think\Vendor;
class Merchant extends Index {

    public function index() {
        $where=" 1=1 and role_id>1 ";
        $user_id = Session::get('user_id');
        $role_id = Session::get('role_id');
        if (request()->isPost()) {
            $select_role_id = $_POST['card_status'];
            $keyworld = $_POST['keyworld'];

            if (!empty($keyworld)) {
                if (preg_match("/^1[34578]{1}\d{9}$/", $keyworld)) {
                    $where.=' and merchant_id='.$keyworld;
                } else {
                    $where.="and name like '%$keyworld%'";
                }
            }
            $user_list_count = Db::table('merchant')->where($where)->count();
            $user_list = Db::table('merchant')->where($where)->paginate(5, $user_list_count);

            $page = $user_list->render();
            $arr['user_list'] = $user_list;

            $this->assign('page', $page);

            return $this->fetch('list', $arr);

        } else {

            $merchant_status=input('merchant_status');
            if(!empty($card_status)){
                $where.=' and merchant_status ='.$merchant_status;
            }

                $user_list_count = Db::table('merchant')->where($where)->count();
                $user_list = Db::table('merchant')->where($where)->paginate(5, $user_list_count);

            $page = $user_list->render();
            $arr['user_list'] = $user_list;

            $this->assign('page', $page);
            return $this->fetch('list', $arr);
        }
    }
    public function edit(){
        return $this->fetch("edit");
    }


    //升级审核
    public function audit(){
        $role_id=input('role_id',2);

        $role_data=Db::table('role')
            ->where('role_id','>','1')
            ->select();
        $this->assign('role_data',$role_data);
        $upgrade=Db::table('role')
            ->where('role_id',$role_id)
            ->find();
        $this->assign('upgrade',$upgrade);


        $where="1=1 and a.card_status=1";
        $where.=' and a.underling+ a.indirect >='.$upgrade['referrer_count'];

        //$where.=' and a.integral >'.$upgrade['integral'];
        $where.=' and a.role_id ='.($role_id-1);
        $merchant_list=Db::table('user')
            ->alias('a')
            ->field('a.*')
            ->where($where)
            ->select();

        foreach($merchant_list as $key=>$val){
            $start_time=time()-strtotime('-30day');
            $val['sum']=Db::table('commission')
                ->where([
                    'user_id'=>$val['user_id'],
                    'commission_time'=>array('>=',$start_time)
                ])
                ->field('sum(commission_money) as sum')
                ->find()['sum'];
            $average=(int)($val['sum']/$upgrade['period']);
            $merchant_list[$key]['average']=$average;
            if($upgrade['upgrade_type']==2 && $average<$upgrade['integral'] || $upgrade['upgrade_type']==0 &&$average<$upgrade['integral']){
                unset($merchant_list[$key]);
            }
        }

        $this->assign('user_list',$merchant_list);
        return $this->fetch();
    }


    public function getWchatQrcode($users_id=1){
        //带LOGO
        // $url = 'http://mydd.0317cn.net/index.php/Home/Logo/res/users_id/'.$users_id; //二维码内容
        // $errorCorrectionLevel = 'L';//容错级别
        // $matrixPointSize = 9;//生成图片大小
        // //生成二维码图片
        // Vendor('phpqrcode.phpqrcode');
        // $object = new \QRcode();
        // $ad = 'erweima/'.$users_id.'.jpg';
        // $object->png($url, $ad, $errorCorrectionLevel, $matrixPointSize, 2);
        // $logo = 'erweima/2.jpg';//准备好的logo图片
        // $QR = 'erweima/'.$users_id.'.jpg';//已经生成的原始二维码图

        // if ($logo !== FALSE) {
        //   $QR = imagecreatefromstring(file_get_contents($QR));
        //   $logo = imagecreatefromstring(file_get_contents($logo));
        //   $QR_width = imagesx($QR);//二维码图片宽度
        //   $QR_height = imagesy($QR);//二维码图片高度
        //   $logo_width = imagesx($logo);//logo图片宽度
        //   $logo_height = imagesy($logo);//logo图片高度
        //   $logo_qr_width = $QR_width / 5;
        //   $scale = $logo_width/$logo_qr_width;
        //   $logo_qr_height = $logo_height/$scale;
        //   $from_width = ($QR_width - $logo_qr_width) / 2;
        //   //重新组合图片并调整大小
        //   imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
        //   $logo_qr_height, $logo_width, $logo_height);
        // }
        //输出图片  带logo图片
        // imagepng($QR, 'erweima/'.$users_id.'.png');
        $url="http://www.baidu.com";$level=3;$size=4;

        Vendor('Phpqrcode.phpqrcode');
        $errorCorrectionLevel =intval($level) ;//容错级别
        $matrixPointSize = intval($size);//生成图片大小
        //生成二维码图片
        $object = new \QRcode();
        $object->png($url, false, $errorCorrectionLevel, $matrixPointSize, 2);
    }

}
