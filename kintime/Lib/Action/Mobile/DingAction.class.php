<?php



class DingAction extends CommonAction {

	protected $cart = array();

   public function _initialize() {
        parent::_initialize();
		$this->assign('cfg',D('Shopding')->getCfg());
		$this->assign('room',D('Shopding')->getType());	
        // $this->cart = $this->getcart();	
        $this->assign('dingtypes',D('Shopding')->getDingType());
        $this->assign('price_list',D('Shopding')->getPrice());

    }

   public function index() {
        $linkArr = array();
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        $linkArr['keyword'] = $keyword;

        $type_id = $this->_param('type_id','htmlspecialchars');
        $this->assign('type_id', $type_id);
        $linkArr['type_id'] = $type_id;
        
        $order = $this->_param('order','htmlspecialchars');
        $this->assign('order', $order);
        $linkArr['order'] = $order;

        $area_id = (int) $this->_param('area_id');
        $this->assign('area_id', $area_id);
        $linkArr['area_id'] = $area_id;

        $business_id = (int) $this->_param('business_id');
        $this->assign('business_id', $business_id);
        $linkArr['business_id'] = $business_id;
        
        $this->assign('nextpage', LinkTo('ding/loaddata',$linkArr,array('t' => NOW_TIME,'p' => '0000')));
        $this->assign('linkArr',$linkArr);
        $this->mobile_title = '订座列表';
        $this->display(); // 输出模板 
    }

	public function loaddata()
	{
		$shopding = D('Shopding');
		import('ORG.Util.Page');
        $map = array('audit' => 1, 'closed' => 0, 'city_id' => $this->city_id);
		$linkArr = array();
		if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['shop_name'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
            $linkArr['keywrod'] = $keyword;
        }
        $type_id = (int) $this->_param('type_id');
        if($type_id){
            $linkArr['type_id'] = $type_id;
            $this->assign('type_id', $type_id);
            $result = D('Shopdingattr')->where(array('type_id'=>$type_id))->select();
            $shop_ids = array();
            foreach($result as $k=>$val){
                $shop_ids[] = $val['shop_id'];
            }
            if($shop_ids){
                $map['shop_id'] = array('IN',$shop_ids);
            }
        }
        $area_id = (int) $this->_param('area_id');
        if ($area_id) {
            $map['area_id'] = $area_id;
            $linkArr['area_id'] = $area_id;
        }
        $this->assign('area_id', $area_id);
        $business_id = (int) $this->_param('business_id');
        if ($business_id) {
            $map['business_id'] = $business_id;
            $linkArr['business_id'] = $business_id;
        }
        $this->assign('business_id', $business_id);
        $order = $this->_param('order', 'htmlspecialchars');
        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if (empty($lat) || empty($lng)) {
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        $orderby = '';
        switch ($order) {
            case 'd':
                $orderby = " (ABS(lng - '{$lng}') +  ABS(lat - '{$lat}') ) asc ";
                break;
            case 'p':
                $orderby = array('price' => 'asc');
                break;
            case 's':
                $orderby = array('orders' => 'desc');
                break;
            default:
                $orderby = array('orders' => 'desc','score'=>'desc', 'price' => 'asc');
                break;
        }
        $this->assign('order', $order);
        $count = $shopding->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $shopding->where($map)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $k => $val) {
            $list[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);
        }
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
	}

