<?php



class MartAction extends CommonAction {

    public function _initialize() {
        parent::_initialize();
        $this->_cart = $this->getcart();
        $cates = D('Weidiancate')->fetchAll();
        $this->assign('cates', $cates);
    }

    
    public function getcart(){
        $id = (int)$this->_param('id');
        $wd = D('WeidianDetails')->find($id);
        $cart = (array)json_decode($_COOKIE['mall']);
        $carts = array();
        foreach($cart as $kk=>$vv){
            foreach($vv as $key=>$v){
                $v = (array)$v;
                $carts[$kk][$key] = $v;
                if($v['num'] == 0){
                    unset($carts[$kk][$key]);
                }
            }
        }
        $ids = $nums = array();
        foreach($carts[$wd['shop_id']] as $k=>$val){
            $ids[$val['goods_id']] = $val['goods_id'];
            $nums[$val['goods_id']] = $val['num'];
        }
        $goods = D('Goods')->itemsByIds($ids);
        foreach ($goods as $k => $val) {
            $goods[$k]['cart_num'] = $nums[$val['goods_id']];
            $goods[$k]['total_price'] = $nums[$val['goods_id']] * $val['mall_price'];
        }
        return $goods;
    }

    
    
    public function index() {
        $cat = (int) $this->_param('cat');
        $this->assign('cat',$cat);
        $linkArr['cat'] = $cat;
        $this->assign('nextpage', LinkTo('mart/loaddata',$linkArr, array('t' => NOW_TIME, 'p' => '0000')));
         $this->assign('linkArr',$linkArr);
         $this->mobile_title = '店铺列表';
        $this->display(); // 输出模板   
    }

