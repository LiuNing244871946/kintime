<?php


require_once 'chinapay/common.php';
require_once 'chinapay/SecssUtil.class.php';
require_once 'chinapay/Settings_INI.php';
class chinapay {
    
    private $pay_url = 'https://payment.chinapay.com/CTITS/service/rest/page/nref/000000000017/0/0/0/0/0';


    /**
     * 生成支付代码
     * @param   array   $logs  订单信息
     * @param   array   $payment    支付方式信息
     */

    function getCode($logs, $payment)
    {

        $sendMap['MerId']           = $payment['chinapay_account']; //商户ID
        $sendMap['MerOrderNo']      =  pad($logs['logs_id'],'l',16,0);//订单ID 要补齐到16位
        $sendMap['OrderAmt']        = (int)($logs['logs_amount']*100);//订单金额
        $sendMap['TranDate']        = date('Ymd',NOW_TIME); //交易日期
        $sendMap['TranTime']        = date('His',NOW_TIME); //交易时间
        $sendMap['TranType']        = '0001';
        
        $sendMap['BusiType']        = '0001'; //固定值 业务类型
        $sendMap['Version']         = '20140728';//支付接口版本号
        $sendMap['CurryNo']         = 'CNY';//支付币种
        $sendMap['AccessType']      = '0';
        $sendMap['AcqCode']         = '000000000000014';
        $sendMap['MerPageUrl']      = __HOST__ . U( 'pchome/payment/respond', array('code' => 'chinapay','respond'=>1)); // 前台通知地址
        $sendMap['MerBgUrl']        = __HOST__ . U( 'pchome/payment/respond', array('code' => 'chinapay'));//后台通知地址

        $sendMap['MerResv']         = 'kintime';//私有域
        
        $secssUtil = new SecssUtil();
       // echo $payment['chinapay_file'];die;
        $secssUtil->init($payment['chinapay_file']);
        $secssUtil->sign($sendMap);
        $sendMap['Signature'] = $secssUtil->getSign();
        //var_dump($sendMap);
        $html = '<form name="payment" action="'.$this->pay_url.'" method="POST">';
        
        foreach($sendMap as $k=>$v){
            $html.="<input type='hidden' name = '" . $k . "' value ='" . $v . "'/>";
        }
    
        $html .= '<input type="submit" class="payment" value="立刻支付"/></form>';    
        return $html;
    }

    /**
     * 响应操作
     */
    function respond()
    {
        if (!empty($_POST)) {
            foreach ($_POST as $key => $data) {
                $_GET[$key] = $data;
            }
        }
       // print_r($_GET);die;
        $payment = D('Payment')->getPayment($_GET['code']);
        //print_r($payment);die;
        $logs_id = ltrim($_GET['MerOrderNo'],"0");
        $secssUtil = new SecssUtil();
        $secssUtil->init($payment['chinapay_file']);
        $data = array();
        $keys = array('code','g','respond','m','a',C('VAR_URL_PARAMS'));
        foreach($_GET as $k=>$v){
            if(!in_array($k, $keys)){
                $data[$k] = $v;
            }
        }  
        $result = $secssUtil->verify($data);
       // var_dump($result);die;
        //print_r($data);die;
        //echo $logs_id;die;
        if ($secssUtil->verify($data)) {
            /* 改变订单状态 */
            D('Payment')->logsPaid($logs_id);
            return true;
        }
        return false;
    }

}
