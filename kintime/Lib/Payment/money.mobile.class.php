<?php

class money{//余额支付
    
    public function  getCode($logs){
            return  '<a  class="payment" set-lan="html:m-pay-immediately" href="'.U('mcenter/member/pay',array('logs_id'=>$logs['logs_id'])).'">立刻支付</a>';
    }

    public function respond(){
        
    }
    
}