    public function loaddata() {
        $weidian = D('WeidianDetails');
        import('ORG.Util.Page'); // 导入分页类
        //初始数据
        $map = array('audit' => 1,'city_id'=>$this->city_id);
        $cat = (int) $this->_param('cat');
        if($cat){
            $map['cate_id'] = $cat;
        }
        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if (empty($lat) || empty($lng)) {
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        $count = $weidian->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 8); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出

        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $weidian->order("(ABS(lng - '{$lng}') +  ABS(lat - '{$lat}') ) asc")->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $k => $val) {
            $list[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);
        }
        $shop_ids = array();
        foreach ($list as $key => $v) {
            $shop_ids[$v['shop_id']] = $v['shop_id'];
        }
        $shopdetails = D('Shopdetails')->itemsByIds($shop_ids);
        foreach ($list as $k => $val) {
            $list[$k]['price'] = $shopdetails[$val['shop_id']]['price'];
        }
        $this->assign('linkArr',$linkArr);
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板   
    }

    public function lists() { //进入微店
        $goods_id = (int) $this->_get('goods_id');
        $id = (int) $this->_get('id');
        $wd = D('WeidianDetails')->find($id);
        if (!$detail = D('WeidianDetails')->find($id)) {
            $this->error('没有该微店商家');
            die;
        }
        if ($detail['audit'] != 1) {
            $this->error('没有该微店商家');
            die;
        }
        $autocates = D('Goodsshopcate')->order(array('orderby' => 'asc'))->where(array('shop_id' => $wd['shop_id']))->select();
        $Goods = D('Goods');
        //初始数据
        $map = array('closed' => 0, 'audit' => 1,'city_id'=>$this->city_id, 'shop_id' => $wd['shop_id'], 'end_date' => array('EGT', TODAY));
        $list = $Goods->order(array('sold_num'=>'desc','goods_id'=>'desc'))->where($map)->select();
        foreach($list as $k=>$val){
            $list[$k]['cart_num'] = $this->_cart[$val['goods_id']]['cart_num'];
        }
        $this->assign('list', $list); // 赋值数据集
        $this->assign('autocates', $autocates);
        $this->assign('detail', $detail);
        $this->assign('goods_id',$goods_id);
        $this->mobile_title = $detail['weidian_name'];
        $this->display(); // 输出模板   
    }
    
    public function detail($goods_id) {
        $goods_id = (int) $goods_id;
        $detail = D('Goods')->find($goods_id);
        //$shop_id = $detail['shop_id'];
        //$recom = D('Goods')->where(array('shop_id' => $shop_id, 'goods_id' => array('neq', $goods_id)))->select();
        //$record = D('Usersgoods');
        //$insert = $record->getRecord($this->uid, $goods_id);
        //$this->assign('recom', $recom);
        $this->assign('detail', $detail);
        $this->display();
    }
     
     public function cart() {
         $goods_id = (int) $this->_get('goods_id');
        $id = (int) $this->_param('id');
        if(empty($id)){
            $this->error('参数不正确');
        }
        $detail = D('WeidianDetails')->find($id);
        if(empty($detail)){
            $this->error('微店不存在');
        }
        $goods = $this->_cart;
        if (empty($goods)) {
            $this->error("亲还没有选购产品呢!", U('mart/lists',array('id'=>$id)));
        }
        $this->assign('cart_goods', $goods);
        $this->assign('detail',$detail);
        $this->assign('goods_id',$goods_id);
        $this->mobile_title = '购物车';
        $this->display();
    }
    

    public function order() {
        if (empty($this->uid)) {
            AppJump();
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
            $this->ajaxReturn('很抱歉请填写正确的购买数量');
        $goods = D('Goods')->itemsByIds($goods_ids);
        foreach ($goods as $key => $val) {
            if ($val['closed'] != 0 || $val['audit'] != 1 || $val['end_date'] < TODAY) {
                unset($goods[$key]);
            }
        }
        if (empty($goods)) {
            $this->ajaxReturn('很抱歉，您提交的产品暂时不能购买！');
        }
        $tprice = 0;
        $ip = get_client_ip();
        $ordergoods = $total_price = array();

        foreach ($goods as $val) {
            $price = $val['mall_price'] * $num[$val['goods_id']];
            $js_price = $val['settlement_price'] * $num[$val['goods_id']];
            $mobile_fan = $val['mobile_fan'] * $num[$val['goods_id']];
            $m_price = $val['mall_price'] * $num[$val['goods_id']] - $val['mobile_fan'] * $num[$val['goods_id']];
            $tprice+= $m_price;
            $ordergoods[$val['shop_id']][] = array(
                'goods_id' => $val['goods_id'],
                'shop_id' => $val['shop_id'],
                'num' => $num[$val['goods_id']],
                'price' => $val['mall_price'],
                'total_price' => $price,
                'mobile_fan'=>$mobile_fan,
                'js_price' => $js_price,
                'create_time' => NOW_TIME,
                'create_ip' => $ip
            );
             $total_price[$val['shop_id']] += $m_price;
            $mm_price[$val['shop_id']] += $mobile_fan;
        }
        //总订单
        $order = array(
            'user_id' => $this->uid,
            'create_time' => NOW_TIME,
            'create_ip' => $ip,
            'total_price' => $total_price,
            'mobile_fan' => $mm_price,
        );

        $order_ids = array();
        foreach ($ordergoods as $k => $val) {
            $order['shop_id'] = $k;
            $order['total_price'] = $total_price[$k];
			$shop = D('Shop')->find($k);
            $order['is_shop'] = (int) $shop['is_pei']; //是否由商家自己配送
            if ($order_id = D('Order')->add($order)) {
                $order_ids[] = $order_id;
                foreach ($val as $k1 => $val1) {
                    $val1['order_id'] = $order_id;
                    D('Ordergoods')->add($val1);
                }
            }
        }
        cookie('mall', null);
        if (count($order_ids) > 1) {//如果大于1 那么形成一个 支付记录 来合并付款！如果其他条件可以直接去付款
            $need_pay = D('Order')->useIntegral($this->uid,$order_ids);
            $logs = array(
                'type' => 'goods',
                'user_id' => $this->uid,
                'order_id' => 0,
                'order_ids' => join(',', $order_ids),
                'code' => '',
                'need_pay' => $need_pay,
                'create_time' => NOW_TIME,
                'create_ip' => get_client_ip(),
                'is_paid' => 0
            );
            $logs['log_id'] = D('Paymentlogs')->add($logs);
            $url = U('mart/paycode', array('log_id' => $logs['log_id'])); 
        } else {
            $url = U('mart/pay', array('order_id' => $order_id));
        }
        $this->ajaxReturn(array('ret'=>0,'url'=>$url));
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
        $this->assign('useraddr', D('Useraddr')->where(array('user_id' => $this->uid))->limit()->select());
        $this->assign('payment', D('Payment')->getPayments());
        $this->assign('logs', $detail);
        $this->mobile_title = '订单支付';
        $this->display();
    }
    
    public function pay() {
        if (empty($this->uid)) {
            AppJump();
        }
        
        $this->check_mobile();
        
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
        $this->assign('useraddr', D('Useraddr')->where(array('user_id' => $this->uid))->limit()->select());
        $this->assign('order', $order);
        $this->assign('payment', D('Payment')->getPayments(true));
        $this->mobile_title = '订单支付';
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
            $this->error('请选择一个要配送的地址！');
        }
        D('Order')->where(array('order_id' => array('IN', $order_ids)))->save(array('addr_id' => $addr_id));
        if (!$code = $this->_post('code')) {
            $this->error('请选择支付方式！');
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
             D('Payment')->mallSold($order_ids);
            D('Payment')->mallPeisong(array($order_ids),1);
             D('Sms')->mallTZshop($order_ids);
            $this->success('恭喜您下单成功！', U('mcenter/goods/index'));
        }else{
            $payment = D('Payment')->checkPayment($code);
            if (empty($payment)) {
                $this->error('该支付方式不存在');
            }
            $detail['code'] = $code;
            D('Paymentlogs')->save($detail);
            header("Location:" . U('cart/combine', array('log_id' => $detail['log_id'])));
        }
    }
    
    public function combine(){
         if (empty($this->uid)) {
            AppJump();
        }
        $log_id = (int) $this->_get('log_id');
        if (!$detail = D('Paymentlogs')->find($log_id)) {
            $this->error('没有有效的支付记录！');
        }
        if ($detail['is_paid'] != 0 || empty($detail['order_ids']) || !empty($detail['order_id']) || empty($detail['need_pay'])) {
            $this->error('没有有效的支付记录！');
        }
        $this->assign('button', D('Payment')->getCode($detail));
        $this->assign('logs',$detail);
        $this->mobile_title = '订单支付';
        $this->display();
    }
    
    public function pay2() {
        if (empty($this->uid)) {
            AppJump();
        }
        $order_id = (int) $this->_get('order_id');
        $order = D('Order')->find($order_id);
        if (empty($order) || $order['status'] != 0 || $order['user_id'] != $this->uid) {
            $this->error('该订单不存在');
        }

        $addr_id = (int) $this->_post('addr_id');

        if (empty($addr_id)) {
            $this->error('请选择一个要配送的地址！');
        }
        D('Order')->save(array('addr_id' => $addr_id, 'order_id' => $order_id));
        if (!$code = $this->_post('code')) {
            $this->error('请选择支付方式！');
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

            $goods_ids   = D('Ordergoods')->where("order_id={$order_id}")->getField('goods_id',true);
            $goods_ids   = implode(',', $goods_ids);
            $map         = array('goods_id'=>array('in',$goods_ids));
            $goods_name  = D('Goods')->where($map)->getField('title',true);
            $goods_name  = implode(',', $goods_name);

            include_once "Kintime/Lib/Net/Wxmesg.class.php";
            /*微信订单通知消息-开始*/
            $notice_data = array(
                'url'     =>  "http://".$_SERVER['HTTP_HOST']."/mcenter/goods/detail/order_id/".$order_id.".html",
                'first'   => '亲,您的订单创建成功!',
                'remark'  => '详情请登录-http://'.$_SERVER['HTTP_HOST'],
                'amount'  => round($order['total_price']/100,2).'元',
                'order'   => $order_id,
                'info'    => $goods_name
            );
            $notice_data = Wxmesg::notice($notice_data);            
            Wxmesg::net($this->uid, 'OPENTM206930158', $notice_data);
            /*微信订单通知消息-结束*/
            $this->success('恭喜您下单成功！', U('mcenter/goods/index'));
        } else {
            $payment = D('Payment')->checkPayment($code);
            if (empty($payment)) {
                $this->error('该支付方式不存在');
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
                $logs['need_pay'] =$need_pay;
                $logs['code'] = $code;
                D('Paymentlogs')->save($logs);
            }

            $goods_ids   = D('Ordergoods')->where("order_id={$order_id}")->getField('goods_id',true);
            $goods_ids   = implode(',', $goods_ids);
            $map         = array('goods_id'=>array('in',$goods_ids));
            $goods_name  = D('Goods')->where($map)->getField('title',true);
            $goods_name  = implode(',', $goods_name);

             include_once "Kintime/Lib/Net/Wxmesg.class.php";
            /*微信订单通知消息-开始*/
            $notice_data = array(
                'url'     =>  "http://".$_SERVER['HTTP_HOST']."/mcenter/goods/detail/order_id/".$order_id.".html",
                'first'   => '亲,您的订单创建成功!',
                'remark'  => '详情请登录-http://'.$_SERVER['HTTP_HOST'],
                'amount'  => round($order['total_price']/100,2).'元',
                'order'   => $order_id,
                'info'    => $goods_name
            );
            $notice_data = Wxmesg::notice($notice_data);            
            Wxmesg::net($this->uid, 'OPENTM206930158', $notice_data);
            /*微信订单通知消息-结束*/
            
                header("Location:" . U('payment/payment', array('log_id' => $logs['log_id'])));
                die;
        }
    }

   

     public function shopdetail() {
            $id = (int) $this->_param('id');
        if (!$wshop = D('WeidianDetails')->find($id)) {
            $this->error('没有该微店商家');
            die;
        }
        if ($wshop['closed'] != 0 ||$wshop['audit'] !=1) {
            $this->error('该微店商家不存在');
            die;
        }
        if(!$detail = D('Shop')->find($wshop['shop_id'])){
            $this->error('没有该商家');
            die;
        }
        if ($detail['closed'] != 0 ||$detail['audit'] !=1) {
            $this->error('该商家不存在');
            die;
        }
        $this->assign('wshop',$wshop);
        $this->assign('detail',$detail);
        $this->assign('ex',D('Shopdetails')->find($wshop['shop_id']));
        $this->mobile_title = '商家详情';
        $this->display();
    }

    public function dianping() {
        $id = (int) $this->_param('id');
        if (!$wshop = D('WeidianDetails')->find($id)) {
            $this->error('没有该微店商家');
            die;
        }
        if ($wshop['closed'] != 0 ||$wshop['audit'] !=1) {
            $this->error('该微店商家不存在');
            die;
        }
        if(!$detail = D('Shop')->find($wshop['shop_id'])){
            $this->error('没有该商家');
            die;
        }
        if ($detail['closed'] != 0 ||$detail['audit'] !=1) {
            $this->error('该商家不存在');
            die;
        }
        $this->assign('wshop',$wshop);
        $this->assign('detail', $detail);
        $this->mobile_title = '商家评价';
        $this->display();
    }

    public function dianpingloading() {
        $id = (int) $this->_get('id');
        if (!$wshop = D('WeidianDetails')->find($id)) {
            die('0');
        }
        if ($wshop['closed'] != 0 ||$wshop['audit'] != 1) {
            die('0');
        }
        if(!$detail = D('Shop')->find($wshop['shop_id'])){
           die('0');
        }
        if ($detail['closed'] != 0 ||$detail['audit'] !=1) {
            die('0');
        }
        $shopdianping = D('Shopdianping');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0, 'shop_id' => $detail['shop_id'], 'show_date' => array('ELT', TODAY));
        $count = $shopdianping->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 5); // 实例化分页类 传入总记录数和每页显示的记录数

        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }

        $show = $Page->show(); // 分页显示输出
        $list = $shopdianping->where($map)->order(array('dianping_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
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
            $this->assign('pics', D('Shopdianpingpics')->where(array('dianping_id' => array('IN', $dianping_ids)))->select());
        }
        $this->assign('totalnum', $count);
        $this->assign('list', $list); // 赋值数据集
        $this->assign('detail', $detail);
        $this->assign('wshop',$wshop);
        $this->display();
    }
    
    
}
