<?php



class EleAction extends CommonAction {

    protected $cart = array();

    public function _initialize() {
        parent::_initialize();
        //$this->cart = session('eleproduct');
        $this->cart = $this->getcart();
        $this->assign('cartnum', (int) array_sum($this->cart));
        $cate = D('Ele')->getEleCate();
        $this->assign('elecate', $cate);
    }
   
    public function getcart(){
        $shop_id = (int)$this->_param('shop_id');
        $cart = (array)json_decode($_COOKIE['ele']);
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
        $eleproducts = D('Eleproduct')->itemsByIds($ids);
        foreach ($eleproducts as $k => $val) {
            $eleproducts[$k]['cart_num'] = $nums[$val['product_id']];
            $eleproducts[$k]['total_price'] = $nums[$val['product_id']] * $val['price'];
        }

        return $eleproducts;
    }


    
    public function main() {
        $map = array('is_open' => 1, 'audit' => 1,'city_id'=>$this->city_id);
        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if (empty($lat) || empty($lng)) {
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        $list = D('Ele')->where($map)->order(" (ABS(lng - '{$lng}') +  ABS(lat - '{$lat}') ) asc ")->limit(0, 10)->select();
        $shop_ids = array();
        foreach ($list as $k=>$val) {
            if(!empty($val['shop_id'])){
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $list[$k]['d'] =  getDistance($lat, $lng, $val['lat'], $val['lng']);
        }
        $this->assign('shops',D('Shop')->itemsByIds($shop_ids));
        $this->assign('list', $list);
        $this->mobile_title = '外卖主页';
        $this->display();
    }

    public function index() {
        $linkArr = array();
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        $linkArr['keyword'] = $keyword;

        $cate = $this->_param('cate','htmlspecialchars');
        $this->assign('cate', $cate);
        $linkArr['cate'] = $cate;
        
        $order = $this->_param('order','htmlspecialchars');
        $this->assign('order', $order);
        $linkArr['order'] = $order;

        $area = (int) $this->_param('area');
        $this->assign('area', $area);
        $linkArr['area'] = $area;

        $business = (int) $this->_param('business');
        $this->assign('business', $business);
        $linkArr['business'] = $business;
        $this->assign('nextpage', LinkTo('ele/loaddata', $linkArr, array('t' => NOW_TIME, 'p' => '0000')));
        $this->assign('linkArr',$linkArr);
        $this->mobile_title = '外卖首页';
        $this->display(); // 输出模板 
    }

    public function loaddata() {
        $ele = D('Ele');
        import('ORG.Util.Page'); // 导入分页类
        //初始数据
        $map = array('audit'=>1,'city_id'=>$this->city_id,'is_open'=>1);
        $area = (int) $this->_param('area');
        if ($area) {
            $map['area_id'] = $area;
        }
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['shop_name'] = array('LIKE', '%' . $keyword . '%');
        }
        $business = (int) $this->_param('business');
        if ($business) {
            $map['business_id'] = $business;
        }
        $order = $this->_param('order','htmlspecialchars');
        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if (empty($lat) || empty($lng)) {
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        switch ($order) {
            case 'a':
                $orderby = array("(ABS(lng - '{$lng}') +  ABS(lat - '{$lat}') )"=>'asc','orderby' => 'asc','month_num'=>'desc','distribution'=>'asc','since_money'=>'asc');
                break;
            case 'p':
                $orderby = array('since_money'=>'asc');
                break;
            case 'v':
                $orderby = array('distribution'=>'asc');
                break;
            case 'd':
                $orderby = " (ABS(lng - '{$lng}') +  ABS(lat - '{$lat}') ) asc ";
                break;
            case 's':
                $orderby = array('month_num'=>'desc');
                break;
        }
        $cate =  $this->_param('cate','htmlspecialchars');
        $lists = $ele->order($orderby)->where($map)->select();
        foreach ($lists as $k => $val) {
            if (!empty($cate)) {
                if (strpos($val['cate'],$cate) === false) {
                    unset($lists[$k]);
                }
            }
        }
        $count = count($lists);  // 查询满足要求的总记录数 
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出  
        
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = array_slice($lists, $Page->firstRow, $Page->listRows);
        $shop_ids = array();
        foreach ($list as $k => $val) {
            $shop_ids[$val['shop_id']] = $val['shop_id'];
            $list[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);
        }
        if ($shop_ids) {
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板   
    }

    public function shop() {
        empty($this->_param('shop_id'))?$shop_id = cookie('shop'):$shop_id = (int) $this->_param('shop_id');
        if (!$detail = D('Ele')->find($shop_id)) {
            $this->error('该餐厅不存在');
        }
        if (!$shop = D('Shop')->find($shop_id)) {
            $this->error('该餐厅不存在');
        }
        cookie("shop",$shop_id);
        $Eleproduct = D('Eleproduct');
        $map = array('closed' => 0,'audit'=>1, 'shop_id' => $shop_id);
        $list = $Eleproduct->where($map)->order(array('sold_num' => 'desc', 'price' => 'asc'))->select();
        foreach($list as $k=>$val){
            $list[$k]['cart_num'] = $this->cart[$val['product_id']]['cart_num'];
        }

        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if (empty($lat) || empty($lng)) {
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        $d = getDistanceNone($lat, $lng, $shop['lat'], $shop['lng'])/10000;

        $row = D('Elesetting')->where('mileage > '.$d)->order('mileage asc')->find();
        $delivery_money = $row['delivery'];

        $this->assign('list', $list); // 赋值数据集
        $this->assign('detail', $detail);
        $this->assign('delivery_money', $delivery_money);
        $this->assign('cates', D('Elecate')->where(array('shop_id' => $shop_id, 'closed' => 0))->select());
        $this->assign('shop', $shop);
        $this->mobile_title = '店铺菜单';
        $this->display(); // 输出模板 
    }
  
    
    //重写购物车
    public function cart() {
        $cart = null;
        if($goods=cookie('ele')){
            $total = array('num' => 0, 'money' => 0);
            $goods = (array)json_decode($goods);
            $ids = array();
            foreach ($goods as $shop_id=>$items) {
              foreach($items as $k2=>$item){
                $item = (array)$item;
                $total['num']+=$item['num'];
                $total['money']+=$item['price']*$item['num'];
                $ids[] = $item['product_id'];
                $product_item_num[$item['product_id']] = $item['num'];
              }
            }
            $ids = implode(',',$ids);
            $products = D('Eleproduct')->where('closed=0')->select($ids);
            foreach($products as $k=>$val){
                $products[$k]['cart_num'] = $product_item_num[$val['product_id']];
                if($products[$k]['cart_num'] == 0){
                    unset($products[$k]);
                }
            }
            $lat = addslashes(cookie('lat'));
            $lng = addslashes(cookie('lng'));
            if (empty($lat) || empty($lng)) {
                $lat = $this->city['lat'];
                $lng = $this->city['lng'];
            }
            $shop_row = D('Shop')->find($shop_id);
            $d = getDistanceNone($lat, $lng, $shop_row['lat'], $shop_row['lng'])/10000;

            $row = D('Elesetting')->where('mileage > '.$d)->order('mileage asc')->find();
            $delivery_money = $row['delivery'];
            $this->assign('detail', D('Ele')->find($shop_id));
            $this->assign('total', $total);
            $this->assign('delivery_money', $delivery_money);
            $this->assign('shop_id',$shop_id);
            $this->assign('cartgoods', $products);
        }
        $this->mobile_title = '购物车';
        $this->display();
    }

    public function order() {
        if (empty($this->uid)) {
            AppJump();
        }
        $num = $this->_post('num', false);
        if (empty($num)) {
            $this->ajaxReturn(array('status'=>'error','msg'=>'您还没有订餐呢'));
        }
        $shop_id = 0;
        $shops = array();
        $products = array();
        $total = array(
            'money' => 0,
            'num' => 0,
        );
        //产品名
        $product_name = array( );
        foreach ($num as $key => $val) {
            $key = (int) $key;
            $val = (int) $val;
            if ($val < 1 || $val > 99) {
                $this->ajaxReturn(array('status'=>'error','msg'=>'请选择正确的购买数量'));
            }
            $product = D('Eleproduct')->find($key);
            $product_name[]=$product['product_name'];
            if (empty($product)) {
                $this->ajaxReturn(array('status'=>'error','msg'=>'产品不正确'));
            }
            $shop_id = $product['shop_id'];
            $product['buy_num'] = $val;
            $products[$key] = $product;
            $shops[$shop_id] = $shop_id;
            $total['money'] += ($product['price'] * $val);
            $total['num'] += $val;
        }

        if (count($shops) > 1) {
            $this->ajaxReturn(array('status'=>'error','msg'=>'您购买的商品是2个商户的！'));
        }
        if (empty($shop_id)) {
            $this->ajaxReturn(array('status'=>'error','msg'=>'该商家不存在'));
        }
        $shop = D('Ele')->find($shop_id);
        if (empty($shop)) {
            $this->ajaxReturn(array('status'=>'error','msg'=>'该商家不存在'));
        }
        if (!$shop['is_open']) {
            $this->ajaxReturn(array('status'=>'error','msg'=>'商家已经打烊，实在对不住客官'));
        }
        
        if ($shop['since_money'] > $total['money']) {
            $this->ajaxReturn(array('status'=>'error','msg'=>'客官，您再订点吧！'));
        }
        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if (empty($lat) || empty($lng)) {
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        $shop_row = D('Shop')->find($shop_id);
        $d = getDistanceNone($lat, $lng, $shop_row['lat'], $shop_row['lng'])/10000;

        $row = D('Elesetting')->where('mileage > '.$d)->order('mileage asc')->find();
        $settlement_price = $shop['package_money']+$total['money'];
        $total['need_pay'] = $total['money']+$row['delivery']+$shop['package_money'];
        $month = date('Ym', NOW_TIME);
        if ($order_id = D('Eleorder')->add(array(
            'user_id' => $this->uid,
            'shop_id' => $shop_id,
            'total_price' => $total['money'],
            'need_pay' => $total['need_pay'],
            'delivery_price' => $row['delivery'],
            'num' => $total['num'],
            'settlement_price' => $settlement_price,
            'status' => 0,
            'create_time' => NOW_TIME,
            'create_ip' => get_client_ip(),
            'is_pay' => 0,
            'month' => $month //主要后面最统计用好用
                ))) {

            foreach ($products as $val) {
                D('Eleorderproduct')->add(array(
                    'order_id' => $order_id,
                    'product_id' => $val['product_id'],
                    'num' => $val['buy_num'],
                    'total_price' => $val['price'] * $val['buy_num'],
                    'month' => $month
                ));
            }
            //session('eleproduct', null);
            cookie('eleproduct', null);
            cookie('ele', null);
            $this->ajaxReturn(array('status'=>'success','msg'=>'下单成功!','url'=>U('ele/pay', array('order_id' => $order_id))));
        }
        $this->ajaxReturn(array('status'=>'error','msg'=>'创建订单失败'));
    }

    public function pay() {
        if (empty($this->uid)) {
            AppJump();
        }
        $order_id = (int) $this->_get('order_id');
        $order = D('Eleorder')->find($order_id);
        $shop_row = D('Shop')->find($order['shop_id']);
        $this->check_mobile();

        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if (empty($lat) || empty($lng)) {
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }

        $near_addr = D('Useraddr')->where('user_id='.$this->uid.' and abs(lat - '.$lat.')<0.0001 and abs(lng - '.$lng.')<0.0001')->limit()->find();
        $d = getDistanceNone($lat, $lng, $shop_row['lat'], $shop_row['lng'])/10000;
        $row = D('Elesetting')->where('mileage > '.$d)->order('mileage asc')->find();
        
        
        if (empty($order) || $order['status'] != 0 || $order['user_id'] != $this->uid) {
            $this->error('该订单不存在');
            die;
        }
        $this->assign('shop', D('Ele')->find($order['shop_id']));
        $ordergoods = D('Eleorderproduct')->where(array('order_id' => $order_id))->select();
        $goods = array();
        foreach ($ordergoods as $key => $val) {
            $goods[$val['product_id']] = $val['product_id'];
        }
        $products = D('Eleproduct')->itemsByIds($goods);

        $map['user_id'] = $this->uid;
        if(!empty($near_addr)){
            $map['addr_id'] = array('neq',$near_addr['addr_id']);
            $near_addr['delivery_money'] = $row['delivery'];
        } 

        $useraddr =D('Useraddr')->where($map)->limit()->select();
        foreach ($useraddr as $key => $value) {
            $d = getDistanceNone($value['lat'], $value['lng'], $shop_row['lat'], $shop_row['lng'])/10000;
            $row = D('Elesetting')->where('mileage > '.$d)->order('mileage asc')->find();
            $useraddr[$key]['delivery_money'] = $row['delivery'];
        }
        
        $this->assign('products', $products);
        $this->assign('ordergoods', $ordergoods);
        $this->assign('useraddr', $useraddr);
        $this->assign('order', $order);
        $this->assign('near', $near_addr);
        $this->assign('payment', D('Payment')->getPayments(true));
        $this->mobile_title = '订单支付';
        $this->display();
    }
    
    
    public function app_pay() {
        if (empty($this->uid)) {
            $this->ajaxReturn(array('status'=>'login','message'=>'请登录后操作'));
        }
        $order_id = (int) $this->_get('order_id');
        $order = D('Eleorder')->find($order_id);
        if (empty($order) || $order['status'] != 0 || $order['user_id'] != $this->uid) {
            $this->ajaxReturn(array('status'=>'error','message'=>'该订单不存在'));
        }
        $addr_id = (int) $this->_post('addr_id');
        if (empty($addr_id)) {
            $this->ajaxReturn(array('status'=>'error','message'=>'请选择配送地址'));
        }
        D('Eleorder')->save(array('addr_id' => $addr_id, 'order_id' => $order_id));
        $code = $this->_post('code');
        if(empty($code)){
            $code = 'alipay';
        }
        $dv = D('DeliveryOrder');
        $ua = D('UserAddr');
        $uaddr = $ua -> where(array('addr_id'=>$order['addr_id'])) -> find();
        //为写入物流记录，查询商家类型
        $shop = D('Shop');
        $fshop = $shop -> where('shop_id ='.$order['shop_id']) -> find();
        
        if ($code == 'wait') { //如果是货到付款
            D('Eleorder')->save(array(
                'order_id' => $order_id,
                'status' => 1,
            ));
            $eleorder = D('Eleorder')->find($order_id);
            $shops = D('Shop')->find($eleorder['shop_id']);
            
            //如果是货到付款，该订单已经下单了，并且商家是配送员配送
            if($fshop['is_pei'] == 0){
                $dv_data = array(
                    'type' => 1,
                    'type_order_id' => $order['order_id'],
                    'delivery_id' => 0,
                    'shop_id' => $order['shop_id'],
                    'city_id'=>$fshop['city_id'],
                    'user_id' => $order['user_id'],
                    'shop_name' => $fshop['shop_name'],
                    'shop_addr' => $fshop['addr'],
                    'lat'=>$fshop['lat'],
                    'lng'=>$fshop['lng'],
                    'shop_mobile' => $fshop['tel'],
                    'user_name' => $this->member['nickname'],
                    'user_addr' => $uaddr['addr'],
                    'user_mobile' => $this->member['mobile'],
                    'create_time' => time(),
                    'update_time' => 0,
                    'status' => 0
                );
  
                $dv -> add($dv_data);
             }
            
            
            // D('Sms')->sendSms('sms_ele', $this->member['mobile'], array(
            //     'nickname' => $this->member['nickname'],
            //     'shopname' => $shops['shop_name'],
            // ));


           
            /*====================微信支付通知===========================*/
            $product_ids  = D('Eleorderproduct')->where("order_id=".$order['order_id'])->getField('product_id',true);
            $product_ids  = implode(',', $product_ids);
            $map          = array('product_id'=>array('in',$product_ids));
            $product_name = D('Eleproduct')->where($map)->getField('product_name',true);
            $product_name = implode(',', $product_name);
            include_once "Kintime/Lib/Net/Wxmesg.class.php";
            /*微信订单通知消息-开始*/
            $notice_data = array(
                'url'     => "http://".$_SERVER['HTTP_HOST']."/mcenter/eleorder/detail/order_id/".$order_id.".html",
                'first'   => '亲,您的订单创建成功!',
                'remark'  => '详情请登录-http://'.$_SERVER['HTTP_HOST'],
                'amount'  => round($order['need_pay']/100,2).'元',
                'order'   => $order_id,
                'info'    => $product_name
            );
            $notice_data = Wxmesg::notice($notice_data);
            Wxmesg::net($this->uid, 'OPENTM206930158', $notice_data);
            /*微信订单通知消息-结束*/
            //====================微信支付通知==============================




            //到付通知商家
            D('Sms')->eleTZshop($order_id);
            $this->ajaxReturn(array('status'=>'success','message'=>'恭喜您下单成功'));
        } else {
            $payment = D('Payment')->checkPayment($code);
            if (empty($payment)) {
                $this->ajaxReturn(array('status'=>'error','message'=>'该支付方式不存在'));
            }
            $logs = D('Paymentlogs')->getLogsByOrderId('ele', $order_id);
            if (empty($logs)) {
                $logs = array(
                    'type' => 'ele',
                    'user_id' => $this->uid,
                    'order_id' => $order_id,
                    'code' => $code,
                    'need_pay' => $order['need_pay'],
                    'create_time' => NOW_TIME,
                    'create_ip' => get_client_ip(),
                    'is_paid' => 0
                );
                $logs['log_id'] = D('Paymentlogs')->add($logs);
            } else {
                $logs['need_pay'] = $order['need_pay'];
                $logs['code'] = $code;
                D('Paymentlogs')->save($logs);
            }

           
            //====================微信支付通知===========================*/
            $product_ids  = D('Eleorderproduct')->where("order_id=".$order_id)->getField('product_id',true);
            $product_ids  = implode(',', $product_ids);
            $map          = array('product_id'=>array('in',$product_ids));
            $product_name = D('Eleproduct')->where($map)->getField('product_name',true);
            $product_name = implode(',', $product_name);
            include_once "Kintime/Lib/Net/Wxmesg.class.php";
            /*微信订单通知消息-开始*/
            $notice_data = array(
                'url'     => "http://".$_SERVER['HTTP_HOST']."/mcenter/eleorder/detail/order_id/".$order_id.".html",
                'first'   => '亲,您的订单创建成功!',
                'remark'  => '详情请登录-http://'.$_SERVER['HTTP_HOST'],
                'amount'  => round($order['need_pay']/100,2).'元',
                'order'   => $order_id,
                'info'    => $product_name
            );
            $notice_data = Wxmesg::notice($notice_data);
            Wxmesg::net($this->uid, 'OPENTM206930158', $notice_data);
            /*微信订单通知消息-结束*/
            //====================微信支付通知==============================
            $this->ajaxReturn(array('status'=>'success','message'=>'操作成功，立即支付','log_id'=>$logs['log_id']));
           
        }
    }

    public function pay2() {
        if (empty($this->uid)) {
            AppJump();
        }
        $order_id = (int) $this->_get('order_id');
        $order = D('Eleorder')->find($order_id);
        if (empty($order) || $order['status'] != 0 || $order['user_id'] != $this->uid) {
            $this->error('该订单不存在');
            die;
        }
        $addr_id = (int) $this->_post('addr_id');
        if (empty($addr_id)) {
            $this->error('请选择一个要配送的地址！');
        }
        $shop = D('Shop');
        $fshop = $shop -> where('shop_id ='.$order['shop_id']) -> find();
        
        $ua = D('UserAddr');
        $uaddr = $ua -> where(array('addr_id'=>$addr_id)) -> find();
        $d = getDistanceNone($uaddr['lat'], $uaddr['lng'], $fshop['lat'], $fshop['lng'])/10000;
        $row = D('Elesetting')->where('mileage > '.$d)->order('mileage asc')->find();
        $order['need_pay'] = $order['settlement_price']+$row['delivery'];

        D('Eleorder')->save(array('addr_id' => $addr_id, 'order_id' => $order_id,            'elesetting_id' => $row['elesetting_id'], 'delivery_price' =>$row['delivery'], 'need_pay' => $order['need_pay']));
        if (!$code = $this->_post('code')) {
            $this->error('请选择支付方式！');
        }
        $dv = D('DeliveryOrder');
        
        
        //为写入物流记录，查询商家类型
        
        
        
            $payment = D('Payment')->checkPayment($code);
            if (empty($payment)) {
                $this->error('该支付方式不存在');
            }
            $logs = D('Paymentlogs')->getLogsByOrderId('ele', $order_id);
            if (empty($logs)) {
                $logs = array(
                    'type' => 'ele',
                    'user_id' => $this->uid,
                    'order_id' => $order_id,
                    'code' => $code,
                    'need_pay' => $order['need_pay'],
                    'create_time' => NOW_TIME,
                    'create_ip' => get_client_ip(),
                    'is_paid' => 0
                );
                $logs['log_id'] = D('Paymentlogs')->add($logs);
            } else {
                $logs['need_pay'] = $order['need_pay'];
                $logs['code'] = $code;
                D('Paymentlogs')->save($logs);
            }

           
            //====================微信支付通知===========================*/
            $product_ids  = D('Eleorderproduct')->where("order_id=".$order_id)->getField('product_id',true);
            $product_ids  = implode(',', $product_ids);
            $map          = array('product_id'=>array('in',$product_ids));
            $product_name = D('Eleproduct')->where($map)->getField('product_name',true);
            $product_name = implode(',', $product_name);
            include_once "Kintime/Lib/Net/Wxmesg.class.php";
            /*微信订单通知消息-开始*/
            $notice_data = array(
                'url'     => "http://".$_SERVER['HTTP_HOST']."/mcenter/eleorder/detail/order_id/".$order_id.".html",
                'first'   => '亲,您的订单创建成功!',
                'remark'  => '详情请登录-http://'.$_SERVER['HTTP_HOST'],
                'amount'  => round($order['need_pay']/100,2).'元',
                'order'   => $order_id,
                'info'    => $product_name
            );
            $notice_data = Wxmesg::notice($notice_data);
            Wxmesg::net($this->uid, 'OPENTM206930158', $notice_data);
            /*微信订单通知消息-结束*/
            //====================微信支付通知==============================
            $this->success('选择支付方式成功！下面请进行支付！', U('payment/payment', array('log_id' => $logs['log_id'])));
        
    }

    public function ajax() {
        //$this->cart = session('eleproduct');
        $this->cart = cookie('eleproduct');
        $num = count($this->cart);
        $num = $num + 1;
        die("{$num}");
    }

    private function getShop($shop, $lng, $lat) { // 2公里过滤
        foreach ($shop as $k => $v) {

            $shop[$k]['d'] = getDistanceNone($lat, $lng, $v['lat'], $v['lng']);
            if ($shop[$k]['d'] > 20000) { //大于2KM的要咔嚓掉
                unset($shop[$k]);
            }
        }
        return $shop;
    }


    public function favorites() {
        if (empty($this->uid)) {
            AppJump();
        }
        $shop_id = (int) $this->_get('shop_id');
        if (!$detail = D('Shop')->find($shop_id)) {
            $this->error('没有该商家');
        }
        if ($detail['closed']) {
            $this->error('该商家已经被删除');
        }
        if (D('Shopfavorites')->check($shop_id, $this->uid)) {
            $this->error('您已经收藏过了！');
        }
        $data = array(
            'shop_id' => $shop_id,
            'user_id' => $this->uid,
            'create_time' => NOW_TIME,
            'create_ip' => get_client_ip()
        );
        if (D('Shopfavorites')->add($data)) {
            $this->success('恭喜您收藏成功！', U('ele/detail', array('shop_id' => $shop_id)));
        }
        $this->error('收藏失败！');
    }

     public function detail() {
            $shop_id = (int) $this->_param('shop_id');
        if (!$detail = D('Ele')->find($shop_id)) {
            $this->error('没有该商家');
            die;
        }
        if ($detail['audit'] !=1) {
            $this->error('该商家不存在');
            die;
        }
        $this->assign('detail',$detail);
        $this->assign('shop',D('Shop')->find($shop_id));
        $this->assign('ex',D('Shopdetails')->find($shop_id));
        $this->mobile_title = '店铺详情';
        $this->display();
    }

    public function dianping() {
        $shop_id = (int) $this->_get('shop_id');
        if (!$detail = D('Ele')->find($shop_id)) {
            $this->error('没有该商家');
            die;
        }
        if ($detail['closed']) {
            $this->error('该商家已经被删除');
            die;
        }
        $this->assign('shop',D('Shop')->find($shop_id));
        $this->assign('detail', $detail);
        $this->mobile_title = '店铺评价';
        $this->display();
    }

    public function dianpingloading() {
        $shop_id = (int) $this->_get('shop_id');
        if (!$detail = D('Ele')->find($shop_id)) {
            die('0');
        }
        if ($detail['audit'] != 1) {
            die('0');
        }
        $Eledianping = D('Eledianping');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0, 'shop_id' => $shop_id, 'show_date' => array('ELT', TODAY));
        $count = $Eledianping->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 5); // 实例化分页类 传入总记录数和每页显示的记录数

        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            $this->assign('totalnum', $count);
            $this->display();
            die();
        }

        $show = $Page->show(); // 分页显示输出
        $list = $Eledianping->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
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
            $this->assign('pics', D('Eledianpingpics')->where(array('order_id' => array('IN', $order_ids)))->select());
        }
        $this->assign('totalnum', $count);
        $this->assign('list', $list); // 赋值数据集
        $this->assign('detail', $detail);
        $this->display();
    }

}
