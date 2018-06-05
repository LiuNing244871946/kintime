<?php



class CrowdAction extends CommonAction {

    public function _initialize() {
        parent::_initialize();
        $this->autocates = D('Goodsshopcate')->where(array('shop_id' => $this->shop_id))->select();
        $this->assign('autocates', $this->autocates);
    }

    public function index() {
        $Goods = D('Goods');
		$Crowd = D('Goodscrowd');
        import('ORG.Util.Page'); // 导入分页类    
        $map = array('closed' => 0, 'shop_id' => $this->shop_id,'type'=>'crowd','ltime'=>array('GT',time()));
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $Goods->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 25); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
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

    public function history() {
        $Goods = D('Goods');
		$Crowd = D('Goodscrowd');
        import('ORG.Util.Page'); // 导入分页类
		$map = array('closed' => 0, 'shop_id' => $this->shop_id,'type'=>'crowd','ltime'=>array('LT',time()));
        $count = $Goods->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 25); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
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

	public function create() {
        if ($this->isPost()) {
            $data = $this->createCheck();
            $details = $this->_post('details', 'SecurityEditorHtml');
            if (empty($details)) {
                $this->baoError('众筹详情不能为空');
            }
            if ($words = D('Sensitive')->checkWords($details)) {
                $this->baoError('详细内容含有敏感词：' . $words);
            }
            $instructions = $this->_post('instructions', 'SecurityEditorHtml');
            if (empty($instructions)) {
                $this->baoError('购买须知不能为空');
            }
            if ($words = D('Sensitive')->checkWords($instructions)) {
                $this->baoError('购买须知含有敏感词：' . $words);
            }
            $thumb = $this->_param('thumb', false);
            foreach ($thumb as $k => $val) {
                if (empty($val)) {
                    unset($thumb[$k]);
                }
                if (!isImage($val)) {
                    unset($thumb[$k]);
                }
            }
            $data['thumb'] = serialize($thumb);
			$Goods = D('Goods');
			$data['type'] = 'crowd';
			$data['shop_id'] = $this->shop_id;
			$data['city_id'] =	$this->shop['city_id'];
			$data['area_id'] =	$this->shop['area_id'];

            if ($goods_id = $Goods->add($data)) {
                D('Goodscrowd')->add(array('goods_id' => $goods_id, 'details' => $details, 'instructions' => $instructions, 'title' => $data['title'], 'all_price' => $data['price'], 'img' => $data['photo'], 'ltime' => $data['ltime'],'dateline'=>time()));
                $this->baoSuccess('添加成功', U('crowd/index'));
            }
            $this->baoError('操作失败！');
        } else {
            $this->display();
        }
    }

	 public function edit($goods_id = 0) {
        if ($goods_id = (int) $goods_id) {
            $obj = D('Goods');
			$Crowd = D('Goodscrowd');
            if (!$detail = $obj->find($goods_id)) {
                $this->baoError('请选择要编辑的众筹');
            }
			if ($detail['type'] != 'crowd') {
                $this->baoError('该众筹不存在');
            }
            if ($detail['shop_id'] != $this->shop_id) {
                $this->baoError('请不要操作别人的众筹');
            }
            if ($detail['closed'] != 0) {
                $this->baoError('该众筹已被删除');
            }

            $Crowd_list = $Crowd->find($goods_id);
            if ($this->isPost()) {
                $data = $this->editCheck();
                $details = $this->_post('details', 'SecurityEditorHtml');
                if (empty($details)) {
                    $this->baoError('众筹详情不能为空');
                }
                if ($words = D('Sensitive')->checkWords($details)) {
                    $this->baoError('详细内容含有敏感词：' . $words);
                }
                $instructions = $this->_post('instructions', 'SecurityEditorHtml');
                if (empty($instructions)) {
                    $this->baoError('购买须知不能为空');
                }
                if ($words = D('Sensitive')->checkWords($instructions)) {
                    $this->baoError('购买须知含有敏感词：' . $words);
                }

				if (false !== $obj->where(array('goods_id' => $goods_id))->save($data)) {
					$Crowd->where(array('goods_id' => $goods_id))->save(array('details' => $details, 'instructions' => $instructions, 'title' => $data['title'], 'all_price' => $data['price'], 'img' => $data['photo'], 'ltime' => $data['ltime']));
					$this->baoSuccess('操作成功', U('crowd/index'));
				}

                $this->baoError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->assign('crowd', $Crowd_list);
                $this->assign('shop', D('Shop')->find($detail['shop_id']));
                $this->display();
            }
        } else {
            $this->baoError('请选择要编辑的众筹');
        }
    }