	public function detail($shop_id)
	{
		$shopding = D('Shopding');
        if(!$shop_id = (int)$shop_id){
            $this->error('该商家不存在');
        }elseif(!$detail = $shopding->find($shop_id)){
			$this->error('该商家不存在');
        }elseif($detail['audit'] !=1||$detail['closed']!=0){
            $this->error('该商家已删除或未审核');
        }else{
            $lat = addslashes(cookie('lat'));
            $lng = addslashes(cookie('lng'));
            if (empty($lat) || empty($lng)) {
                $lat = $this->city['lat'];
                $lng = $this->city['lng'];
            }
            $shop = D('Shop')->find($shop_id);
            $detail['d'] = getDistance($lat, $lng, $shop['lat'], $shop['lng']);
            
            //评论
            $dianping = D('Shopdingdianping');
            import('ORG.Util.Page'); // 导入分页类
            $map = array('closed' => 0, 'shop_id' => $shop_id);
            $list = $dianping->where($map)->order(array('order_id' => 'desc'))->limit(2)->select();
            $user_ids = $order_ids = array();
            foreach ($list as $k => $val) {
                $user_ids[$val['user_id']] = $val['user_id'];
                $order_ids[$val['order_id']] = $val['order_id']; 
            }
            if (!empty($user_ids)) {
                $this->assign('users', D('Users')->itemsByIds($user_ids));
            }
            if (!empty($order_ids)) {
                $this->assign('pics', D('Shopdingdianpingpic')->where(array('order_id' => array('IN', $order_ids)))->select());
            }
            //高于同行
            $less_count = $shopding->where(array('audit'=>1,'closed'=>0,'score'=>array('ELT',$detail['score'])))->count();
            $total_count = $shopding->where(array('audit'=>1,'closed'=>0))->count();
            $high_to = round(($less_count/$total_count)*100,2);
            $this->assign('high_to',$high_to);
            
            $this->assign('list', $list); // 评价赋值数据集
            $this->assign('shop', $shop); // 商家美食资料赋值数据集
            $this->assign('ding_date',htmlspecialchars($_COOKIE['ding_date'])); 
            $this->assign('ding_num',htmlspecialchars($_COOKIE['ding_num'])); 
            $this->assign('ding_time',htmlspecialchars($_COOKIE['ding_time'])); 
            $this->assign('ding_type',htmlspecialchars($_COOKIE['ding_type'])); 
			$this->assign('detail',$detail);
            
            $this->display();
		}
		
	}

    public function ding($shop_id) {
		$shopding = D('Shopding');
        if(!$shop_id = (int)$shop_id){
            $this->error('该商家不存在');
        }elseif(!$detail = $shopding->find($shop_id)){
			$this->error('该商家不存在');
        }elseif($detail['audit'] !=1||$detail['closed']!=0){
            $this->error('该商家已删除或未审核');
        }else{
            $this->assign('note',htmlspecialchars($_COOKIE['note'])); 
            $this->assign('name',htmlspecialchars($_COOKIE['name'])); 
            $this->assign('mobile',htmlspecialchars($_COOKIE['mobile'])); 
            $this->assign('sex',htmlspecialchars($_COOKIE['sex'])); 
            $this->assign('ding_date',htmlspecialchars($_COOKIE['ding_date'])); 
            $this->assign('ding_num',htmlspecialchars($_COOKIE['ding_num'])); 
            $this->assign('ding_time',htmlspecialchars($_COOKIE['ding_time'])); 
            $this->assign('ding_type',htmlspecialchars($_COOKIE['ding_type'])); 
            $dingmenus = $this->_getCartGoods($shop_id);
            $this->assign('dingmenus',$dingmenus);
            $this->assign('shop', D('Shop')->find($shop_id)); // 商家美食资料赋值数据集
			$this->assign('detail',$detail);
            $this->display(); 
        }
    }
    
    
    public function menu($shop_id=0,$table_id=0,$order_id=0){	
        if($order_id != 0){
            $shop_id = D('Shopdingorder')->find($order_id)['shop_id'];
            $this->assign('order_id',$order_id);
        }	
        $shopding = D('Shopding');
        $menu = D('Shopdingmenu');
        $table_id = (int)$table_id;
        if(!$shop_id = (int)$shop_id){
            $this->error('该商家不存在');
        }elseif(!$detail = $shopding->find($shop_id)){
			$this->error('该商家不存在');
        }elseif($detail['audit'] !=1||$detail['closed']!=0){
            $this->error('该商家已删除或未审核');
        }elseif($detail['table_num'] < $table_id){
            $this->error('该桌号不存在');
        }else{

            $list = $menu->where(array('shop_id'=>$shop_id,'closed'=>0,'audit'=>1))->select();
            $this->assign('menucates',D('Shopdingcate')->where(array('shop_id'=>$shop_id))->select());
            
            //购物车
            $dingmenus = $this->_getCartGoods($shop_id);
            $total_money = "";
            $cart_num = "";
            $carts = array();
            foreach ($dingmenus as $k => $val) {
                $total_money += $val['total_price'];
                $cart_num += $val['cart_num'];
            }
            foreach($list as $k=>$val){
                foreach($dingmenus as $kk=>$v){
                    if($v['menu_id'] == $val['menu_id']){
                        $list[$k]['cart_num'] = $v['cart_num'];
                    }
                }
            }
            $this->assign('shop', D('Shop')->find($shop_id)); // 赋值数据集
            $this->assign('total_money', $total_money);
            $this->assign('cart_num', $cart_num);
            $this->assign('dingmenus', $dingmenus);
            $this->assign('detail',$detail);
            $this->assign('shop_id',$shop_id);
            $this->assign('table_id',$table_id);
            $this->assign('list', $list); // 赋值数据集
            $this->display();
        }
	}
    public function get_cart(){
        if (IS_AJAX) {
            $shop_id = (int) $this->_param('shop_id');
            $dingmenus = $this->_getCartGoods($shop_id);
            if ($dingmenus) {
                $this->ajaxReturn(array('status' => 'success', 'dingmenus' => $dingmenus));
            } else {
                $this->ajaxReturn(array('status' => 'error'));
            }
        }
    }

