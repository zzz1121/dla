<?php
namespace app\index\controller;
use think\Controller;
use think\Config;
use think\Db;
class Brokerage extends Online
{
    public function index()
    {
        if(request()->isGet()){
//            $status=input('get.order_status');
            $keyworld=input("get.keyworld/s");
            $where_data=" 1 =1 and commission_money>0 ";

            if (!empty($keyworld)) {
                if (preg_match("/^1[34578]{1}\d{9}$/", $keyworld)) {
                    $where_data.=" and user_id ='".$keyworld."'";
                } else {
                    $where_data.=" and order_id ='".$keyworld."'";
                }
            }
//            if(!empty($status)){
//                $where_data.=' and a.order_status="'.$status.'"';
//
//            }
            $count=Db::table('commission')
                ->where($where_data)
                ->count();
            $list=[];
            $page='';
            if(!empty($count)){
                $list = Db::table('commission')
                    ->where($where_data)
					->order('commission_time desc')
                    ->paginate(5,$count)
                    ->each(function($item, $key){
                        $item['commission_time'] = date("Y-m-d H:i:s",$item['commission_time']);
    //                    $item['order_status']=config('orders_status')[$item['order_status']];
                        // 获取分页显示

                    });
                $page = $list->render();

            }
            // 模板变量赋值
            $this->assign('list', $list);
            $this->assign('page', $page);
            $this->assign('order_id',$keyworld);
//            $this->assign('status',$status);
            $orders_status=config('orders_status');
//            $this->assign('orders_status',$orders_status);
            // 渲染模板输出
            return $this->fetch('list');
        }
    }

    public function balance(){
        $balance=model('merchant')
            ->where('merchant_id',$this->user_id)
            ->field('balance')
            ->find()['balance'];
        $this->assign("balance",$balance);
        return $this->fetch('edit');
    }



}
