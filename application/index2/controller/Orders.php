<?php
namespace app\index2\controller;
use think\Controller;
use think\Config;
use think\Db;
class Orders extends Online
{
    protected $field=["order_id"=>"订单号",'user_id'=>'手机号',"from_card"=>"提现卡","to_card"=>"收款卡","order_money"=>'订单金额',
        'arrival_amount'=>'到账金额','cover_charge'=>'手续费','service_charge'=>'服务费','pay_prot_id'=>'支付通道','from_unixtime(order_time)'=>'订单时间','order_status'=>'订单状态'];
    public function index()
    {
        $status=input('get.order_status');
        $keyworld = input('keyworld');
        $order_time = input('order_time');
        $end_time=input('end_time',date("Y-m-d H:i:s",time()));
        $pay_prot_id=input('pay_prot_id',0);

        $where_data=" 1 =1 ";
        if (!empty($keyworld)) {
            if (preg_match("/^1[34578]{1}\d{9}$/", $keyworld)) {
                $where_data.="and a.user_id='".$keyworld."'";
            } elseif(preg_match("/^[\x{4e00}-\x{9fa5}]+$/u",$keyworld)) {
                $where_data.="and b.name like '%".$keyworld."%'";
            }else{
                $where_data.="and a.order_id='".$keyworld."'";
            }
        }
        if(!empty($status)){
            $where_data.='and a.order_status="'.$status.'"';

        }
        if(!empty($order_time)){
            $where_data.=' and a.order_time between"'.strtotime($order_time).'" and '.strtotime($end_time);
//            $where_data.=' and a.order_time <="'.strtotime($order_time)+strtotime('1 day').'"';
            $this->sort='asc';
        }

        if(!empty($pay_prot_id)){
            $where_data.=' and pay_prot_id='.$pay_prot_id;
        }
        $where_data.="and a.user_id='".$this->user_id."'";
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
                ->order('order_time '.$this->sort)
                ->paginate(10,$count,[
                    'page' => input('param.page'),
                    'path'=>url('orders/index').'?page=[PAGE]'."&order_status=".$status."&keyworld=".$keyworld."&order_time=".$order_time."&end_time=".$end_time
                            ."&pay_prot_id=".$pay_prot_id
                ])
                ->each(function($item, $key){
                    $item['order_time'] = date("Y-m-d H:i:s",$item['order_time']);
//                    $item['order_status']=config('orders_status')[$item['order_status']];
                    // 获取分页显示

                });
            $page = $list->render();

        }

        //订单总金额
        $data['orders_total']=Db::table('orders')
            ->where($where_data)
            ->alias('a')
            ->sum('order_money');

        //成功订单数
        $data['success_count']=Db::table('orders')
            ->alias('a')
            ->where($where_data.' and a.order_status="SUCCESS"')
            ->count();
        //成功订单金额
        $data['success_total']=Db::table('orders')
            ->alias('a')
            ->where($where_data.' and a.order_status="SUCCESS"')
            ->sum('order_money');

        //失败订单数
        $data['falt_count']=Db::table('orders')
            ->alias('a')
            ->where($where_data.' and a.order_status!="SUCCESS"')
            ->count();
        //失败订单金额
        $data['falt_total']=Db::table('orders')
            ->alias('a')
            ->where($where_data.' and a.order_status!="SUCCESS"')
            ->sum('order_money');
        //结算金额
        $data['into_total']=Db::table('orders')
            ->alias('a')
            ->where($where_data.' and a.order_status="SUCCESS"')
            ->sum('arrival_amount');
        $seach='?';
        foreach(input('get.') as $key =>$val){
            $seach.=$key.'='.$val.'&';
        }
        $this->assign('seach',$seach);

        // 模板变量赋值
//            var_dump($list);
        $this->assign('data', $data);
        $this->assign('count', $count);
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

    //数据表格下载
    public function load(){
        $status=input('get.order_status');
        $keyworld = input('keyworld');
        $order_time = input('order_time');
        $end_time=input('end_time',date("Y-m-d H:i:s",time()));
        $pay_prot_id=input('pay_prot_id',0);

        $where_data=" 1 =1 ";
        if (!empty($keyworld)) {
            if (preg_match("/^1[34578]{1}\d{9}$/", $keyworld)) {
                $where_data.="and a.user_id='".$keyworld."'";
            } elseif(preg_match("/^[\x{4e00}-\x{9fa5}]+$/u",$keyworld)) {
                $where_data.="and b.name like '%".$keyworld."%'";
            }else{
                $where_data.="and a.order_id='".$keyworld."'";
            }
        }
        if(!empty($status)){
            $where_data.='and a.order_status="'.$status.'"';

        }
        if(!empty($order_time)){
            $where_data.=' and a.order_time between" '.strtotime($order_time).'" and '.strtotime($end_time);
//            $where_data.=' and a.order_time <="'.strtotime($order_time)+strtotime('1 day').'"';
            $this->sort='asc';
        }

        if(!empty($pay_prot_id)){
            $where_data.=' and pay_prot_id='.$pay_prot_id;
        }

        $map=Db::table('orders')
            ->alias('a')
            ->where($where_data)
            ->field(array_keys($this->field))
            ->select();
        foreach($map as $key =>$val){
//            dump($val);die();
            if($val['pay_prot_id']==1){
                $map[$key]['pay_prot_id']='上福支付';
            }else{
                $map[$key]['pay_prot_id']='汇享支付';
            }
        }
        $excel=new Excel();
        $table_name="orders";
        $field=$this->field;
//        $map=$where_data;
        $excel->setExcelName("订单查询".date('Y-m-d H:i:s'))
            ->createSheet("查询结果集",$table_name,$field,$map)
            ->downloadExcel();
    }

    /**
     * @return View
     */
    public function edit()
    {
        if(request()->isPost()){
            $settle_rate=input('post.settle_rate');
            $role_id=input('post.role_id');
            $save_data=input('post.');

            if($role_id==1){
                $this->returnMsg['message']='默认注册登记无法修改。';
                return $this->returnMsg;
            }
            $result=Db::table('role')
                ->where("role_id",$role_id)
                ->update($save_data);

            if(!$result){
                $this->returnMsg['message']='更新失败';
                return $this->returnMsg;
            }
            $this->returnMsg['message']='更新成功';
            $this->returnMsg['url']=url('level/index');
            $this->returnMsg['status']=200;
            return $this->returnMsg;
        }
        $ids=input('ids');
        $data=model('orders')
            ->alias('a')
            ->join('user b', 'a.user_id=b.user_id')
            ->field('a.*,b.name')
            ->where('id',$ids)
            ->find();
        $orders_status=config('orders_status');
        foreach($orders_status as $key=>$val) {
            if ($data['order_status'] == $key) {
                $data['order_status'] = $val;
            }
        }
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
