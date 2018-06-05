<?php



class CrowdAction extends CommonAction {

    public function index() 
	{
		$map = array('uid' => $this->uid);
		$Goods = D('Goods');
		$Goodslist = D('Goodslist');
		$Crowd = D('Goodscrowd');
		$list = $Goodslist->where($map)->select();
		$this->assign('meals', $list);
		
		foreach($list as $k => $v){
			$goods_ids[$v['goods_id']] = $v['goods_id'];
			$type_ids[$v['type_id']] = $v['type_id'];
		}
		if (!empty($type_ids)) {
			$this->assign('type', D('Goodstype')->itemsByIds($type_ids));
		}
		if (!empty($goods_ids)) {
			$this->assign('goodss', $Goods->itemsByIds($goods_ids));
		}
		$this->display();
    }
}
