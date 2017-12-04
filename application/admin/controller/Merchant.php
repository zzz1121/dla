<?php

namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Session;
use think\Vendor;
class Merchant extends Online {


    public function edit(){
        return $this->fetch("edit");
    }


    //升级审核
    public function audit(){
//        $this->redirect('user/index');
        $up_role=input('up_role',2);
        if($up_role<2){
            $up_role=2;
        }
        $role_data=Db::table('role')
            ->where('role_id','>','1')
            ->select();


        $this->assign('up_role',$up_role);
        $this->assign('role_data',$role_data);
        $upgrade=Db::table('role')
            ->where('role_id',$up_role)
            ->find();
        $this->assign('upgrade',$upgrade);
        $where['card_status']=1;
        $where['a.role_id']=$up_role-1;

        $merchant_list=model('user')
            ->alias('a')
            ->join('role b','a.role_id=b.role_id')
            ->field('a.*,b.role_name')
            ->where($where)
            ->select();
        foreach($merchant_list as $key=>$val){
            $merchant_id=$val['user_id'];
            $total=Db::table('user')
                ->where('merchant_id','IN',function($query)use($merchant_id) {
                    $query->table('user')->where('merchant_id',"$merchant_id")->whereOr('user_id',$merchant_id)->field('user_id');
                })
                ->where('card_status',1)
                ->count();
            $merchant_list[$key]['referrer_count']=$total;
            $start_time=time()-strtotime($upgrade['period'].'day');
            $sum=Db::table('commission')
                ->where([
                    'user_id'=>$val['user_id'],
                    'commission_time'=>array('>=',$start_time)
                ])
                ->sum('order_money');
            if(!$upgrade['period']){
                $average=(int)$sum;
            }else{
                $average=(int)($sum/$upgrade['period']);
            }
            $merchant_list[$key]['average']=$average;

            if($upgrade['upgrade_type']==1 && $total< $upgrade['referrer_count'] || $upgrade['upgrade_type']==3 && $total< $upgrade['referrer_count']){
                unset($merchant_list[$key]);
            }

            if($upgrade['upgrade_type']==2 && $average<$upgrade['integral'] || $upgrade['upgrade_type']==1 && $average<$upgrade['integral']){


                unset($merchant_list[$key]);
            }
        }

        $this->assign('upgrade',$upgrade);
        $this->assign('lists',$merchant_list);
        return $this->fetch();
    }


    public function up_level(){
        $ids=input('ids/a');
        $up_role=input('up_role',2);
        if($up_role<2){
            $this->returnMsg['message']='升级等级有误';
            return $this->returnMsg;
        }
        if(empty($ids)){
            $this->returnMsg['message']='请选择升级用户';
            return $this->returnMsg;
        }
        $role_data=Db::table('role')
            ->where('role_id','>','1')
            ->select();
        if($up_role>count($role_data)){
            $this->returnMsg['message']='升级等级有误';
            return $this->returnMsg;
        }
        $res=model('user')
            ->where('ids','in',$ids)
            ->update([
                'role_id'=>$up_role
            ]);
        $this->returnMsg['sss']=$res;
        if(!$res){
            $this->returnMsg['message']='升级等级失败';
            return $this->returnMsg;
        }
        $this->returnMsg['message']='升级等级成功';
        $this->returnMsg['status']=200;
        $this->returnMsg['url']=url('audit');

        return $this->returnMsg;

    }



}
