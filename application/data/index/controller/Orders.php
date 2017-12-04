<?php
namespace app\index\controller;
use think\Controller;
use think\Config;
use think\Db;
class Orders extends Index
{
    public function index()
    {
        if(request()->isGet()){
            $status=input('get.order_status');
            $keyworld = input('keyworld');

            $where_data=" 1 =1 ";
            if (!empty($keyworld)) {
                if (preg_match("/^1[34578]{1}\d{9}$/", $keyworld)) {
                    $where_data.="and a.user_id='".$keyworld."'";
                } else {
                    $where_data.="and a.order_id='".$keyworld."'";
                }
            }

            if(!empty($status)){
                $where_data.='and a.order_status="'.$status.'"';

            }
            $count=Db::table('orders')
                ->alias('a')
                ->join('user b','a.user_id=b.user_id')
                ->field('count(order_id) as count')
                ->where($where_data)
				
                ->find()['count'];
            $list=[];
            $page='';
            if(!empty($count)){
                $list = Db::table('orders')
                    ->alias('a')
                    ->join('user b','a.user_id=b.user_id')
                    ->where($where_data)
					->order('order_time desc')
                    ->paginate(5,$count,[
                        'page' => input('param.page'),
                        'path'=>url('orders/index').'?page=[PAGE]'."&order_status=".$status."&keyworld=".$keyworld
                    ])
                    ->each(function($item, $key){
                        $item['order_time'] = date("Y-m-d H:i:s",$item['order_time']);
    //                    $item['order_status']=config('orders_status')[$item['order_status']];
                        // 获取分页显示

                    });
                $page = $list->render();

            }
            // 模板变量赋值
//            var_dump($list);
            $this->assign('list', $list);
            $this->assign('page', $page);
            $this->assign('order_id',$keyworld);
            $this->assign('status',$status);
            $orders_status=config('orders_status');
            $this->assign('orders_status',$orders_status);
            // 渲染模板输出
//            var_dump($this->fetch('orders'));exit;
            return $this->fetch('orders');
        }
    }

    public function close_order(){
        if(request()->isPost()){
            $order_id=input('post.order_id');
            if(empty($order_id)){
                $this->returnMsg['message']='请选择取消订单';
                return $this->returnMsg;
            }
            $res=Db::table('orders')
                ->where('order_id',$order_id)
                ->update(['order_status'=>"CLOSE"]);
            if($res>0){
                $this->returnMsg['message']='订单关闭成功';
                $this->returnMsg['status']=200;
                return $this->returnMsg;
            }
        }
    }
    public function getOrderById(){
        $id = input('order_id');
        $res=Db::table('orders')
            ->alias('a')
            ->join('user b','a.user_id=b.user_id')
            ->where('id',$id)
            ->find();
        $orders_status=config('orders_status');
        foreach($orders_status as $key=>$val) {
            if ($res['order_status'] == $key) {
                $res['order_status'] = $val;
            }
        }
        return $res;

    }

}
