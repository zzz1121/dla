<?php

namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Session;

class User extends Online {

    public function index() {

        $user_id = Session::get('user_id');
        $role_id = Session::get('role_id');
        $reg_time=input('reg_time');
        $this->assign('role_id',$role_id);
        $role_data=model('role')->select();
        $this->assign('role_data',$role_data);

        $card_status = input('card_status');
        $keyworld = input('keyworld');
        $user_role=input('role_id');
        $where['user_id']=array('>',0);
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
        if(!empty($reg_time)){
            $where['reg_time']=array('>',strtotime($reg_time));
            $this->sort='asc';
        }

        $user_list_count = Db::table('user')->where($where)->count();
        $user_list = Db::table('user')->where($where)->order('reg_time '.$this->sort)->paginate(5, $user_list_count,[
            'page' => input('param.page'),
            'path'=>url('user/index').'?page=[PAGE]'."&card_status=".$card_status."&keyworld=".$keyworld."&role_id=".$user_role."&reg_time=".$reg_time
        ]);
        $page = $user_list->render();


        $this->assign('pages', $page);
        $this->assign('user_list', $user_list);

        return $this->fetch();

    }

    public  function info(){
        $this->redirect('user/index');
        $user_id=session('user_id');
        $user=model('user')
            ->where('user_id',$user_id)
            ->field('name,address,number,balance,user_id,reg_time,underling,indirect,role_id,login_status')
            ->find();
        if(empty($user)){
            $this->redirect('user/index');
        }

        $this->assign('user',$user);
        $this->fetch();
    }

