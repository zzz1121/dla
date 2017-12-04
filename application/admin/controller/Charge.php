<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\api;
class Charge extends Online
{
    protected $sye_rate;
    protected $role_rate;
    public function index(){
        $count=Db::table('rate')->count();
        $lists=Db::table('rate')
            ->paginate(5,$count);
        $pages = $lists->render();
        $this->assign('lists',$lists);
        $this->assign('pages',$pages);
        return $this->fetch('lists');
    }


    public function edit()
    {
        $id=input('id/d',1);
        $this->assign('settle_rate',$this->sye_rate);
        $rate_data=Db::table('rate')
            ->where('pay_prot_id',$id)
            ->find();
        $this->assign('rate_data',$rate_data);

        $this->assign("sye_rate",$this->sye_rate);
        return $this->fetch('rate');

    }

    public function status(){
        $ids=input('ids/a');

        $status=input('status');

        if(empty($ids)){
            $this->returnMsg['message']='请选择用户';
            return $this->returnMsg;
        }
//        $login_status=$user_data['login_status'];
//        if($login_status==1){
//            $login_status=2;
//        }else{
//            $login_status=1;
//        }
        $map=[];
        $map['pay_prot_id']=['in', $ids];

        $result=$res = Db::name('rate')->where($map)->setField('status', $status);
        $this->returnMsg['sss']=$result;
        if(!$result){
            $this->returnMsg['message']='操作失败,请重试';
            return $this->returnMsg;
        }
        $this->returnMsg['message']='设置成功';
        $this->returnMsg['status']=200;
        return $this->returnMsg;
    }



    public function update(){
        if(request()->isPost()){
            $pay_prot_id=input('pay_prot_id');

            if(empty($pay_prot_id)){
                $this->returnMsg['message']='请选择修改通道';
                return $this->returnMsg;
            }
            $save_data=input('post.');

            $admin=Db::table('rate')
                ->where('pay_prot_id',$pay_prot_id)
                ->update($save_data);
            if($admin==0){
                $this->returnMsg['message']='写入失败';
                return $this->returnMsg;
            }

            $this->returnMsg['url']=url('charge/index')."?id=".$pay_prot_id;
            $this->returnMsg['message']='设定成功';
            $this->returnMsg['status']=200;
            return $this->returnMsg;

        }
    }

    //代理商汇率修改
    public function role_rate(){
        $settle_rate=input('post.settle_rate');
        $role_id=input('post.role_id');
        $save_data=input('post.');
        if($settle_rate>$this->sye_rate){
            $this->returnMsg['message']='输入汇率不能高于平台最高汇率';
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
        $this->returnMsg['status']=200;
        return $this->returnMsg;
    }


}
