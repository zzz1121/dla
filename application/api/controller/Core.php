<?php
/**
 * @author qianwang-zlq
 * @version 2017-06-04
 *  api 基础父类
 */
namespace app\api\controller;
use think\Controller;
use think\Db;
use alisms\sms\Smsdemo;
class Core extends Controller
{
    protected $returnMsg=[
        'status'=>401,
        'message'=>'请求错误',
        'data'=>[]
    ]; //默认返回信息格式
    protected $online;//在线用户信息
    protected $settle_rate; //用户提现费率
    protected $sye_rate; //平台提现费率参数集合
    public function index()
    {
        $returnMsg=[
            'status'=>200,
            'message'=>'success',
            'data'=>[]
        ];
        return $returnMsg;
    }
    protected function random($length = 6, $numeric = 0) {
        PHP_VERSION < '4.2.0' && mt_srand((double) microtime() * 1000000);
        if ($numeric) {
            $hash = sprintf('%0' . $length . 'd', mt_rand(0, pow(10, $length) - 1));
        } else {
            $hash = '';
            $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz';
            $max = strlen($chars) - 1;
            for ($i = 0; $i < $length; $i++) {
                $hash .= $chars[mt_rand(0, $max)];
            }
        }
        return $hash;
    }
    //token验证
    public function token_check(){
        $token=input('token');
        $user_id=input('phone');
        if(empty($token) || empty($user_id)){
            return 404;
        }
        $user=Db::table('user')
            ->where('user_id',$user_id)
            ->where('token',$token)
            ->where('token_end>'.time())
            //->field('user_id,number,name,card_status,token,picture,address,mcht_no,secretKey,debit_card,merchant_id,role_id,login_status,integral')
            ->find();
        if(empty($user)){
            return 401;
        }
        $sye_rate=Db::table('rate')
            ->find();
        if(empty($sye_rate)){
            return 401;
        }
        $this->sye_rate=$sye_rate; //获取当前平台手续费率
        $this->online=$user;
        return 200;
    }

    //用户名加*
    public function name_mask($name){
        $str='';
        if(strlen($name)>6){
            $str=mb_substr($name,0,1,'utf-8')."*".mb_substr($name,-1,1,'utf-8');
        }else{
            $str="*".mb_substr($name,-1,1,'utf-8');
        }
        return $str;
    }


    //log记录
    public function log_write($file_name,$message){
        $path = ROOT_PATH .'public' . DS . 'log'.DS.date('Y-m-d',time()).DS;

        if(!is_dir($path)){
            mkdir($path);
        }

        $filename= $path.$file_name.".txt";
        $fh = fopen($filename, "a+");
        if(is_object($message) || is_array($message)){
            $message="\n".date('Y--m-d H:i:s',time()).json_encode($message);
        }else{
            $message="\n".date('Y--m-d H:i:s',time()).$message;
        }
        fwrite($fh, $message);    // 输出：
        fclose($fh);
    }


    //获取用户可提现余额
    public function get_balance($user_id){
        $this->sye_rate=db('rate')->where('pay_prot_id',1)->find();
        if($this->sye_rate['period']==0){
            $period_time=0;
        }else{
            $period_time=strtotime( $this->sye_rate['period'].'day' );
        }


        //不可提现余额
        $not_account=Db::table('commission')
            ->where('user_id',$user_id)
            ->where('commission_time','>',(time()-$period_time))
            ->where('commission_time','<',time())
            //->fetchSql(true)
            ->sum('commission_money');
        $not_account=empty($not_account)?0:round( $not_account *100 )/100;
        //可提现余额


        //余额
        $balance=db('commission')
            ->where('user_id',$user_id)
            ->sum('commission_money');
        //已提现余额
        $withdraw_count=db('pay_orders')
            ->where('user_id',$user_id)
            ->where('pay_status','<>',"PAY_FAILURE")
            ->sum('pay_money+pay_service');
        $balance_count=$balance-$withdraw_count-($not_account);
        if($balance_count<0)$balance_count=0;
        if($balance_count<0){
            $balance_count=0;
        }
        return $balance_count;
    }