    //用户锁定,解锁
    public function status(){
        $user_id=input('ids/a');

        $login_status=input('status');

        if(empty($user_id)){
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

    public function update(){
        if(request()->isPost()){
            $user_id=input('post.user_id');
//            $status=input('post.login_status');
            $role_id=input('post.role_id');
            if (!preg_match("/^1[34578]{1}\d{9}$/", $user_id)) {
                $this->returnMsg['message']="用户账号有误";
                return $this->returnMsg;
            }

            $user_data=model('user')
                ->where('user_id',$user_id)
                ->field('user_id,name,card_status')
                ->find();
            if(empty($user_data)){
                $this->returnMsg['message']='用户不存在,无法升级';
                return $this->returnMsg;
            }
            if($user_data['card_status']!==1){
                $this->returnMsg['message']='用户尚未实名认证,无法修改等级';
                return $this->returnMsg;
            }
            if($role_id<1 && $role_id>5){
                $this->returnMsg['message']='超出设定权限';
                return $this->returnMsg;
            }

            $add_user=Db::table('user')
                ->where('user_id',$user_data['user_id'])
                ->update([
                    'role_id'=>$role_id
//                    'login_status'=>$status
                ]);
            $pay=input('pay_id/a',[]);
            $settle=input('settle_rate/a',[]);
            $extra=input('extra_rate/a',[]);
            foreach($pay as $key=>$val){
                $rate=model('rate')
                    ->where('pay_prot_id',$val)
                    ->find();
                if($settle[$key]/100<$rate['costing']){
                    $this->returnMsg['message']='用户费率不能低于平台成本';
                    return $this->returnMsg;
                }
                if($extra[$key]*100<$rate['costing']){
                    $this->returnMsg['message']='用户服务费不能低于平台成本';
                    return $this->returnMsg;
                }
                $user_settle=db('user_settle')
                    ->where('user_id',$user_id)
                    ->where('pay_id',$val)
                    ->find();
                if(!empty($user_settle)){
                    $res=db('user_settle')
                        ->where('pay_id',$val)
                        ->update([
                            'settle_rate'=>$settle[$key]/100,
                            'extra_rate'=>$extra[$key]*100
                        ]);

                }else{
                    db('user_settle')
                        ->insert([
                            'user_id'=>$user_id,
                            'pay_id'=>$val,
                            'settle_rate'=>$settle[$key]/100,
                            'extra_rate'=>$extra[$key]*100
                        ]);
                }
            }

//            if($add_user==0){
//                $this->returnMsg['message']='设定失败,请选择商户等级';
//                return $this->returnMsg;
//            }
            // 提交事务
            $this->returnMsg['message']='设定成功';
            $this->returnMsg['status']=200;
            return $this->returnMsg;

        }
    }


    public function edit($user_id=null){
        if(empty($user_id)){
            $this->redirect('user/index');
        }
        $user=model('user')
            ->where('user_id',$user_id)
//            ->field('name,address,number,balance,user_id,reg_time,underling,indirect,role_id,login_status')
            ->find();
        if(empty($user)){
            $this->redirect('user/index');
        }

        $this->assign('user',$user);


        //下属提现金额
        $order_count=Db::table('commission')
            ->where('user_id',$user_id)
            ->field('sum(order_money) as sum')
            ->find();
        $user['order_count']=$order_count['sum'];


        //已结算分润
        $cleared_money=Db::table('commission')
            ->where('user_id',$user_id)
            ->where('commission_time','<',(time()-604800))
            ->field('sum(commission_money) as sum')
//                ->fetchSql(true)
            ->find()['sum'];
        $cleared_money=empty($indirect_count)?0:$cleared_money;

        //未结算分润
        $not_account=Db::table('commission')
            ->where('user_id',$user_id)
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
        $rate=model('rate')->select();
        foreach($rate as $key=>$val){
            $rate[$key]=$this->get_settle($val['pay_prot_id'],$user_id);
        }



        $user['order_count']=$order_count['sum'];
        $user['cleared_money']=$cleared_money;
        $user['not_account']=$not_account;
        $user['balance_count']=$balance_count;
        $this->assign('rate',$rate);
        $this->assign('user',$user);
        return $this->fetch();
    }



    // 添加代理
    public function insert_merchant(){
        if(request()->isPost()){
            $user_arr=input('user_id/a');
            $role_id=input('role_id');
            if(empty($role_id)){
                $this->returnMsg['message']='选择等级错误';
            }
            if(count($user_arr)==1){
                $user_id=$user_arr[0];

                if (!preg_match("/^1[34578]{1}\d{9}$/", $user_id)) {
                    $this->returnMsg['message']="用户账号有误";
                    return $this->returnMsg;
                }

                $user_data=model('user')
                    ->where('user_id',$user_id)
                    ->field('user_id,name,card_status')
                    ->find();
                if(empty($user_data)){
                    $this->returnMsg['message']='用户不存在,无法升级';
                    return $this->returnMsg;
                }
                if($user_data['card_status']!==1){
                    $this->returnMsg['message']='用户尚未实名认证,无法升级为代理';
                    return $this->returnMsg;
                }
                if($role_id==0){
                    $this->returnMsg['message']='超出设定权限';
                    return $this->returnMsg;
                }

                $add_user=Db::table('user')
                    ->where('user_id',$user_data['user_id'])
                    ->update([
                        'role_id'=>$role_id
                    ]);
                if($add_user==0){
                    $this->returnMsg['message']='设定失败,请刷新重试';
                    return $this->returnMsg;
                }
                // 提交事务
                $this->returnMsg['message']='用户角色等级设定成功';
                $this->returnMsg['status']=200;
                return $this->returnMsg;
            }else{
                foreach($user_arr as $val){
                    $user_id=$val;
                    if (!preg_match("/^1[34578]{1}\d{9}$/", $user_id)) {
                        $this->returnMsg['message']="用户账号有误";
                        return $this->returnMsg;
                    }

                    $user_data=model('user')
                        ->where('user_id',$user_id)
                        ->field('user_id,name,card_status')
                        ->find();
                    if(empty($user_data)){
                        $this->returnMsg['message']='用户不存在,无法升级';
                        return $this->returnMsg;
                    }
                    if($user_data['card_status']!==1){
                        $this->returnMsg['message']=$val.'用户尚未实名认证,无法升级为代理';
                        return $this->returnMsg;
                    }
                    if($role_id==0){
                        $this->returnMsg['message']='超出设定权限';
                        return $this->returnMsg;
                    }

                    $add_user=Db::table('user')
                        ->where('user_id',$user_data['user_id'])
                        ->update([
                            'role_id'=>$role_id
                        ]);
                    if($add_user==0){
                        $this->returnMsg['message']=$val.'设定失败,请刷新重试';
                        return $this->returnMsg;
                    }
                }
                $this->returnMsg['message']='设定成功';
                $this->returnMsg['status']=200;
                return $this->returnMsg;

            }

        }
    }
}
