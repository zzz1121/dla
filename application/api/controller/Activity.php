<?php
namespace app\api\controller;
use think\Validate;
use think\Db;
use \think\Request;
use alisms\sms\Smsdemo;
class Activity extends Index
{
    public function index(){
        $data_list=Db::table('activity')
            ->select();
        $this->returnMsg['status']=200;
        $this->returnMsg['message']='请求成功';
        $this->returnMsg['data']=$data_list;
        return $this->returnMsg;
    }

}