	public function setting($goods_id = 0) {
        if ($goods_id = (int) $goods_id) {
			$Goods = D('Goods');
            $Goodstype = D('Goodstype');
			$Crowd = D('Goodscrowd');
			$detail = D('Goods')->find($goods_id);
			$this->assign('detail', $detail);
			$this->assign('meals', $Goodstype->where(array('goods_id' => $goods_id))->select());
			$this->display();
        } else {
            $this->baoError('请选择要设置的众筹');
        }
    }

	public function type_create($goods_id)
	{
		if ($goods_id = (int) $goods_id) {
			$Goods = D('Goods');
            $Goodstype = D('Goodstype');
			$Crowd = D('Goodscrowd');

            if (!$detail = D('Goods')->find($goods_id)) {
                $this->baoError('请选择要设置的众筹');
            }
			if ($detail['type'] != 'crowd') {
                $this->baoError('该众筹不存在');
            }
            if ($detail['shop_id'] != $this->shop_id) {
                $this->baoError('请不要操作别人的众筹');
            }
            if ($detail['closed'] != 0) {
                $this->baoError('该众筹已被删除');
            }
            $Crowd_list = $Crowd->find($goods_id);
			
			
			if ($data = $this->_post('data', false)) {
				if (!empty($data['price']) && !empty($data['content']) && !empty($data['max_num']) && !empty($data['fahuo'])){
					$Goodstype->add(array(
						'goods_id' => $goods_id,
						'price' => $data['price']*100,
						'content' => $data['content'],
						'max_num' => $data['max_num'],
						'yunfei' => $data['yunfei']*100,
						'fahuo' => $data['fahuo'],
						'choujiang' => $data['choujiang'],
						'img' => $data['img'],
						'dateline' => time(),
					));
					$this->baoSuccess('操作成功', U('crowd/setting',array('goods_id'=>$detail['goods_id'])));
				}else{
					 $this->baoError('内容不能为空');
				}
			}else{
				$this->assign('detail', $detail);
				$this->display();
			}

		} else {
            $this->baoError('请选择要设置的众筹');
        }
	}

	public function type_edit($type_id)
	{
		if ($type_id = (int) $type_id) {
			$Goods = D('Goods');
            $Goodstype = D('Goodstype');
			$Crowd = D('Goodscrowd');
			if (!$type = $Goodstype->find($type_id)) {
                $this->baoError('修改的内容不存在');
            }
			$goods_id = $type['goods_id'];
            if (!$detail = D('Goods')->find($goods_id)) {
                $this->baoError('请选择要设置的众筹');
            }
			if ($detail['type'] != 'crowd') {
                $this->baoError('该众筹不存在');
            }
            if ($detail['shop_id'] != $this->shop_id) {
                $this->baoError('请不要操作别人的众筹');
            }
            if ($detail['closed'] != 0) {
                $this->baoError('该众筹已被删除');
            }
            $Crowd_list = $Crowd->find($goods_id);
			
			
			
			if ($data = $this->_post('data', false)) {
				if (!empty($data['price']) && !empty($data['content']) && !empty($data['max_num']) && !empty($data['fahuo'])){
					$data = array(
						'goods_id' => $goods_id,
						'price' => $data['price']*100,
						'content' => $data['content'],
						'max_num' => $data['max_num'],
						'yunfei' => $data['yunfei']*100,
						'fahuo' => $data['fahuo'],
						'choujiang' => $data['choujiang'],
						'img' => $data['img'],
					);

					if (false !== $Goodstype->where(array('type_id' => $type_id))->save($data)) {
						$this->baoSuccess('操作成功', U('crowd/setting',array('goods_id'=>$detail['goods_id'])));
					}
					
				}else{
					 $this->baoError('内容不能为空');
				}
			}else{
				$this->assign('type', $type);
				$this->assign('detail', $detail);
				$this->display();
			}

		} else {
            $this->baoError('修改的内容不存在');
        }
	}

