<?php

class TuanModel extends CommonModel {

    protected $pk = 'tuan_id';
    protected $tableName = 'tuan';

    public function _format($data) {
        $data['save'] = round(($data['price'] - $data['tuan_price']), 2);
        $data['price'] = round($data['price'], 2);
        $data['tuan_price'] = round($data['tuan_price'], 2);
        $data['mobile_fan'] = round($data['mobile_fan'], 2);
        $data['settlement_price'] = round($data['settlement_price'] , 2);
        $data['discount'] = round($data['tuan_price'] * 10 / $data['price'], 1);
        return $data;
    }

	public function getTuanQiang($city_id)
	{
		$cache = cache(array('type'=>'File','expire'=> 600));
		if(!$items = $cache->get('getTuanQiang')){
			$map = array('audit' => 1, 'closed' => 0, 'city_id' => $city_id, 'end_date' => array('EGT', TODAY));
			$time = time();
			$orderby = array('end_date' => 'asc');
			$items = D('Tuan')->where($map)->order($orderby)->limit(0,6)->select();
			$i=0;
			foreach($items as $k => $item){
				$i++;
				$shop = D('Shop')->where(array('shop_id'=>$item['shop_id']))->find();
				$count = D('Tuandianping')->where(array('closed' => 0, 'tuan_id'=>$item['tuan_id']))->count();
				$discount = round(($item['tuan_price']/$item['price'])*10,1);
				$items[$k]['score'] = $shop['score'];
				$items[$k]['count'] = $count;
				$items[$k]['discount'] = $discount;
				$items[$k]['daojishi1'] = strtotime($item['end_date'])-$time;
				$items[$k]['k']=$i;
			}
			$cache->set('getTuanQiang',$items);
		}
		return $items;
	}

    public function CallDataForMat($items) { //专门针对CALLDATA 标签处理的
        if (empty($items))
            return array();
        $obj = D('Shop');
        $shop_ids = array();
        foreach ($items as $k => $val) {
            $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
        $shops = $obj->itemsByIds($shop_ids);
        foreach ($items as $k => $val) {
            $val['shop'] = $shops[$val['shop_id']];
            $items[$k] = $val;
        }
        return $items;
    }

}
