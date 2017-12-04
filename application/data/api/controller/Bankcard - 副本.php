<?php
namespace app\api\controller;
use \think\File;
use \think\Db;
use \think\Model;
use \think\Request;
class Bankcard extends Online
{
    public function index(){
		//$this->returnMsg['message']=123;
			//return $this->returnMsg;
        $card_type=input('card_type');//卡类型(DEBIT,储蓄卡;CREDIT,信用卡)
        if(request()->isPost()){
            if($this->online['card_status']!=1){
                $this->returnMsg['message']='尚未实名认证,无法绑定银行卡';
                return $this->returnMsg;
            }

            $user_name=$this->online['name'];//持有人
            $user_card=$this->online['number'];//持有人
            if(empty($user_card)){
                $this->returnMsg['message']='上传信息不全,请重新输入';
                return $this->returnMsg;
            }
            if($user_card!==$this->online['number']){
                $this->returnMsg['message']='只能绑定本人银行卡';
                return $this->returnMsg;
            }

            if(empty($card_type)){
                $this->returnMsg['message']='请确认绑定的银行卡类型';
                return $this->returnMsg;
            }
            if($card_type=='CREDIT'){
                $card_end=input('card_end');//有效期
                $card_cvv=input('card_cvv');//卡片安全码
                if(empty($card_end)|| strlen($card_end)>4  || strlen($card_end)<=3){
                    $this->returnMsg['message']='请输入有效的卡片有效期';
                    return $this->returnMsg;
                }
                $month=substr($card_end,0,2);
                if($month>12 || $month==0){
                    $this->returnMsg['message']='卡片有效期格式为：月份/年份';
                    return $this->returnMsg;
                }
                if(empty($card_cvv)|| strlen($card_cvv)>3 || strlen($card_cvv)<=2 ){
                    $this->returnMsg['message']='请输入正确的CVV码';
                    return $this->returnMsg;
                }
				
            }
            $card_id=input('card_id');//银行卡
            $card_phone=input('card_phone');//预留手机号
            $bank_no=input('bank_no');
            $card_pic=input('card_pic');//银行卡照片
			//$this->log_write('card_base',$card_pic);
            if(empty($card_pic)){
                $this->returnMsg['message']='银行卡图片上传失败';
                return $this->returnMsg;
            }
            if(empty($card_phone)){
                $this->returnMsg['message']='请输入预留手机号';
                return $this->returnMsg;
            }
            $pic_path=$this->base_img_upload($card_pic,'bank_card');
            //图片api 信息读取
            $url = "http://jisuyhksb.market.alicloudapi.com/bankcardcognition/recognize";
            $response=$this->get_pic_data($url,$pic_path);
			$this->log_write('bank_card_data',$response);
            if($response->status>200){
                $this->returnMsg['message']='图片识别失败,请尽量减少图片反光';
                return $this->returnMsg;
            }
            $card_data=$response->result;
			$this->returnMsg['message']=$card_data->type;
			
			if(empty($card_data->type) || empty($card_data->bankno) || empty($card_data->number)){
				  $this->returnMsg['message']='图片识别失败，请重新上传';
                return $this->returnMsg;
			}
				//return $this->returnMsg;
			if($card_data->type=="未知"){
				$this->returnMsg['message']='图片识别失败,请尽量减少图片反光';
				return $this->returnMsg;
			}
			
            if($card_data->type=='借记卡'){
                $card_pic_type='DEBIT';
            }elseif($card_data->type=='贷记卡'){
                $card_pic_type='CREDIT';
            }else{
				 $card_pic_type='';
			}
            if($card_type!==$card_pic_type){
                $this->returnMsg['message']='上传图片信息与所选银行卡类型不符';
                return $this->returnMsg;
            }
            $card_id=$card_data->number;//银行卡
            $model=model('user_card');
            $is_have=$model
                ->where('card_id',$card_id)
                ->field("card_id")
                ->find();
            if(!empty($is_have)){
                $this->returnMsg['message']='该卡已绑定,请勿重复绑定';
                return $this->returnMsg;
            }
			
			if($card_type=="CREDIT"){
				(string)$res=$card_data->bankname;
				$card_data->bankno='';
				//$card_data->bankname=mb_substr($res,0,4);
				$bank_no_credit=config("bank_no_credit");
				foreach($bank_no_credit as $val){
					if($val['bank_name']==$card_data->bankname){
						$card_data->bankno=$val['bank_no'];
						break;
					}
				}
			}elseif($card_type=="DEBIT"){
				
				$card_data->bankno='';
				$bank_no_debit=config('bank_no_debit');
				foreach($bank_no_debit as $val){
					if($val['bank_name']==$card_data->bankname){
						$card_data->bankno=$val['bank_no'];
						break;
					}
				}
			}


			if(empty($card_data->bankno)){
				$this->returnMsg['message']="暂不支持该行银行卡，请更换卡片";
				return $this->returnMsg;
			}

			// 银行卡4要素认证
			$bank_4_res=$this->bank_card_4($card_id,$user_card,$user_name,$card_phone);
			if($bank_4_res>200){
			    return $this->returnMsg;
            }
			
			$card_data=$this->object_to_array($card_data);
            if($card_type=='DEBIT' && empty($this->online['debit_card']) ) {//新用户商户注册
               
                $card_data['bank_name']=$card_data['bankname'];
                $card_data['bank_no']=$card_data['bankno'];
                $card_data['card_phone']=$card_phone;
                $card_data['card_id']=$card_id;
                $reg_shangfu_res=$this->reg_shangfu_pay($card_data);
                if($reg_shangfu_res!==200){
                    return $this->returnMsg;
                }
                
            }


            $model['user_id']=$this->online['user_id'];
            $model['card_id']=$card_id;
            $model['card_name']=$user_name;
            $model['card_phone']=$card_phone;
            $model['card_type']=$card_type;
            $model['bank_no']=$card_data['bankno'];
            $model['bank_name']=$card_data['bankname'];
			$model['md_card']=substr($card_id,0,4)."**********".substr($card_id,-4);
            if($card_type=='CREDIT'){
                $model['card_end']=$card_end;
                $model['card_cvv']=$card_cvv;
            }
            $insert_result=$model->save();
            if(!$insert_result){
                $this->returnMsg['message']='系统错误,请稍后再试';
                return $this->returnMsg;
            }


            $this->returnMsg['data']['card_data']=$this->get_my_card();
            //
            $this->returnMsg['message']='认证成功';
            $this->returnMsg['status']='200';
            return $this->returnMsg;
        }else if(request()->isGet()){
            $this->returnMsg['status']=200;
            $card_data=$this->get_my_card();
            //if(empty($card_data)){
           //     $this->returnMsg['message']='暂未绑定银行卡';
             //   return $this->returnMsg;
            //}
            $this->returnMsg['data']['card_data']=$card_data;
            $this->returnMsg['message']='sueecss';
            return $this->returnMsg;
        }
    }