    public function orderCreate($shop_id){
        $shopding = D('Shopding');
        if (empty($this->uid)) {
            $this->ajaxReturn(array('status'=>'login'));
        }
		if (!$shop_id = (int)$shop_id) {
            $this->ajaxReturn(array('status'=>'error','msg'=>'该商家不存在'));
        }
        if (!$detail = $shopding->find($shop_id)) {
            $this->ajaxReturn(array('status'=>'error','msg'=>'该商家不存在'));
        }
        if ($detail['audit'] != 1||$detail['closed']!=0) {
            $this->ajaxReturn(array('status'=>'error','msg'=>'商家已删除或未审核'));
        }
		$ding_date = htmlspecialchars($this->_param('ding_date'));
        $ding_time = htmlspecialchars($this->_param('ding_time'));

		$is_open = $shopding->get_time($shop_id);
		if (empty($ding_time)) { 
            $this->ajaxReturn(array('status'=>'error','msg'=>'请选择时间'));
        }else if($ding_date < TODAY){
			$this->ajaxReturn(array('status'=>'error','msg'=>'预约日期已过,请重新选择日期'));
		}else if($ding_date == TODAY && !(in_array($ding_time,$is_open))){
			$this->ajaxReturn(array('status'=>'error','msg'=>'商家已经打烊或者选择时间不正确'));
		}
        $ding_num = htmlspecialchars($this->_param('ding_num'));
        if(!$ding_num){
            $this->ajaxReturn(array('status'=>'error','msg'=>'订座人数不能为空'));
        }
        $name = htmlspecialchars($this->_param('name'));
        if(!$name){
            $this->ajaxReturn(array('status'=>'error','msg'=>'联系人不能为空'));
        }
        $mobile = htmlspecialchars($this->_param('mobile'));
        if(!$mobile){
            $this->ajaxReturn(array('status'=>'error','msg'=>'联系手机号不能为空'));
        }
        $sex = htmlspecialchars($this->_param('sex'));
        $note = htmlspecialchars($this->_param('note'));
        //订单总额
        $data = array(
            'shop_id'   => $shop_id,
            'ding_date' => $ding_date,
            'ding_time' => $ding_time,
            'ding_num'  => $ding_num,
            'ding_type' => 1,
            'name'      => $name,
            'user_id'   => $this->uid,
            'mobile'    => $mobile,
            'note'      => $note,
            'sex'       => $sex,
            'create_time'=>NOW_TIME,
            'create_ip' =>  get_client_ip(),
        );
        if($order_id = D('Shopdingorder')->add($data)){
            D('Shopding')->updateCount($shop_id,'orders');
            cookie('ding_date', null);
            cookie('ding_time', null);
            cookie('ding_num', null);
            cookie('ding_type', null);
            cookie('ding_'.$shop_id, null);
            cookie('note',null);
            cookie('sex',null);
            cookie('mobile',null);
            cookie('name',null);
            $this->ajaxReturn(array('status'=>'success','msg'=>'下单成功！去选菜','order_id'=>$order_id));
        }else{
            $this->ajaxReturn(array('status'=>'error','msg'=>'创建订单失败'));
        }
    }
    
    
    private function _getCartGoods($shop_id) {
        $cart = (array)json_decode($_COOKIE['elemenu']);
        $carts = array();
        foreach($cart as $kk=>$vv){
            foreach($vv as $key=>$v){
                $carts[$kk][$key] = (array)$v;
            }
        }
        $ids = $nums = array();
        foreach($carts[$shop_id] as $k=>$val){
            $ids[$val['product_id']] = $val['product_id'];
            $nums[$val['product_id']] = $val['num'];
        }
        $dingproducts = D('Shopdingmenu')->itemsByIds($ids);
        foreach ($dingproducts as $k => $val) {
            $dingproducts[$k]['cart_num'] = $nums[$val['menu_id']];
            $dingproducts[$k]['total_price'] = $nums[$val['menu_id']] * $val['ding_price'];
        }
        // var_dump($dingproducts);
        return $dingproducts;
    }
    
