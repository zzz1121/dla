<?php

namespace app\index\controller;

use think\Controller;
use think\Db;
use think\Session;

class User extends Online {

    public function index() {

        $user_id = Session::get('user_id');
        $role_id = Session::get('role_id');
        $this->assign('role_id',$role_id);
        $role_data=Db::table('role')
            ->where(['role_id'=>array('>','0')])
            ->select();
        $this->assign('role_data',$role_data);
        if (request()->isPost()) {

            $card_status = input('card_status');
            $keyworld = input('keyworld');
            $user_role=input('user_role');
            $where['user_id']=array('>',0);
            if($card_status>=0 && $card_status!==''){
                $where['card_status'] = $card_status;
            }
            if($user_role>=0 && $user_role!==''){
                $where['role_id'] = $user_role;
            }
            if (!empty($keyworld)) {
                if (preg_match("/^1[34578]{1}\d{9}$/", $keyworld)) {
                    $where['user_id'] = $keyworld;
                } else {
                    $where['name'] = array('like', "%$keyworld%");
                }
            }

            $user_list_count = Db::table('user')->where($where)->count();
            $user_list = Db::table('user')->where($where)->order('reg_time desc')->paginate(5, $user_list_count,[
                'page' => input('param.page'),
                'path'=>url('user/index').'?page=[PAGE]'."&card_status=".$card_status."&keyworld=".$keyworld."&role_id=".$user_role
            ]);

            $page = $user_list->render();
            $arr['user_list'] = $user_list;



            $this->assign('page', $page);

            return $this->fetch('list', $arr);
            
        } else {
            $where=" 1=1 and a.role_id>=1 ";
            $card_status=input('card_status');
            if(!empty($card_status)){
                $where.=' and a.card_status ='.$card_status;
            }
            $user_list_count = Db::table('user')
                ->alias('a')
                ->join('role b','a.role_id=b.role_id')
                ->where($where)
                ->count();
            $user_list = Db::table('user')
                ->alias('a')
                ->join('role b','a.role_id=b.role_id')
                ->where($where)
				->order('reg_time desc')
                ->paginate(5, $user_list_count);
				


            $page = $user_list->render();
            $arr['user_list'] = $user_list;

            $this->assign('page', $page);

            return $this->fetch('list', $arr);
        }
    }
    public function get_user_data(){
        if(request()->isPost()){
            $user_id=input('user_id');
            if (empty($user_id)){
                $this->returnMsg['message']='请求用户不能为空';
                return $this->returnMsg;
            }
            $user_data=model('user')
                ->where('user_id',$user_id)
                ->field('name,address,sex,number,balance,user_id,reg_time,underling,indirect')
                ->find();
            if(empty($user_data)){
                $this->returnMsg['message']='用户信息出错';
                return $this->returnMsg;
            }
            $user_data['reg_time']=date("Y-h-d H:i:s",$user_data['reg_time']);

            $this->returnMsg['data']=$user_data;


            //下属提现金额
            $order_count=Db::table('commission')
                ->where('user_id',$user_id)
                ->field('sum(order_money) as sum')
                ->find();
            $this->returnMsg['data']['order_count']=$order_count['sum'];

			
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
			$not_account=($not_account*100)/100;


            //可提现余额
            $balance_count=$user_data['balance']-$not_account;
			if($balance_count<0){
				$balance_count=0;
			}

            $this->returnMsg['data']['order_count']=$order_count['sum'];
            $this->returnMsg['data']['cleared_money']=$cleared_money;
            $this->returnMsg['data']['not_account']=$not_account;
            $this->returnMsg['data']['balance_count']=$balance_count;

            $this->returnMsg['message']='请求成功';
            $this->returnMsg['status']=200;
            return $this->returnMsg;
        }
    }

    //用户锁定,解锁

    public function login_status_update(){
        if(request()->isPost()){
            $user_id=input('user_id/d');
            $user_data=model('user')
                ->where('user_id',$user_id)
                ->field('login_status')
                ->find();
            if(empty($user_data)){
                $this->returnMsg['message']='用户不存在';
                return $this->returnMsg;
            }
            $login_status=$user_data['login_status'];
            if($login_status==1){
                $login_status=2;
            }else{
                $login_status=1;
            }
            $result=model('user')
                ->where('user_id',$user_id)
                ->update(['login_status'=>$login_status]);
            if($result<1){
                $this->returnMsg['操作失败,请重试'];
                return $this->returnMsg;
            }
            $this->returnMsg['message']='设置成功';
            $this->returnMsg['status']=200;
            return $this->returnMsg;
        }
    }



    public function edit(){
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
