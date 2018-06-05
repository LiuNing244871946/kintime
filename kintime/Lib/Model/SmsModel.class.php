<?php

class SmsModel extends CommonModel{
    protected $pk   = 'sms_id';
    protected $tableName =  'sms';
    protected $token  = 'bao_sms';
    
    public function sendSms($code,$mobile,$areacode,$data){
        switch ($code) {  //判断短信模板
            case 1:
                $tpl_id = 81247;
                break;
            case 2:
                $tpl_id = 83459;
                break;
            case 3:
                $tpl_id = 84054;
                break;
        }
        $sdkappid = 1400065026;
        $time = time();
        $appkey = '815e7e58cbffe916374c1c134cf2a3c6';
        $url = 'https://yun.tim.qq.com/v5/tlssmssvr/sendsms';
        $wholeUrl = $url."?sdkappid=".$sdkappid."&random=".$scode;
        $tel = new \stdClass();
        $tel->nationcode = $areacode;                // 手机区域码 
        $tel->mobile = $mobile;                    // 用户手机
        $jsondata = new \stdClass();
        $jsondata->tel = $tel;                        // 手机号结构数据
        $jsondata->tpl_id = $tpl_id;            // 模板ID
        $jsondata->sign = 'kintime';            // 签名
        $params = $data;
        $jsondata->params = $params; 
        $jsondata->time = $time;   
        $jsondata->extend = "";                       // 根据需要添加，一般保持默认
        $jsondata->ext = "";     // 根据需要添加，一般保持默认
        $jsondata->sig = hash("sha256", "appkey=815e7e58cbffe916374c1c134cf2a3c6&random=$scode&time=$time&mobile=$mobile");  // 生成签名
        $curlPost =json_encode($jsondata);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $wholeUrl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $ret = curl_exec($ch);
        if($ret === false) {
            return  curl_error($ch);
        } else {
            $json = json_decode($ret);
            if($json === false) {
                echo $ret;
            } else {
               echo  $json;
            }
        }
        curl_close($ch);
    }
    
    public function mallTZshop($order_id){
        if(is_numeric($order_id) &&  ($order_id = (int)$order_id)){
           $order_id = array($order_id); 
        }
        $orders = D('Order')->itemsByIds($order_id);
        $shop = array();
        foreach($orders as $val){
            $shop[$val['shop_id']] =$val['shop_id'];             
        }
        $shops = D('Shop')->itemsByIds($shop);
        // foreach($shops as $val){            
        //     $this->sendSms('sms_shop_mall', $val['mobile'], array());
        // }
        return true;
    }
    
    public function eleTZshop($order_id){
        if(is_numeric($order_id) &&  ($order_id = (int)$order_id)){
          $order = D('Eleorder')->find($order_id); 
          $shop = D('Shop')->find($order['shop_id']);
		  // $this->sendSms('sms_shop_ele', $shop['mobile'], array());
        }
        return true;
    }

	public function dingTZshop($order_id){
        if(is_numeric($order_id) &&  ($order_id = (int)$order_id)){
          $order = D('Shopdingorder')->find($order_id); 
          $shop = D('Shopding')->find($order['shop_id']);
		  // $this->sendSms('sms_shop_ele', $shop['mobile'], array());
        }
        return true;
    }
    
     public function tuanTZshop($shop_id){
        $shop_id = (int)$shop_id;
        $shop = D('Shop')->find($shop_id);
        //file_put_contents('/www/web/bao_baocms_cn/public_html/Baocms/Lib/Model/aaa.txt', var_export($shop, true));
        // $this->sendSms('sms_shop_tuan', $shop['mobile'], array());
        return true;
    }
  
    
     public function fetchAll(){
        $cache = cache(array('type'=>'File','expire'=>  $this->cacheTime));
        if(!$data = $cache->get($this->token)){
            $result = $this->order($this->orderby)->select();
            $data = array();
            foreach($result  as $row){
                $data[$row['sms_key']] = $row;
            }
            $cache->set($this->token,$data);
        }   
        return $data;
     }
  
}