    //上福支付通道注册
    public function reg_shangfu_pay($card_data){
        if(empty($card_data)){
            return 400;
        }
        $post_data = [
            'sp_id' => config('sp_id'),
            'mcht_name' => '个体商户',
            'mcht_short_name' => $this->online['name'] . '的店铺',
            'address' => $this->online['address'],
            'leg_name' => $this->online['name'],
            'leg_phone' => $this->online['user_id'],
            'leg_email' => $this->online['user_id'] . '@163.com',
            'acc_no' => $card_data['card_id'],
            'acc_bank_name' => $card_data['bankname'],
            'acc_bank_no' => $card_data['bankno'],
            'service_tel' => $card_data['card_phone'],
            'id_type' => '01',
            'id_no' => $this->online['number'],
            'nonce_str' => $this->random(4, 1)
        ];

        $post_data = $this->sbt_sign($post_data,config('sbt_key'));
        $url = $this->sbt_url . '/gate/msvr/mbreg';
        $result = $this->curl_allinfo($url, false, $post_data['data']);


        if ( empty($result) || $result->status !== 'SUCCESS' ||!strtolower($result->sign) == $this->sbt_sign($result)['sign'] ) {
            //$this->returnMsg['message'] = '商户注册失败,请稍后再试';
            $this->returnMsg['message'] = $result->message.",银行卡绑定失败";
            return 400;
        }
        Db::startTrans();
        $user_model = model('user');
        $update_data['mcht_no_1'] = $result->mcht_no;
        $update_data['secretKey_1'] = $result->secretKey;
		 $update_data['debit_card']=$card_data['card_id'];
        $res = $user_model
            ->where('user_id', $this->online['user_id'])
            ->update($update_data);
        if ($res==0) {
            $this->returnMsg['message'] = '支付通道开通失败,请重试';
            return 400;
        }
        $reg_data = [
            'sp_id' => config('sp_id'),
            'mcht_no' => $result->mcht_no,
            'busi_type' => 'EPAYS',
            'settle_type' => 'REAL_PAY',
            'settle_rate' => $this->sye_rate['settle_rate'],
            'extra_rate_type' => $this->sye_rate['extra_rate_type'],
            'extra_rate' => $this->sye_rate['extra_rate'],
            'nonce_str' => $this->random(4, 1)
        ];
        $reg_data = $this->sbt_sign($reg_data,config('sbt_key'));
        $url = $this->sbt_url . '/gate/msvr/busiopen';
        $result_reg = $this->curl_allinfo($url, false, $reg_data['data']);
        if(empty($result_reg)){
            $this->returnMsg['message'] = '认证失败';
            Db::rollback();
            return 400;
        }
        if ($result_reg->status !== 'SUCCESS') {
            $this->returnMsg['message'] = $result->message;
            Db::rollback();
            return 400;
        }
        if($result_reg->result_code!=="SUCCESS"){
            $this->returnMsg['message'] = $result->err_msg;
            Db::rollback();
            return 400;
        }
        Db::commit();
        return 200;
    }