    //获取用户通道费率
    public function get_settle($pay_id=1,$user_id){
        $user=model('user')
            ->where('user_id',$user_id)
            ->find();
        if(empty($user)){
            return false;
        }

        if($user['user_type']==1 && $user['role_id']>1){
            $data=Db::table('role_settle')
                ->alias('a')
                ->join('rate b','a.pay_id=b.pay_prot_id')
                ->where('a.role_id',$user['role_id'])
                ->where('a.pay_id',$pay_id)
                ->field('a.settle_rate,a.extra_rate,b.parent,b.superior,b.pay_prot_id,b.rate_type,b.pay_name,b.min_charge,b.start_time,b.end_time,b.min_money,b.max_money,b.costing')
                ->find();
        }elseif($user['is_merchant']==2 ){
            $data=Db::table('group_settle')
                ->alias('a')
                ->join('rate b','a.pay_id=b.pay_prot_id')
                ->where('group_id',$user_id)
                ->where('user_lv',1)
                ->where('a.pay_id',$pay_id)
                ->field('a.settle_rate,a.extra_rate,a.parent,a.superior,b.pay_prot_id,b.rate_type,b.pay_name,b.min_charge,b.start_time,b.end_time,b.min_money,b.max_money,b.costing')
                ->find();
        }elseif($user['user_type']==2 && $user['group_up']==$user['group_id']){
            $data=Db::table('group_settle')
                ->alias('a')
                ->join('rate b','a.pay_id=b.pay_prot_id')
                ->where('group_id',$user['group_id'])
                ->where('user_lv',2)
                ->where('a.pay_id',$pay_id)
                ->field('a.settle_rate,a.extra_rate,a.parent,a.superior,b.pay_prot_id,b.rate_type,b.pay_name,b.min_charge,b.start_time,b.end_time,b.min_money,b.max_money,b.costing')
                ->find();
        }elseif($user['user_type']==2 && $user['group_up']!==$user['group_id']){
            $data=Db::table('group_settle')
                ->alias('a')
                ->join('rate b','a.pay_id=b.pay_prot_id')
                ->where('group_id',$user['group_id'])
                ->where('user_lv',3)
                ->where('a.pay_id',$pay_id)
                ->field('a.settle_rate,a.extra_rate,a.parent,a.superior,b.pay_prot_id,b.rate_type,b.pay_name,b.min_charge,b.start_time,b.end_time,b.min_money,b.max_money,b.costing')
                ->find();
        }elseif($user['user_type']==1){
            $data = Db::table('rate')
//                ->field('pay_prot_id,rate_type,pay_name,settle_rate,extra_rate,min_charge,start_time,end_time,min_money,max_money,parent,')
                ->where('pay_prot_id',$pay_id)
                ->find();
        }

        if(empty($data)){
            $data = Db::table('rate')
//                ->field('pay_prot_id,rate_type,pay_name,settle_rate,extra_rate,min_charge,start_time,end_time,min_money,max_money,parent,')
                ->where('pay_prot_id',$pay_id)
                ->find();
        }
        $rate=db('user_settle')
            ->where('user_id',$user['user_id'])
            ->where('pay_id',$pay_id)
            ->field('settle_rate,extra_rate')
            ->find();
        if(!empty($rate)){
            $data['settle_rate']=$rate['settle_rate'];
            $data['extra_rate']=$rate['extra_rate'];
        }
        return $data;
    }


