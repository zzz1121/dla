<?php
/**
 * @author qianwang-zlq
 * @version 2017-06-04
 *  api 基础父类
 */
namespace app\api\controller;
use think\Controller;
use think\Db;
class Index extends Controller
{
    private $appcode="be8d2d3ce04f43b386f180a14fcdb604";
    public $returnMsg;
    protected $online;
	protected $settle_rate;
	protected $sye_rate;
    public function _initialize(){
        $this->returnMsg=[
            'status'=>401,
            'message'=>'请求错误',
            'data'=>[]
        ];
    }
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
            ->field('user_id,number,name,card_status,token,picture,address,mcht_no,secretKey,debit_card,merchant_id,role_id')
            ->find();
		if(empty($user)){
            return 401;
        }
		$sye_rate=Db::table('rate')
			->where('id',1)
			->find();
		$this->sye_rate=$sye_rate['settle_rate'];
		
		$this->merchant_rate=$sye_rate['settle_rate'];
		if($user['role_id']==5 && empty($user['merchant_id']) ){
			$rate=$sye_rate;
		}elseif( !empty($user['merchant_id']) && $user['role_id']==5 ){
			$rate=Db::table('merchant')
			->where('merchant_id',$user['merchant_id'])
			->find();
			$merchant_rate=Db::table('merchant')
                ->alias('a')
                ->join('role b','a.role_id=b.role_id')
                ->where('a.merchant_id',$user['merchant_id'])
                ->field('b.settle_rate')
                ->find()['settle_rate'];
            $this->merchant_rate=$merchant_rate;
		}else{
			$rate=Db::table('role')
			->where('role_id',$user['role_id'])
			->find();
		}
		$this->settle_rate=$rate['settle_rate'];
		//$this->returnMsg['data2']=$rate['settle_rate'];
       
        $this->online=$user;
        return 200;
    }

    //php对象转数组
    public function object_to_array($object) {
        if (is_object($object)) {
            foreach ($object as $key => $value) {
                $array[$key] = $value;
            }
        }
        else {
            $array = $object;
        }
        return $array;
    }
    //获取图片接口信息
    public function get_pic_data($url,$pic_path,$type=null){
        $postData="pic=".$this->img_to_base($pic_path); //post数据拼接
        if(!empty($type)){
            $url.="?typeid=".$type;
        }
        return $this->aliyun_curl($url,"POST",$postData);
    }
    //阿里云接口接入
    protected function aliyun_curl($url,$method="GET",$postFile=null,$appcode=NULL){
        $headers = array();
        $appcode=empty($appcode)?$this->appcode:$appcode;
        array_push($headers, "Authorization:APPCODE " . $appcode);
		//$this->returnMsg['appcode']=$appcode;
		//$this->returnMsg['postFile']=$postFile;
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
        $this->image_png_size_add($pic_path,$pic_path);
        return $pic_path;
    }

    /**
     * desription 压缩图片
     * @param sting $imgsrc 图片路径
     * @param string $imgdst 压缩后保存路径
     */
    function image_png_size_add($imgsrc,$imgdst){
        list($width,$height,$type)=getimagesize($imgsrc);
        $new_width = ($width>1024?1024:$width)*0.95;
        $new_height =($height>1024?1024:$height)*0.95;
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
	
	 public function change_settle_rate(){
        $reg_data = [
            'sp_id' => config('sp_id'),
            'mcht_no' => $this->online['mcht_no'],
            'busi_type' => 'EPAYS',
            'settle_type' => 'REAL_PAY',
            'settle_rate' => $this->settle_rate,
            'extra_rate_type' => 'AMOUNT',
            'extra_rate' => 200,
            'nonce_str' => $this->random(4, 1)
        ];
        $reg_data = $this->sbt_sign($reg_data,config('sbt_key'));
        $url = config('sbt_api_url'). '/gate/msvr/busiratemodify';
        $result_reg = $this->curl_allinfo($url, false, $reg_data['data']);
		
		//$this->returnMsg['data4']=$this->online;
		//$this->returnMsg['data5']=$result_reg;
		//$this->returnMsg['data3']=$this->settle_rate;
        if(empty($result_reg)){
            $this->returnMsg['message'] = '系统繁忙，请稍候再试';
			return 401;
		   //return $this->returnMsg;
        }
        if ($result_reg->status !== 'SUCCESS') {
            $this->returnMsg['message'] = $result_reg->message;
            //return $this->returnMsg;
			return 401;
        }
        if($result_reg->result_code!=="SUCCESS"){
            $this->returnMsg['message'] = $result_reg->err_msg;
            //return $this->returnMsg;
			return 401;
        }
        return 200;
    }
	
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
        curl_close($ch);
        return json_decode($data);
    }
    protected function sbt_sign($post_data=null,$secret_key=NULL){
        $str='';
        if(!is_array($post_data)&&!is_object($post_data)){
            return false;
        }
        if(is_object($post_data)){
            $arr=[];
            foreach($post_data as $key =>$val){
                $arr[$key]=$val;
            }
            $post_data=$arr;
        }
        ksort($post_data);//key值排序
        foreach($post_data as $key=>$val){
            if($key!=='sign'){
                $str.=empty($val)?'':$key."=".$val."&";
            }
        }
        $sign_key=empty($this->online['secretKey'])?config('sbt_key'):$this->online['secretKey'];
        if(!empty($secret_key)){
            $sign_key=$secret_key;
        }
        $str_sign=substr($str,0,-1).'&key='.$sign_key;
		$this->returnMsg['$str']=$str;
        //$this->returnMsg['string']=$str_sign;
		$this->returnMsg['key']=$sign_key;
        //$this->returnMsg['sign']=strtoupper (md5($str_sign));
        $result['sign']=strtoupper (md5($str_sign));//小写字符转义大写
        $result['data']=$str."sign=".strtoupper (md5($str_sign));
        return $result;
    }
}