	public function dianping() {
        $shop_id = (int) $this->_get('shop_id');
        if (!$detail = D('Shop')->find($shop_id)) {
            $this->error('没有该商家');
            die;
        }
        if ($detail['closed']) {
            $this->error('该商家已经被删除');
            die;
        }
        $this->assign('detail', $detail);
        $this->mobile_title = '商家点评';
        $this->display();
    }

	public function dianpingloading() {
        $shop_id = (int) $this->_get('shop_id');
        if (!$detail = D('Shop')->find($shop_id)) {
            die('0');
        }
        if ($detail['closed']) {
            die('0');
        }
        $Shopdianping = D('Shopdingdianping');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0, 'shop_id' => $shop_id);
        $count = $Shopdianping->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 25); // 实例化分页类 传入总记录数和每页显示的记录数

        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }

        $show = $Page->show(); // 分页显示输出
        $list = $Shopdianping->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$user_ids = $order_ids = array();
		foreach ($list as $k => $val) {
			$list[$k] = $val;
			$user_ids[$val['user_id']] = $val['user_id'];
			$order_ids[$val['order_id']] = $val['order_id'];
		}
		if (!empty($user_ids)) {
			$this->assign('users', D('Users')->itemsByIds($user_ids));
		}
		if (!empty($order_ids)) {
			$this->assign('pics', D('Shopdingdianpingpic')->where(array('order_id' => array('IN', $order_ids)))->select());
		}
        $this->assign('totalnum', $count);
        $this->assign('list', $list); // 赋值数据集
        $this->assign('detail', $detail);
        $this->display();
    }

	

	public function load()
	{
		$menu = D('Shopdingmenu');
		$shop_id = (int) $this->_get('shop_id');
		$yuyue_id = (int) $this->_get('yuyue_id');
		$cat = (int) $this->_get('cat');

        $detail = D('shop')->where('audit=1,closed=0,is_ding=1,city_id='.$this->city_id)->find($shop_id);
        $dingmenu = D('shopdingmenu');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0, 'shop_id' => $shop_id);
       
        $cat = (int) $this->_param('cat');
        if ($cat) {
            $map['cate_id'] = $cat;
        }
        $count = $dingmenu->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $dingmenu->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('detail', $detail);
		$this->assign('yuyue_id', $yuyue_id);
        $this->assign('cates', $menu->get_cate($shop_id));
        $this->assign('shop', $shop);
        if (!empty($this->cart)) {
            $ids = array_keys($this->cart);
            $total = array(
                'num' => 0, 'money' => 0
            );
            $menus = $dingmenu->itemsByIds($ids);
            foreach ($menus as $k => $val) {
                $menus[$k]['cart_num'] = $this->cart[$val['menu_id']];
                $total['num'] += $this->cart[$val['menu_id']];
                $total['money'] +=( $this->cart[$val['menu_id']] * $val['ding_price']);
            }
            $this->assign('total', $total);
            $this->assign('cartgoods', $menus);
        }
        // var_dump($menu->get_cate($shop_id));
        $this->display();
	}

	public function add($menu_id) {

        $menu_id = (int) $menu_id;
        if (empty($menu_id)) {
            die('参数错误');
        }
        if (!$detail = D('Shopdingmenu')->find($menu_id)) {
            die('该产品不存在');
        }
        if (!empty($this->cart)) {
            foreach ($this->cart as $k => $v) {
                $data = D('Shopdingmenu')->find($k);
                if ($data['shop_id'] != $detail['shop_id']) {
                    die('一次只能订购一家的外卖，您可以清空购物车重新定！');
                }
                break;
            }
        }
        if (isset($this->cart[$menu_id])) {
            $this->cart[$menu_id]+=1;
        } else {
            $this->cart[$menu_id] = 1;
        }
		$S = $detail['shop_id'].'dingproduct';
        cookie($S, $this->cart);
        die('0');
    }

	 public function cart() {
        $cart = null;
        if($goods=cookie('elemenu')){
            $total = array('num' => 0, 'money' => 0);
            $goods = (array)json_decode($goods);
            foreach ($goods as $shop_ids=>$items) {
                if(is_numeric($shop_ids)) $shop_id = $shop_ids;
                foreach($items as $k2=>$item){
                    $item = (array)$item;
                    $total['num']+=$item['num'];
                    $total['money']+=$item['price']*$item['num'];
                    $ids[] = $item['product_id'];
                    $product_item_num[$item['product_id']] = $item['num'];
                }
            }// cartnum
            $ids = implode(',',$ids);
            $products = D('Shopdingmenu')->where('closed=0')->select($ids);
            foreach($products as $k=>$val){
                $products[$k]['cart_num'] = $product_item_num[$val['menu_id']];
                if($products[$k]['cart_num'] == 0){
                    unset($products[$k]);
                }
            }
            if(!empty($_GET['order_id'])){
                $this->assign('order_id',$_GET['order_id']);
            }
            $this->assign('detail', D('Shopding')->find($shop_id));
            $this->assign('total', $total);
            $this->assign('shop_id',$shop_id);
            $this->assign('table_id',$goods['table_id']);
            $this->assign('cartgoods', $products);
        }
        $this->mobile_title = '购物车';
        $this->display();
    }

	public function delete2($menu_id) {
        $menu_id = (int) $menu_id;
		$shop_id = (int) $this->_param('shop_id');
        if (!$detail = D('Shopdingmenu')->find($menu_id)) {
            $this->error('该产品不存在');
        }else{
			if (isset($this->cart[$menu_id])) {
				unset($this->cart[$menu_id]);
				cookie('elemenu', $this->cart);
			}
			$this->success('删除成功', U('ding/cart', array('shop_id' => $shop_id)));
		}
    }

	public function order() {
		$shop_id = (int) $this->_post('shop_id');

        if (empty($this->uid)) {
            AppJump();
        }
		if (empty($shop_id)) {
           $this->error('该商家不存在');
        }
		$shop = D('shop')->find($shop_id);
        if (empty($shop)) {
            $this->error('该商家不存在');
        }
		$data = array();
        $num = $this->_post('num', false);
        $total = array(
            'money' => 0,
            'num' => 0,
        );
		if(!empty($num)){
			foreach ($num as $key => $val) {
				$key = (int) $key;
				$val = (int) $val;
				if ($val < 1 || $val > 99) {
					$this->error('请选择正确的购买数量');
				}
				$menu = D('Shopdingmenu')->where('shop_id='.$shop_id)->find($key);
				if (empty($menu)) {
					$this->error('产品不正确');
				}
				$menu_str .= $menu['menu_id'].':'.$val.'|';
				$menu['buy_num'] = $val;
				$products[$key] = $menu;
				$total['money'] += ($menu['ding_price'] * $val);

				$total['num'] += $val;
			}
		}else{
            $this->error('您还没有点餐呢');
        }

        $goods=cookie('elemenu');
        $goods = (array)json_decode($goods);
        $order = (int) $this->_post('order_id');
        if(!empty($order)){
            $order_id = $order;
            $order2 = D('Shopdingorder')->save(array(
                'order_id' => $order_id,
                'table_num' => $goods['table_id'],
                'amount' => $total['money'],
                'menu_amount' => $total['num'],
            ));
        }else{
            $order2 = D('Shopdingorder')->add(array(
                'shop_id'   => $shop_id,
                'comment_status' => 0,
                'order_status' => 0,
                'table_num' => $goods['table_id'],
                'amount' => $total['money'],
                'menu_amount' => $total['num'],
                'user_id' => $this->uid,
                'create_time' => NOW_TIME,
                'create_ip' => get_client_ip()
            ));
            $order_id = $order2;
        }



			if($order2){
                foreach ($products as $val) {
                    D('Shopdingordermenu')->add(array(
                        'order_id' => $order_id,
                        'menu_id' => $val['menu_id'],
                        'price' => $val['ding_price'] * $val['buy_num'],
                        'menu_name' => $val['menu_name'],
                        'lao_menu_name' => $val['lao_menu_name'],
                        'eng_menu_name' => $val['eng_menu_name'],
                        'num' => $val['buy_num'],
                    ));
                    $menu = D('Shopdingmenu')->where('shop_id='.$shop_id)->find($val['menu_id']);
                    D('Shopdingmenu')->save(array(
                        'menu_id'=>$val['menu_id'],
                        'sold_num'=>$menu['sold_num']+$val['buy_num']
                    ));
                }
                D('Shop')->updateCount($shop_id, 'ding_num');
                cookie('elemenu', null);
                $this->success('下单成功！请付款', U('ding/pay', array('order_id' => $order_id)));

            }else{
                $this->error('创建订单失败！');
            }
			

        
            
    }
    
    public function pay() {
        if (empty($this->uid)) {
            AppJump();
        }

        $order_id = (int) $this->_get('order_id');

        $order = D('Shopdingorder')->find($order_id);
        if (empty($order) || $order['order_status'] != 0 || $order['user_id'] != $this->uid) {
            $this->error('该订单不存在');
            die;
        }
        $this->assign('payment', D('Payment')->getPayments(true));
        $this->assign('order', $order);
        $this->mobile_title = '订单支付';
        $this->display();
    }


    public function pay2(){
        if (empty($this->uid)) {
            AppJump();
        }
        $order_id = (int) $this->_get('order_id');
        $order = D('Shopdingorder')->find($order_id);
        if (empty($order) || $order['order_status'] != 0 || $order['user_id'] != $this->uid) {
            $this->error('该订单不存在');
            die;
        }
        if (!$code = $this->_post('code')) {
            $this->error('请选择支付方式！');
        }
        $shop = D('Shopding')->find($order['shop_id']);
        $payment = D('Payment')->checkPayment($code);
        if (empty($payment)) {
            $this->error('该支付方式不存在');
        }
        $logs = D('Paymentlogs')->getLogsByOrderId('ding', $order_id);
        if (empty($logs)) {
            $logs = array(
                'type' => 'ding',
                'user_id' => $this->uid,
                'order_id' => $order_id,
                'code' => $code,
                'need_pay' => $order['amount'],
                'create_time' => NOW_TIME,
                'create_ip' => get_client_ip(),
                'is_paid' => 0
            );
            $logs['log_id'] = D('Paymentlogs')->add($logs);
        } else {
            $logs['need_pay'] = $order['amount'];
            $logs['code'] = $code;
            D('Paymentlogs')->save($logs);
        }
            $this->success('选择支付方式成功！下面请进行支付！', U('payment/payment',array('log_id' => $logs['log_id'])));
    }
    
}
