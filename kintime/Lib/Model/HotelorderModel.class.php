<?php



class HotelorderModel extends CommonModel{
    protected $pk   = 'order_id';
    protected $tableName =  'hotel_order';
    
    public function cancel($order_id){
        if(!$order_id = (int)$order_id){
            return false;
        }elseif(!$detail = $this->find($order_id)){
            return false;
        }else{
            if($detail['online_pay'] == 1&&$detail['order_status'] == 1){
                $detail['is_fan'] = 1;
            }else{
                $detail['is_fan'] = 0;
            }
            $room = D('Hotelroom')->find($detail['room_id']);
            if(!$room['is_cancel']){
                return false;
            }
            if(false !== $this->save(array('order_id'=>$order_id,'order_status'=>-1))){
                if($detail['is_fan'] == 1){
                    D('Users')->addMoney($detail['user_id'],(int)$detail['amount'],'酒店订单取消,ID:'.$order_id.'，返还余额');
                }
                D('Payment')->logsPaid($logs_id);
                D('HotelNum')->where('order_id = '.$order_id)->save(array('state'=>0));
                return true;
            }else{
                return false;
            }
            
        }  
    }
     
    public function plqx($hotel_id){
        if($hotel_id = (int)$hotel_id){
            $ntime = date('Y-m-d',NOW_TIME);
            $map['stime'] = array('LT',$ntime);
            $map['hotel_id'] = $hotel_id;
            $order = $this->where($map)->select();
            foreach ($order as $k=>$val){
                $this->cancel($val['order_id']);
            }
            return true;
        }else{
            return false;
        }
    }
    
    public function complete($order_id){
        if(!$order_id = (int)$order_id){
            return false;
        }elseif(!$detail = $this->find($order_id)){
            return false;
        }else{
            $shop_detail = D('Hotel')->find($detail['hotel_id']);
            $mer = D('Merchants')->find($shop_detail['mer_id']);
            if($detail['online_pay'] == 1&&$detail['order_status'] == 1){
                $detail['is_fan'] = 1;
            }
            
            if(false !== $this->save(array('order_id'=>$order_id,'order_status'=>2))){
                if($detail['is_fan'] == 1){
                    D('Users')->addMoney($mer['user_id'], $detail['amount'], '酒店订单完成,ID:'.$order_id.'，结算金额');
                    
                    $rebate = D('rebate')->find($shop_detail['rate']);
                    $user_money= $detail['amount']*$rebate['rebate']*$rebate['user']/10000; //用户返利金额
                    D('Users')->addMoney($detail['user_id'], $user_money, '酒店订单完成,ID:'.$order_id.'，用户消费返利'); 

                    $invite = D('users')->find($detail['user_id'])['invite1'];
                    if($invite != 0){
                        $tui_user_money= $detail['amount']*$rebate['rebate']*$rebate['tui_user']/10000;  // 推荐人返利金额
                        D('Users')->addMoney($invite, $tui_user_money, '酒店订单完成,ID:'.$order_id.'，推荐人消费返利'); 

                        $platform_money= $detail['amount']*$rebate['rebate']*$rebate['platform']/10000;  // 平台扣除金额
                    }else{
                        $platform_money= $detail['amount']*$rebate['rebate']*($rebate['platform']+$rebate['tui_user'])/10000;  // 平台扣除金额
                    }
                    D('Users')->addMoney2($detail['user_id'], $platform_money, '酒店订单返利,ID:'.$order_id); 
                }
                D('Shopmoney')->add(array(
                    'mer_id' => $shop_detail['mer_id'],
                    'money' => $detail['amount'],
                    'type'=>'hotel',
                    'order_id' =>$order_id,
                    'create_time' => NOW_TIME,
                    'create_ip' => $ip,
                    'intro' => '酒店预订，来自订单ID：' . $order_id,
                    'eng_intro' => '英酒店预订，来自订单ID：' . $order_id,
                    'lao_intro' => '老酒店预订，来自订单ID：' . $order_id
                ));
                $data_all = array(
                    'order_id'=>$order_id,
                    'shop_id'=>$shop_detail['shop_id'],
                    'user_id'=>$detail['user_id'],
                    'price'=>$detail['amount'],
                    'type'=>'hotel',
                    'time'=>time()
                );
                D('AllOrder')->add($data_all);
                return true;
            }else{
                return false;
            }
            
        }  
    }
    
}