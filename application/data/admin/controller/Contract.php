<?php

namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Session;

class Contract extends Online {

    public function index() {

        $user_id = Session::get('user_id');
        $role_id = Session::get('role_id');
        $reg_time=input('reg_time');
        $this->assign('role_id',$role_id);
        $this->assign('role_data',session('role_data'));

        $card_status = input('card_status');
        $keyworld = input('keyworld');
        $user_role=input('role_id');
        $where['a.is_merchant']=2;
        if(!empty($card_status) && $card_status >-1){
            $where['card_status'] = $card_status;
        }
        if(!empty($user_role) && $user_role>-1){
            $where['role_id'] = $user_role;
        }
        if (!empty($keyworld)) {
            if (preg_match("/^1[34578]{1}\d{9}$/", $keyworld)) {
                $where['user_id'] = $keyworld;
            } else {
                $where['name'] = array('like', "%$keyworld%");
            }
        }
        $user_list_count = Db::table('user')->alias('a')->where($where)->count();
        $user_list = Db::table('user')
            ->alias('a')
            ->where($where)
            ->paginate(5, $user_list_count,[
            'page' => input('param.page'),
            'path'=>url('index').'?page=[PAGE]'."&card_status=".$card_status."&keyworld=".$keyworld."&role_id=".$user_role."&reg_time=".$reg_time
        ]);
        $page = $user_list->render();

        $this->assign('title','代理商列表');
        $this->assign('pages', $page);
        $this->assign('user_list', $user_list);

        return $this->fetch();
            
    }

    //用户锁定,解锁
    public function status(){
        $user_id=input('ids/a');

        $login_status=input('status');

        if(empty($user_id)){
            $this->returnMsg['message']='请选择用户';
            return $this->returnMsg;
        }
        $map=[];
        $map['user_id']=['in', $user_id];

        $result=$res = Db::name('user')->where($map)->setField('login_status', $login_status);
        $this->returnMsg['sss']=$result;
        if(!$result){
            $this->returnMsg['message']='操作失败,请重试';
            return $this->returnMsg;
        }
        $this->returnMsg['message']='设置成功';
        $this->returnMsg['status']=200;
        return $this->returnMsg;
    }



