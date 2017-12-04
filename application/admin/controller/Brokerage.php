<?php
namespace app\admin\controller;
use think\Controller;
use think\Config;
use think\Db;
class Brokerage extends Online
{
    protected $field=[
        "commission_id"=>"分润序号",
        'order_id'=>'订单号',
        'a.user_id'=>'手机号',
        'b.name'=>'用户姓名' ,
        "order_money"=>'订单金额（元）',
        'commission_money'=>'分润总金额（元）',
        'rate_money'=>'费率差分润（元）',
        'service_money'=>'服务费分润（元）',
        'share_money'=>'推广分润（元）',
        'from_unixtime(commission_time)'=>'分润订单时间'];

    public function index()
    {
        $keyworld=input("get.keyworld/s");
        $where_data=" 1 =1 and commission_money>0 ";
        $order_time=input('get.order_time');
        $end_time=input('get.end_time',date("Y-m-d H:i:s",time()));
        if (!empty($keyworld)) {
            if (preg_match("/^1[34578]{1}\d{9}$/", $keyworld)) {
                $where_data.=" and a.user_id ='".$keyworld."'";
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
        $count=Db::table('commission')
            ->alias('a')
            ->join('user b','a.user_id=b.user_id')
            ->where($where_data)
            ->field('a.*,b.name')
            ->count();
        $list=[];
        $page='';
        if(!empty($count)){
            $list = Db::table('commission')
                ->alias('a')
                ->join('user b','a.user_id=b.user_id')
                ->where($where_data)
                ->field('a.*,b.name')
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

        $com_data=Db::table('commission')
            ->alias('a')
            ->join('user b','a.user_id=b.user_id')
            ->where($where_data)
            ->field('count(commission_money) count,sum(commission_money) total_money,sum(rate_money) rate_total,sum(service_money) service_total,sum(share_money) share_total')
            ->find();

        $this->assign('com_data',$com_data);


        $seach='';
        foreach(input('get.') as $key =>$val){
            $seach.=$key.'='.$val.'&';
        }
        $this->assign('seach',$seach);

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

    //数据表格下载
    public function load(){
        $keyworld=input("get.keyworld/s");
        $where_data=" 1 =1 and commission_money>0 ";
        $order_time=input('get.order_time');
        $end_time=input('get.end_time',date("Y-m-d",time()));
        if (!empty($keyworld)) {
            if (preg_match("/^1[34578]{1}\d{9}$/", $keyworld)) {
                $where_data.=" and a.user_id ='".$keyworld."'";
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
        $map=Db::table('commission')
            ->alias('a')
            ->join('user b','a.user_id=b.user_id')
            ->where($where_data)
            ->field(array_keys($this->field))
            ->select();
        $excel=new Excel();
        $table_name="orders";
        $field=$this->field;
//        $map=$where_data;
        $excel->setExcelName("分润订单查询".date('Y-m-d H:i:s'))
            ->createSheet("查询结果集",$table_name,$field,$map)
            ->downloadExcel();
    }

}
