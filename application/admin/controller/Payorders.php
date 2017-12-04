<?php
namespace app\admin\controller;
use think\Controller;
use think\Config;
use think\Db;
class Payorders extends Online
{
    protected $field=[
        "id"=>"提现序号",
        'pay_order_id'=>'提现订单号',
        'a.user_id'=>'提现用户',
        'b.name'=>'用户姓名',
        'a.pay_money'=>'提现金额',
        'pay_service'=>'提现服务费' ,
        "pay_to_card"=>'提现到账卡',
        'pay_status'=>'提现状态',
        'from_unixtime(pay_time)'=>'提现时间'];

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
        $com_data=Db::table('pay_orders')
            ->field('count(pay_money) count,sum(pay_money) total_money')
            ->find();

        $com_data['total_count']=db('pay_orders')->count();
        $fail_data=db('pay_orders')
            ->alias('a')
            ->join('user b','a.user_id=b.user_id')
            ->where($where_data)
            ->where('pay_status','PAY_FAILURE')
            ->field('count(pay_money) fail_count,sum(pay_money) fail_money')
            ->select();
        $suc_data=db('pay_orders')
            ->alias('a')
            ->join('user b','a.user_id=b.user_id')
            ->where($where_data)
            ->where('pay_status','PAY_SUCCESS')
            ->field('count(pay_money) success_count,sum(pay_money) success_money,sum(pay_service) service_total')
            ->select();
        $com_data=array_merge_recursive($com_data,$fail_data[0]);

        $com_data=array_merge($com_data,$suc_data[0]);
        $this->assign('com_data',$com_data);

        $seach='';
        foreach(input('get.') as $key =>$val){
            $seach.=$key.'='.$val.'&';
        }
        $this->assign('seach',$seach);

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

//数据表格下载
    public function load(){
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
        $map=Db::table('pay_orders')
            ->alias('a')
            ->join('user b','a.user_id=b.user_id')
            ->where($where_data)
            ->field(array_keys($this->field))
            ->select();
        $excel=new Excel();
        $table_name="orders";
        $field=$this->field;
//        $map=$where_data;
        $excel->setExcelName("提现订单查询".date('Y-m-d H:i:s'))
            ->createSheet("查询结果集",$table_name,$field,$map)
            ->downloadExcel();
    }

}
