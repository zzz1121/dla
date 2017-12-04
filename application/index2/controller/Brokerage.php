<?php
namespace app\index2\controller;
use think\Controller;
use think\Config;
use think\Db;
class Brokerage extends Online
{
    public function index()
    {
        $keyworld=input("get.keyworld/s");
        $where_data=" 1 =1 and commission_money>0 ";
        $order_time=input('get.order_time');
        $end_time=input('get.end_time',date("Y-m-d H:i:s",time()));
        if (!empty($keyworld)) {
            if (preg_match("/^1[34578]{1}\d{9}$/", $keyworld)) {
                $where_data.=" and b.user_id ='".$keyworld."'";
            }elseif(preg_match("/^[\x{4e00}-\x{9fa5}]+$/u",$keyworld)) {
                $where_data.="and b.name like '%".$keyworld."%'";
            } else {
                $where_data.=" and a.order_id ='".$keyworld."'";
            }
        }
        if(!empty($order_time)){
            $where_data.=' and a.commission_time between"'.strtotime($order_time).'" and '.strtotime($end_time);
//            $where_data.=' and a.order_time <="'.strtotime($order_time)+strtotime('1 day').'"';
            $this->sort="asc";
        }
        $where_data.=" and a.user_id ='".$this->user_id."'";
        $count=Db::table('commission')
            ->alias('a')
            ->join('orders c','a.order_id=c.order_id')
            ->join('user b','c.user_id=b.user_id')
            ->where($where_data)
            ->field('a.*,b.name')
            ->count();
        $list=[];
        $page='';
        if(!empty($count)){
            $list = Db::table('commission')
                ->alias('a')
                ->join('orders c','a.order_id=c.order_id')
                ->join('user b','c.user_id=b.user_id')
                ->where($where_data)
                ->field('a.*,b.name username,c.user_id user_ids')
                ->order('a.commission_time '.$this->sort)
                ->paginate(10,$count,[
                    'page' => input('param.page'),
                    'path'=>url('brokerage/index').'?page=[PAGE]'."&keyworld=".$keyworld."&order_time=".$order_time."&end_time=".$end_time
                ])
                ->each(function($item, $key){
                    $item['commission_time'] = date("Y-m-d H:i:s",$item['commission_time']);
//                    $item['order_status']=config('orders_status')[$item['order_status']];
                    // 获取分页显示

                });

            $page = $list->render();

        }
        // 模板变量赋值
        $this->assign('lists', $list);
        $this->assign('pages', $page);
        $this->assign('order_id',$keyworld);
//            $this->assign('status',$status);
        $orders_status=config('orders_status');
//            $this->assign('orders_status',$orders_status);
        // 渲染模板输出
        return $this->fetch('');
    }

    public function delete(){
        $this->returnMsg['message']='分润订单无法删除';
        return $this->returnMsg;
    }

}
