<?php

class ShopcateModel extends CommonModel {

    protected $pk = 'cate_id';
    protected $tableName = 'shop_cate';
    protected $token = 'shop_cate';
    protected $orderby = array('orderby' => 'asc');

    public function getParentsId($id) {
        $data = $this->fetchAll();
        $parent_id = $data[$id]['parent_id']; 
        return $parent_id;
    }
	
	public function getShopCateNum()
	{
		$cache = cache(array('type'=>'File','expire'=> 600));
		if(!$items = $cache->get('getShopCateNum')){
			$items = array();
			$data = $this->where('parent_id=0')->limit('10')->select();
			foreach($data as $k => $v){
				$cat_ids = $count =  '';
				$cat_ids = $this->getChildren($v['cate_id']);
				$count = D('Shop')->where(array('cate_id'=> array('IN',$cat_ids) , 'closed'=>'0' , 'audit'=>'1'))->count();
				$items[$k]['cate_name'] = $v['cate_name'];
				$items[$k]['count'] = $count;
				$items[$k]['cate_id'] = $v['cate_id'];
			}
			$cache->set('getShopCateNum',$items);
		}
		return $items;
	}

    public function getChildren($id) {
        $local = array();
        //暂时 只支持 2级分类
        $data = $this->fetchAll();
        $local[] = $id;
        foreach ($data as $val) {
            if ($val['parent_id'] == $id) {
                $local[] = $val['cate_id'];
            }
        }
        return $local;
    }

}