    //分润算法
    public function fenrun($order,$user){

        if($order['order_status']=="SUCCESS" || empty($user)){
            return false;
        }

        $order['order_money']=(int)$order['order_money'];
        if($user['user_type']==1){
            $sye_rate=Db::table('rate')
                ->where('pay_prot_id',$order['pay_prot_id'])
                ->find();
            if(empty($sye_rate)){

                $this->log_write('shangfu_log','通道费率未找到');
                return false;
            }
            //获取当前平台手续费率
        }elseif($user['is_merchant']==2){
            return true;
        }elseif($user['user_type']==2){
            $sye_rate=$this->get_settle($order['pay_prot_id'],$user['group_id']); //获取当前平台手续费率

            if(empty($sye_rate)){
                $this->log_write('shangfu_log','通道费率未找到');
                return false;
            }
        }
        db::startTrans();
        Db::table('commission')
            ->insert([
                'order_id'=>$order['order_id'],
                'commission_money'=>0,
                'order_money'=>$order['order_money'],
                'user_id'=>$user['user_id'],
                'commission_time'=>time()
            ]);
        $res=Db::table('user')
            ->where("user_id",$user['user_id'])
            ->setInc('integral', $order['order_money']);
        $user_rate=$this->get_settle($order['pay_prot_id'],$user['user_id']);
        if(!empty($user['merchant_id'])){
            // 代理商抽成提取
            $parent=Db::table('user')
                ->alias('a')
                ->where('a.user_id',$user['merchant_id'])
//                        ->field('a.balance,a.merchant_id,a.integral,user_id')
                ->find();
            $share_money=bcmul( $order['order_money'], $sye_rate['parent'],2 );
            $rate_money=0;
			$parent_rate=$this->get_settle($order['pay_prot_id'],$parent['user_id']);
            if($parent['role_id']>1){
				$rate_tatal=$user_rate['settle_rate']-$parent_rate['settle_rate'];
				if($rate_tatal>0){
					$rate_money=bcmul( $order['order_money'], ($rate_tatal),2 );
				}
            }


            $res=Db::table('user')
                ->where("user_id",$parent['user_id'])
                ->inc('balance',($share_money+$rate_money))
                ->inc('integral',$order['order_money'])
                ->update();
            Db::table('commission')
                ->insert([
                    'order_id'=>$order['order_id'],
                    'commission_money'=>($share_money+$rate_money),
                    'order_money'=>$order['order_money'],
                    'user_id'=>$parent['user_id'],
                    'rate_money'=>$rate_money,
                    'share_money'=>$share_money,
                    'commission_time'=>time()
                ]);

            if(!empty($parent['merchant_id'])){
                $superior=Db::table('user')
                    ->where('user_id',$parent['merchant_id'])
//                            ->field('balance,integral,user_id')
                    ->find();
                $superior_balance=bcmul( $order['order_money'], $sye_rate['superior'] ,2);

                $rate_money=0;
				  $superior_rate=$this->get_settle($order['pay_prot_id'],$superior['user_id']);
                if($superior['role_id']>1){
                  
					$superior_rate_total=$parent_rate['settle_rate']-$superior_rate['settle_rate'];
					if($superior_rate_total>0){
						$rate_money=bcmul( $order['order_money'], $superior_rate_total,2 );
					}
                }
                
                $balance_total=$rate_money+$superior_balance;
                $res=Db::table('user')
                    ->where("user_id",$superior['user_id'])
                    ->inc('balance',$balance_total)
                    ->inc('integral',$order['order_money'])
                    ->update();

                Db::table('commission')
                    ->insert([
                        'order_id'=>$order['order_id'],
                        'commission_money'=>$balance_total,
                        'share_money'=>$superior_balance,
                        'rate_money'=>$rate_money,
                        'order_money'=>$order['order_money'],
                        'user_id'=>$superior['user_id'],
                        'commission_time'=>time()
                    ]);
            }

        }
        if(!empty($user['merchant_id']) && $user['user_type']==2){

            $total=bcmul( $order['order_money'], ($user_rate['settle_rate']-$sye_rate['settle_rate']),2 );//总代总分润

            $balance=0;//总分润
            $rate_money=0;
            $service_money=0;
            $group_up_settle=$this->get_settle($order['pay_prot_id'],$user['group_up']);
            $rate_money=bcmul( $order['order_money'], ($user_rate['settle_rate']-$group_up_settle['settle_rate']),2 );
            $res=Db::table('user') //代理分润写入
            ->where("user_id",$user['group_up'])
                ->inc('balance',$rate_money)
                ->inc('integral',$order['order_money'])
                ->update();
            $comm_order=db('commission')
                ->where('order_id',$order['order_id'])
                ->where('user_id',$user['group_up'])
                ->find();
            if(empty($comm_order)){
                Db::table('commission')     //代理分润写入
                ->insert([
                    'order_id'=>$order['order_id'],
                    'commission_money'=>$rate_money,
                    'order_money'=>$order['order_money'],
                    'user_id'=>$user['group_up'],
                    'rate_money'=>$rate_money,
                    'commission_time'=>time()
                ]);
            }else{
                db('commission')
                    ->where('order_id',$order['order_id'])
                    ->where('user_id',$user['group_up'])
                    ->inc('commission_money',$rate_money)
                    ->update([
                        'rate_money'=>$rate_money,
                    ]);
            }
            if($user['group_up']==$user['group_id']){//总代理分润写入
                $service_money=($user_rate['extra_rate']-$sye_rate['extra_rate'])/100;
                $group_order=db('commission')
                    ->where('order_id',$order['order_id'])
                    ->where('user_id',$user['group_id'])
                    ->find();
                if(empty($group_order)){
                    Db::table('commission')     //代理分润写入
                    ->insert([
                        'order_id'=>$order['order_id'],
                        'commission_money'=>$service_money,
                        'service_money'=>$service_money,
                        'order_money'=>$order['order_money'],
                        'user_id'=>$user['group_id'],
                        'rate_money'=>$rate_money,
                        'share_money'=>0,
                        'commission_time'=>time()
                    ]);
                }else{
                    db('commission')
                        ->where('order_id',$order['order_id'])
                        ->where('user_id',$user['group_id'])
                        ->inc('commission_money',$service_money-$share_money)
                        ->update([
                            'service_money'=>$service_money,
                            'share_money'=>0,
                        ]);
                }
                $res=Db::table('user') //代理分润写入
                ->where("user_id",$user['group_id'])
                    ->inc('balance',$service_money)
                    ->update();
            }
            if($user['group_up']!==$user['group_id']){
                //总代理分润写入
                $group_up_settle=$this->get_settle($order['pay_prot_id'],$user['group_up']);
                $rate=$group_up_settle['settle_rate']-$sye_rate['settle_rate']-$sye_rate['parent']-$sye_rate['superior'];
                $group_money=bcmul( $order['order_money'], $rate,2 );
                $service_money=($user_rate['extra_rate']-$sye_rate['extra_rate'])/100;
                $group_order=db('commission')
                    ->where('order_id',$order['order_id'])
                    ->where('user_id',$user['group_id'])
                    ->find();
                if(empty($group_order)){
                    Db::table('commission')     //代理分润写入
                    ->insert([
                        'order_id'=>$order['order_id'],
                        'commission_money'=>($group_money+$service_money),
                        'service_money'=>$service_money,
                        'order_money'=>$order['order_money'],
                        'user_id'=>$user['group_id'],
                        'rate_money'=>$group_money,
                        'commission_time'=>time()
                    ]);
                }else{
                    db('commission')
                        ->where('order_id',$order['order_id'])
                        ->where('user_id',$user['group_id'])
                        ->inc('commission_money',($group_money+$service_money))
                        ->update([
                            'rate_money'=>$group_money,
                            'service_money'=>$service_money,
                        ]);
                }

                $res=Db::table('user') //代理分润写入
                ->where("user_id",$user['group_id'])
                    ->inc('balance',($group_money+$service_money))
                    ->inc('integral',$order['order_money'])
                    ->update();
            }
        }
        db::commit();
        return true;
    }


