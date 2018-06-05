<?php



class MallAction extends CommonAction {
	
    protected $goodscate = array();

    public function _initialize() {
        parent::_initialize();
        $this->goodscate = D('Goodscate')->fetchAll();
        $this->assign('goodscate', $this->goodscate);
        $this->type = D('Keyword')->fetchAll();
        $this->assign('types', $this->type);
        $goods = cookie('goods');
        $this->assign('cartnum', (int) array_sum($goods));
    }

	public function mallList()
	{
		// 获取热售
		$map = array('closed' => 0, 'audit' => 1,'type'=>'crowd', 'city_id' => $this->city_id, 'ltime' => array('GT', time()));
		$Goods = D('Goods');
		$Goodscrowd = D('Goodscrowd');
		$heat = $Goods->where($map)->order($orderby)->limit('11')->select();
		$arr = array();
		foreach($heat as $k => $v){
			$arr[] = $v['goods_id'];
		}
		$details['goods_id'] = array('IN',implode(',',$arr));
		$crowd = $Goodscrowd->where($details)->select();
		$items = $Goodscrowd->merge($heat,$crowd);
		$this->assign('itemss', $items);
		$this->display('mallList');
	}

	public function index()
	{
		// 获取众筹
		$map = array('closed' => 0, 'audit' => 1,'type'=>'crowd', 'city_id' => $this->city_id, 'ltime' => array('GT', time()));
		$Goods = D('Goods');
		$Goodscrowd = D('Goodscrowd');
		$heat = $Goods->where($map)->order($orderby)->limit('11')->select();
		$arr = array();
		foreach($heat as $k => $v){
			$arr[] = $v['goods_id'];
		}
		$details['goods_id'] = array('IN',implode(',',$arr));
		$crowd = $Goodscrowd->where($details)->select();
		$items = $Goodscrowd->merge($heat,$crowd);
		$this->assign('itemss', $items);

		//新品上架
		$map2 = array('closed' => 0, 'audit' => 1,'type'=>'goods', 'city_id' => $this->city_id);
		$orderby = array('create_time'=>'DESC');
		$new = $Goods->where($map2)->order($orderby)->limit('3')->select();
		$this->assign('new', $new);

		//推荐
		$orderby = array('orderby'=>'ASC');
		$tuijian = $Goods->where($map2)->order($orderby)->limit('5')->select();
		$this->assign('tuijian', $tuijian);

		//限时抢购
		$map3 = array('closed' => 0, 'audit' => 1,'type'=>'goods', 'city_id' => $this->city_id, 'rush_ltime' => array('GT', time()), 'rush_kucun' => array('GT', '0'));
		$xianshi = $Goods->where($map3)->order($orderby)->limit(4)->select();
		$this->assign('xianshi', $xianshi);

		//猜你喜欢
		$orderby = array('views'=>'DESC');
		$like = $Goods->where($map2)->order($orderby)->limit('8')->select();
		$this->assign('like', $like);
		$this->display('index');
	}


