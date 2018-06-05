<?php



class GoodscrowdModel extends CommonModel{
    protected $pk   = 'goods_id';
    protected $tableName =  'goods_crowd';
    
    
    public function _format($data){
        $data['all_price'] = round($data['all_price']/100,2); 
        $data['have_price'] = round($data['have_price']/100,2); 
        return $data;
    }

	public function merge($arr1,$arr2)
	{
		$detail = array();
		foreach($arr1 as $k => $v){
			foreach($arr2 as $kk => $vv){
				if($v['goods_id'] == $vv['goods_id']){
					$detail[$v['goods_id']] = array_merge($v,$vv);
				}
			}
		}
		return $detail;
	}

	public function buy($goods_id, $user_id,$data,$type){
        $detail = $this->find($goods_id);
		$Goodstype = D('Goodstype');
		$Goodslist = D('Goodslist');
		$type_id = $data['type_id'];
		$type = $Goodstype->where(array('type_id'=>$type_id))->find();
        if ($type['have_num'] >= $type['max_num']) {
            return false;
        }
        $member = D('Users')->find($user_id);
        if ($type['price'] > $member['money']){
            return false;
        }
		if($detail['ltime']<=time()){
			 return false;
		}
		if($type['choujiang'] == 1){
			$choujiang = 0;
		}else{
			$choujiang = 1;
		}

        if (false !== D('Users')->addMoney($user_id, -$type['price'], '众筹商品' . $detail['title'] . '购买，扣费')) {
            $Goodslist->add(array('goods_id' => $goods_id, 'uid' => $user_id, 'type_id' => $type_id, 'name' => $data['name'], 'mobile' => $data['mobile'], 'addr' => $data['addr'], 'price' => $data['price'],'is_zhong'=>$choujiang, 'dateline' => NOW_TIME));
			$this->updateCount($goods_id, 'have_price',$data['price']);
			$this->updateCount($goods_id, 'have_num');
			$Goodstype->updateCount($type_id, 'have_num');
            return TRUE;
        } else {
            return false;
        }
    }
}