    //php对象转数组
    public function object_to_array($object) {
        $array=array();
        if (is_object($object)) {
            foreach ($object as $key => $value) {
                if(empty($value)){
                    $array[$key]='';

                }else{
                    $array[$key] = $value;
                }

            }
        }
        else {
            $array = $object;
        }
        return $array;
    }
    //获取图片接口信息
    public function get_pic_data($url,$pic_path,$type=null){
        $postData="bas64String=".$this->img_to_base($pic_path); //post数据拼接

        if(!empty($type)){
            $url.="?typeid=".$type;
        }
        $this->returnMsg['url']=$url;
        $this->returnMsg['post']=$postData;
        return $this->aliyun_curl($url,"POST",$postData,'ff74eab759eb4485b67eab325b8ac0ac');
    }
    //阿里云接口接入
    protected function aliyun_curl($url,$method="GET",$postFile=null,$appcode=NULL){
        $headers = array();
        $appcode=empty($appcode)?$this->appcode:$appcode;
        array_push($headers, "Authorization:APPCODE " . $appcode);

        if(!empty($postFile)) {
            //根据API的要求，定义相对应的Content-Type
            array_push($headers, "Content-Type" . ":" . "application}/x-www-form-urlencoded; charset=UTF-8");
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        if (1 == strpos("$".$url, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        if(!empty($postFile)){
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postFile);
        }
        //$this->returnMsg['dats']=curl_exec($curl);
        $result=json_decode(curl_exec($curl));
        curl_close($curl);
        return $result;
    }


    //base64图片上传处理保存
    public function base_img_upload($base64=NULL,$dir="temporary"){
        // 获取表单上传文件 例如上传了001.jpg
        if(empty($base64)){
            $this->returnMsg['message']='图片上传失败';
            return false;
        }
        // ###文件处理
        // 创建对应的目录
        $pic_path = 'public' . DS . 'uploads/'.$dir.'/'.date('Ymd',time()) . '/';
        !file_exists($pic_path) && mkdir($pic_path, 0777);
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64, $result)){
            $type = $result[2];
            $random=$this->random(3);
            $pic_path .=md5(md5(time().$random)).".".$type;
            $file_size=file_put_contents(ROOT_PATH.$pic_path, base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64)));
            if($file_size>10*1024*1024){
                $this->returnMsg['message']='图片大小不能大于10M';
                return false;
            }
        }else{
            $this->returnMsg['message']='图片上传错误,请重新上传';
            return false;
        }
