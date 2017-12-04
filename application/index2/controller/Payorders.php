<?php
namespace app\index2\controller;
use think\Controller;
use think\Config;
use think\Db;
class Payorders extends Online
{
    public function index()
    {
        $status=input('get.order_status');
        $keyworld = input('keyworld');
        $pay_time = input('pay_time');
        $where_data=" 1 =1 ";
        if (!empty($keyworld)) {
            if (preg_match("/^1[34578]{1}\d{9}$/", $keyworld)) {
                $where_data.="and a.user_id='".$keyworld."'";
            } elseif(preg_match("/^[\x{4e00}-\x{9fa5}]+$/u",$keyworld)) {
                $where_data.="and b.name like '%".$keyworld."%'";
            }else{
                $where_data.="and a.pay_order_id='".$keyworld."'";
            }
        }
        if(!empty($status)){
            $where_data.='and a.pay_status="'.$status.'"';

        }
        if(!empty($pay_time)){
            $where_data.=' and a.pay_time >="'.strtotime($pay_time).'"';
//            $where_data.=' and a.pay_time <="'.strtotime($pay_time)+strtotime('1 day').'"';
            $this->sort='asc';
        }
        $where_data.="and a.user_id='".$this->user_id."'";
        $count=Db::table('pay_orders')
            ->alias('a')
            ->join('user b','a.user_id=b.user_id')
            ->field('count(pay_order_id) as count')
            ->where($where_data)

            ->find()['count'];
        $list=[];
        $page='';
        if(!empty($count)){
            $list = Db::table('pay_orders')
                ->alias('a')
                ->join('user b','a.user_id=b.user_id')
                ->where($where_data)
                ->order('pay_time '.$this->sort)
                ->paginate(10,$count,[
                    'page' => input('param.page'),
                    'path'=>url('index').'?page=[PAGE]'."&order_status=".$status."&keyworld=".$keyworld."&pay_time=".$pay_time
                ])
                ->each(function($item, $key){
                    $item['pay_time'] = date("Y-m-d H:i:s",$item['pay_time']);
//                    $item['order_status']=config('orders_status')[$item['order_status']];
                    // 获取分页显示

                });
            $page = $list->render();

        }
        // 模板变量赋值
//            var_dump($list);
        $this->assign('lists', $list);
        $this->assign('pages', $page);
        $this->assign('order_id',$keyworld);
        $this->assign('status',$status);
        $orders_status=config('orders_status');
        $this->assign('orders_status',$orders_status);
        // 渲染模板输出
//            var_dump($this->fetch('orders'));exit;
        return $this->fetch('');
    }



}
