<?php

class alipay {

    private $alipay_gateway = 'https://mapi.alipay.com/gateway.do?';
    private $https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';
    private $sign_type = 'MD5';
    private $input_charset = 'UTF-8';
    private $alipay_config = array();
	private $cacert_url = null;

	public function __construct()
	{
		$this->cacert_url = dirname(__FILE__).DIRECTORY_SEPARATOR.'alipay/cacert.pem'; 
	}

    public function getCode($logs, $setting) {  
        $this->alipay_config = $setting;
        $params = array(
            "service"       => 'alipay.wap.create.direct.pay.by.user',
            "partner"       => $this->alipay_config['alipay_partner'],
            "seller_id"  => $this->alipay_config['alipay_account'],
            "payment_type"  => '1',
            "notify_url"    =>  __HOST__ . U('mobile/payment/respond', array('code' => 'alipay')),
            "return_url"    =>  __HOST__ . U('mobile/payment/respond', array('code' => 'alipay')),
            "_input_charset"    => $this->input_charset,
            "out_trade_no"  => $logs['logs_id'],
            "subject"   => $logs['subject'],
            "total_fee" => $logs['logs_amount'],
            "body"  => $logs['subject']
        );
	
        //待请求参数数组
        $params = $this->build_params($params);     
        $sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='".$this->alipay_gateway."_input_charset=UTF-8".trim(strtolower($this->input_charset))."' method='get'>";
        while (list ($key, $val) = each ($params)) {
            $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
        }
		
        $sHtml = $sHtml."<input type='submit'  value='立刻支付'></form>";       
        /*$sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>"; */
		 
        return $sHtml;
    }

    public function respond() {
        $payment = D('Payment')->getPayment('alipay');
        $this->alipay_config = $payment;
        if(empty($_POST)) {//return verify
            $notify = $_GET;
        } else { //notify verify
            //生成签名结果
            $notify = $_POST;
        }
        $verify_sign = $this->verify_sign($notify, $notify["sign"]);
        //获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
        $responseTxt = 'false';
        if (! empty($notify["notify_id"])) {
            $responseTxt = $this->verify_notify($notify["notify_id"]);
        }
        $out_trade_no = $notify['out_trade_no'];
		$this->log(array('notify'=>$notify, 'responseText'=>$responseTxt, 'verify_sign'=>$verify_sign, 'payment'=>$payment));
        if (preg_match("/true$/i",$responseTxt) && $verify_sign) {
            D('Payment')->logsPaid($out_trade_no);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 远程获取数据，POST模式
     * 注意：
     * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
     * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
     * @param $url 指定URL完整路径地址
     * @param $cacert_url 指定当前工作目录绝对路径
     * @param $para 请求的数据
     * @param $input_charset 编码格式。默认值：空值
     * return 远程输出的数据
     */
    private function http($url, $para, $input_charset = '') {
        if (trim($input_charset) != '') {
            $url = $url."_input_charset=".$input_charset;
        }
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
        curl_setopt($curl, CURLOPT_CAINFO,$this->cacert_url);//证书地址
        curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
        curl_setopt($curl,CURLOPT_POST,true); // post传输数据
        curl_setopt($curl,CURLOPT_POSTFIELDS,$para);// post传输数据
        $responseText = curl_exec($curl);
        //var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
        curl_close($curl);        
        return $responseText;
    }


    /**
     * 生成要请求给支付宝的参数数组
     * @param $para_temp 请求前的参数数组
     * @return 要请求的参数数组
     */
    function build_params($params) {
        //除去待签名参数数组中的空值和签名参数
        $params = $this->filter_params($params);
        ksort($params);
        reset($params);
        //生成签名结果
        $mysign = $this->create_sign($params);        
        //签名结果与签名方式加入请求提交参数组中
        $params['sign'] = $mysign;
        $params['sign_type'] = strtoupper(trim($this->sign_type));        
        return $params;
    }


    /**
     * 除去数组中的空值和签名参数
     * @param $para 签名参数组
     * return 去掉空值与签名参数后的新签名参数组
     */
    private function  filter_params($para) {
        $para_filter = array();
        while (list ($key, $val) = each ($para)) {
            if($key == "sign" || $key == "sign_type" || $val == "" || $key == 'code' || $key == '_URL_')continue;
            else    $para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }

    private function build_querystring($para) {
        ksort($para);
        reset($para);
        $arg  = "";
        while (list ($key, $val) = each ($para)) {
            $arg.=$key."=".$val."&";
        }
        //去掉最后一个&字符
        $arg = substr($arg,0,count($arg)-2);        
        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){
            $arg = stripslashes($arg);
        }        
        return $arg;
    }

    /**
     * 生成签名结果
     * @param $para_sort 已排序要签名的数组
     * return 签名结果字符串
     */
    private function create_sign($params) {
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $params = $this->filter_params($params);        
        $prestr = $this->build_querystring($params);        
        $mysign = "";
		$this->log($prestr.$this->alipay_config['alipay_key']);
        switch (strtoupper(trim($this->sign_type))) {
            case "MD5" :
                $mysign = MD5($prestr.$this->alipay_config['alipay_key']);
                break;
            default :
                $mysign = "";
        }
        
        return $mysign;
    }

    /**
     * 获取返回时的签名验证结果
     * @param $para_temp 通知返回来的参数数组
     * @param $sign 返回的签名结果
     * @return 签名验证结果
     */
    private function verify_sign($params, $sign) {
        $mysign = $this->create_sign($params);
        return $sign == $mysign;
    }        

    /**
     * 获取远程服务器ATN结果,验证返回URL
     * @param $notify_id 通知校验ID
     * @return 服务器ATN结果
     * 验证结果集：
     * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空 
     * true 返回正确信息
     * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
     */
    private function verify_notify($notify_id){
        $partner = trim($this->alipay_config['alipay_partner']);
        $veryfy_url = $this->https_verify_url;
        $veryfy_url = $veryfy_url."partner=" . $partner . "&notify_id=" . $notify_id;
        $responseTxt = $this->http($veryfy_url);        
        return $responseTxt;
    }

	public function Log($log)
	{
		if(is_array($log) || is_object($log)){
			$log = var_export($log,true);
		}
		$log =	'<?php exit("Access denied");?>'."\n+-----------------------------------------------------+\n".
				"Time:".date("Y-m-d H:i:s")."\nLog:{$log}\n\n";
		$fp = @fopen(dirname(__FILE__)."/alipay.mobile.log.php","a");
		@fwrite($fp,$log);
		@fclose($fp);
	}
}