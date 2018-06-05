<?php



class PaymentModel extends CommonModel {

    protected $pk = 'payment_id';
    protected $tableName = 'payment';
    protected $token = 'payment';
    protected $types = array(
        'goods' => '商城购物',
        'gold' => '金块充值',
        'tuan' => '生活购物',
        'money' => '余额充值',
        'ele' => '在线订餐',
        'ding'  => '在线订座',
        'fzmoney'=> '冻结金充值',
        'breaks'=>'优惠买单',
        'hotel' =>'酒店预订',
        'farm'=>'农家乐预订',
    );
    protected $lao_types = array(
        'goods' => 'lao商城购物',
        'gold' => 'lao金块充值',
        'tuan' => 'lao生活购物',
        'money' => 'lao余额充值',
        'ele' => 'lao在线订餐',
        'ding'  => 'lao在线订座',
        'fzmoney'=> 'lao冻结金充值',
        'breaks'=>'lao优惠买单',
        'hotel' =>'lao酒店预订',
        'farm'=>'lao农家乐预订',
    );
    protected $eng_types = array(
        'goods' => 'eng商城购物',
        'gold' => 'eng金块充值',
        'tuan' => 'eng生活购物',
        'money' => 'eng余额充值',
        'ele' => 'eng在线订餐',
        'ding'  => 'eng在线订座',
        'fzmoney'=> 'eng冻结金充值',
        'breaks'=>'eng优惠买单',
        'hotel' =>'eng酒店预订',
        'farm'=>'eng农家乐预订',
    );
    protected $type = null;
    protected $log_id = null;

    public function getType() {
        return $this->type;
    }

    public function getLogId() {
        return $this->log_id;
    }

    public function getTypes() {
        return $this->types;
    }

    public function getLaoTypes() {
        return $this->lao_types;
    }
    public function getEngTypes() {
        return $this->eng_types;
    }
    public function getPayments($mobile = false) {
        $datas = $this->fetchAll();
        $return = array();
        foreach ($datas as $val) {
            if ($val['is_open']) {
                if ($mobile == false) {
                    if (!$val['is_mobile_only'])
                        $return[$val['code']] = $val;
                }else {
                    if ($val['code'] != 'tenpay') {
                        $return[$val['code']] = $val;
                    }
                }
            }
        }
        /*if (!is_weixin()) {
            unset($return['weixin']);
        }

        if (is_weixin()) {
            unset($return['alipay']);
        }*/

        return $return;
    }

    public function _format($data) {
        $data['setting'] = unserialize($data['setting']);
        return $data;
    }

    public function respond($code) {
        $payment = $this->checkPayment($code);
        if (empty($payment))
            return false;
        if (defined('IN_MOBILE')) {
            require_cache(APP_PATH . 'Lib/Payment/' . $code . '.mobile.class.php');
        } else {
            require_cache(APP_PATH . 'Lib/Payment/' . $code . '.class.php');
        }
        $obj = new $code();
        return $obj->respond();
    }

    public function getCode($logs) {
        $CONFIG = D('Setting')->fetchAll();
        $datas = array(
            'subject' => $CONFIG['site']['sitename'] . $this->types[$logs['type']],
            'logs_id' => $logs['log_id'],
            'logs_amount' => $logs['need_pay'],
        );
        $payment = $this->getPayment($logs['code']);
        if(defined('IN_APP') && IN_APP){
            require_cache(APP_PATH . 'Lib/Payment/' . $logs['code'] . '.app.class.php');
        } else if (defined('IN_MOBILE')) {
            require_cache(APP_PATH . 'Lib/Payment/' . $logs['code'] . '.mobile.class.php');
        } else  {
            require_cache(APP_PATH . 'Lib/Payment/' . $logs['code'] . '.class.php');
        }
        $obj = new $logs['code']();
        return $obj->getCode($datas, $payment);
    }

    public function checkMoney($logs_id, $money) {
        $money = (int) ($money );
        $logs = D('Paymentlogs')->find($logs_id);
        if ($logs['need_pay'] == $money)
            return true;
        return false;
    }

