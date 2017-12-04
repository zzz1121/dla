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
        if(request()->isPost()){
            $pay_data=input("post.pay_prot_id/a");
            $settle_data=input("post.settle/a");
            $extra_rate=input("post.extra_rate/a");
            $user_id=input("post.user_id");


            $user=model("user")
                ->where('user_id',$user_id)
                ->find();

            if($user['is_merchant']==2){
                $this->returnMsg['message']='该用户已经是签约代理了';
                return $this->returnMsg;
            }


            $rate=Db::table('rate')->select();
            foreach($rate as $key   => $val){
                if($settle_data[$key]/100<=$val['costing'] || $settle_data[$key]/100>=$val['settle_rate']){
                    $this->returnMsg['message']="成本费率超出可设定范围，请检查";
                    return $this->returnMsg;
                }
                if($extra_rate[$key]*100<$val['extra_rate']){
                    $this->returnMsg['message']="服务费不可低于平台服务费";
                    return $this->returnMsg;
                }
            }
            $insert_data['group_id']=$user_id;

            Db::startTrans();
            $res=model('user')
                ->where('user_id',$user_id)
                ->update([
                    'is_merchant'=>2,
                    'user_type'=>2
                ]);

            if(!$res){
                $this->returnMsg['message']='修改失败';
                return $this->returnMsg;
            }


            foreach($pay_data as $key=> $val){
                $rate=model('rate')
                    ->where('pay_prot_id',$val)
                    ->find();
                $insert_data['user_lv']=1;
                $insert_data['pay_id']=$val;
                $insert_data['settle_rate']=$settle_data[$key]/100;
                $insert_data['extra_rate']=(int)($extra_rate[$key]*100);
//                $insert_data['parent']=$rate['parent'];
//                $insert_data['superior']=$rate['superior'];
                $res=Db::table('group_settle')
                    ->insert($insert_data);
                if(!$res){
                    $this->returnMsg['message']="费率写入失败";
                    Db::rollback();
                    return $this->returnMsg;
                }
                $insert_data['user_lv']=2;
                $insert_data['settle_rate']=$rate['settle_rate'];
                $res2=Db::table('group_settle')
                    ->insert($insert_data);
                if(!$res2){
                    $this->returnMsg['message']="费率写入失败";
                    Db::rollback();
                    return $this->returnMsg;
                }
                $insert_data['user_lv']=3;
                $res2=Db::table('group_settle')
                    ->insert($insert_data);
                if(!$res2){
                    $this->returnMsg['message']="费率写入失败";
                    Db::rollback();
                    return $this->returnMsg;
                }

            }

//            if(!empty($user['underling'])){
                $res2=model('user')
                    ->where(['merchant_id'=>$user_id])
                    ->update([
                        'user_type'=>2,
                        'group_id'=>$user_id,
                        'group_up'=>$user_id

                    ]);

//                if(!empty($user['indirect'])){
                    $merchant_id=$user_id;
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
                            'group_id'=>$merchant_id
                        ]);

//                }
//            }

            Db::commit();

            $this->returnMsg['message']='代理商升级成功';
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

        $where['a.is_merchant']=1;
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
        $this->assign('title','代理签约');
        $this->assign('pages', $page);
        $this->assign('user_list', $user_list);

        return $this->fetch();

    }



    public function edit(){
        $user_id=input('user_id');
        $user=model('user')
            ->alias('a')
            ->where('a.user_id',$user_id)
            ->find();
        $rate=Db::table('rate')->select();
        if(empty($user)){

        }


        $this->assign('title',"代理签约");
        $this->assign('user',$user);
        $this->assign('rate',$rate);
        return $this->fetch();
    }

    public function update(){
        if(request()->isPost()){
            $user_id=input("post.user_id");
            $pay_data=input("post.pay_prot_id/a");
            $settle_data=input("post.settle/a");
            $extra_rate=input("post.extra_rate/a");



            $user=model("user")
                ->where('user_id',$user_id)
                ->find();

            if($user['is_merchant']!==2){
                $this->returnMsg['message']='该用户还不是签约代理';
                return $this->returnMsg;
            }


            $rate=Db::table('rate')->select();
            foreach($rate as $key   => $val){
                if($settle_data[$key]/100<=$val['costing'] || $settle_data[$key]/100>=$val['settle_rate']){
                    $this->returnMsg['message']="成本费率超出可设定范围，请检查";
                    return $this->returnMsg;
                }
                if((int)($extra_rate[$key]*100)<$val['extra_rate']){
                    $this->returnMsg['message']="服务费不可低于平台服务费";
                    return $this->returnMsg;
                }
            }


            Db::startTrans();



            foreach($pay_data as $key => $val){
                $user_lv=1;
                $insert_data['pay_id']=$val;
                $insert_data['settle_rate']=$settle_data[$key]/100;
                $res=Db::table('group_settle')
                    ->where('group_id',$user_id)
                    ->where('user_lv',$user_lv)
                    ->fetchSql(1)
                    ->update([
                        'settle_rate'=>$settle_data[$key]/100,
                         'extra_rate'=>(int)($extra_rate[$key]*100)
                    ]);
//                $this->returnMsg['ss']=$res;
                if(!$res){
                    $this->returnMsg['message']="费率写入失败";
                    Db::rollback();
                    return $this->returnMsg;
                }
           }
            $this->returnMsg['status']=200;
            $this->returnMsg['message']="代理费率修改成功";
            return $this->returnMsg;
        }
        $user_id=input('user_id');
        $user=model('user')->where('user_id',$user_id)->find();
        $rate=model('rate')
            ->alias('a')
            ->join('group_settle b','a.pay_prot_id=b.pay_id')
            ->where('b.group_id',$user_id)
            ->where('b.user_lv',1)
            ->field('a.*,b.settle_rate settle,b.extra_rate extra')
            ->select();
        $this->assign('user',$user);
        $this->assign('rate',$rate);
        return $this->fetch();



    }


}
