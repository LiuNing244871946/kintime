<?php

class WeixinAction extends CommonAction {

    public function index() {
        $code_id = $this->_get('code_id');
        $t = $this->_get('t');
        $sign = $this->_get('sign');
        if (empty($sign) || empty($t) || empty($code_id)) {
            $this->error('参数错误！');
        }
        if ($sign != md5($code_id . C('AUTH_KEY') . $t)) {
            $this->error('签名不正确');
        }
        if (!$data = D('Tuancode')->find($code_id)) {
            $this->error('抢购券不存在！');
        }
        if(!$this->uid){
            $this->error('您还没有绑定用户！');
        }
        if(!$shops = D('Shop')->where(array('user_id'=>$this->uid))->select()){
            $this->error('您不是商家用户！');
        }
        if($data['is_used'] == 1){
            $this->error('该抢购已使用！');
        }
        foreach($shops as $k=>$v){
            $shop = $v;
        }
        if($shop['shop_id'] != $data['shop_id']){
            $this->error('该抢购券不属于您！');
        }
        if ((int) $data['is_used'] == 0 && (int) $data['status'] == 0) {
            $ip = get_client_ip();
            $obj = D('Tuancode');
            $shopmoney = D('Shopmoney');
            if ($obj->save(array('code_id' => $data['code_id'], 'is_used' => 1))) { //这次更新保证了更新的结果集      
            $order_row = D('Tuanorder')->find($data['order_id']);
            $mer_id = D('Shop')->find($order_row['shop_id'])['mer_id'];
            $all_data = array(
                "mer_id"=>$mer_id,
                "order_id"=>$order_row['order_id'],
                "user_id"=>$order_row['user_id'],
                "type"=>'tuan',
                "price"=>$order_row['need_pay'],
                "time"=>time()
            );
            D('AllOrder')->add($all_data);
                /*if ((int) $data['price'] == 0) {
                    D('Tuanorder')->save(array(//将原有的订单更新成已经完成
                        'order_id' => $data['order_id'],
                        'status' => 1,
                    ));
                    $obj->save(array('code_id' => array('used_time' => NOW_TIME, 'used_ip' => $ip))); //拆分2次更新是保障并发情况下安全问题

                    $this->assign('waitSecond', 60);
                    $this->success("该抢购券为到店付抢购券，该用户需要额外付消费款给您！", U('index/index'));
                } else {*/
                    //增加MONEY 的过程 稍后补充
                    
                    
                    $amount = $data['real_money']*$order_row['num'];

                    $shop2 = D('Shop')->find($data['shop_id']);
                    $rebate = D('rebate')->find($shop2['rate']);
                    $user_money= $amount*$rebate['rebate']*$rebate['user']/10000; //用户返利金额
                    D('Users')->addMoney($data['user_id'], $user_money, '团购订单完成,ID:'.$order_id.'，用户消费返利'); 

                    $invite = D('users')->find($data['user_id'])['invite1'];
                    if($invite != 0){
                        $tui_user_money= $amount*$rebate['rebate']*$rebate['tui_user']/10000;  // 推荐人返利金额
                        D('Users')->addMoney($invite, $tui_user_money, '团购订单完成,ID:'.$order_id.'，推荐人消费返利'); 

                        $platform_money= $amount*$rebate['rebate']*$rebate['platform']/10000;  // 平台扣除金额
                    }else{
                        $platform_money= $amount*$rebate['rebate']*($rebate['platform']+$rebate['tui_user'])/10000;  // 平台扣除金额
                    }
                    D('Users')->addMoney2($data['user_id'], $platform_money, '团购订单返利,ID:'.$order_id); 






                   // $data['settlement_price'] =  D('Quanming')->quanming($data['user_id'],$data['settlement_price'],'tuan'); //扣去全民营销
                    $shopmoney->add(array(
                        "mer_id"=>$mer_id,
                        'money' => $amount,
                        'create_ip' => $ip,
                        'create_time' => NOW_TIME,
                        'order_id' => $data['order_id'],
                        'intro' => '抢购卷消费'.$data['order_id'],
                    ));
                    $mer = D('Merchants')->where('mer_id='.$mer_id)->find();
                    D('Users')->addMoney($mer['user_id'], $amount , '抢购消费'.$data['order_id']);
                    $obj->save(array('code_id' => array('used_time' => NOW_TIME, 'used_ip' => $ip))); //拆分2次更新是保障并发情况下安全问题
                    D('Users')->gouwu($data['user_id'],$data['real_money'],'抢购券消费');
                    
                    $this->assign('waitSecond', 60);
                    $this->success("恭喜您成功使用了该消费券！！！", U('tuancode/weixin',array('code_id'=>$code_id)));
                // }
            }
        }

        $this->error('该抢购券无效');
    }

}