    public function logsPaid($logs_id) {
        $this->log_id = $logs_id; //用于外层回调
        $logs = D('Paymentlogs')->find($logs_id);
        if (!empty($logs) && !$logs['is_paid']) {
            $data = array(
                'log_id' => $logs_id,
                'is_paid' => 1,
            );
            if (D('Paymentlogs')->save($data)) { //总之 先更新 然后再处理逻辑  这里保障并发是安全的
                $ip = get_client_ip();
                D('Paymentlogs')->save(array(
                    'log_id' => $logs_id,
                    'pay_time' => NOW_TIME,
                    'pay_ip' => $ip
                ));
                $this->type = $logs['type'];
                if($logs['type'] == 'fzmoney'){
                    $CONFIG = D('Setting')->fetchAll();
                    D('Usersex')->save(array('user_id'=>$logs['user_id'],'frozen_money'=>$logs['need_pay'],'frozen_date'=>NOW_TIME + $CONFIG['quanming']['money_day']*86400));
                    D('Quanming')->fzmoney($logs['user_id']);
                    return true;
                }elseif($logs['type'] == 'hotel'){   //酒店预订
                    $order = D('Hotelorder')->find($logs['order_id']);
                    $room = D('Hotelroom')->find($order['room_id']);
                    $hotel = D('Hotel')->find($order['hotel_id']);
                    D('Usermoneylogs')->add(array(
                        'user_id' => $logs['user_id'],
                        'money' => $logs['need_pay'],
                        'create_time' => NOW_TIME,
                        'create_ip' => $ip,
                        'intro' => '酒店预订，支付记录ID：' . $logs['log_id'],
                    ));
                    
                    D('Hotelorder')->save(array('order_id' => $logs['order_id'], 'order_status' => 1)); //设置已付款

                    $aa = $order['mobile'];
                    $len = strlen($aa);
                    if($len == 14){  //老挝
                        $mobile = substr($aa,3);
                        $areacode = substr($aa,0,3);
                    }else if($len == 13){  //中国
                        $mobile = substr($aa,2);
                        $areacode = substr($aa,0,2);
                    }
                    $data = array($hotel['hotel_name'],$room['title'],$order['stime'],$order['ltime']);
                    D('Sms')->sendSms(3, $mobile, $areacode , $data);
                    
                    
                    return true;
                    
                } elseif($logs['type'] == 'farm'){   //农家乐预订
                    $order = D('FarmOrder')->find($logs['order_id']);
                    $f = D('FarmPackage')->find($order['pid']);
                    $farm = D('Farm')->find($order['farm_id']);
                    $shop = D('Shop')->find($farm['shop_id']);
                    D('Users')->updateCount($shop['user_id'], 'money', $logs['need_pay']);
                    D('Usermoneylogs')->add(array(
                        'user_id' => $shop['user_id'],
                        'money' => $logs['need_pay'],
                        'create_time' => NOW_TIME,
                        'create_ip' => $ip,
                        'intro' => '农家乐预订，支付记录ID：' . $logs['log_id'],
                    ));
                    D('Shopmoney')->add(array(
                        'shop_id' => $hotel['shop_id'],
                        'money' => $logs['need_pay'],
                        'type'=>'hotel',
                        'order_id' =>$logs['order_id'],
                        'create_time' => NOW_TIME,
                        'create_ip' => $ip,
                        'intro' => '农家乐预订，支付记录ID：' . $logs['log_id'],
                    ));
                    
                    D('FarmOrder')->save(array('order_id' => $logs['order_id'], 'order_status' => 1)); //设置已付款
                    D('Sms')->sendSms('sms_farm', $order['mobile'], array(
                        'farm_name' => $farm['farm_name'],
                        'addr'=>$farm['addr'],
                        'tel' => $farm['tel'],
                        'gotime' => $order['gotime'],
                        'title' => $f['title'],
                    ));
                    D('Sms')->sendSms('sms_shop_farm', $shop['mobile'], array());
                    
                    
                    return true;
                    
                } elseif ($logs['type'] == 'money') {
                    D('Users')->updateCount($logs['user_id'], 'money', $logs['need_pay']);
                    D('Usermoneylogs')->add(array(
                        'user_id' => $logs['user_id'],
                        'money' => $logs['need_pay'],
                        'create_time' => NOW_TIME,
                        'create_ip' => $ip,
                        'intro' => '余额充值，支付记录ID：' . $logs['log_id'],
                    ));

                    return true;
                } elseif ($logs['type'] == 'tuan') {//抢购都是发送抢购券！
                    $member = D('Users')->find($logs['user_id']);
                    $codes = array(); //抢购券
                    $obj = D('Tuancode');
                    $order = D('Tuanorder')->find($logs['order_id']);
                    $tuan = D('Tuan')->find($order['tuan_id']);
                    $local = $obj->getCode();
                    $insert = array(
                        'user_id' => $logs['user_id'],
                        'shop_id' => $tuan['shop_id'],
                        'order_id' => $order['order_id'],
                        'tuan_id' => $order['tuan_id'],
                        'code' => $local,
                        'price' => $tuan['price'],
                        'real_money' => (int) ($order['need_pay'] / $order['num']), //退款的时候用
                        'real_integral' => (int) ($order['use_integral'] / $order['num']), //退款的时候用
                        'fail_date' => $tuan['fail_date'],
                        'settlement_price' => $tuan['settlement_price'],
                        'create_time' => NOW_TIME,
                        'create_ip' => $ip,
                    );
                    $codes[] = $local;
                    $obj->add($insert);
                    D('Usermoneylogs')->add(array(
                        'user_id' => $logs['user_id'],
                        'money' => $logs['need_pay'],
                        'create_time' => NOW_TIME,
                        'create_ip' => $ip,
                        'intro' => '美食优惠，支付记录ID：' . $logs['log_id'],
                    ));
                    D('Tuanorder')->save(array('order_id' => $order['order_id'], 'status' => 1)); //设置已付款
                    D('Tuan')->updateCount($tuan['tuan_id'], 'sold_num' ,$order['num']); //更新卖出产品
                    D('Tuan')->updateCount($tuan['tuan_id'], 'num', -$order['num']);

                    // D('Sms')->sendSms('sms_tuan', $member['mobile'], array(
                    //     'code' => join(',', $codes),
                    //     'nickname' => $member['nickname'],
                    //     'tuan' => $tuan['title'],
                    // ));
                    D('Users')->prestige($member['user_id'], 'tuan');
                    $tg = D('Users')->checkInvite($order['user_id'], $tuan['price']);
                    if ($tg !== false) {

                        D('Users')->addIntegral($tg['uid'], $tg['integral'], "分享获得积分！");
                    }
                    D('Tongji')->log(1, $logs['need_pay']); //统计
                    //发送短信
                    // D('Sms')->tuanTZshop($tuan['shop_id']);


                    return true;
                } elseif ($logs['type'] == 'ele') {//餐饮订餐
                    D('Usermoneylogs')->add(array(
                        'user_id' => $logs['user_id'],
                        'money' => $logs['need_pay'],
                        'create_time' => NOW_TIME,
                        'create_ip' => $ip,
                        'intro' => '外卖订餐，支付记录ID：' . $logs['log_id'],
                    ));
                    D('Eleorder')->save(array(
                        'order_id' => $logs['order_id'],
                        'status' => 1,
                        'is_pay' => 1
                    ));
                    $this->elePeisong($logs['order_id']);
					// $member = D('Users')->find($logs['user_id']);
     //                $order = D('Eleorder')->find($logs['order_id']);
     //                $shops = D('Shop')->find($order['shop_id']);
     //                D('Sms')->sendSms('sms_ele', $member['mobile'], array(
     //                    'nickname' => $member['nickname'],
     //                    'shopname' => $shops['shop_name'],
     //                ));
                    D('Tongji')->log(3, $logs['need_pay']); //统计
                    //通知商家
                    // D('Sms')->eleTZshop($logs['order_id']);
                } elseif ($logs['type'] == 'ding') { //订座定金
                    D('Usermoneylogs')->add(array(
                        'user_id' => $logs['user_id'],
                        'money' => $logs['need_pay'],
                        'create_time' => NOW_TIME,
                        'create_ip' => $ip,
                        'intro' => '预定订座，支付记录ID：' . $logs['log_id'],
                    ));
                    D('Shopdingorder')->save(array(
                        'order_id' => $logs['order_id'],
                        'order_status' => 1
                    ));
                    
                    // $member = D('Users')->find($logs['user_id']);
                    // $order = D('Shopdingorder')->find($logs['order_id']);
                    // $shops = D('Shopding')->find($order['shop_id']);
                    // D('Sms')->sendSms('sms_ding', $member['mobile'], array(
                    //     'nickname' => $member['nickname'],
                    //     'shopname' => $shops['shop_name'],
                    // ));
                    D('Tongji')->log(3, $logs['need_pay']); //统计
                    //通知商家
                    // D('Sms')->dingTZshop($logs['order_id']);
                } else { // 商城购物
                    if (empty($logs['order_id']) && !empty($logs['order_ids'])) {//合并付款
                        $order_ids = explode(',', $logs['order_ids']);
                        D('Order')->save(array(
                            'status' => 1
                                ), array('where' => array('order_id' => array('IN', $order_ids))));
                        //通知商家
                        D('Sms')->mallTZshop($order_ids);
                        $this->mallSold($order_ids);
                        $this->mallPeisong(array($order_ids),1);
                    } else {
                        D('Order')->save(array(
                            'order_id' => $logs['order_id'],
                            'status' => 1
                        ));
                        $this->mallPeisong(array($logs['order_id']),1);
                        $this->mallSold($logs['order_id']);

                        //通知商家
                        D('Sms')->mallTZshop($logs['order_id']);
                    }
                    D('Tongji')->log(2, $logs['need_pay']); //统计
                }
            }
        }
        return true;
    }

