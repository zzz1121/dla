<?php
namespace app\admin\controller;
use think\Controller;
use think\Config;
use think\Db;
class Question extends Online
{
    public function index()
    {
        $status=input('get.order_status');
        $keyworld = input('keyworld');
        $order_time = input('order_time');
        $where_data=" 1 =1 ";
        if (!empty($keyworld)) {
            if (preg_match("/^1[34578]{1}\d{9}$/", $keyworld)) {
                $where_data.="and a.user_id='".$keyworld."'";
            } elseif(preg_match("/^[\x{4e00}-\x{9fa5}]+$/u",$keyworld)) {
                $where_data.="and b.name like '%".$keyworld."%'";
            }else{
                $where_data.="and a.question_id='".$keyworld."'";
            }
        }
        if(!empty($status)){
            $where_data.='and a.question_status="'.$status.'"';

        }
        if(!empty($order_time)){
            $where_data.=' and a.question_time >="'.strtotime($order_time).'"';
//            $where_data.=' and a.order_time <="'.strtotime($order_time)+strtotime('1 day').'"';
            $this->sort='asc';
        }
        $count=Db::table('question')
            ->alias('a')
            ->join('user b','a.user_id=b.user_id')
            ->field('count(id) as count')
            ->where($where_data)

            ->find()['count'];
        $list=[];
        $page='';
        if(!empty($count)){
            $list = Db::table('question')
                ->alias('a')
                ->where($where_data)
                ->order('question_id '.$this->sort)
                ->paginate(10,$count,[
                    'page' => input('param.page'),
                    'path'=>url('question/index').'?page=[PAGE]'."&_status=".$status."&keyworld=".$keyworld."&order_time=".$order_time
                ]);
            $page = $list->render();

        }
        // 模板变量赋值
        $this->assign('lists', $list);
        $this->assign('pages', $page);
        // 渲染模板输出
        return $this->fetch('');
    }

    /**
     * @return View
     */
    public function edit()
    {
        if(request()->isPost()){
            $ids=input('post.id');
            $save_data=input('post.');


            $result=Db::table('question')
                ->where("id",$ids)
                ->update($save_data);

            if(!$result){
                $this->returnMsg['message']='更新失败';
                return $this->returnMsg;
            }
            $this->returnMsg['message']='更新成功';
            $this->returnMsg['url']=url('question/index');
            $this->returnMsg['status']=200;
            return $this->returnMsg;
        }
        $ids=input('ids');
        $data=model('question')
            ->alias('a')
            ->where('id',$ids)
            ->find();
        $this->assign('data',$data);
        return $this->fetch('edit');
    }

    public function delete(){
        $ids=input('ids/a');
        if(empty($ids)){
            $this->returnMsg['message']='请选择取消订单';
            return $this->returnMsg;
        }
        $orders=model('orders')
            ->where('id','in',$ids)
            ->field('order_status')
            ->select();
        if(empty($orders)){
            $this->returnMsg['message']='订单不存在';
            return $this->returnMsg;
        }
        foreach($orders as $val){
            if($val['order_status']!=="NOTPAY"){
                $this->returnMsg['message']='只能关闭未支付订单';
                return $this->returnMsg;
            }
        }
        $res=Db::table('orders')
            ->where('id','in',$ids)
            ->update(['order_status'=>"FAIL"]);
        $this->returnMsg['sss']=$res;
        if($res>0){
            $this->returnMsg['message']='订单关闭成功';
            $this->returnMsg['status']=200;
            $this->returnMsg['url']=url('orders/index');
            return $this->returnMsg;
        }
        $this->returnMsg['message']='订单关闭失败';
        return $this->returnMsg;
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
