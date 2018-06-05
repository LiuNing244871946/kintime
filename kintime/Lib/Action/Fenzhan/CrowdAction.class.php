<?php



class CrowdAction extends CommonAction {

   public function index() {
        $Goods = D('Goods');
		$Crowd = D('Goodscrowd');
        import('ORG.Util.Page'); // 导入分页类 125062100   
        $map = array('closed' => 0,'type'=>'crowd');
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
		if ($shop_id = (int) $this->_param('shop_id')) {
            $map['shop_id'] = $shop_id;
            $shop = D('Shop')->find($shop_id);
            $this->assign('shop_name', $shop['shop_name']);
            $this->assign('shop_id', $shop_id);
        }
		if ($audit = (int) $this->_param('audit')) {
            $map['audit'] = ($audit === 1 ? 1 : 0);
            $this->assign('audit', $audit);
        }
		$map['shop_id'] = array('in',D('Shop')->getCityShop($this->_fenzhan['city_id']));
        $count = $Goods->where($map)->count(); // 查询满足要求的总记录125062100数 
        $Page = new Page($count, 25); // 实例化分页类 传入总记录数和每页显示的125062100记录数
        $show = $Page->show(); // 分页显示125062100输出
        $list = $Goods->where($map)->order(array('goods_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $k => $val) {
			$goods_ids[] = $val['goods_id']; 
            $val = $Goods->_format($val);
            $list[$k] = $val;
        }
		if($goods_ids){
			$f['goods_id'] = array('IN',implode(',',$goods_ids));
			$Crowd_list = $Crowd->where($f)->select();
			foreach($Crowd_list as $k => $v){
				$Crowd_lists[$v['goods_id']] = $v;
			}
			$this->assign('crowd', $Crowd_lists);
		}
		
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

	public function lists($goods_id)
	{
		if ($goods_id = (int) $goods_id) {
			$Goods = D('Goods');
            $Goodslist = D('Goodslist');
			$Crowd = D('Goodscrowd');
			$detail = D('Goods')->find($goods_id);
			$this->assign('detail', $detail);
			$list = $Goodslist->where(array('goods_id' => $goods_id))->select();
			$this->assign('meals', $list);
			
			foreach($list as $k => $v){
				$user_ids[$v['uid']] = $v['uid'];
				$type_ids[$v['type_id']] = $v['type_id'];
			}
			if (!empty($type_ids)) {
				$this->assign('type', D('Goodstype')->itemsByIds($type_ids));
			}
			if (!empty($user_ids)) {
				$this->assign('users', D('Users')->itemsByIds($user_ids));
			}
			$this->display();
        } else {
            $this->baoError('请选择众筹');
        }
	}

     public function delete($goods_id = 0) {
        if (is_numeric($goods_id) && ($goods_id = (int) $goods_id)) {
            $obj = D('Goods');
            if(!$good = $obj->find($goods_id)){
                $this->baoError('数据不存在或已删除');
            }
            $shop = D('shop')->find($good['shop_id']);
            if($shop['city_id'] != $this->_fenzhan['city_id']){
                $this->baoError('数据错误，不属于当前城市');
            }
            $obj->save(array('goods_id' => $goods_id, 'closed' => 1));
            $this->baoSuccess('删除成功！', U('crowd/index'));
        } else {
            $goods_id = $this->_post('goods_id', false);
            if (is_array($goods_id)) {
                $obj = D('Goods');
                foreach ($goods_id as $id) {
                    $obj->save(array('goods_id' => $id, 'closed' => 1));
                }
                $this->baoSuccess('删除成功！', U('crowd/index'));
            }
            $this->baoError('请选择要删除的商品');
        }
    }

    public function audit($goods_id = 0) {
        if (is_numeric($goods_id) && ($goods_id = (int) $goods_id)) {
            $obj = D('Goods');
            $r = $obj -> where('goods_id ='.$goods_id) -> find();
            if(empty($r['settlement_price'])){
                $this->baoError('不设置结算价格无法审核通过！');
            }
            $obj->save(array('goods_id' => $goods_id, 'audit' => 1));
            $this->baoSuccess('审核成功！', U('goods/index'));
        } else {
            $goods_id = $this->_post('goods_id', false);
            if (is_array($goods_id)) {
                $obj = D('Goods');
                $error = 0;
                foreach ($goods_id as $id) {
                    $r = $obj -> where('goods_id ='.$id) -> find();
                    if(empty($r['settlement_price'])){
                       $error++;
                        //$this->baoError('遇到了结算价格没有设置的，该条无法审核通过！');
                    }
                    $obj->save(array('goods_id' => $id, 'audit' => 1));
                }
                $this->baoSuccess('审核成功！'.$error.'条失败', U('crowd/index'));
            }
            $this->baoError('请选择要审核的商品');
        }
    }
}