//        $this->image_png_size_add($pic_path,$pic_path);
        return $pic_path;
    }

    /**
     * desription 压缩图片
     * @param sting $imgsrc 图片路径
     * @param string $imgdst 压缩后保存路径
     */
    function image_png_size_add($imgsrc,$imgdst){
        list($width,$height,$type)=getimagesize($imgsrc);
        $new_width = ($width>1024?1024:$width)*2;
        $new_height =($height>1024?1024:$height)*2;
        switch($type){
            case 1:
                $giftype=check_gifcartoon($imgsrc);
                if($giftype){
                    header('Content-Type:image/gif');
                    $image_wp=imagecreatetruecolor($new_width, $new_height);
                    $image = imagecreatefromgif($imgsrc);
                    imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                    imagejpeg($image_wp, $imgdst,75);
                    imagedestroy($image_wp);
                }
                break;
            case 2:
                header('Content-Type:image/jpeg');
                $image_wp=imagecreatetruecolor($new_width, $new_height);
                $image = imagecreatefromjpeg($imgsrc);
                imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagejpeg($image_wp, $imgdst,75);
                imagedestroy($image_wp);
                break;
            case 3:
                header('Content-Type:image/png');
                $image_wp=imagecreatetruecolor($new_width, $new_height);
                $image = imagecreatefrompng($imgsrc);
                imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagejpeg($image_wp, $imgdst,75);
                imagedestroy($image_wp);
                break;
        }
    }
    /**
     * desription 判断是否gif动画
     * @param sting $image_file图片路径
     * @return boolean t 是 f 否
     */
    function check_gifcartoon($image_file){
        $fp = fopen($image_file,'rb');
        $image_head = fread($fp,1024);
        fclose($fp);
        return preg_match("/".chr(0x21).chr(0xff).chr(0x0b).'NETSCAPE2.0'."/",$image_head)?false:true;
    }
    /**
     * desription 图片转码base64
     * @param sting $image_file图片路径
     * @return boolean t 是 f 否
     */
    function img_to_base($image_file){
        return urlencode(base64_encode(file_get_contents($image_file)));
    }


    // 阿里云api 接口调用
    protected function curl_allinfo($urls, $header = NULL, $post = FALSE) {
        $url = is_array($urls) ? $urls['0'] : $urls;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //带header方式提交
        if(!empty($header)){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        //post提交方式
        if($post != FALSE){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        if(is_array($urls)){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        $data = curl_exec($ch);
        return $data;
        curl_close($ch);
        return json_decode($data);
    }



    //图片文件上传保存
    protected function load_img_save($file,$file_path="temporary"){
        if($file["error"])
        {
            echo $file["error"];
            return false;
        }
        else
        {
            //控制上传文件的类型，大小
            if(($file["type"]=="image/jpeg" || $file["type"]=="image/png") && $file["size"]<1024*1024*3)
            {
                //找到文件存放的位置

                $filepath = "./public/uploads/".$file_path."/".date('Ymd',time()).'/';
                !file_exists($filepath) && mkdir($filepath, 0777);

                $file_type=substr(strrchr($file['type'],'/'),1);
                $filename = $filepath.md5(time().$this->random()).".".$file_type ;

                //转换编码格式
                $filename = iconv("UTF-8","gb2312",$filename);

                //判断文件是否存在
                if(file_exists($filename))
                {
                    //echo "该文件已存在！";
                    return false;
                }
                else
                {
                    //保存文件
                    move_uploaded_file($file["tmp_name"],$filename);
                    return $filename;
                }
            }
            else
            {
//                echo "文件类型不正确！";
                return false;
            }
        }
    }

    protected function sms_ali($mobile_code="1233",$moblie="18649738701"){
        if(empty($mobile_code) || empty($moblie)){

            return false;
        }
        $demo = new Smsdemo(
            "LTAItTnVL6dKGHMg", // 请替换成您自己的AccessKeyId
            "eKzCbneB27Dhzs8wrgvPGbj1Pbv2OX" // 请替换成您自己的AccessKeySecret
        );
        $response = $demo->sendSms(
            "随便提", // 短信签名
            "SMS_105020005", // 短信模板编号
            (string)$moblie, // 短信接收者
            Array(  // 短信模板中字段的值
                "code"=>(string)$mobile_code
            )
        );
        if(empty($response)){
            $this->returnMsg['message']='短信发送失败';
            $this->returnMsg['status']='401';
            return false;
        }
        $result=$this->object_to_array($response);
        $this->log_write('send_sms',$result);
        if($result['Code']=='isv.BUSINESS_LIMIT_CONTROL'){
            $this->returnMsg['message']='发送请求太过频繁，请稍后再试';
            $this->returnMsg['status']='401';
            return false;
        }elseif($result['Code']=='OK'){
            return true;
        }
        return false;
    }

    //互亿无线短信发送
    protected function sms_hy($mobile,$code){
        $res=vendor('hywxsms.smshy');
        $sms=new \Smshywx();
        $gets=$sms->send($mobile,$code);
        if($gets['SubmitResult']['code']==2){
            return true;
        }
        $this->returnMsg['message']=$gets['SubmitResult']['msg'];
        return false;

    }


}
