<?php



class EleorderModel extends CommonModel {

    protected $pk = 'order_id';
    protected $tableName = 'ele_order';
    protected $cfg = array(
        0 => '等待付款',
        1 => '等待商家接单',
        2 => '等待骑手接单',
        4 => '配送中',
        3 => '客户已收货',
        8 => '已完成',
    );

    public function checkIsNew($uid, $shop_id) {
        $uid = (int) $uid;
        $shop_id = (int) $shop_id;
        return $this->where(array('user_id' => $uid, 'shop_id' => $shop_id, 'closed' => 0))->count();
    }

    public function getCfg() {
        return $this->cfg;
    }

    public function overOrder($order_id) {
        $detail = D('Eleorder')->find($order_id);
        if (empty($detail))
            return false;
        if (($detail['status'] != 2)&& ($detail['status'] != 8) ) //后台可以对2 或者3 的做处理
            return false;

        $ele = D('Ele')->find($detail['shop_id']);
        $shop = D('Shop')->find($detail['shop_id']);
        $mer = D('Merchants')->find($shop['mer_id']);
        if (D('Eleorder')->save(array('order_id' => $order_id, 'status' => 3)) && D('DeliveryOrder')->where('type_order_id='.$order_id)->save(array('status' => 3))) { //防止并发请求
            if ($detail['is_pay'] == 1) {
                $settlement_price = $detail['total_price'];

                if ($settlement_price > 0) {
                    $settlement_price =  D('Quanming')->quanming($detail['user_id'],$settlement_price,'ele'); //扣去全民营销
                    D('Shopmoney')->add(array(
                        'shop_id' => $detail['shop_id'],
                        'type' => 'ele',
                        'money' => $settlement_price,
                        'create_ip' => get_client_ip(),
                        'create_time' => NOW_TIME,
                        'order_id' => $order_id,
                        'intro' => '餐饮订单:' . $order_id
                    ));

                    D('Users')->addMoney($mer['user_id'], $settlement_price, '外卖订单:' . $order_id);

                    $rebate = D('rebate')->find($shop['rate']);
                    $user_money= $settlement_price*$rebate['rebate']*$rebate['user']/10000; //用户返利金额
                    D('Users')->addMoney($detail['user_id'], $user_money, '外卖订单完成,ID:'.$order_id.'，用户消费返利'); 

                    $invite = D('users')->find($detail['user_id'])['invite1'];
                    if($invite != 0){
                        $tui_user_money= $settlement_price*$rebate['rebate']*$rebate['tui_user']/10000;  // 推荐人返利金额
                        D('Users')->addMoney($invite, $tui_user_money, '外卖订单完成,ID:'.$order_id.'，推荐人消费返利'); 

                        $platform_money= $settlement_price*$rebate['rebate']*$rebate['platform']/10000;  // 平台扣除金额
                    }else{
                        $platform_money= $settlement_price*$rebate['rebate']*($rebate['platform']+$rebate['tui_user'])/10000;  // 平台扣除金额
                    }
                    D('Users')->addMoney2($detail['user_id'], $platform_money, '外卖订单返利,ID:'.$order_id); 



                }
                // D('Users')->gouwu($detail['user_id'],$detail['total_price'],'外卖积分奖励');

            }
            //更新卖出数
            D('Eleorderproduct')->updateByOrderId($order_id);
            D('Ele')->updateCount($detail['shop_id'], 'sold_num'); //这里是订单数
            D('Ele')->updateMonth($detail['shop_id']);
        }
        return true;
    }

    public function confirm($order_id,$auto=1,$shop_id,$num=1){ 
        if(is_array($order_id)){
            foreach($order_id as $id){
                if (!$detail = $this->find($id)) {
                    continue;
                }
                if ($detail['shop_id'] != $shop_id) {
                    continue;
                }
                if ($detail['status'] != 1) {
                    continue;
                }
                $this->save(array(
                    'order_id' => $id,
                    'status' => 2,
                    'audit_time' => NOW_TIME
                ));
                if($auto == 1){
                    $this->auto_print($id,$num);
                }
            }
            return true;
        }
    }

    
     public function auto_print($order_id,$num=1){
         if($this->order_print($order_id, $num)){
             return true;
           }else{
               return false;
           }   
    }



    public function order_print($order_id, $num=1){
        $order = D('EleOrder')->find($order_id);
        $shop = D('Shop')->find($order['shop_id']);
        $orderp = D('EleOrderProduct')->where('order_id ='.$order['order_id'])->select();
        $ids = array();
        foreach($orderp as $k => $v){
            $ids[$v['product_id']] = $v['product_id'];
        }
        $ep = D('EleProduct')->where(array('product_id'=>array('in',$ids)))->select();
        $ep2 = array();
        foreach($ep as $k => $v){
            $ep2[$v['product_id']] = $v;
        }
        $u = D('Users') -> find($order['user_id']);
        $pl = D('PaymentLogs')->where(array('type'=>'ele','order_id'=>$order['order_id']))->find();
        $addr = D('Useraddr')->find($order['addr_id']);
        $paymentcode = D('Payment')->getPayments();
        $setting = D('Shopsetting')->where(array('shop_id'=>$order['shop_id']))->find();
        //dump($setting);die;
        
        $content = '';
        $content .= "<MN>".$num."</MN>\n";
        $content .= "<FW><FH><FB>".$shop['shop_name']."</FB></FH></FW>\n";
        $content .= "<FB>店名:</FB>".$shop['shop_name']."\n";
        $content .= "<FB>地址:</FB>".$shop['addr']."\n";
        $content .= "<FB>电话:</FB>".$shop['tel']."\n";
        $content .= "<FB>订单编号:</FB>".$order_id."\n";
        $content .= "<FB>下单时间:</FB>".date('Y-m-d H:i:s',$order['create_time'])."\n";
        $content .= "＝*＝*＝*＝*＝*＝*＝*＝*＝*＝*＝\n";
        $content .= "<FB>品名\t单价\t数量\t小计</FB>\n";
        foreach($orderp as $k=>$val){
            $content .= $ep2[$val['product_id']]['product_name']."\t￥".round($ep2[$val['product_id']]['price']/100,2)."\t".$val['num']."\t￥".round($val['total_price']/100,2)."\n";
        }
        $content .= "＝*＝*＝*＝*＝*＝*＝*＝*＝*＝*＝\n";
        $content .= "<FB>\n";
        if($order['new_money']>0){
            $content .= "首单立减:<FW><FH>￥".round($order['new_money']/100,2)."</FH></FW>\n";
        }
        $content .= "运费:<FW><FH>￥".round($order['logistics']/100,2)."</FH></FW>\n";
        $content .= "订单总额:<FW><FH>￥".round($order['total_price']/100,2)."</FH></FW>\n";
        $content .= "</FB>\n";
        $content .= "＝*＝*＝*＝*＝*＝*＝*＝*＝*＝*＝\n";
        
        $content .= "<FB>支付方式:<FH><FW>".$paymentcode[$pl['code']]['name']."</FW></FH></FB>\n";
        $content .= "<FB>送餐地址:</FB>".$addr['name']."、".$addr['mobile']."、".$addr['addr']."\n";
        
        $apiKey = $setting['apiKey'];         
        $mKey = $setting['mKey'];
        $partner = (int)$setting['partner'];        
        $machine_code = $setting['machine_code'];  
        return D('Yunprint')->print_order($content,$apiKey,$mKey,$partner,$machine_code);
        
    }
    
}
