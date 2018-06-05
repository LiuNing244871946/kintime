<?php



class  DingAction extends  CommonAction{
    
    protected $cart = array();
    
    public function _initialize() {
        parent::_initialize();
		$this->assign('cfg',D('Shopding')->getCfg());
		$this->assign('room',D('Shopding')->getType());		
        $this->assign('dingtypes',D('Shopding')->getDingType());
        $this->assign('price_list',D('Shopding')->getPrice());
    }

	public function index()
	{
        $this->assign('ding_date',htmlspecialchars($_COOKIE['ding_date'])); 
        $this->assign('ding_num',htmlspecialchars($_COOKIE['ding_num'])); 
        $this->assign('ding_time',htmlspecialchars($_COOKIE['ding_time'])); 
		$this->display();
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
			$pics = D('Shopdingpics')->where(array('shop_id'=>$shop_id))->select();
            $pics[] = array('photo'=>$detail['photo']);
            $this->assign('photos',$pics);
            //评论
            $dianping = D('Shopdingdianping');
            import('ORG.Util.Page'); // 导入分页类
            $map = array('closed' => 0, 'shop_id' => $shop_id);
            if($have_photo = (int)$this->_param('have_photo')){
                $map['have_photo'] = $have_photo;
                $this->assign('have_photo',$have_photo);
            }
            $count = $dianping->where($map)->count(); // 查询满足要求的总记录数 
            $Page = new Page($count, 5); // 实例化分页类 传入总记录数和每页显示的记录数
            $show = $Page->show(); // 分页显示输出
            $list = $dianping->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
            //dump($list);die;
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
            //dump(D('Shopdingdianpingpic')->where(array('order_id' => array('IN', $order_ids)))->select());
            //优惠券
            $coupon_list = D('Coupon')->where(array('shop_id'=>$detail['shop_id']))->limit(2)->select();
            $this->assign('coupon_list',$coupon_list);
            //菜单
            $this->assign('menucates',D('Shopdingcate')->where(array('shop_id'=>$shop_id))->select());
            $this->assign('menu_list',D('Shopdingmenu')->where(array('closed'=>0,'shop_id'=>$shop_id))->select());
            
            $this->assign('list', $list); // 赋值数据集
            $this->assign('page', $show); // 赋值分页输出
            $this->assign('ding_date',htmlspecialchars($_COOKIE['ding_date'])); 
            $this->assign('ding_num',htmlspecialchars($_COOKIE['ding_num'])); 
            $this->assign('ding_time',htmlspecialchars($_COOKIE['ding_time'])); 
            $this->assign('ding_type',htmlspecialchars($_COOKIE['ding_type'])); 
            if($coupon_list){
                $height_num = 963;
            }else{
                $height_num = 760;
            }
            //dump($shopding->get_time($shop_id));
            //dump($this->_getCartGoods($shop_id));
            $this->assign('height_num',$height_num);
			$this->assign('detail',$detail);
            
            $this->display();
		}
		
	}

    public function menu($shop_id){		
        $shopding = D('Shopding');
        $menu = D('Shopdingmenu');
        if(!$shop_id = (int)$shop_id){
            $this->error('该商家不存在');
        }elseif(!$detail = $shopding->find($shop_id)){
			$this->error('该商家不存在');
        }elseif($detail['audit'] !=1||$detail['closed']!=0){
            $this->error('该商家已删除或未审核');
        }else{
            import('ORG.Util.Page'); // 导入分页类
            $linkArr['shop_id'] = $shop_id;
            $map = array('closed' => 0,'shop_id'=>$shop_id);
            if($keyword = $this->_param('keyword', 'htmlspecialchars')){
                $map['menu_name'] = array('LIKE', '%' . $keyword . '%');
                $this->assign('keyword', $keyword);
                $linkArr['keyword'] = $keyword;
            }
            if($cate_id = (int)$this->_param('cate_id')){
                $map['cate_id'] = $cate_id;
                $this->assign('cate_id',$cate_id);
                $linkArr['cate_id'] = $cate_id;
            }
            $order = $this->_param('order', 'htmlspecialchars');
            $orderby = '';
            switch ($order) {
                case 's':
                    $orderby = array('sold_num' => 'desc');
                    break;
                case 'p':
                    $orderby = array('ding_price' => 'asc');
                    break;
                default:
                    $orderby = array('sold_num' => 'desc', 'ding_price' => 'asc');
                    break;
            }
            $linkArr['order'] = $order;
            $this->assign('order', $order);
            $count = $menu->where($map)->count(); // 查询满足要求的总记录数 
            $Page = new Page($count, 12); // 实例化分页类 传入总记录数和每页显示的记录数
            $show = $Page->show(); // 分页显示输出
            $list = $menu->where($map)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();
            $this->assign('list', $list); // 赋值数据集
            $this->assign('page', $show); // 赋值分页输出
            $this->assign('linkArr',$linkArr);
            $this->assign('menucates',D('Shopdingcate')->where(array('shop_id'=>$shop_id))->select());
                    
            //推荐菜
            $this->assign('tui_list',$menu->where(array('shop_id'=>$shop_id,'is_tuijian'=>1))->limit(5)->select());
            $this->assign('detail',$detail);
            
            //购物车
            $dingmenus = $this->_getCartGoods($shop_id);
            $total_money = "";
            $cart_num = "";
            $carts = array();
            foreach ($dingmenus as $k => $val) {
                $total_money += $val['total_price'];
                $cart_num += $val['cart_num'];
                $carts[] = $val['product_id'] . '_' . $val['cart_num'];
            }
            $this->assign('total_money', $total_money);
            $this->assign('cart_num', $cart_num);
            $this->assign('dingmenus', $dingmenus);
            
            $this->display();
        }
	}
    
    public function order($shop_id){
        $shopding = D('Shopding');
        if(!$shop_id = (int)$shop_id){
            $this->error('该商家不存在');
        }elseif(!$detail = $shopding->find($shop_id)){
			$this->error('该商家不存在');
        }elseif($detail['audit'] !=1||$detail['closed']!=0){
            $this->error('该商家已删除或未审核');
        }else{
            $this->assign('ding_date',htmlspecialchars($_COOKIE['ding_date'])); 
            $this->assign('ding_num',htmlspecialchars($_COOKIE['ding_num'])); 
            $this->assign('ding_time',htmlspecialchars($_COOKIE['ding_time'])); 
            $this->assign('ding_type',htmlspecialchars($_COOKIE['ding_type']));
            $this->assign('note',htmlspecialchars($_COOKIE['note']));
            
            //购物车
            $dingmenus = $this->_getCartGoods($shop_id);
            $total_money = "";
            $cart_num = "";
            $carts = array();
            foreach ($dingmenus as $k => $val) {
                $total_money += $val['total_price'];
                $cart_num += $val['cart_num'];
                $carts[] = $val['product_id'] . '_' . $val['cart_num'];
            }
            $amount = $total_money + $detail['deposit']*100;
            $this->assign('amount',$amount);
            $this->assign('total_money', $total_money);
            $this->assign('cart_num', $cart_num);
            $this->assign('dingmenus', $dingmenus);
            $this->assign('detail',$detail);
            $this->display();
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
        $dingmenus = $this->_getCartGoods($shop_id);
        
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
		$ding_type = (int)$this->_param('ding_type');
        $name = htmlspecialchars($this->_param('name'));
        if(!$name){
            $this->ajaxReturn(array('status'=>'error','msg'=>'联系人不能为空'));
        }
        $mobile = htmlspecialchars($this->_param('mobile'));
        if(!$mobile){
            $this->ajaxReturn(array('status'=>'error','msg'=>'联系手机号不能为空'));
        }
        if(!isMobile($mobile)){
            $this->ajaxReturn(array('status'=>'error','msg'=>'手机号格式不正确'));
        }
        $sex = htmlspecialchars($this->_param('sex'));
        $note = htmlspecialchars($this->_param('note'));
        //订单总额
        $total_money = 0;
        foreach ($dingmenus as $k => $val) {
            $total_money += $val['total_price'];
        }
        $amount = $detail['deposit']*100;
        $data = array(
            'shop_id'   => $shop_id,
            'ding_date' => $ding_date,
            'ding_time' => $ding_time,
            'ding_num'  => $ding_num,
            'ding_type' => $ding_type,
            'name'      => $name,
            'user_id'   => $this->uid,
            'mobile'    => $mobile,
            'note'      => $note,
            'sex'       => $sex,
            'menu_amount'=> $total_money,
            'amount'    =>$amount,
            'create_time'=>NOW_TIME,
            'create_ip' =>  get_client_ip(),
        );
        if($order_id = D('Shopdingorder')->add($data)){
            foreach ($dingmenus as $k => $val) {
                $data2 = array(
                    'order_id'  => $order_id,
                    'menu_id'   => $val['menu_id'],
                    'price'     => $val['ding_price'],
                    'menu_name' => $val['menu_name'],
                    'num'       => $val['cart_num'],
                    'amount'    => $val['total_price'],
                );
                D('Shopdingordermenu')->add($data2);
                D('Shopdingmenu')->updateCount($val['menu_id'],'sold_num',$val['cart_num']);
            }
            D('Shopding')->updateCount($shop_id,'orders');
            cookie('ding_date', null);
            cookie('ding_time', null);
            cookie('ding_num', null);
            cookie('ding_type', null);
            cookie('ding_'.$shop_id, null);
            cookie('note',null);
            if($amount == 0){
                D('Shopdingorder')->save(array('order_id'=>$order_id,'order_status'=>1));
                $this->ajaxReturn(array('status'=>'success','msg'=>'下单成功','order_id'=>$order_id,'amount'=>$amount));
            }else{
                $this->ajaxReturn(array('status'=>'success','msg'=>'下单成功！去支付','order_id'=>$order_id,'amount'=>$amount));
            }
        }else{
            $this->ajaxReturn(array('status'=>'error','msg'=>'创建订单失败'));
        }
    }

    



    public function lists()
	{
        $shopding = D('Shopding');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('audit' => 1, 'closed' => 0, 'city_id' => $this->city_id);
        $linkArr = array();
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['shop_name'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
            $linkArr['keywrod'] = $map['shop_name'];
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
        $price = (int)$this->_param('price');
        switch ($price) {
            case 1:
                $map['price'] = array('LT', '50');
                break;
            case 2:
                $map['price'] = array('between', '50,99.9');
                break;
            case 3:
                $map['price'] = array('between', '100,199.9');
                break;
            case 4:
                $map['price'] = array('between', '200,299.9');
                break;
            case 5:
                $map['price'] = array('EGT', '300');
                break;
        }
        $linkArr['price'] = $price;
        $this->assign('price', $price);
        
        $order = $this->_param('order', 'htmlspecialchars');
        $orderby = '';
        switch ($order) {
            case 's':
                $orderby = array('orders' => 'desc');
                break;
            case 'p':
                $orderby = array('price' => 'asc');
                break;
            case 'k':  //口味
                $orderby = array('kw_score' => 'desc');
                break;
            case 'h': //环境
                $orderby = array('hj_score' => 'desc');
                break;
            case 'f': //服务
                $orderby = array('fw_score' => 'desc');
                break;
            default:
                $orderby = array('orders' => 'desc', 'score' => 'desc', 'price' => 'asc');
                break;
        }
        $linkArr['order'] = $order;
        $this->assign('order', $order);
        $count = $shopding->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 16); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $shopding->where($map)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出 
        $this->assign('linkArr', $linkArr);
        $this->display(); // 输出模板
	}
    
    
    public function getTuijian(){ //推荐菜
        if (IS_AJAX) {
            $shop_id = (int) $this->_param('shop_id');
            $lists = D('Shopdingmenu')->where(array('shop_id'=>$shop_id,'is_tuijian'=>1))->order('rand()')->limit(5)->select();
            if ($lists) {
                $this->ajaxReturn(array('status' => 'success', 'lists' => $lists));
            } else {
                $this->ajaxReturn(array('status' => 'error'));
            }
        }
    }

    
    private function _getCartGoods($shop_id) {
		
        $carts = cookie('ding_'.$shop_id);
        if (empty($carts))
            return null;
        $carts = explode('|', $carts);
        $ids = $nums = array();
        foreach ($carts as $key => $val) {
            $local = explode('_', $val);
            $local[0] = (int) $local[0];
            $local[1] = (int) $local[1];
            if (!empty($local[0]) && !empty($local[1]) && $local[1] > 0) {
                $ids[$local[0]] = $local[0];
                $nums[$local[0]] = $local[1];
            }
        }
		$menu = D('Shopdingmenu');
        $dingmenus = $menu->itemsByIds($ids);
        foreach ($dingmenus as $k => $val) {
            $dingmenus[$k]['cart_num'] = $nums[$val['menu_id']];
            $dingmenus[$k]['total_price'] = $nums[$val['menu_id']] * $val['ding_price'];    
        }
        $cookies = array();
        foreach ($nums as $k => $v) {
            $cookies[] = $k . '_' . $v;
        }
        $cookiestr = join('|', $cookies);
        //setcookie('eleproduct', join('|', $cookies),NOW_TIME+604800);
        cookie('ding_'.$shop_id, join('|', $cookies),array('expire'=>NOW_TIME+604800));
        $_COOKIE['ding_'.$shop_id] = $cookiestr; //因为页面不刷新所以要赋值一下
        return $dingmenus;
    }


	public function pay(){
        if (empty($this->uid)) {
            header("Location:" . U('passport/login'));
            die;
        }
        $order_id = (int) $this->_get('order_id');
        $order = D('Shopdingorder')->find($order_id);
        if (empty($order) || $order['order_status'] != 0 || $order['user_id'] != $this->uid) {
            $this->error('该订单不存在');
            die;
        }
        $this->assign('payment', D('Payment')->getPayments());
        $this->assign('order', $order);
        $this->display();
    }

	public function pay2(){
        if (empty($this->uid)) {
            $this->ajaxLogin();
        }
        $order_id = (int) $this->_get('order_id');
        $order = D('Shopdingorder')->find($order_id);
        if (empty($order) || $order['order_status'] != 0 || $order['user_id'] != $this->uid) {
            $this->error('该订单不存在');
            die;
        }
        if (!$code = $this->_post('code')) {
            $this->baoError('请选择支付方式！');
        }
        $shop = D('Shopding')->find($order['shop_id']);
        $payment = D('Payment')->checkPayment($code);
        if (empty($payment)) {
            $this->baoError('该支付方式不存在');
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
            
            //====================微信支付通知==抢购=========================
            include_once "Kintime/Lib/Net/Wxmesg.class.php";
            /*微信订单通知消息-开始*/
            $notice_data = array(
                'url'       =>  "http://".$_SERVER['HTTP_HOST']."/mcenter/ding/detail/order_id/".$order_id.".html",
                'first'   => '亲,您的订单创建成功!',
                'remark'  => '详情请登录-http://'.$_SERVER['HTTP_HOST'],
                'amount'  => $order['amount'].'元',
                'order'   => $order_id,
                'info'    => $shop['shop_name']
            );
            $notice_data = Wxmesg::notice($notice_data);
            Wxmesg::net($this->uid, 'OPENTM206930158', $notice_data);
            /*微信订单通知消息-结束*/
            //====================微信支付通知==============================
            
            $this->baoSuccess('选择支付方式成功！下面请进行支付！', U('payment/payment', array('log_id' => $logs['log_id'])));
    }


    private function remainMoneyNotify($order_id, $need_price ,$shop_name)
    {
        //微信通知
        $openid = D('Connect')->getFieldByUid($this->uid,'open_id'); 

        if($openid){
            $template_id = D('Weixintmpl')->getFieldByTmpl_id(2,'template_id');//支付成功模板
            $tmpl_data =  array(
                'touser'      => $openid,//用户微信openid
                 'url'         => 'http://bao.com/mcenter/ding/index.html',//相对应的订单详情页地址
                'template_id' => $template_id,
                'topcolor'    => '#2FBDAA',
                'data'        => array(
                    'first'=>array('value'=>'恭喜订单创建成功！' ,'color'=>'#2FBDAA'),   
                    'keynote1'=>array('value'=> $order_id, 'color'=>'#2FBDAA'),//订单号
                    'keynote2'=>array('value'=> "在{$shop_name}预定了座位", 'color'=>'#2FBDAA'),//商品名称
                    'keynote3'=>array('value'=> 1, 'color'=>'#2FBDAA'),//订单数量
                    'keynote4'=>array('value'=> $need_price, 'color'=>'#2FBDAA'),//订单总额
                    'keynote5'=>array('value'=>'货到付款', 'color'=>'#2FBDAA'),//付款方式
                    'remark'  =>array('value'=>'请在继续下一步配送地址环节！', 'color'=>'#2FBDAA')
                )
            );
            D('Weixin')->tmplmesg($tmpl_data);
        }
    }

}