    //阿里云四要素验证
    public function bank_card_4($card_id,$user_card,$user_name,$card_phone){
        //银行卡实名认证
        $url = "http://yhsys.market.alicloudapi.com/bank4";
        $querys="bankCardNo=".$card_id."&identityNo=".$user_card."&name=".urlencode($user_name)."&mobileNo=".$card_phone;
        $url .= "?" . $querys;
        $response=$this->aliyun_curl($url,"GET");
        //$this->returnMsg['sss']=$response;
        $this->log_write('card_data',$response);
        if(empty($response) || $response->code!=="0000"){
            $this->returnMsg['message']="请求银行卡认证失败，请联系克服";
            return 400;
        }
        $this->log_write('card_message',$querys);
        $this->log_write('card_message',$response);
        if($response->data->thirdparty->code>1){
            $this->returnMsg['message']="银行卡实名认证失败,或图片识别有误";
            return 400;
        }
        return 200;
    }




    public function get_my_card(){
        $card_type=input('card_type');//卡类型(1,借记卡;2,信用卡)
        $model=model('user_card');
        $card_arr=$model
            ->where('user_id',$this->online['user_id'])
            ->where('card_type',$card_type)
            ->field('card_id,bank_no,bank_name')
            ->select();
        if(!empty($card_arr)){
            foreach($card_arr as $key=>$val){
				$card_arr[$key]['debit_card']=0;
				if($val['card_id']==$this->online['debit_card']){
					$card_arr[$key]['debit_card']=1;
				}
                $card_arr[$key]['card_id']=substr($val['card_id'],0,4)."**********".substr($val['card_id'],-4);
            }
        }
        return $card_arr;
    }
	
	//卡片删除
	public function delete_card(){
		
		if(request()->isPost()){
			$card_id=input('card_id');
			if(empty($card_id)){
				$this->returnMsg['message']='请选择删除卡片';
				return $this->returnMsg;
			}
			$card_data=model('user_card')
				->where('md_card',$card_id)
				->where('user_id',$this->online['user_id'])
				->find();
			if(empty($card_data)){
				$this->returnMsg['message']='您未绑定该卡,请重新选择';
				return $this->returnMsg;
			}
			if($card_data['card_type']=='CREDIT'){
				$card_data=model('user_card')
				->where('md_card',$card_id)
				->delete();
				if($card_data==0){
					$this->returnMsg['message']='删除失败，请稍候再试';
					return $this->returnMsg;
				}
			}else{
				if($card_data['card_id']==$this->online['debit_card']){
					$this->returnMsg['message']='默认收款卡无法删除';
					return $this->returnMsg;
				}else{
					$card_data=model('user_card')
					->where('md_card',$card_id)
					->delete();
					if($card_data==0){
						$this->returnMsg['message']='删除失败，请稍候再试';
						return $this->returnMsg;
					}
				}
			}
			$this->returnMsg['message']='删除成功';
			$this->returnMsg['status']=200;
			return $this->returnMsg;
			
		}
	}
	
	//moren yinhanka genghuan
	public function change_debit(){
		if(request()->isPost()){
			$card_id=input('card_id');
			$card_data=model('user_card')
				->where('md_card',$card_id)
				->where('user_id',$this->online['user_id'])
				->find();
			if(empty($card_data)){
				$this->returnMsg['message'] = '尚未绑定该卡，无法设定为默认收款卡';
				return $this->returnMsg;
			}
			$post_data = [
                    'sp_id' => config('sp_id'),
					'mcht_no'=>$this->online['mcht_no_1'],
                    'acc_no' => $card_data['card_id'],
                    'acc_bank_name' => $card_data['bank_name'],
                    'acc_bank_no' => $card_data['bank_no'],
                    //                'acc_bank_no'=>'104391011208',
                    'service_tel' => $card_data['card_phone'],
                    'nonce_str' => $this->random(4, 1)
                ];

			$post_data = $this->sbt_sign($post_data,config('sbt_key'));
			$url = $this->sbt_url . '/gate/msvr/modify/mbsettle';
			
			$result = $this->curl_allinfo($url, false, $post_data['data']);
               //$this->returnMsg['result']=$result;
                //$this->returnMsg['post_data']=$post_data;
				//return $this->returnMsg;
			if ( empty($result) || $result->status !== 'SUCCESS' ||!strtolower($result->sign) == $this->sbt_sign($result)['sign'] ) {
				//$this->returnMsg['message'] = '系统繁忙,请稍后再试';
				$this->returnMsg['message'] = $result->message;
				return $this->returnMsg;
			}
			$user_model = model('user');
			$update_data['debit_card']=$card_data['card_id'];
			$res = $user_model
				->where('user_id', $this->online['user_id'])
				->update($update_data);
			if (!$res) {
				$this->returnMsg['message'] = '系统繁忙,请稍后再试';
				return $this->returnMsg;
			}
			$this->returnMsg['message'] = '修改默认收款卡成功';
			$this->returnMsg['status']=200;
			return $this->returnMsg;
		}
	}
}
