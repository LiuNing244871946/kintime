<?php

class PaymentAction extends  CommonAction{

    public function log($log_id){
        //取log日志 是否为有效的log_id
        if (empty($this->uid)) {
           $this->showmsg('用户未登录',1001);
        }
        //dump($this->uid);
        $log_id = (int) $log_id;
        $logs = D('Paymentlogs')->find($log_id);
        if (empty($logs) || $logs['user_id'] != $this->uid || $logs['is_paid'] == 1) {
            $this->showmsg('没有有效的支付记录！',1002);
        }else{
            $this->showmsg($logs);
        }
        //json log
    }

    public function payment($log_id, $code='alipay'){
        //取log日志 是否为有效的log_id
        if (empty($this->uid)) {
           $this->showmsg('用户未登录',1001);
        }
        $log_id = (int) $log_id;
        $logs = D('Paymentlogs')->find($log_id);
        if (empty($logs) || $logs['user_id'] != $this->uid || $logs['is_paid'] == 1) {
            $this->ajaxReturn('没有有效的支付记录！',1002);
        }else if($logs['code'] != $code){
            //update payment code 为参数传来的
            K::M('Paymentlogs')->save(array('log_id'=>$log_id,'code'=>$code));
            $logs['code'] = $code;
        }
        if($data = D('Payment')->getCode($logs)){
             //$url 支付成功后让APP打开的webview页面URL 
            if($logs['type'] == 'ele'){
                $url = U('mcenter/eleorder/detail',array('order_id'=>$logs['order_id']));
            }elseif($logs['type'] == 'tuan'){
                $url = U('mcenter/tuan/detail',array('order_id'=>$logs['order_id']));
            }elseif($logs['type'] == 'gold'||$logs['type'] == 'fzmoney'||$logs['type'] == 'money'){
                $url = U('mcenter/index/index');
            }elseif($logs['type'] == 'ding'){
                 $url = U('mcenter/ding/detail',array('order_id'=>$logs['order_id']));
            }elseif($logs['type'] == 'hotel'){
                $url = U('mcenter/hotel/detail',array('order_id'=>$logs['order_id']));
            }elseif($logs['type'] == 'farm'){
                $url = U('mcenter/farm/detail',array('order_id'=>$logs['order_id']));
            }
            $this->showmsg(array('data'=>$data, 'show_url'=>$url));
        }else{
            $this->showmsg("支付失败",1010);
            //返回错误信息
        }

    }

    public function respond()
    {
        $code = $this->_get('code');
        if (empty($code)) {
            $this->error('没有该支付方式！');
            die;
        }
        $ret = D('Payment')->respond($code);
        if ($ret == false) {
            echo  'FAID';
            die;
        }else{
           echo 'SUCESS';
            die;            
        }
    }

      
}