	public function type_delete($type_id)
	{
		if ($type_id = (int) $type_id) {
			$Goods = D('Goods');
            $Goodstype = D('Goodstype');
			$Crowd = D('Goodscrowd');
			if (!$type = $Goodstype->find($type_id)) {
                $this->baoError('删除的内容不存在');
            }
			$goods_id = $type['goods_id'];
            if (!$detail = D('Goods')->find($goods_id)) {
                $this->baoError('请选择要设置的众筹');
            }
			if ($detail['type'] != 'crowd') {
                $this->baoError('该众筹不存在');
            }
            if ($detail['shop_id'] != $this->shop_id) {
                $this->baoError('请不要操作别人的众筹');
            }
            if ($detail['closed'] != 0) {
                $this->baoError('该众筹已被删除');
            }
            $Crowd_list = $Crowd->find($goods_id);
			
			if (false !== $Goodstype->where(array('type_id' => $type_id))->delete()) {
				$this->baoSuccess('操作成功', U('crowd/setting',array('goods_id'=>$detail['goods_id'])));
			}
		} else {
            $this->baoError('删除的内容不存在');
        }
	}

	public function project($goods_id)
	{
		if ($goods_id = (int) $goods_id) {
			$Goods = D('Goods');
            $Goodsproject = D('Goodsproject');
			$Crowd = D('Goodscrowd');
			$detail = D('Goods')->find($goods_id);
			$this->assign('detail', $detail);
			$this->assign('meals', $Goodsproject->where(array('goods_id' => $goods_id))->select());
			$this->display();
        } else {
            $this->baoError('请选择要设置的众筹');
        }
	}

	public function project_create($goods_id)
	{
		if ($goods_id = (int) $goods_id) {
			$Goods = D('Goods');
            $Goodsproject = D('Goodsproject');
			$Crowd = D('Goodscrowd');

            if (!$detail = D('Goods')->find($goods_id)) {
                $this->baoError('请选择要设置的众筹');
            }
			if ($detail['type'] != 'crowd') {
                $this->baoError('该众筹不存在');
            }
            if ($detail['shop_id'] != $this->shop_id) {
                $this->baoError('请不要操作别人的众筹');
            }
            if ($detail['closed'] != 0) {
                $this->baoError('该众筹已被删除');
            }
            $Crowd_list = $Crowd->find($goods_id);
			
			
			if ($data = $this->_post('data', false)) {
				if (!empty($data['content'])){
					$Goodsproject->add(array(
						'goods_id' => $goods_id,
						'content' => $data['content'],
						'dateline' => time(),
					));
					$this->baoSuccess('操作成功', U('crowd/project',array('goods_id'=>$detail['goods_id'])));
				}else{
					 $this->baoError('内容不能为空');
				}
			}else{
				$this->assign('detail', $detail);
				$this->display();
			}
		} else {
            $this->baoError('请选择要设置的众筹');
        }
	}

	public function project_delete($project_id)
	{
		if ($project_id = (int) $project_id) {
			$Goods = D('Goods');
            $Goodsproject = D('Goodsproject');
			$Crowd = D('Goodscrowd');
			if (!$type = $Goodsproject->find($project_id)) {
                $this->baoError('删除的内容不存在');
            }
			$goods_id = $type['goods_id'];
            if (!$detail = D('Goods')->find($goods_id)) {
                $this->baoError('请选择要设置的众筹');
            }
			if ($detail['type'] != 'crowd') {
                $this->baoError('该众筹不存在');
            }
            if ($detail['shop_id'] != $this->shop_id) {
                $this->baoError('请不要操作别人的众筹');
            }
            if ($detail['closed'] != 0) {
                $this->baoError('该众筹已被删除');
            }
            $Crowd_list = $Crowd->find($goods_id);
			
			if (false !== $Goodsproject->where(array('project_id' => $project_id))->delete()) {
				$this->baoSuccess('操作成功', U('crowd/project',array('goods_id'=>$detail['goods_id'])));
			}
		} else {
            $this->baoError('删除的内容不存在');
        }
	}