    //更新商城销售接口
    public function mallSold($order_ids) {
        if (is_array($order_ids)) {
            $order_ids = join(',', $order_ids);
            $ordergoods = D('Ordergoods')->where("order_id IN ({$order_ids})")->select();
            foreach ($ordergoods as $k => $v) {
                D('Goods')->updateCount($v['goods_id'], 'sold_num', $v['num']);
            }
        } else {
            $order_ids = (int) $order_ids;
            $ordergoods = D('Ordergoods')->where('order_id =' . $order_ids)->select();
            foreach ($ordergoods as $k => $v) {
                D('Goods')->updateCount($v['goods_id'], 'sold_num', $v['num']);
            }
        }
        return TRUE;
    }

    //商城购物配送接口
    public function mallPeisong($order_ids,$wait = 0) {
        if($wait == 0){
            $status = 1;
        }else{
            $status = 0;
        }
        if(is_array($order_ids[0])){
            $order_ids=$order_ids[0];
        }
        foreach ($order_ids as $order_id) {
            $order = D('Order')->where('order_id =' . $order_id)->find();
            $weidian = D('WeidianDetails')->find($order['id']);
            if (!empty($weidian['tel'])) {
                $mobile = $weidian['tel'];
            } else {
                $mobile = $weidian['mobile'];
            }
            $member = D('Users')->find($order['user_id']);
            $ua = D('UserAddr');
            $uaddr = $ua->where(array('addr_id'=>$order['addr_id']))->find();

            if ($order['mall_type'] == 0) {
                $dv = D('DeliveryOrder');
                //在线支付成功以后，进入配送员判断
                $dv_data = array(
                    'type' => 0,
                    'type_order_id' => $order['order_id'],
                    'delivery_id' => 0,
                    'mer_id' => $weidian['mer_id'],
                    'city_id' => $weidian['city_id'],
                    'user_id' => $order['user_id'],
                    'shop_name' => $weidian['weidian_name'],
                    'lat' => $weidian['lat'],
                    'lng' => $weidian['lng'],
                    'shop_addr' => $weidian['addr'],
                    'shop_mobile' => $mobile,
                    'user_name' => $member['nickname'],
                    'user_addr' => $uaddr['addr'],
                    'user_mobile' => $member['mobile'],
                    'create_time' => NOW_TIME,
                    'update_time' => 0,
                    'status' => $status,
                    'user_lat' => $uaddr['lat'],
                    'user_lng' => $uaddr['lng']
                );
                $dv->add($dv_data);
            }elseif($order['mall_type'] == 1){
                $mo = D('MailOrder');
                //在线支付成功以后，进入配送员判断
                $mo_data = array(
                    'mail_order_id' => $order['order_id'],
                    'delivery_id' => 0,
                    'mer_id' => $weidian['mer_id'],
                    'city_id' => $weidian['city_id'],
                    'user_id' => $order['user_id'],
                    'shop_name' => $weidian['weidian_name'],
                    'lat' => $weidian['lat'],
                    'lng' => $weidian['lng'],
                    'shop_addr' => $weidian['addr'],
                    'shop_mobile' => $mobile,
                    'user_name' => $member['nickname'],
                    'user_addr' => $uaddr['addr'],
                    'user_mobile' => $member['mobile'],
                    'create_time' => NOW_TIME,
                    'update_time' => 0,
                    'status' => $status,
                    'user_lat' => $uaddr['lat'],
                    'user_lng' => $uaddr['lng']
                );
                $mo->add($mo_data);
                $dv = D('DeliveryOrder');
                //在线支付成功以后，进入配送员判断
                $dv_data = array(
                    'type' => 2,
                    'type_order_id' => $order['order_id'],
                    'delivery_id' => 0,
                    'mer_id' => $weidian['mer_id'],
                    'city_id' => $weidian['city_id'],
                    'user_id' => $order['user_id'],
                    'shop_name' => $weidian['weidian_name'],
                    'lat' => $weidian['lat'],
                    'lng' => $weidian['lng'],
                    'shop_addr' => $weidian['addr'],
                    'shop_mobile' => $mobile,
                    'user_name' => $member['nickname'],
                    'user_addr' => $uaddr['addr'],
                    'user_mobile' => $member['mobile'],
                    'create_time' => NOW_TIME,
                    'update_time' => 0,
                    'status' => $status,
                    'user_lat' => $uaddr['lat'],
                    'user_lng' => $uaddr['lng']
                );
                $dv->add($dv_data);
            }
        }
        return true;
    }