	public function crowdList(){
		$Goods = D('Goods');
		$Goodscrowd = D('Goodscrowd');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0, 'audit' => 1,'type'=>'crowd', 'city_id' => $this->city_id, 'ltime' => array('GT', time()));
        $linkArr = array();
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
            $linkArr['keywrod'] = $map['title'];
        }

        $count = $Goods->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($count, 20); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $Goods->where($map)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $k => $v){
			$arr[] = $v['goods_id'];
		}
		$details['goods_id'] = array('IN',implode(',',$arr));
		$crowd = $Goodscrowd->where($details)->select();
		$items = $Goodscrowd->merge($list,$crowd);
        $this->assign('itemss', $items); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
		$this->display('crowdList');
	}

	

	public function crowdDetail($goods_id) {
        $goods_id = (int) $goods_id;
		$Goodscrowd = D('Goodscrowd');
		$Goods = D('Goods');
		$Goodsproject = D('Goodsproject');
		$Goodsask = D('Goodsask');
		$Goodslist = D('Goodslist');
		$Goodstype = D('Goodstype');
        if (!$goods = D('Goods')->find($goods_id)) {
            $this->error('您访问的产品不存在！');
        }
        if ($goods['closed'] != 0 || $goods['audit'] != 1) {
            $this->error('您访问的产品不存在！');
        }
		if (!$crowd = $Goodscrowd->find($goods_id)) {
            $this->error('您访问的产品不存在！');
        }
		$detail = array_merge($goods,$crowd);
        $shop = D('Shop')->find($detail['shop_id']);
        $this->assign('shop', $shop);
      

        //其他众筹
        $maps = array('closed' => 0, 'audit' => 1,'type'=>'crowd', 'city_id' => $this->city_id, 'ltime' => array('GT', time()));
        $list = $Goods->where($maps)->order(array('views'=>'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $k => $v){
			$arr[] = $v['goods_id'];
		}
		$details['goods_id'] = array('IN',implode(',',$arr));
		$crowd = $Goodscrowd->where($details)->select();
		$like = $Goodscrowd->merge($list,$crowd);
        $this->assign('like', $like);



        $goodsdianping = D('Goodsdianping');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0, 'goods_id' => $goods_id, 'show_date' => array('ELT', TODAY));
        $count = $goodsdianping->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 5); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $goodsdianping->where($map)->order(array('dianping_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $dianping_ids = array();
        foreach ($list as $k => $val) {
            $list[$k] = $val;
            $user_ids[$val['user_id']] = $val['user_id'];
            $dianping_ids[$val['dianping_id']] = $val['dianping_id'];
        }
        
        if (!empty($dianping_ids)) {
            $this->assign('pics', D('Goodsdianpingpics')->where(array('dianping_id' => array('IN', $dianping_ids)))->select());
        }

		//项目进展
		$projectcount = $Goodsproject->where(array('goods_id' =>$goods_id))->count();
		$project = $Goodsproject->where(array('goods_id' =>$goods_id))->order(array('dateline' => 'desc'))->select();
		$this->assign('projectcount', $projectcount);
		$this->assign('project', $project);

		//问题回答
		$askcount = $Goodsask->where(array('goods_id' =>$goods_id))->count();
		$ask_list = $Goodsask->where(array('goods_id' =>$goods_id))->order(array('dateline' => 'desc'))->select();
        $user_idss = array();
		foreach($ask_list as $k => $v){
			$user_idss[$v['uid']] = $v['uid'];
		}
		$this->assign('askcount', $askcount);
		$this->assign('ask_list', $ask_list);
        $this->assign('userss', D('Users')->itemsByIds($user_idss));
		//获取购买列表

		$goodstype = $Goodstype->where(array('goods_id' =>$goods_id))->order(array('price' => 'asc'))->select();
		$this->assign('goodstype', $goodstype);

		//获取购买记录
		$listcount = $Goodslist->where(array('goods_id' =>$goods_id))->count();
		$goods_list = $Goodslist->where(array('goods_id' =>$goods_id))->order(array('dateline' => 'desc'))->select();
		foreach($goods_list as $k => $v){
			$user_ids[$v['uid']] = $v['uid'];
		}

		if (!empty($user_ids)) {
            $this->assign('users', D('Users')->itemsByIds($user_ids));
        }
		
		$this->assign('listcount', $listcount); // 赋值数据集
		$this->assign('goods_list', $goods_list); // 赋值数据集
		
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->seodatas['title'] = $detail['title'];
        D('Goods')->updateCount($goods_id, 'views');
        $this->assign('detail', $detail);
        $this->display();
    }

	public function ask($goods_id)
	{
		$Goodsask = D('Goodsask');
		if (empty($this->uid)) {
            $this->ajaxLogin(); //提示异步登录
        }
		$goods_id = (int) $goods_id;
        if (!$detail = D('Goods')->find($goods_id)) {
            $this->error('该商品不存在');
        }
		$data['uid'] = $this->uid;
		$data['goods_id'] = $goods_id;
		$data['ask_c'] = $this->_post('ask_c', 'htmlspecialchars');
        if(empty($data['ask_c'])){
            $this->baoError("提问问题不能为空");
        }
		$data['dateline'] =	time();

		if ($ask_id = $Goodsask->add($data)) {
			$this->baoMsg('提交成功');
		}
		
	}

	public function favorites($goods_id)
	{
		$Goodsfollow = D('Goodsfollow');
		$Goodscrowd = D('Goodscrowd');
		if (empty($this->uid)) {
            $this->ajaxLogin(); //提示异步登录
        }
		$goods_id = (int) $goods_id;
        if (!$detail = D('Goods')->find($goods_id)) {
            $this->error('该商品不存在');
        }
		$data['type'] = 'follow';
		$data['goods_id'] = $goods_id;
		$data['uid'] = $this->uid;
		$data['dateline'] =	time();
		$map = array('type'=>'follow','uid'=>$this->uid,'goods_id'=>$goods_id);
		if($Goodsfollow->where($map)->find()){
			$this->baoMsg('您已经关注过了');
		}else if ($ask_id = $Goodsfollow->add($data)) {
			$Goodscrowd->updateCount($goods_id, 'follow_num');
			$this->baoMsg('关注成功',U('mall/crowdDetail',array('goods_id'=>$goods_id)));
		}
	}
 
	public function detail_c($goods_id)
	{
		$Goods = D('Goods');
		$Goodscrowd = D('Goodscrowd');
        $map = array('closed' => 0, 'audit' => 1,'type'=>'crowd', 'city_id' => $this->city_id,'goods_id' => array('NEQ',$goods_id), 'ltime' => array('GT', time()));
       
        $list = $Goods->where($map)->order($orderby)->limit('20')->select();
		shuffle($list);$num = 0;
        foreach($list as $k => $v){
			$num++;
			if($num<5){
				$arr[] = $v['goods_id'];
				$arrs[] = $v;
			}
		}
		
		$details['goods_id'] = array('IN',implode(',',$arr));
		$crowd = $Goodscrowd->where($details)->select();
		$items = $Goodscrowd->merge($arrs,$crowd);
        $this->assign('itemss', $items); // 赋值分页输出
		$this->display();
	}

	public function buy($goods_id)
	{
		$Goodscrowd = D('Goodscrowd');
		$Goodstype = D('Goodstype');
		if (empty($this->uid)) {
            $this->ajaxLogin(); //提示异步登录
        }
		$data = $this->_post('data');
		$goods_id = (int) $goods_id;
        if (!$detail = D('Goods')->find($goods_id)) {
            $this->baoMsg('该商品不存在');
        }
		if (!$type = $Goodstype->find($data['type_id'])) {
            $this->baoMsg('该商品不存在');
        }

		if(!$data['name']){
			$this->baoMsg('姓名不能为空');
		}
		if(!$data['mobile']){
			$this->baoMsg('手机号码不能为空');
		}
		if(!$data['addr']){
			$this->baoMsg('地址不能为空');
		}

		if ($this->member['money'] < $type['price']) {
			$this->ajaxReturn(array('status' => 'error', 'msg' => '抱歉，您的余额不足', 'url' => U('member/money/money')));
		}
		if (false !== $Goodscrowd->buy($goods_id, $this->uid, $data,$type)) {
			$this->baoMsg('众筹成功', U('mall/crowdDetail',array('goods_id'=>$goods_id)));
		} else {
			$this->baoMsg('众筹失败', U('mall/crowdDetail',array('goods_id'=>$goods_id)));
		}

	}

	public function zan($goods_id)
	{
		$Goodsfollow = D('Goodsfollow');
		$Goodscrowd = D('Goodscrowd');
		if (empty($this->uid)) {
            $this->ajaxLogin(); //提示异步登录
        }
		$goods_id = (int) $goods_id;
        if (!$detail = D('Goods')->find($goods_id)) {
            $this->error('该商品不存在');
        }
		$data['uid'] = $this->uid;
		$data['goods_id'] = $goods_id;
		$data['type'] = 'zan';
		$data['dateline'] =	time();

		$map = array('type'=>'zan','uid'=>$this->uid,'goods_id'=>$goods_id);
		if($Goodsfollow->where($map)->find()){
			$this->baoMsg('您已经赞过了');
		}else if ($ask_id = $Goodsfollow->add($data)) {
			$Goodscrowd->updateCount($goods_id, 'zan_num');
			$this->baoMsg('点赞成功',U('mall/crowdDetail',array('goods_id'=>$goods_id)));
		}
	}
	
    public function items() {
        $Goods = D('Goods');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0, 'audit' => 1, 'city_id' => $this->city_id, 'end_date' => array('EGT', TODAY));
        $linkArr = array();
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
            $linkArr['keywrod'] = $map['title'];
        }
        $cat = (int) $this->_param('cat');
        $cate_id = (int) $this->_param('cate_id');
        if ($cat) {
            if (!empty($cate_id)) {
                $map['cate_id'] = $cate_id;
                $this->seodatas['cate_name'] = $this->goodscate[$cate_id]['cate_name'];
                $linkArr['cat'] = $cat;
                $linkArr['cate_id'] = $cate_id;
            } else {
                $catids = D('Goodscate')->getChildren($cat);
                if (!empty($catids)) {
                    $map['cate_id'] = array('IN', $catids);
                }
                $this->seodatas['cate_name'] = $this->goodscate[$cat]['cate_name'];
                $linkArr['cat'] = $cat;
            }
        }
        $this->assign('cat', $cat);
        $this->assign('cate_id', $cate_id);
        $area = (int) $this->_param('area');
        if ($area) {
            $map['area_id'] = $area;
            $this->seodatas['area_name'] = $this->areas[$area]['area_name'];
            $linkArr['area'] = $area;
        }
        $this->assign('area_id', $area);
        $business = (int) $this->_param('business');
        if ($business) {
            $map['business_id'] = $business;
            $this->seodatas['business_name'] = $this->bizs[$business]['business_name'];
            $linkArr['business'] = $business;
        }
        $this->assign('business_id', $business);

        $order = $this->_param('order', 'htmlspecialchars');
        $orderby = '';
        switch ($order) {
            case 's':
                $orderby = array('sold_num' => 'desc');
                $linkArr['order'] = $order;
                break;
            case 'p':
                $orderby = array('mall_price' => 'asc');
                $linkArr['order'] = $order;
                break;
            case 'v':
                $orderby = array('views' => 'asc');
                $linkArr['order'] = $order;
                break;
			case 'n':
                $orderby = array('create_time' => 'desc');
                $linkArr['order'] = $order;
                break;
            default:
                $orderby = array('orderby' => 'asc', 'sold_num' => 'desc', 'goods_id' => 'desc');
                $linkArr['order'] = $order;
                break;
        }
        $this->assign('order', $order);
        $count = $Goods->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($count, 20); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $Goods->where($map)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $k => $v) {
            $list[$k] = $Goods->_format($v);
        }
        $selArr = $linkArr;
        foreach ($selArr as $k => $val) {
            if ($k == 'order') {
                unset($selArr[$k]);
            }
        }
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('selArr', $selArr);
        $this->assign('linkArr', $linkArr);
        $this->display('items');
    }

    public function shoplist() {
        $Shop = D('Shop');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0, 'audit' => 1, 'is_mall' => 1);
        $count = $Shop->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $Shop->where($map)->order(array('orderby' => 'asc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display();
    }
    public function shop() {
        if (!$shop_id = (int) $this->_param('shop_id')) {
            $this->error('该商家不存在');
        }
        if (!$shop = D('Shop')->find($shop_id)) {
            $this->error('该商家不存在');
        }
        if (!$shop['is_mall']) {
            $this->error('该商家不存在');
        }
        $this->assign('shop_id', $shop_id);
        $this->assign('shop', $shop);
        $this->assign('details', D('Shopdetails')->find($shop_id));
        $this->assign('cates', D('Goodsshopcate')->where(array('shop_id' => $shop_id))->select());
        D('Shop')->updateCount($shop_id, 'view');
        $this->seodatas['shop_name'] = $shop['shop_name'];
        $Goods = D('Goods');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0, 'shop_id' => $shop_id, 'audit' => 1);
        $linkArr = array('shop_id' => $shop_id);

        $cat = (int) $this->_param('cat');
        if ($cat) {
            $map['shopcate_id'] = $cat;
            $linkArr['cat'] = $cat;
        }
        $this->assign('cat', $cat);


        $linkArr['order'] = $order = $this->_param('order', 'htmlspecialchars');
        $orderby = '';
        switch ($order) {
            case 's':
                $orderby = array('sold_num' => 'desc');
                break;
            case 'p':
                $orderby = array('mall_price' => 'desc');
                break;
            case 'v':
                $orderby = array('views' => 'desc');
            default:
                $order = 'd';
                $orderby = array('orderby' => 'asc', 'sold_num' => 'desc', 'goods_id' => 'desc');
                break;
        }
        $this->assign('order', $order);


        $count = $Goods->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($count, 25); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $Goods->where($map)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $k => $v) {
            $list[$k] = $Goods->_format($v);
        }

        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('linkArr', $linkArr);
        $this->display();
    }

    public function cartdel() {
        $sku_id = I('goods_id','','trim');
        if(!strpos($sku_id, '_')){
            $sku_id = $sku_id.'_0';
        }
        $cart_goods = cookie('goods');
        //$cart_goods = session('goods');
        if (isset($cart_goods[$sku_id])) {
            unset($cart_goods[$sku_id]);
            cookie('goods', $cart_goods, 604800);
            //session('goods', $cart_goods, 604800);
            $this->ajaxReturn(array('status'=>'success','msg'=>'删除成功'));
        }else{
            $this->ajaxReturn(array('status'=>'error','msg'=>'删除失败'));
        }
    }
    // public function cartdel() {

    //     $goods_id = (int) $this->_get('goods_id');
    //     $vid = (int) $this->_get('vid');

    //     $goods = session('goods');
    //     $goods_vid = $goods_id . "_" . $vid;
    //     if (isset($goods[$goods_vid])) {

    //         unset($goods[$goods_vid]);

    //         session('goods', $goods);
    //     }

    //     header("Location:" . U('mall/cart'));

    //     //$this->success('删除成功', U('mall/cart'));
    // }

    public function cart() {
            $cart_goods = cookie('goods');    
            if (empty($cart_goods)) {
                $this->error("亲还没有选购产品呢!", U('mall/index'));
            }
            $good_list = array();
            $total_num = 0;
            foreach($cart_goods as $k => $v){
                list($gid, $vid) = explode('_', $k);
                $gid = (int)$gid; $vid = (int)$vid;
                $goods_ids[$gid] = $gid;
                $value_ids[$vid] = $vid;
            }
            $goods_list = $goods_value_list = array();
            if($goods_ids){
                if($value_ids){
                    $_values_list = D('Goodsformat')->where(array('id'=>array('IN', implode(',', $value_ids))))->select();
                    foreach($_values_list as $v){
                        $goods_value_list[$v['id']] = $v;
                    }
                }
                if($_goods_list = D('Goods')->where(array('goods_id'=>array('IN', implode(',', $goods_ids))))->select()){
                    foreach($_goods_list as $v){
                        if ($v['closed'] != 0 || $v['audit'] != 1 || $v['end_date'] < TODAY) {
                            continue;
                        }else if($v['rush_ltime'] > $now_time && $v['rush_kucun']>= $cart_goods[$v['goods_id'].'_0']){
                            //抢购时商品价格和库存使用抢购的
                            if($v['rush_price'] && $v['rush_kucun']){
                                $v['mall_price'] = $v['rush_price'];
                                $v['kucun'] = $v['rush_kucun'];
                            }
                            
                        }                        
                        $goods_list[$v['goods_id']] = $v;
                    }
                }
                $_cart_goods = array();
                foreach($cart_goods as $k=>$v){
                    list($gid, $vid) = explode('_', $k);
                    $gid = (int)$gid; $vid = (int)$vid;
                    $row = $value = array();
                    $row = $goods_list[$gid];
                    if($vid){
                        if($goods_value_list[$vid]['goods_id'] != $gid){
                            continue;
                        }
                        $value = $goods_value_list[$vid];
                        $total_price += $value['price'] * (int)$v;
                    }else{
                        $total_price += $row['mall_price'] * (int)$v;
                    }
                    $row['num'] = (int)$v;
                    $row['vid'] = $vid;
                    $total_num += (int)$v;
                    $row['store'] = $row['kucun'] = $value['store'];
                    if($value){
                        $row['mall_price'] = $value['price'];
                        $row['total_price'] = $row['price'] * $row['num'];
                    }else{
                        
                        $row['total_price'] = $row['mall_price'] * $row['num'];
                    }
                    if($value['photo']){
                        $row['photo'] = $value['photo'];
                    }
                    $row['value'] = $value;
                    $row = array_merge($goods_list[$gid], $row);
                    $_cart_goods[$k] = $row;
                }              
                $cart_goods = $_cart_goods;
            }
           // print_r($_cart_goods);echo 'File:',__FILE__,',Line:',__LINE__;exit;
            
            $this->assign('total_num',$total_num);
            $this->assign('total_price',$total_price);
            $this->assign('cart_goods',$cart_goods);
            
            $this->display();
        }

    // public function cart() {
    //     $order_id = (int) $_GET['order_id'];
    //     if (!empty($order_id)) {
    //         $order = D('Order')->find($order_id);
    //         if($order['user_id'] != $this->uid){
    //             $this->error("不能修改别人的订单!", U('mall/index'));
    //         }
    //         if($order['status'] != 0){
    //             $this->error("该订单不能修改!", U('mall/index'));
    //         }
    //         $order_goods = D('Ordergoods')->where(array('order_id' => $order_id))->select();
    //         $goods_ids = $nums = array();
    //         foreach ($order_goods as $key => $v) {
    //             $goods_ids[$v['goods_id']] = $v['goods_id'];
    //             $nums[$v['goods_id']] = $v['num'];
    //         }
    //         $cart_goods = D('Goods')->itemsByIds($goods_ids);
    //         $shop_ids = array();
    //         foreach ($cart_goods as $k => $val) {
    //             $cart_goods[$k]['buy_num'] = $nums[$k];
    //             $shop_ids[$val['shop_id']] = $val['shop_id'];
				// if($cart_goods[$k]['max_buy']<=0){
				// 	$cart_goods[$k]['max_buy'] = '99';
				// }
    //         }
    //         $this->assign('order_id', $order_id);
    //         $this->assign('cart_shops', D('Shop')->itemsByIds($shop_ids));
    //         $this->assign('cart_goods', $cart_goods);
    //         $this->display('change_cart');
    //     } else {
    //         $now_time = time();
    //         $cart_goods = cookie('goods');    
    //         if (empty($cart_goods)) {
    //             $this->error("亲还没有选购产品呢!", U('mall/index'));
    //         }

    //         $good_list = array();
    //         $total_num = 0;
    //         foreach($cart_goods as $k => $v){
    //             list($gid, $vid) = explode('_', $k);
    //             $gid = (int)$gid; $vid = (int)$vid;
    //             $goods_ids[$gid] = $gid;
    //             $value_ids[$vid] = $vid;
    //         }
    //         $goods_list = $goods_value_list = array();
    //         if($goods_ids){
    //             if($value_ids){
    //                 $_values_list = D('Goodsformat')->where(array('id'=>array('IN', implode(',', $value_ids))))->select();
    //                 foreach($_values_list as $v){
    //                     $goods_value_list[$v['id']] = $v;
    //                 }
    //             }
    //             if($_goods_list = D('Goods')->where(array('goods_id'=>array('IN', implode(',', $goods_ids))))->select()){
    //                 foreach($_goods_list as $v){
    //                     if ($v['closed'] != 0 || $v['audit'] != 1 || $v['end_date'] < TODAY) {
    //                         continue;
    //                     }else if($v['rush_ltime'] > $now_time && $v['rush_kucun']>= $cart_goods[$v['goods_id'].'_0']){
    //                         //抢购时商品价格和库存使用抢购的
    //                         $v['mall_price'] = $v['rush_price'];
    //                         $v['kucun'] = $v['rush_kucun'];
    //                     }
    //                     $goods_list[$v['goods_id']] = $v;
    //                 }
    //             }                
    //             $_cart_goods = array();
    //             foreach($cart_goods as $k=>$v){
    //                 list($gid, $vid) = explode('_', $k);
    //                 $gid = (int)$gid; $vid = (int)$vid;
    //                 $sku_val = $value = array();
    //                 $sku_val = $goods_list[$gid];
    //                 if($vid){
    //                     if($goods_value_list[$vid]['goods_id'] != $gid){
    //                         continue;
    //                     }
    //                     $value = $goods_value_list[$vid];
    //                     $total_price += $value['mall_price'] * (int)$v;
    //                 }else{
    //                     $total_price += $goods['mall_price'] * (int)$v;
    //                 }
    //                 $sku_val['num'] = (int)$v;
    //                 $sku_val['vid'] = $vid;
    //                 $total_num += (int)$v;
    //                 $sku_val['store'] = $sku_val['kucun'] = $value['store'];
    //                 $sku_val['mall_price'] = $value['mall_price'];
    //                 $sku_val['value'] = $value;
    //                 //$sku_val = array_merge($goods_list[$gid], $sku_val);
    //                 $_cart_goods[$k] = $sku_val;
    //             }                         
    //             $cart_goods = $_cart_goods;
    //         }                   
    //         $this->assign('total_num',$total_num);
    //         $this->assign('total_price',$total_price);
    //         $this->assign('cart_goods',$cart_goods);
    //         $this->display();
            
    //     }
    // }

    public function ajaxcart() {
        $goods = cookie('goods');
        if (!empty($goods)) {
            $goods_ids = array_keys($goods);
            $cart_goods = D('Goods')->itemsByIds($goods_ids);
            $shop_ids = array();
            foreach ($cart_goods as $k => $val) {
                $cart_goods[$k]['buy_num'] = $goods[$k];
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $this->assign('cart_shops', D('Shop')->itemsByIds($shop_ids));
            $this->assign('cart_goods', $cart_goods);
        }
        $this->display();
    }

    public function ajaxcartlist() {
        $goods = cookie('goods');
        if (!empty($goods)) {
            $goods_ids = array_keys($goods);
            $cart_goods = D('Goods')->itemsByIds($goods_ids);
            $shop_ids = array();
            foreach ($cart_goods as $k => $val) {
                $cart_goods[$k]['buy_num'] = $goods[$k];
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $this->assign('cart_shops', D('Shop')->itemsByIds($shop_ids));
            $this->assign('cart_goods', $cart_goods);
        }
        $this->display();
    }

    public function detail($goods_id) {
        $goods_id = (int) $goods_id;
        if (!$detail = D('Goods')->find($goods_id)) {
            $this->error('您访问的产品不存在！');
        }
        if ($detail['closed'] != 0 || $detail['audit'] != 1) {
            $this->error('您访问的产品不存在！');
        }

        $shop = D('Shop')->find($detail['shop_id']);
        if (!$favo = D('Shopfavorites')->where(array('user_id' => $this->uid, 'shop_id' => $shop_id))->find()) {
            $shop['favo'] = 0;
        } else {
            $shop['favo'] = 1;
        }
        $this->assign('shop', $shop);
        $shop_id = $detail['shop_id'];
        $this->assign('ex', D('Shopdetails')->find($shop_id));
        //开启cookie记录用户行为习惯，展示到“猜你喜欢”
        $cate_id = (int) $detail['cate_id'];
        $cookie = unserialize($_COOKIE['iLikegoods']); //取出cookie数组
        $cookie[] = $cate_id;
        $cookie = array_flip(array_flip($cookie));
        $cate_arr = serialize($cookie);
        cookie('iLikegoods', $cate_arr, 86400); // 指定cookie保存时间
        $like_where = array();
        $like_where['cate_id'] = array('in', $cookie);
        $like_where['audit'] = 1;
        $like_where['closed'] = 0;

        $like = D('Goods')->where($like_where)->order('rand()')->limit(5)->select();

        $this->assign('like', $like);
        $goodsdianping = D('Goodsdianping');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0, 'goods_id' => $goods_id, 'show_date' => array('ELT', TODAY));
        $count = $goodsdianping->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 5); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $goodsdianping->where($map)->order(array('dianping_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $dianping_ids = array();
        foreach ($list as $k => $val) {
            $list[$k] = $val;
            $user_ids[$val['user_id']] = $val['user_id'];
            $dianping_ids[$val['dianping_id']] = $val['dianping_id'];
        }
        if (!empty($user_ids)) {
            $this->assign('users', D('Users')->itemsByIds($user_ids));
        }
        if (!empty($dianping_ids)) {
            $this->assign('pics', D('Goodsdianpingpics')->where(array('dianping_id' => array('IN', $dianping_ids)))->select());
        }
        $viewArr = cookie('viewgoods');
        $cooarr = array('goods_id' => $goods_id, 'title' => $detail['title'], 'price' => $detail['price'], 'mall_price' => $detail['mall_price'], 'photo' => $detail['photo']);
        if (!$viewArr) {
            cookie('viewgoods', serialize($cooarr[$detail['goods_id']]));
        } else {
            $viewArr = unserialize($viewArr);
            if (count($viewArr) == 5) {
                $arr = array_pop($viewArr);
                unset($arr);
            }
            if (!isset($viewArr[$detail['goods_id']])) {
                $viewArr[$detail['goods_id']] = $cooarr;
                cookie('viewgoods', serialize($viewArr));
            }
        }
        $viewgoods = unserialize(cookie('viewgoods'));
        $viewgoods = array_reverse($viewgoods, TRUE);
        $this->assign('viewgoods', $viewgoods);
        $this->assign('totalnum', $count);
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        //分店  + 
        $maps = array('closed' => 0, 'audit' => 1, 'shop_id' => $detail['shop_id']);
        $lists = D('Shopbranch')->where($maps)->order(array('orderby' => 'asc'))->select();
        $shop_arr = array(
            'name' => '总店',
            'score' => $shop['score'],
            'score_num' => $shop['score_num'],
            'lng' => $shop['lng'],
            'lat' => $shop['lat'],
            'telephone' => $shop['tel'],
            'addr' => $shop['addr'],
        );
        if (!empty($lists)) {
            array_unshift($lists, $shop_arr);
        } else {
            $lists[] = $shop_arr;
        }
        $counts = count($lists);
        if ($counts % 5 == 0) {
            $num = $counts / 5;
        } else {
            $num = (int) ($counts / 5) + 1;
        }
        $this->assign('count', $counts);
        $this->assign('totalnums', $num);
        $this->assign('lists', $lists);
        //分店end
        $this->seodatas['shop_name'] = $shop['shop_name'];
        $this->seodatas['title'] = $detail['title'];
        D('Goods')->updateCount($goods_id, 'views');
		if($detail['max_buy']<=0){
			$detail['max_buy'] = '99';
		}
		$this->assign('now_time', time());
        $this->assign('detail', $detail);
        $this->assign('height_num', 675);
        $goods_vid = 0;
        if($goods_value_list = D('Goodsformat')->where(array('goods_id'=>$goods_id))->select()){
            $gvid = (int)$_GET['vid'];
            $goods_value = $goods_value_list[0];
            $goods_vid = $goods_value['id'];
            $format_list = D('Format')->format_goodsid($goods_id, $detail['shopcate_id']);
            $content_format_values = array(); //以content为key的数组
            foreach($goods_value_list as $v){
                if($v['id'] == $gvid){
                    $goods_value = $v;
                    $goods_vid = $gvid;
                }
                foreach(explode('-', $v['content']) as $vid){
                    $goods_value_ids[$vid] = $vid;
                }
                $content_format_values[$v['content']] = $v;
            }
            $goods_value['values'] = explode('-', $goods_value['content']);
            $format_select_value = array();
            foreach($format_list as $v){
                foreach($v['values'] as $vv){
                    if(in_array($vv['id'], $goods_value['values'])){
                        $format_select_value[$vv['format_id']] = $vv['id'];
                    }
                }
            }

            foreach($format_list as $v){
                $_is_have = false;
                $_values = array();
                $__vids = $format_select_value;
                foreach($v['values'] as $vv){                    
                    if(in_array($vv['id'], $goods_value_ids)){
                        $__vids[$vv['format_id']] = $vv['id'];
                        asort($__vids);
                        if($_val = $content_format_values[implode('-', $__vids)]){
                            $_is_have = true;
                            $vv['vid'] = $_val['id'];
                            $_values[$vv['id']] = $vv; 
                        }
                        if(in_array($vv['id'], $format_select_value)){
                            $vv['checked'] = true;
                        }
                        $_is_have = true;
                        $_values[$vv['id']] = $vv;                        
                    }
                    $v['values'] = $_values;
                }                
                if($_is_have){
                    $_format_list[$v['id']] = $v;
                }
            }
            $format_list = $_format_list; 
            $detail['value'] = $goods_value;
            if($goods_value['photo']){
                $detail['photo'] = $goods_value['photo'];
            }
            $detail['store'] = $goods_value['store'];
            $detail['price'] = $goods_value['mall_price'];
            $detail['mall_price'] = $goods_value['price'];

        }
        $detail['vid'] = $goods_vid;
        $thumb = unserialize($detail['thumb']);
        $this->assign('thumb', $thumb);
        $this->assign('detail', $detail);        
        $this->assign('goods_vid', $goods_vid);
        $this->assign('goods_value', $goods_value);
        $this->assign('format_list',$format_list);
        $this->display();
    }

    public function emptygoods() {
        cookie('viewgoods', null);
        $this->ajaxReturn(array('status' => 'success', 'msg' => '清空成功'));
    }

    public function get_like() {

        if (IS_AJAX) {

            $cookie = unserialize($_COOKIE['iLikegoods']); //取出cookie数组
            //查询我喜欢的内容
            $like_where = array();
            $like_where['cate_id'] = array('in', $cookie);

            $likes = D('Goods')->where($like_where)->order('rand()')->limit(5)->select();

            if ($likes) {
                $this->ajaxReturn(array('status' => 'success', 'likes' => $likes));
            } else {
                $this->ajaxReturn(array('status' => 'error', 'message' => '换一换失败！'));
            }
        }
    }

	function goods()
	{
		$goods = cookie('goods');

        $good_list = array();
        foreach($goods as $k => $v){
            $kk = explode('_',$k);
            $goods = D('Goods')->find($kk[0]);
            $gf = D('Goodsformat')->where(array('goods_id'=>$kk[0],'content'=>$kk[1]))->find();
            $gf['photo'] = $goods['photo'];
            $gf['num'] = $v;
            $good_list[] = $gf;
        }
        $this->assign('cart_goods',$good_list);
		$this->display();
	}

    public function cartadd() {
        $goods_id = (int) $this->_param('goods_id');
        $vid = (int) I('vid','','trim');
        $sku_id = $goods_id.'_'.$vid;

        if (empty($goods_id)) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '请选择产品'));
        }
        if (!$detail = D('Goods')->find($goods_id)) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品不存在'));
        }
        if ($detail['closed'] != 0 || $detail['audit'] != 1) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品不存在'));
        }
        if ($detail['end_date'] < TODAY) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品已经过期，暂时不能购买'));
        }
        $cart_goods = cookie('goods');

        $num = (int) $this->_get('num');
        if (empty($num) || $num <= 0) {
            $num = 1;
        }
        if ($num > 99) {
            $num = 99;
        }
        if($g_value_list = D('Goodsformat')->where(array('goods_id'=>$goods_id))->select()){
            $goods_value_list = array();
            foreach($g_value_list as $v){
                $goods_value_list[$v['id']] = $v;
            }
            if(!$goods_value = $goods_value_list[$vid]){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '你没有选择规格'));
            }else if($goods_value_list[$vid]['goods_id'] != $goods_id){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品配置信息不正确'));
            }
            $cart_goods[$sku_id] = (int)$cart_goods[$sku_id] + $num;
            if($cart_goods[$sku_id] > $goods_value['store']){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '商品库存不存'));
            }
        }else if($cart_goods[$sku_id] > $detail['kucun']){
            $this->ajaxReturn(array('status' => 'error', 'msg' => '商品库存不存'));
        }else{
			$cart_goods[$sku_id] = (int)$cart_goods[$sku_id] + $num;
		}        
        cookie('goods', $cart_goods, 604800);
        $this->ajaxReturn(array('status' => 'success', 'msg' => '添加购物车成功'));
    }

    public function cartadd2($goods_id, $vid) {
        $goods_id = (int) $this->_param('goods_id');
        $vid = (int) I('vid','','trim');
        $sku_id = $goods_id.'_'.$vid;

        if (empty($goods_id)) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '请选择产品'));
        }
        if (!$detail = D('Goods')->find($goods_id)) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品不存在'));
        }
        if ($detail['closed'] != 0 || $detail['audit'] != 1) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品不存在'));
        }
        if ($detail['end_date'] < TODAY) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品已经过期，暂时不能购买'));
        }
        $cart_goods = cookie('goods');

        $num = (int) $this->_get('num');
        if (empty($num) || $num <= 0) {
            $num = 1;
        }
        if ($num > 99) {
            $num = 99;
        }
        if($g_value_list = D('Goodsformat')->where(array('goods_id'=>$goods_id))->select()){
            $goods_value_list = array();
            foreach($g_value_list as $v){
                $goods_value_list[$v['id']] = $v;
            }
            if(!$goods_value = $goods_value_list[$vid]){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '你没有选择规格'));
            }else if($goods_value_list[$vid]['goods_id'] != $goods_id){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品配置信息不正确'));
            }
            $cart_goods[$sku_id] = (int)$cart_goods[$sku_id] + $num;
            if($cart_goods[$sku_id] > $goods_value['store']){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '商品库存不存'));
            }
        }else if($cart_goods[$sku_id] > $detail['kucun']){
            $this->ajaxReturn(array('status' => 'error', 'msg' => '商品库存不存'));
        }else{
            $cart_goods[$sku_id] = (int)$cart_goods[$sku_id] + $num;
        }        
        cookie('goods', $cart_goods, 604800);
        $this->ajaxReturn(array('status' => 'success', 'msg' => '加入购物车成功,正在跳转到购物车', 'url' => U('mall/cart')));
    }

    public function neworder() {
        $goods = $this->_get('goods');
        $goods = explode(',', $goods);
        if (empty($goods)) {
            $this->error('亲购买点吧');
        }
        $datas = array();
        foreach ($goods as $val) {
            $good = explode('-', $val);
            $good[1] = (int) $good[1];
            if (empty($good[0]) || empty($good[1])) {
                $this->error('亲购买点吧');
            }
            if ($good[1] > 99 || $good[1] < 0) {
                $this->error('本店不支持批发');
            }
            $datas[$good[0]] = $good[1];
        }
        cookie('goods', $datas, 604800);
        header("Location:" . U('mall/cart'));
        die;
    }

    public function order_change() {
        if (empty($this->uid)) {
            $this->ajaxReturn(array('status' => 'login'));
        }
        $order_id = (int) $_POST['order_id'];
        $order = D('Order')->find($order_id);
        if($order['user_id'] != $this->uid){
            $this->ajaxReturn(array('status' => 'error', 'msg' => '不能修改别人的订单'));
        }
        if($order['status'] != 0){
            $this->error("该订单不能修改!", U('mall/index'));
        }
        $num = $this->_post('num', false);
        $goods_ids = array();
        foreach ($num as $k => $val) {
            $val = (int) $val;
            if (empty($val)) {
                unset($num[$k]);
            } elseif ($val < 1 || $val > 99) {
                unset($num[$k]);
            } else {
                $goods_ids[$k] = (int) $k;
            }
        }
        if (empty($goods_ids))
            $this->ajaxReturn(array('status' => 'error', 'msg' => '很抱歉请填写正确的购买数量'));
        $goods = D('Goods')->itemsByIds($goods_ids);
        foreach ($goods as $key => $val) {
            if ($val['closed'] != 0 || $val['audit'] != 1 || $val['end_date'] < TODAY) {
                unset($goods[$key]);
            }
        }
        if (empty($goods)) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '很抱歉，您提交的产品暂时不能购买！'));
        }
        $tprice = 0;
        $ip = get_client_ip();
        $ordergoods = $total_price = array();
        $can_use_integral = 0;
        foreach ($goods as $val) {
            $price = $val['mall_price'] * $num[$val['goods_id']];
            $can_use_integral+= $val['use_integral'] * $num[$val['goods_id']];
            $js_price = $val['settlement_price'] * $num[$val['goods_id']];
            $tprice+= $price;
            $ordergoods = array(
                'num' => $num[$val['goods_id']],
                'price' => $val['mall_price'],
                'total_price' => $price,
                'js_price' => $js_price,
                'update_time' => NOW_TIME,
                'update_ip' => $ip,
            );
            D('Ordergoods')->where(array('order_id' => $order_id, 'goods_id' => $val['goods_id']))->setField($ordergoods); //忽略报错
        }
        if(false !== D('Order')->save(array('order_id'=>$order_id,'can_use_integral'=>$can_use_integral,'total_price'=>$tprice,'update_time'=>NOW_TIME,'update_ip'=>$ip))){
            $this->ajaxReturn(array('status' => 'success', 'msg' => '成功修改订单，正在跳转到支付页面','url'=>U('mall/pay', array('order_id' => $order_id))));
        }else{
            $this->ajaxReturn(array('status' => 'error', 'msg' => '修改订单失败'));
        }
        
    }

    
    
    public function order() {
        if (empty($this->uid)) {
            $this->ajaxLogin();
        }
        $num = $this->_post('num', false);
        $vids = $goods_ids = array();
        $now_time = time();
        $cart_goods = $goods_ids = $value_ids = array();
        foreach ($num as $k => $val) {
            if(($val = (int) $val) && ($val >0) && ($val < 100)){
                $cart_goods[$k] = $val;
                list($gid, $vid) = explode('_', $k);
                $gid = (int)$gid; $vid = (int)$vid;
                $goods_ids[$gid] = $gid;
                $value_ids[$vid] = $vid;
                $cart_goods[$k] = $val;
            }
        }
        if($value_ids){
            if($items = D('Goodsformat')->where(array('id'=>array('IN', implode(',', $value_ids))))->select()){
                foreach($items as $v){
                    $value_list[$v['id']] = $v;
                }
            }
        }

        if($goods_ids){
            if($items = D('Goods')->where(array('goods_id'=>array('IN', implode(',', $goods_ids))))->select()){                
                foreach($items as $v){
                    if ($v['closed'] != 0 || $v['audit'] != 1 || $v['end_date'] < TODAY) {
                        continue;
                    }else if($v['rush_ltime'] > $now_time && $v['rush_kucun']>= $cart_goods[$v['goods_id'].'_0']){
                        //抢购时商品价格和库存使用抢购的
                        $v['mall_price'] = $v['rush_price'];
                        $v['kucun'] = $v['rush_kucun'];
                    }
                    $goods_list[$v['goods_id']] = $v;
                }
            }
        }
        $_cart_goods = $order_goods = array();
        foreach($cart_goods as $k=>$v){
            list($gid, $vid) = explode('_', $k);
            $gid = (int)$gid; $vid = (int)$vid;
            $val = $value = array();
            if($val = $goods_list[$gid]){
                if($vid){
                    if($value_list[$vid]['goods_id'] != $gid){
                        continue;
                    }
                    $value = $value_list[$vid];
                    $val['store'] = $value['store'];
					if($value['mall_price']>$value['price']){
						$mall_price = $value['price'];
						$value['price'] = $value['mall_price'];
						$value['mall_price']=$mall_price;
					}
                    $val['mall_price'] = $value['mall_price'];
                    $total_price += $value['mall_price'] * (int)$v;
                }else{
					if($goods['mall_price']>$goods['price']){
						$mall_price = $goods['price'];
						$goods['price'] = $goods['mall_price'];
						$goods['mall_price']=$mall_price;
					}
                    $total_price += $goods['mall_price'] * (int)$v;
                }
                if($val['store'] < $val['num']){
                    $this->baoError($val['title'].'的库存不足！');
                }
                $val['num'] = (int)$v;
                $val['vid'] = $vid;
                $total_num += (int)$v;
                $val['value'] = $value;
                $val = array_merge($goods_list[$gid], $val);
                $_cart_goods[$k] = $val;
                $order_goods[$val['shop_id']]['total_price'] += $val['mall_price'] * $val['num'];
                $order_goods[$val['shop_id']]['goods'][$k] = $val;
            }
        }        
        if (empty($_cart_goods)){
            $this->baoError('订单错误:没有选择要购买的商品！');
        }        
        $order_ids = array();        
        foreach($order_goods as $sid=>$val){
            $a = array('shop_id'=>$sid, 'user_id'=>$this->uid);
            $a['create_time'] = time();
            $a['create_ip'] = get_client_ip();
            $a{'total_price'} = $val['total_price'];            
            if($order_id = D('Order')->add($a)){ //后期可以考虑加上事务回滚功能
                $order_ids[$order_id] = $order_id;
                foreach($val['goods'] as $k=>$v){
                    $b = array('order_id'=>$order_id, 'shop_id'=>$sid, 'goods_id'=>$v['goods_id'], 'vid'=>$v['vid']);
                    $b['num'] = $v['num'];
					if($v['mall_price']>$v['price']){
						$mall_price = $v['price'];
						$v['price'] = $v['mall_price'];
						$v['mall_price']=$mall_price;
					}
                    $b['price'] = $v['mall_price'];
                    $b['total_price'] = $v['mall_price'] * $v['num'];
                    $b['spec_name'] = $v['value']['format_title'];
                    $b['title'] = $v['title'];
                    if(!$photo = $v['value']['photo']){
                        $photo = $v['photo'];
                    }
                    $b['photo'] = $photo;
                    $b['create_time'] = time();
                    $b['create_ip'] = get_client_ip();                    
                    if(D('Ordergoods')->add($b)){ //扣除库存                        
                        if($v['vid'] && $v['rush_ltime'] > $now_time && $v['rush_kucun']>= $v['num']){ //抢购时减去抢购库存
                            D('Goods')->updateCount($v['goods_id'], 'rush_kucun', -$v['num']);
                        }
                        D('Goods')->updateCount($v['goods_id'], 'kucun', -$v['num']);
                    }
                }                
            }  
        }
        cookie('goods', null);
        if (count($order_ids) > 1) {//如果大于1 那么形成一个 支付记录 来合并付款！如果其他条件可以直接去付款
            $need_pay = D('Order')->useIntegral($this->uid,$order_ids);
            $logs = array(
                'type' => 'goods',
                'user_id' => $this->uid,
                'order_id' => 0,
                'order_ids' => join(',', $order_ids),
                'code' => '',
                'need_pay' => $need_pay, //使用积分后的支付金额
                'create_time' => NOW_TIME,
                'create_ip' => get_client_ip(),
                'is_paid' => 0
            );
            $logs['log_id'] = D('Paymentlogs')->add($logs);
            $this->baoJump(U('mall/paycode', array('log_id' => $logs['log_id'])));
        } else {
            $this->baoJump(U('mall/pay', array('order_id' => $order_id)));
        }
    }

    public function change_addr() {
        if (IS_AJAX) {
            $order_id = (int) $_POST['order_id'];
            $addr_id = (int) $_POST['addr_id'];
            $data = array(
                'order_id' => $order_id,
                'addr_id' => $addr_id,
            );
            if (false !== D('Order')->save($data)) {
                $thisaddr = D('Useraddr')->find($addr_id);
                $addrs = D('Useraddr')->where(array('user_id' => $this->uid, 'addr_id' => array('NEQ', $addr_id)))->order('addr_id DESC')->limit(0, 4)->select();
                if (empty($addrs)) {
                    $addrs[] = $thisaddr;
                } else {
                    array_unshift($addrs, $thisaddr);
                }
                $addr_array = array();
                foreach ($addrs as $k => $val) {
                    $addr_array[$k]['addr_id'] = $val['addr_id'];
                    $addr_array[$k]['city_id'] = $val['city_id'];
                    $addr_array[$k]['area_id'] = $val['area_id'];
                    $addr_array[$k]['business_id'] = $val['business_id'];
                    $addr_array[$k]['city'] = $this->citys[$val['city_id']]['name'];
                    $addr_array[$k]['area'] = $this->areas[$val['area_id']]['area_name'];
                    $addr_array[$k]['bizs'] = $this->bizs[$val['business_id']]['business_name'];
                    $addr_array[$k]['name'] = $val['name'];
                    $addr_array[$k]['addr'] = $val['addr'];
                    $addr_array[$k]['mobile'] = $val['mobile'];
                }
                $this->ajaxReturn(array('status' => 'success', 'msg' => '更换成功', 'res' => $addr_array));
            } else {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '更换失败'));
            }
        }
    }

    public function paycode() {
        $log_id = (int) $this->_get('log_id');
        if (empty($log_id)) {
            $this->error('没有有效支付记录！');
        }
        if (!$detail = D('Paymentlogs')->find($log_id)) {
            $this->error('没有有效的支付记录！');
        }
        if ($detail['is_paid'] != 0 || empty($detail['order_ids']) || !empty($detail['order_id']) || empty($detail['need_pay'])) {
            $this->error('没有有效的支付记录！');
        }
        $order_ids = explode(',', $detail['order_ids']);
        $ordergood = D('Ordergoods')->where(array('order_id' => array('IN', $order_ids)))->select();

        $goods_id = $shop_ids = array();

        foreach ($ordergood as $k => $val) {
            $goods_id[$val['goods_id']] = $val['goods_id'];
            $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
        $this->assign('goods', D('Goods')->itemsByIds($goods_id));
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));

        $this->assign('ordergoods', $ordergood);
        if (!empty($order['addr_id'])) {
            $thisaddr = D('Useraddr')->find($order['addr_id']);
            $addrs = D('Useraddr')->where(array('user_id' => $this->uid, 'addr_id' => array('NEQ', $order['addr_id'])))->order('addr_id DESC')->limit(0, 4)->select();
            if (empty($addrs)) {
                $addrs[] = $thisaddr;
            } else {
                array_unshift($addrs, $thisaddr);
            }
        } else {
            $addrs = D('Useraddr')->where(array('user_id' => $this->uid))->order(array('is_default' => 'desc', 'addr_id' => 'desc'))->limit(0, 5)->select();
        }
        $this->assign('useraddr', $addrs);
        $this->assign('payment', D('Payment')->getPayments());
        $this->assign('logs', $detail);
        $this->display();
    }

    public function pay() {
        if (empty($this->uid)) {
            header("Location:" . U('passport/login'));
            die;
        }
        $order_id = (int) $this->_get('order_id');
        $order = D('Order')->find($order_id);
        if (empty($order) || $order['status'] != 0 || $order['user_id'] != $this->uid) {
            $this->error('该订单不存在');
            die;
        }
        $ordergood = D('Ordergoods')->where(array('order_id' => $order_id))->select();

        $goods_id = $shop_ids = array();
        foreach ($ordergood as $k => $val) {
            $goods_id[$val['goods_id']] = $val['goods_id'];
            $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
        $this->assign('goods', D('Goods')->itemsByIds($goods_id));
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('ordergoods', $ordergood);
        if (!empty($order['addr_id'])) {
            $thisaddr = D('Useraddr')->find($order['addr_id']);
            $addrs = D('Useraddr')->where(array('user_id' => $this->uid, 'addr_id' => array('NEQ', $order['addr_id'])))->order('addr_id DESC')->limit(0, 4)->select();
            if (empty($addrs)) {
                $addrs[] = $thisaddr;
            } else {
                array_unshift($addrs, $thisaddr);
            }
        } else {
            $addrs = D('Useraddr')->where(array('user_id' => $this->uid))->order(array('is_default' => 'desc', 'addr_id' => 'desc'))->limit(0, 5)->select();
        }
        $this->assign('useraddr', $addrs);
        $this->assign('order', $order);
        $this->assign('payment', D('Payment')->getPayments());
        $this->display();
    }

    public function paycode2() { //这里是因为原来的是按订单付，这里是合并付款逻辑部分 
        $log_id = (int) $this->_get('log_id');
        if (empty($log_id)) {
            $this->error('没有有效支付记录！');
        }
        if (!$detail = D('Paymentlogs')->find($log_id)) {
            $this->error('没有有效的支付记录！');
        }
        if ($detail['is_paid'] != 0 || empty($detail['order_ids']) || !empty($detail['order_id']) || empty($detail['need_pay'])) {
            $this->error('没有有效的支付记录！');
        }
        $order_ids = explode(',', $detail['order_ids']);
        $addr_id = (int) $this->_post('addr_id');
        if (empty($addr_id)) {
            $this->baoError('请选择一个要配送的地址！');
        }
        D('Order')->where(array('order_id' => array('IN', $order_ids)))->save(array('addr_id' => $addr_id));
        if (!$code = $this->_post('code')) {
            $this->baoError('请选择支付方式！');
        }
        if ($code == 'wait') { //如果是货到付款
            D('Order')->save(array(
                'is_daofu' => 1,
                    ), array('where' => array('order_id' => array('IN', $order_ids))));
            D('Ordergoods')->save(array(
                'is_daofu' => 1,
                    ), array('where' => array(
                    'order_id' => array('IN', $order_ids)
            )));
            D('Sms')->mallTZshop($order_ids);
             D('Payment')->mallSold($order_ids);
            D('Payment')->mallPeisong(array($order_ids),1);

            $this->baoSuccess('恭喜您下单成功！', U('member/order/goods'));
        } else {
            $payment = D('Payment')->checkPayment($code);
            if (empty($payment)) {
                $this->baoError('该支付方式不存在');
            }
            $detail['code'] = $code;
            D('Paymentlogs')->save($detail);
            $this->baoJump(U('mall/combine', array('log_id' => $detail['log_id'])));
        }
    }

    public function combine() {
        if (empty($this->uid)) {
            header("Location:" . U('passport/login'));
            die;
        }
        $log_id = (int) $this->_get('log_id');
        if (!$detail = D('Paymentlogs')->find($log_id)) {
            $this->error('没有有效的支付记录！');
        }
        if ($detail['is_paid'] != 0 || empty($detail['order_ids']) || !empty($detail['order_id']) || empty($detail['need_pay'])) {
            $this->error('没有有效的支付记录！');
        }
        $url =  U('mall/paycode',array('order_id'=>$logs['order_id']));
        $this->assign('url',$url);
        $this->assign('button', D('Payment')->getCode($detail));
        $this->assign('logs', $detail);
        $this->assign('types', D('Payment')->getTypes());
        $this->assign('paytype', D('Payment')->getPayments());
        
        $this->display();
    }

    public function pay2() {
        if (empty($this->uid)) {
            $this->ajaxLogin();
        }
        $order_id = (int) $this->_get('order_id');
        $order = D('Order')->find($order_id);
        if (empty($order) || $order['status'] != 0 || $order['user_id'] != $this->uid) {
            $this->baoError('该订单不存在');
            die;
        }
        $addr_id = (int) $this->_post('addr_id');
        if (empty($addr_id)) {
            $this->baoError('请选择一个要配送的地址！');
        }
        D('Order')->save(array('addr_id' => $addr_id, 'order_id' => $order_id));
        if (!$code = $this->_post('code')) {
            $this->baoError('请选择支付方式！');
        }
        $ua = D('UserAddr');
        $uaddr = $ua -> where(array('addr_id'=>$order['addr_id'])) -> find();
        if ($code == 'wait') { //如果是货到付款
            D('Order')->save(array(
                'order_id' => $order_id,
                'is_daofu' => 1,
            ));
            D('Ordergoods')->save(array(
                'is_daofu' => 1,
                    ), array('where' => array(
                    'order_id' => $order_id
            )));
            D('Payment')->mallSold($order_id);
            D('Payment')->mallPeisong(array($order_id),1);
            $goods_ids   = D('Ordergoods')->where("order_id=".$order['order_id'])->getField('goods_id',true);
            $goods_ids   = implode(',', $goods_ids);
            $map         = array('goods_id'=>array('in',$goods_ids));
            $goods_name  = D('Goods')->where($map)->getField('title',true);
            $goods_name  = implode(',', $goods_name);
            //====================微信支付通知===========================
             
            include_once "Kintime/Lib/Net/Wxmesg.class.php";
            $_data_pay = array(
                'url'       =>  "http://".$_SERVER['HTTP_HOST']."/mcenter/goods/index/aready/".$order['order_id'].".html",
                'topcolor'  =>  '#F55555',
                'first'     =>  '亲,您的货到付款订单我们已经收到,我们马上发货！',
                'remark'    =>  '更多信息,请登录http://'.$_SERVER['HTTP_HOST'].'！再次感谢您的惠顾！',
                'money'     =>  round($order['total_price']/100,2).'元',
                'orderInfo' =>  $goods_name,
                'addr'      =>  $uaddr['addr'],
                'orderNum'  =>  $order['order_id']
            );
            $pay_data = Wxmesg::pay($_data_pay);
            $return   = Wxmesg::net($this->uid, 'OPENTM201490088', $pay_data);

            //====================微信支付通知==============================

            $this->baoSuccess('恭喜您下单成功！', U('member/order/goods'));
        } else {
            $payment = D('Payment')->checkPayment($code);
            if (empty($payment)) {
                $this->baoError('该支付方式不存在');
            }
            
            $logs = D('Paymentlogs')->getLogsByOrderId('goods', $order_id);
            $need_pay = D('Order')->useIntegral($this->uid,array($order_id));
            if (empty($logs)) {
                $logs = array(
                    'type' => 'goods',
                    'user_id' => $this->uid,
                    'order_id' => $order_id,
                    'code' => $code,
                    'need_pay' => $need_pay,
                    'create_time' => NOW_TIME,
                    'create_ip' => get_client_ip(),
                    'is_paid' => 0
                );
                $logs['log_id'] = D('Paymentlogs')->add($logs);
            } else {
                $logs['need_pay'] = $need_pay;
                $logs['code'] = $code;
                D('Paymentlogs')->save($logs);
            }

            $goods_ids   = D('Ordergoods')->where("order_id=".$order['order_id'])->getField('goods_id',true);
            $goods_ids   = implode(',', $goods_ids);
            $map         = array('goods_id'=>array('in',$goods_ids));
            $goods_name  = D('Goods')->where($map)->getField('title',true);
            $goods_name  = implode(',', $goods_name);
            //====================微信支付通知===========================
             
            include_once "Kintime/Lib/Net/Wxmesg.class.php";
            $_data_pay = array(
                'url'       =>  "http://".$_SERVER['HTTP_HOST']."/mcenter/goods/index/aready/".$order['order_id'].".html",
                'topcolor'  =>  '#F55555',
                'first'     =>  '亲,您的在线支付订单已创建,请尽快支付！',
                'remark'    =>  '更多信息,请登录http://'.$_SERVER['HTTP_HOST'].'！再次感谢您的惠顾！',
                'money'     =>  round($order['total_price']/100,2).'元',
                'orderInfo' =>  $goods_name,
                'addr'      =>  $uaddr['addr'],
                'orderNum'  =>  $order['order_id']
            );
            $pay_data = Wxmesg::pay($_data_pay);
            $return   = Wxmesg::net($this->uid, 'OPENTM201490088', $pay_data);

            //====================微信支付通知==============================

             $this->baoJump(U('payment/payment', array('log_id' => $logs['log_id'])));
        }
    }

}