	public function ask($goods_id)
	{
		if ($goods_id = (int) $goods_id) {
			$Goods = D('Goods');
            $Goodsask = D('Goodsask');
			$Crowd = D('Goodscrowd');
			$detail = D('Goods')->find($goods_id);
			$this->assign('detail', $detail);
			$ask = $Goodsask->where(array('goods_id' => $goods_id))->select();
			$this->assign('meals', $ask);

			foreach($ask as $k => $v){
				$user_ids[$v['uid']] = $v['uid'];
			}
			if (!empty($user_ids)) {
				$this->assign('users', D('Users')->itemsByIds($user_ids));
			}
			$this->display();
        } else {
            $this->baoError('请选择众筹');
        }
	}

	public function ask_answer($ask_id)
	{
		if ($ask_id = (int) $ask_id) {
			$Goods = D('Goods');
            $Goodsask = D('Goodsask');
			$Crowd = D('Goodscrowd');
			if (!$ask = $Goodsask->find($ask_id)) {
                $this->baoError('该问题不存在');
            }
            if (!$detail = D('Goods')->find($ask['goods_id'])) {
                $this->baoError('请选择要设置的众筹');
            }
			if ($detail['type'] != 'crowd') {
                $this->baoError('该众筹不存在');
            }
            if ($detail['shop_id'] != $this->shop_id) {
                $this->baoError('请不要操作别人的众筹');
            }
            if ($detail['closed'] != 0) {
                $this->baoError('该众筹已被删除');
            }
            $Crowd_list = $Crowd->find($goods_id);
			
			
			if ($data = $this->_post('data', false)) {
				if (!empty($data['answer_c'])){
					$Goodsask->where(array('ask_id' => $ask_id))->save(array(
						'answer_c' => $data['answer_c'],
						'answer_time' => time(),
					));
					$this->baoSuccess('操作成功', U('crowd/ask',array('goods_id'=>$ask['goods_id'])));
				}else{
					 $this->baoError('内容不能为空');
				}
			}else{
				$this->assign('detail', $detail);
				$this->assign('ask', $ask);
				$this->display();
			}
		} else {
            $this->baoError('请选择要设置的众筹');
        }
	}