    //外卖配送接口
    public function elePeisong($order_id) {
        $order = D('Eleorder')->where('order_id =' . $order_id)->find();
        $shops = D('Shop')->find($order['shop_id']);
        if (!empty($shops['tel'])) {
            $mobile = $shops['tel'];
        } else {
            $mobile = $shops['mobile'];
        }

            $member = D('Users')->find($order['user_id']);
            $ua = D('UserAddr');
            $dv = D('DeliveryOrder');
            $uaddr = $ua->where(array('addr_id'=>$order['addr_id']))->find();
            $elesetting = D('Elesetting')->find($order['elesetting_id']);
            //在线支付成功以后，进入配送员判断

            $dv_data = array(
                'type' => 1,
                'type_order_id' => $order_id,
                'delivery_id' => 0,
                'mer_id' => $shops['mer_id'],
                'city_id' => $shops['city_id'],
                'user_id' => $order['user_id'],
                'shop_name' => $shops['shop_name'],
                'yongjin' => $elesetting['rider'],
                'lat' => $shops['lat'],
                'lng' => $shops['lng'],
                'shop_addr' => $shops['addr'],
                'shop_mobile' => $mobile,
                'user_name' => $uaddr['name'],
                'user_addr' => $uaddr['addr'],
                'user_mobile' => $uaddr['mobile'],
                'create_time' => NOW_TIME,
                'update_time' => 0,
                'status' => 0,
                'user_lat' => $uaddr['lat'],
                'user_lng' => $uaddr['lng']
            );
            $dv->add($dv_data);
        return true;
    }

    public function checkPayment($code) {
        $datas = $this->fetchAll();
        foreach ($datas as $val) {
            if ($val['code'] == $code)
                return $val;
        }
        return array();
    }

    public function getPayment($code) {
        $datas = $this->fetchAll();
        foreach ($datas as $val) {
            if ($val['code'] == $code)
                return $val['setting'];
        }
        return array();
    }

}