    public function add(){

        $rate=model('rate')
            ->find();
        if(request()->isPost()){
            $ids=input('ids/a','');

            $settle=input('settle');
            if(empty($ids) || empty($settle)){
                $this->returnMsg['message']='请检查所选数据';
                return $this->returnMsg;
            }
            if(count($ids)>1){
                $this->returnMsg['message']='当前只提供单用户升级签约';
                return $this->returnMsg;
            }

            if($settle<$rate['costing'] || $settle>$rate['settle_rate']){
                $this->returnMsg['message']='设定费率超出平台成本与平台费率范围';
                return $this->returnMsg;
            }

            $user=model("user")
                ->where('user_id',$ids[0])
                ->find();

            if($user['is_merchant']==2){
                $this->returnMsg['message']='该用户已经是签约代理了';
                return $this->returnMsg;
            }


            Db::startTrans();
            $res=model('user')
                ->where(['user_id'=>array('in',$ids)])
                ->update([
                    'settle_rate'=>$settle,
                    'is_merchant'=>2,
                    'user_type'=>2,
                    'settle_2'=>$rate['settle_rate']-0.0007,
                    'settle_3'=>$rate['settle_rate'],
                    'password'=>md5(substr($ids[0],-6))

                ]);

            if(!$res){
                $this->returnMsg['message']='操作失败，请重试1';
                return $this->returnMsg;
            }
            if(!empty($user['underling'])){
                $res2=model('user')
                    ->where(['merchant_id'=>$ids[0]])
                    ->update([
                        'user_type'=>2,
                        'group_id'=>$ids[0],
                        'group_up'=>$ids[0],
                        'settle_rate'=>$rate['settle_rate']-0.0007

                    ]);
                if(!$res2){
                    $this->returnMsg['message']='操作失败，请重试2';
                    Db::rollback();
                    return $this->returnMsg;
                }
                if(!empty($user['indirect'])){
                    $merchant_id=$ids[0];
                    $res3=Db::table('user')
                        ->field('user_id')
                        ->where('merchant_id','in',function($query)use($merchant_id){
                            $query->table('user')->field('user_id')->where(['merchant_id'=>$merchant_id]);
                        })
                        ->select();
                    $sql='';
                    foreach($res3 as $val){
                        $sql.=$val['user_id'].',';
                    }

                    $sql=substr($sql,0,-1);
                    $res4=model('user')
                        ->where(['user_id'=>array('in',$sql)])
                        ->update([
                            'user_type'=>2,
                            'group_id'=>$merchant_id,
                            'settle_rate'=>$rate['settle_rate']
                        ]);
                    if(!$res3){
                        $this->returnMsg['message']='操作失败，请重试3';
                        Db::rollback();
                        return $this->returnMsg;
                    }
                }
            }

            Db::commit();

            $this->returnMsg['message']='操作成功,代理商默认密码为手机号后6位';
            $this->returnMsg['status']=200;
            $this->returnMsg['url']=url('add');
            return $this->returnMsg;
        }


        $user_id = Session::get('user_id');
        $role_id = Session::get('role_id');
        $times=input('times');

        $user_count = input('user_count');
        $integral = input('integral');
        $keyworld = input('keyworld');

//        $where['a.is_merchant']=1;
        $where['a.card_status']=1;
        if(!empty($user_count) && $user_count>-1){
            $where['a.indirect+a.underling'] = array('>',$user_count);
        }
        if (!empty($keyworld)) {
            if (preg_match("/^1[34578]{1}\d{9}$/", $keyworld)) {
                $where['user_id'] = $keyworld;
            } else {
                $where['name'] = array('like', "%$keyworld%");
            }
        }

        $user_list_count = Db::table('user')->alias('a')->where($where)->count();

        $user_list = Db::table('user')
            ->alias('a')
            ->where($where)
            ->paginate(5, $user_list_count,[
                'page' => input('param.page'),
                'path'=>url('add').'?page=[PAGE]'."&times=".$times."&keyworld=".$keyworld."&integral=".$integral.'&user_count='.$user_count
            ]);

        $page = $user_list->render();
        $this->assign('rate',$rate);
        $this->assign('title','代理签约');
        $this->assign('pages', $page);
        $this->assign('user_list', $user_list);

        return $this->fetch();

    }



    public function edit(){
        $merchant_id=input('merchant_id');
        $user=model('user')
            ->alias('a')
            ->where('a.ids',$merchant_id)
//            ->field('name,address,sex,number,balance,user_id,reg_time,underling,indirect,role_id,login_status')
            ->find();
        if(empty($user)){

        }
        $rate=model('rate')->find();

        $this->assign('user',$user);


        //下属提现金额
        $order_count=Db::table('commission')
            ->where('user_id',$merchant_id)
            ->field('sum(order_money) as sum')
            ->find();
        $user['order_count']=$order_count['sum'];


        //已结算分润
        $cleared_money=Db::table('commission')
            ->where('user_id',$merchant_id)
            ->where('commission_time','<',(time()-604800))
            ->field('sum(commission_money) as sum')
//                ->fetchSql(true)
            ->find()['sum'];
        $cleared_money=empty($indirect_count)?0:$cleared_money;

        //未结算分润
        $not_account=Db::table('commission')
            ->where('user_id',$merchant_id)
            ->where('commission_time','>',(time()-604800))
            ->where('commission_time','<',time())
            ->field('sum(commission_money) as sum')
//                ->fetchSql(true)
            ->find()['sum'];
        //$not_account=empty($indirect_count)?0:$not_account;
        $not_account=floor($not_account*100)/100;


        //可提现余额
        $balance_count=$user['balance']-$not_account;
        if($balance_count<0){
            $balance_count=0;
        }

        $user['order_count']=$order_count['sum'];
        $user['cleared_money']=$cleared_money;
        $user['not_account']=$not_account;
        $user['balance_count']=$balance_count;
        $this->assign('user',$user);
        return $this->fetch();
    }



}
