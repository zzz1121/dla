<?php
namespace app\admin\controller;
use think\Controller;
use think\Config;
use think\Db;
class Activity extends Online
{
    public function index()
    {
        if(request()->isGet()){
            $status=input('get.order_status');
            $order_id=input("get.order_id/s");
            $where_data=" 1 =1 ";
            if(!empty($order_id)){
                $where_data.="and a.order_id='".$order_id."'";

            }
            if(!empty($status)){
                $where_data.='and a.order_status="'.$status.'"';

            }
            $count=Db::table('activity')
                ->field('count(activity_id) as count')
                ->where($where_data)
                ->find()['count'];
            $list=[];
            $page='';
            if(!empty($count)){
                $list = Db::table('activity')
                    ->paginate(5,$count)
                    ->each(function($item, $key){
                       // $item['order_time'] = date("Y-m-d H:i:s",$item['order_time']);
    //                    $item['order_status']=config('orders_status')[$item['order_status']];
                        // 获取分页显示

                    });
                $page = $list->render();

            }
            // 模板变量赋值
//            var_dump($list);
            $this->assign('lists', $list);
            $this->assign('pages', $page);
            $this->assign('order_id',$order_id);
            $this->assign('status',$status);
            $orders_status=config('orders_status');
            $this->assign('orders_status',$orders_status);
            // 渲染模板输出
//            var_dump($this->fetch('orders'));exit;
            return $this->fetch('index');
        }
    }

    public function edit(){
        if(request()->isPost()){

            if(!empty(input('activity_id'))){
                $this->update_activity();
                return $this->returnMsg;
            }
        }
        $ids=input('id');
        $data=model('activity')
            ->where('activity_id',$ids)
            ->find();
        $this->assign('data',$data);
        return $this->fetch();
    }

    //活动添加
    public function add(){
        if(request()->isPost()){
            $post=input('post.');
            unset($post['activity_id']);
            $res=Db::table('activity')
                ->insert($post);
            if(!$res){
                $this->returnMsg['message']='添加失败';
                return $this->returnMsg;
            }
            $this->returnMsg['sattus']='200';
            $this->returnMsg['url']=url('activity/index');
            $this->returnMsg['message']='添加成功';
            return $this->returnMsg;
        }
        $data=[];
        $data['path']='';
        $this->assign('data',$data);
        return $this->fetch('edit');
    }

    //活动删除
    public function delete(){
        $id=input('ids/a');
        $res=Db::table('activity')
            ->where('activity_id','in',$id)
            ->delete();
        if(!$res){
            $this->returnMsg['message']='删除失败';
            return $this->returnMsg;
        }
        $this->returnMsg['status']=200;
        $this->returnMsg['url']=url('activity/index');
        $this->returnMsg['message']="删除成功";
        return $this->returnMsg;
    }

    //活动更新
    public function update_activity(){
        if(request()->isPost()){
            $post=input('post.');
            foreach($post as $key=>$val){
                if(empty($val)){
                    $this->returnMsg['message']=$key.'不能为空';
                    return $this->returnMsg;
                }
            }

            $res=Db::table('activity')
                ->where('activity_id',$post['activity_id'])
                ->update($post);
            $this->returnMsg['data']=$res;
            if($res==0){
                $this->returnMsg['message']='修改失败';
                return $this->returnMsg;
            }
            $this->returnMsg['status']=200;
            $this->returnMsg['message']='修改成功';

            return $this->returnMsg;
        }
    }


    // banner图片上传保存
    public function upload_img(){
        $file = request()->file('upload');
        // 移动到框架应用根目录/public/uploads/ 目录下
        $path='public' . DS . 'uploads'.DS.'activity'.DS;
        if($file){
            $info = $file->move(ROOT_PATH .'public' . DS . 'uploads'.DS.'activity'.DS);
            if($info){
                session('activity_path',$path.$info->getSaveName());
                // 成功上传后 获取上传信息
                $this->returnMsg['message']='图片上传成功';
                $this->returnMsg['status']=200;
                $this->returnMsg['data']=session('activity_path');
                return json($this->returnMsg);
                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg


            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
    }

}
