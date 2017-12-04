<?php
namespace app\api\controller;
use \think\File;
use \think\Db;
use \think\Model;
class Identity extends Online
{
    public function index(){
		//$this->returnMsg['message']='cuowu';
		//return $this->returnMsg;
        if($this->online['card_status']==1){
            $this->returnMsg['message']='你已完成身份认证,请勿重复提交';
            return $this->returnMsg;
        }
//        //保存图片
        $face=input('picture_face');
		if(empty($face)){
			$this->returnMsg['message']="图片上传失败,请重新上传";
            $this->returnMsg['status']=401;
            return $this->returnMsg;
		}
        $face_url=$this->base_img_upload($face,'identity');

        //图片api 信息读取
        $url = "http://jisusfzsb.market.alicloudapi.com/idcardrecognition/recognize";

        $face_data=$this->get_pic_data($url,$face_url,2);
		$this->log_write('face_data',$face_data);
		
        if(empty($face_data) || $face_data->status>=200){
            $this->returnMsg['message']="图片识别失败,请重新上传";
            $this->returnMsg['status']=401;
            return $this->returnMsg;
        }
		if($face_data->status>=200){
			$this->returnMsg['message']=$face_data->status;
            $this->returnMsg['status']=401;
            return $this->returnMsg;
		}
		
		$card_data=$this->object_to_array($face_data->result);
		if(empty($card_data['name'])){
			$this->returnMsg['message']='身份证姓名无法读取，请重新上传';
			return $this->returnMsg;
		}
		if(empty($card_data['address'])){
			$this->returnMsg['message']='身份证地址无法读取，请重新上传';
			return $this->returnMsg;
		}
        $is_card=$this->isCreditNo($face_data->result->number);
		if(!$is_card){
			$this->returnMsg['message']="图片上传有误,请上传身份证正面照片";
            $this->returnMsg['status']=401;
            return $this->returnMsg;
		}
        $card_data['card_status']=1;
		$card_data['card_pic']=$face_url;
        $user_model=model('user');
        $res= $user_model
            ->allowField(true)
            ->save($card_data,['user_id'=>$this->online['user_id']]);
        if(!$res){
            $this->returnMsg['message']="系统错误,请稍后再试";
            $this->returnMsg['status']=401;
            return $this->returnMsg;
        }
        //身份实名认证
        $this->returnMsg['data']['name']=$this->name_mask($card_data['name']);
        $this->returnMsg['data']['number']=substr($card_data['number'],0,6)."******".substr($card_data['number'],-4);
        $this->returnMsg['message']='认证成功';
        $this->returnMsg['path']=$face_url;
        $this->returnMsg['status']='200';
        return $this->returnMsg;
    }
	public function isCreditNo($vStr)
	{
		$vCity = array(
			'11','12','13','14','15','21','22',
			'23','31','32','33','34','35','36',
			'37','41','42','43','44','45','46',
			'50','51','52','53','54','61','62',
			'63','64','65','71','81','82','91'
		);
	 
		if (!preg_match('/^([\d]{17}[xX\d]|[\d]{15})$/', $vStr)) return false;
	 
		if (!in_array(substr($vStr, 0, 2), $vCity)) return false;
	 
		$vStr = preg_replace('/[xX]$/i', 'a', $vStr);
		$vLength = strlen($vStr);
	 
		if ($vLength == 18)
		{
			$vBirthday = substr($vStr, 6, 4) . '-' . substr($vStr, 10, 2) . '-' . substr($vStr, 12, 2);
		} else {
			$vBirthday = '19' . substr($vStr, 6, 2) . '-' . substr($vStr, 8, 2) . '-' . substr($vStr, 10, 2);
		}
	 
		if (date('Y-m-d', strtotime($vBirthday)) != $vBirthday) return false;
		if ($vLength == 18)
		{
			$vSum = 0;
	 
			for ($i = 17 ; $i >= 0 ; $i--)
			{
				$vSubStr = substr($vStr, 17 - $i, 1);
				$vSum += (pow(2, $i) % 11) * (($vSubStr == 'a') ? 10 : intval($vSubStr , 11));
			}
	 
			if($vSum % 11 != 1) return false;
		}
	 
		return true;
	}


	//个人身份信息获取
    public function get_identity(){
        $user_id=input('phone');
        $identity_data=model('user')
            ->where('user_id',$user_id)
            ->field('number,name,card_status')
            ->find();
		if(empty($identity_data)){
			$this->returnMsg['message']="数据为空";
			return $this->returnMsg;
		}
		$identity_data['number']=substr($identity_data['number'],0,5)."*********".substr($identity_data['number'],-1);
		if(strlen($identity_data['name'])>2){
			$identity_data['name']=mb_substr($identity_data['name'],0,1,'utf-8')."*".mb_substr($identity_data['name'],-1,1,'utf-8');
		}else{
			$identity_data['name']="*".mb_substr($identity_data['name'],-1,1,'utf-8');
		}
		$this->returnMsg['data']=$identity_data;
		$this->returnMsg['message']="请求成功";
		$this->returnMsg['status']=200;
		return $this->returnMsg;
	}
}