	public function follow($goods_id)
	{
		if ($goods_id = (int) $goods_id) {
			$Goods = D('Goods');
            $Goodsfollow = D('Goodsfollow');
			$Crowd = D('Goodscrowd');
			$detail = D('Goods')->find($goods_id);
			$this->assign('detail', $detail);
			$follow = $Goodsfollow->where(array('goods_id' => $goods_id))->select();
			$this->assign('meals', $follow);

			foreach($follow as $k => $v){
				$user_ids[$v['uid']] = $v['uid'];
			}
			if (!empty($user_ids)) {
				$this->assign('users', D('Users')->itemsByIds($user_ids));
			}
			$this->display();
        } else {
            $this->baoError('请选择众筹');
        }
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

	public function zhong($list_id)
	{
		
		$Goodslist = D('Goodslist');
		
		$data['is_zhong'] = '1';
		$list = $Goodslist->where(array('list_id'=>$list_id))->find();
		if($Goodslist->where(array('list_id'=>$list_id))->save($data)){
			$this->baoMsg('设为中奖成功', U('crowd/lists',array('goods_id'=>$list['goods_id'])));
		}
	}



    public function order() {
        $Goodsorder = D('Tuanorder');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('shop_id' => $this->shop_id);
        if (($bg_date = $this->_param('bg_date', 'htmlspecialchars') ) && ($end_date = $this->_param('end_date', 'htmlspecialchars'))) {
            $bg_time = strtotime($bg_date);
            $end_time = strtotime($end_date);
            $map['create_time'] = array(array('ELT', $end_time), array('EGT', $bg_time));
            $this->assign('bg_date', $bg_date);
            $this->assign('end_date', $end_date);
        } else {
            if ($bg_date = $this->_param('bg_date', 'htmlspecialchars')) {
                $bg_time = strtotime($bg_date);
                $this->assign('bg_date', $bg_date);
                $map['create_time'] = array('EGT', $bg_time);
            }
            if ($end_date = $this->_param('end_date', 'htmlspecialchars')) {
                $end_time = strtotime($end_date);
                $this->assign('end_date', $end_date);
                $map['create_time'] = array('ELT', $end_time);
            }
        }

        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['order_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }

        if (isset($_GET['st']) || isset($_POST['st'])) {
            $st = (int) $this->_param('st');
            if ($st != 999) {
                $map['status'] = $st;
            }
            $this->assign('st', $st);
        } else {
            $this->assign('st', 999);
        }
        $count = $Goodsorder->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 25); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $Goodsorder->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $shop_ids = $user_ids = $goods_ids = array();
        foreach ($list as $k => $val) {
            if (!empty($val['shop_id'])) {
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $user_ids[$val['user_id']] = $val['user_id'];
            $goods_ids[$val['goods_id']] = $val['goods_id'];
        }
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('tuan', D('Tuan')->itemsByIds($goods_ids));
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function used() {
        if ($this->isPost()) {
            $code = $this->_post('code', false);
            if (empty($code)) {
                $this->baoError('请输入众筹券！');
            }

            $obj = D('Tuancode');
            $shopmoney = D('Shopmoney');
            $return = array();
            $ip = get_client_ip();
            if (count($code) > 10) {
                $this->baoError('一次最多验证10条众筹券！');
            }
            $userobj = D('Users');
            foreach ($code as $key => $var) {
                $var = trim(htmlspecialchars($var));

                if (!empty($var)) {
                    $data = $obj->find(array('where' => array('code' => $var)));

                    if (!empty($data) && $data['shop_id'] == $this->shop_id && (int) $data['is_used'] == 0 && (int) $data['status'] == 0) {
                        if ($obj->save(array('code_id' => $data['code_id'], 'is_used' => 1))) { //这次更新保证了更新的结果集              
                            //增加MONEY 的过程 稍后补充
                            if (!empty($data['price'])) {
                                $data['intro'] = '众筹消费' . $data['order_id'];

                                $data['settlement_price'] =  D('Quanming')->quanming($data['user_id'],$data['settlement_price'],'tuan'); //扣去全民营销

                                $shopmoney->add(array(
                                    'shop_id' => $data['shop_id'],
                                    'money' => $data['settlement_price'],
                                    'create_ip' => $ip,
                                    'create_time' => NOW_TIME,
                                    'order_id' => $data['order_id'],
                                    'intro' => $data['intro'],
                                ));
                                $shop = D('Shop')->find($data['shop_id']);
                                D('Users')->addMoney($shop['user_id'], $data['settlement_price'], $data['intro']);
                                $return[$var] = $var;
                                D('Users')->gouwu($data['user_id'],$data['price'],'众筹券消费成功');
                                $obj->save(array('code_id' => array('used_time' => NOW_TIME, 'used_ip' => $ip))); //拆分2次更新是保障并发情况下安全问题
                                echo '<script>parent.used(' . $key . ',"√验证成功",1);</script>';
                            } else {
                                echo '<script>parent.used(' . $key . ',"√到店付众筹券验证成功，需要现金付款",2);</script>';
                            }
                  
                        }
                    } else {
                        echo '<script>parent.used(' . $key . ',"X该众筹券无效",3);</script>';
                    }
                }
            }
        } else {
            $this->display();
        }
    }

    

    private function createCheck() {
        $data = $this->_post('data', false);
        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->baoError('众筹名称不能为空');
        }
        
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->baoError('请上传图片');
        }
        if (!isImage($data['photo'])) {
            $this->baoError('图片格式不正确');
        } $data['price'] = (int) ($data['price'] * 100);
        if (empty($data['price'])) {
            $this->baoError('众筹金额不能为空');
        }
        
        $data['ltime'] = strtotime($data['ltime']);
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        return $data;
    }

    

   

    private function editCheck() {
        $data = $this->_post('data', false);
        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->baoError('众筹名称不能为空');
        }
        
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->baoError('请上传图片');
        }
        if (!isImage($data['photo'])) {
            $this->baoError('图片格式不正确');
        } $data['price'] = (int) ($data['price'] * 100);
        if (empty($data['price'])) {
            $this->baoError('众筹金额不能为空');
        }
        
        $data['ltime'] = strtotime($data['ltime']);
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        return $data;
    }

}
