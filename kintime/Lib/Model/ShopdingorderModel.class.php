<?php

class ShopdingorderModel extends CommonModel{
    protected $pk   = 'order_id';
    protected $tableName =  'shop_ding_order';
	
    public function getStatus(){
        return array(
            -1 => '已取消',
            0  => '未付款',
            1  => '已付款',
            2  => '已完成',
        );
    }

    

    public function cancel($order_id){
        if(!$order_id = (int)$order_id){
            return false;
        }elseif(!$detail = $this->find($order_id)){
            return false;
        }else{
            if($detail['order_status'] ==1||$detail['order_status'] ==0){
                if(false !== $this->save(array('order_id'=>$order_id,'order_status'=>-1,'update_time'=>NOW_TIME))){
                    if($detail['order_status'] == 1){
                        D('Users')->addMoney($detail['user_id'],(int)$detail['amount'],'订座订单取消,ID:'.$order_id.'，返还定金');
                    }
                    return true;
                }else{
                    return false;
                }
            }else{
                return false;
            }
            
        }                                 
    }
     
    public function plqx($shop_id){
        if($shop_id = (int)$shop_id){
            $ntime = date('H:i',NOW_TIME);
            $order = $this->where("`shop_id` = ".$shop_id." AND `ding_date` <".TODAY." OR (`ding_date` =".TODAY." AND `ding_time` <".$ntime.") ")->select();
            // dump($this->getLastSql());die;
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
            $shop = D('Shop')->find($detail['shop_id']);
            if($detail['order_status'] == 1){
                if(false !== $this->save(array('order_id'=>$order_id,'order_status'=>2,'update_time'=>NOW_TIME))){
                	$mer = D('Merchants')->find($shop['mer_id']);
                    D('Users')->addMoney($mer['user_id'], $detail['amount'], '电子菜单订单完成,ID:'.$order_id.'，结算定金'); //结算金额如需设置改这里
                    
                    $rebate = D('rebate')->find($shop['rate']);
                    $user_money= $detail['amount']*$rebate['rebate']*$rebate['user']/10000; //用户返利金额
                    D('Users')->addMoney($detail['user_id'], $user_money, '电子菜单订单完成,ID:'.$order_id.'，用户消费返利'); 

                    $invite = D('users')->find($detail['user_id'])['invite1'];
                    if($invite != 0){
                    	$tui_user_money= $detail['amount']*$rebate['rebate']*$rebate['tui_user']/10000;  // 推荐人返利金额
                    	D('Users')->addMoney($invite, $tui_user_money, '电子菜单订单完成,ID:'.$order_id.'，推荐人消费返利'); 

                    	$platform_money= $detail['amount']*$rebate['rebate']*$rebate['platform']/10000;  // 平台扣除金额
                    }else{
                    	$platform_money= $detail['amount']*$rebate['rebate']*($rebate['platform']+$rebate['tui_user'])/10000;  // 平台扣除金额
                    }
                    D('Users')->addMoney2($detail['user_id'], $platform_money, '电子菜单订单返利,ID:'.$order_id); 

                    $data_all = array(
	                    'order_id'=>$order_id,
	                    'mer_id'=>$shop['mer_id'],
	                    'user_id'=>$mer['user_id'],
	                    'price'=>$detail['amount'],
	                    'type'=>'ding',
	                    'time'=>time()
	                );
	                D('AllOrder')->add($data_all);

                    return true;
                }else{
                    return false;
                }
            }else{
                return false;
            }  
        }  
    }

public function get_ding($shop_id,$list)
	{
		$dings = $arr = $rooms = $tmp =  array();
		if($list){
			foreach($list as $k => $v){
				$dings[] = $v['ding_id'];
			}
		}
		$Cfg = D('Shopdingsetting')->getCfg();
		$type = D('Shopdingroom')->getType();
		$arr = D('Shopdingyuyue')->itemsByIds($dings);
		$room = D('Shopdingroom')->where('shop_id = '.$shop_id)->select();
		foreach($room as $k => $v){
			$rooms[$v['room_id']] = $v;
		}
		foreach($arr as $k => $v){
			if($v['room_id'] == 0){
				$arr[$k]['room_id'] = '大厅';
			}else{
				$arr[$k]['room_id'] = $rooms[$v['room_id']]['name'];
			}
			$arr[$k]['last_t'] = $Cfg[$v['last_t']];
			$arr[$k]['number'] = $type[$v['number']];
		}
		return $arr;
	}

	public function get_detail($shop_id,$order,$yuyue)
	{
		$Cfg = D('Shopdingsetting')->getCfg();
		$type = D('Shopdingroom')->getType();
		$room = D('Shopdingroom')->where('shop_id = '.$shop_id)->select();
		foreach($room as $k => $v){
			$rooms[$v['room_id']] = $v;
		}
		if($yuyue['room_id'] == 0){
			$yuyue['room_id'] = '大厅';
		}else{
			$yuyue['room_id'] = $rooms[$yuyue['room_id']]['name'];
		}
		$yuyue['last_t'] = $Cfg[$yuyue['last_t']];
		$yuyue['number'] = $type[$yuyue['number']];
		$arr = array_merge($yuyue,$order);
		
		$a = substr($arr['menu'],0,-1);
		$arr1 = explode('|',$a);
		foreach($arr1 as $k => $v){
			$arr2[] = explode(':',$v);
		}
		$arr['menu'] = $arr2;
		return $arr;
	}

	public function get_d($yuyue)
	{
		$Cfg = D('Shopdingsetting')->getCfg();
		$type = D('Shopdingroom')->getType();
		$tem =array();
		foreach($yuyue as $k => $v){
			$yuyue[$k]['last_t'] = $Cfg[$v['last_t']];
			$yuyue[$k]['number'] = $type[$v['number']];
		}
		
		return $yuyue;
	}	
    
}