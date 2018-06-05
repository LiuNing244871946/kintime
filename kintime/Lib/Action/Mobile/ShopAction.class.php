<?php



class ShopAction extends CommonAction {

    public function index() {
        $cat = (int) $this->_param('cat');
        $this->assign('cat', $cat);
        $order = (int) $this->_param('order');
        $this->assign('order', $order);
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        $areas = D('Area')->fetchAll();
        $area = (int) $this->_param('area');
        $this->assign('area_id', $area);
        $biz = D('Business')->fetchAll();
        $business = (int) $this->_param('business');
        $this->assign('business_id', $business);
        $this->assign('areas', $areas);
        $this->assign('biz', $biz);
        $this->assign('nextpage', LinkTo('shop/loaddata', array('cat' => $cat, 'area' => $area, 'business' => $business, 'order' => $order, 't' => NOW_TIME, 'keyword' => $keyword, 'p' => '0000')));
        $this->mobile_title = '商家列表';
        $this->display(); // 输出模板   
    }

    public function branch() {

        $shop_id = I('shop_id', 0, 'intval,trim');
        $s = D('Shop');
        $rs = $s->where('shop_id =' . $shop_id)->find();
        $this->assign('rs', $rs);

        $sb = D('ShopBranch');
        $rsb = $sb->where('shop_id =' . $shop_id)->select();
        $count = $sb->where('shop_id =' . $shop_id)->count();

        $this->assign('count', $count);

        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if (empty($lat) || empty($lng)) {
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }

        foreach ($rsb as $k => $val) {
            $rsb[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);
        }

        $this->assign('rsb', $rsb);
        $this->mobile_title = '商家分店';
        $this->display();
    }

    public function gps($shop_id) {
        $shop_id = (int) $shop_id;
        if (empty($shop_id)) {
            $this->error('该商家不存在');
        }
        $shop = D('Shop')->find($shop_id);
        $this->assign('shop_id', $shop_id);
        $this->assign('shop', $shop);
        $this->mobile_title = '路线导航';
        // var_dump($shop);
        $this->display();
    }

    public function gps2($branch_id) {
        $branch_id = (int) $branch_id;
        if (empty($branch_id)) {
            $this->error('该商家不存在');
        }
        $shop = D('ShopBranch')->find($branch_id);
        $this->assign('shop', $shop);
        $this->mobile_title = '路线导航';
        $this->display();
    }

    public function main() {

        $this->display();
    }

    public function loaddata() {

        $Shop = D('Shop');
        import('ORG.Util.Page'); // 导入分页类
        //初始数据
        $map = array('closed' => 0, 'audit' => 1, 'city_id' => $this->city_id);
        $cat = (int) $this->_param('cat');
        if ($cat) {
            $catids = D('Shopcate')->getChildren($cat);
            if (!empty($catids)) {
                $map['cate_id'] = array('IN', $catids);
            } else {
                $map['cate_id'] = $cat;
            }
        }

        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['shop_name|addr'] = array('LIKE', '%' . $keyword . '%');
        }
        $area = (int) $this->_param('area');
        if ($area) {
            $map['area_id'] = $area;
        }
        $business = (int) $this->_param('business');
        if ($business) {
            $map['business_id'] = $business;
        }

        $order = (int) $this->_param('order');

        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if (empty($lat) || empty($lng)) {
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        switch ($order) {
            case 2:
                $orderby = array('orderby' => 'asc', 'ranking' => 'desc');
                break;
            default:
                $orderby = " (ABS(lng - '{$lng}') +  ABS(lat - '{$lat}') ) asc ";

                break;
        }
        $count = $Shop->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 20); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出

        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $Shop->where($map)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();

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
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板   
    }

    public function detail() {
        $shop_id = (int) $this->_get('shop_id');
        if (!$detail = D('Shop')->find($shop_id)) {
            $this->error('没有该商家');
            die;
        }
        if ($detail['closed']) {
            $this->error('该商家已经被删除');
            die;
        }
        $detail['zhu'] = 0;
        if (D('Shopfavorites')->where(array('shop_id'=>$shop_id,'user_id'=>$this->uid,'type'=>1))->select()) {
            $detail['zhu'] = 1;
        }
        $this->assign('detail', $detail);
        $this->assign('ex', D('Shopdetails')->find($shop_id));
        $tuans = D('Tuan')->order(' tuan_id desc')->where(array('shop_id' => $shop_id, 'audit' => 1, 'city_id' => $this->city_id, 'closed' => 0, 'bg_date' => array('ELT', NOW), 'end_date' => array('EGT', NOW)))->limit(0, 3)->select();
        $this->assign('tuans', $tuans);
        D('Shop')->updateCount($shop_id, 'view');
        if ($this->uid) {
            D('Userslook')->look($this->uid, $shop_id,1);
        }
        $ele = D('Ele')->where(array('shop_id' => $shop_id))->find();
        $this->assign('ele', $ele);



        $this->mobile_title = '商家详情';
        $this->display();
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
        if (D('Shopfavorites')->where(array('shop_id'=>$shop_id,'user_id'=>$this->uid,'type'=>1))->select()) {
            $this->error('您已经关注过了！');
        }
        $data = array(
            'shop_id' => $shop_id,
            'user_id' => $this->uid,
            'create_time' => NOW_TIME,
            'create_ip' => get_client_ip(),
            'type'=>1
        );
        if (D('Shopfavorites')->add($data)) {
        	D('Shop')->updateCount($shop_id,'fans_num');
            $this->success('恭喜您关注成功！', U('shop/detail', array('shop_id' => $shop_id)));
        }
        $this->error('关注失败！');
    }
    
    public function favorites_news() {
        if (empty($this->uid)) {
            AppJump();
        }
        $news_id = (int) $this->_get('news_id');
       
        if (!$detail = D('ShopNews')->find($news_id)) {
            $this->error('没有该新闻');
        }
       
        if (D('Shopfavorites')->check($detail['shop_id'], $this->uid)) {
            $this->error('您已经关注过了！');
        }
        $data = array(
            'shop_id' => $detail['shop_id'],
            'user_id' => $this->uid,
            'create_time' => NOW_TIME,
            'create_ip' => get_client_ip()
        );
        if (D('Shopfavorites')->add($data)) {
            $this->success('恭喜您关注成功！', U('headline/news_detail', array('news_id' => $news_id)));
        }
        $this->error('关注失败！');
    }

    //点评
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
        // var_dump($detail);
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
        $Tuandianping = D('TuanDianping');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0, 'shop_id' => $shop_id, 'show_date' => array('ELT', TODAY));
        $count = $Tuandianping->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 5); // 实例化分页类 传入总记录数和每页显示的记录数

        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            $this->assign('totalnum', $count);
            $this->display();
            die();
        }

        $show = $Page->show(); // 分页显示输出
        $list = $Tuandianping->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $dianping_ids = array();
        foreach ($list as $k => $val) {
            $list[$k] = $val;
            $user_ids[$val['user_id']] = $val['user_id'];
            // $dianping_ids[$val['dianping_id']] = $val['dianping_id'];
            $dianping_ids[$val['order_id']] = $val['order_id'];
        }
        if (!empty($user_ids)) {
            $this->assign('users', D('Users')->itemsByIds($user_ids));
        }
        if (!empty($dianping_ids)) {
            // var_dump($dianping_ids);
            $pics = D('Tuandianpingpics')->where(array('order_id' => array('IN', $dianping_ids)))->select();
            $this->assign('pics', $pics);
        }
        $this->assign('totalnum', $count);
        $this->assign('list', $list); // 赋值数据集
        $this->assign('detail', $detail);
        // var_dump($list);
        $this->display();
    }

    public function tuan() {
        $shop_id = (int) $this->_param('shop_id');
        $this->assign('nextpage', LinkTo('shop/tuanload', array('shop_id' => $shop_id, 't' => NOW_TIME, 'p' => '0000')));
        $this->assign('shop_id', $shop_id);
        $this->assign('shop', D('Shop')->find($shop_id));
        $this->mobile_title = '商家抢购';
        $this->display(); // 输出模板   
    }

    public function tuanload() {
        $tuan = D('Tuan');
        import('ORG.Util.Page'); // 导入分页类
        //初始数据
        $shop_id = (int) $this->_param('shop_id');
        $map = array('closed' => 0, 'shop_id' => $shop_id, 'audit' => 1, 'city_id' => $this->city_id, 'end_date' => array('EGT', NOW));
        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if (empty($lat) || empty($lng)) {
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        $orderby = " (ABS(lng - '{$lng}') +  ABS(lat - '{$lat}') ) asc ";
        $count = $tuan->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 8); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出

        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $tuan->where($map)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板   
    }
    
    public function goods() {
        $shop_id = (int) $this->_param('shop_id');
        $this->assign('nextpage', LinkTo('shop/goodsload', array('shop_id' => $shop_id, 't' => NOW_TIME, 'p' => '0000')));
        $this->assign('shop_id', $shop_id);
        $this->assign('shop', D('Shop')->find($shop_id));
        $this->mobile_title = '商家商品';
        $this->display(); // 输出模板   
    }

    public function goodsload() {
        $goods = D('Goods');
        import('ORG.Util.Page'); // 导入分页类
        //初始数据
        $shop_id = (int) $this->_param('shop_id');
        $map = array('closed' => 0, 'shop_id' => $shop_id, 'audit' => 1, 'city_id' => $this->city_id, 'end_date' => array('EGT', TODAY));
        $count = $goods->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 8); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出

        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $goods->where($map)->order(array('orderby'=>'asc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板   
    }
    

    public function coupon() {
        $shop_id = (int) $this->_param('shop_id');
        $this->assign('nextpage', LinkTo('shop/couponload', array('shop_id' => $shop_id, 't' => NOW_TIME, 'p' => '0000')));
        $this->assign('shop_id', $shop_id);
        $this->assign('shop', D('Shop')->find($shop_id));
        $this->mobile_title = '商家优惠';
        $this->display();
    }

    public function couponload() {
        $coupon = D('Coupon');
        import('ORG.Util.Page'); // 导入分页类
        //初始数据
        $shop_id = (int) $this->_param('shop_id');
        $map = array('closed' => 0, 'shop_id' => $shop_id, 'audit' => 1, 'city_id' => $this->city_id, 'expire_date' => array('EGT', TODAY));
        $count = $coupon->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 8); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出

        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $coupon->where($map)->order(array('downloads' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display();
    }

    public function shopdetail() {
        $shop_id = (int) $this->_param('shop_id');
        if (!$detail = D('Shop')->find($shop_id)) {
            $this->error('没有该商家');
            die;
        }
        if ($detail['closed']) {
            $this->error('该商家已经被删除');
            die;
        }
        $this->assign('detail', $detail);
        $this->assign('ex', D('Shopdetails')->find($shop_id));
        $this->mobile_title = '商家详情';
        $this->display();
    }

    public function breaks($shop_id) { //优惠买单
        if (!$this->uid) {
            AppJump();
        }
        $shop_id = (int) $shop_id;
        if (!$shop_id) {
            $this->error('该商家没有设置买单优惠');
        } elseif (!$detail = D('Shopyouhui')->where(array('shop_id'=>$shop_id,'is_open'=>1))->find()) {
            $this->error('该商家没有设置买单优惠或已关闭');
        }
        if ($this->isPost()) {
            $amount = floatval($_POST['amount']);
            if(empty($amount)){
                $this->baoError('消费金额不能为空');
            }
            $exception = floatval($_POST['exception']);
            $need_pay = D('Shopyouhui')->get_amount($shop_id, $amount, $exception);
            $data = array(
                'shop_id' => $shop_id,
                'user_id' => $this->uid,
                'amount' => $amount,
                'exception' => $exception,
                'need_pay' => $need_pay,
                'create_time' => NOW_TIME,
                'create_ip' => get_client_ip(),
            );
            if ($order_id = D('Breaksorder')->add($data)) {
                $this->baoSuccess('创建订单成功！', U('shop/breakspay', array('order_id' => $order_id)),U('shop/breakspay', array('order_id' => $order_id)));
            } else {
                $this->baoError('创建订单失败！');
            }
        } else {
            $this->assign('detail', $detail);
            $this->mobile_title = '优惠买单';
            $this->display();
        }
    }

    public function breakspay() {
        if (empty($this->uid)) {
            AppJump();
        }
        $order_id = (int) $this->_get('order_id');
        $order = D('Breaksorder')->find($order_id);
        if (empty($order) || $order['status'] != 0 || $order['user_id'] != $this->uid) {
            $this->baoError('该订单不存在');
        }
        $shop = D('Shop')->find($order['shop_id']);
        $this->assign('payment', D('Payment')->getPayments(true));
        $this->assign('shop', $shop);
        $this->assign('order', $order);
        $this->display();
    }

    public function breakspay2() {
        if (empty($this->uid)) {
            AppJump();
        }
        $order_id = (int) $this->_get('order_id');
        $order = D('Breaksorder')->find($order_id);
        if (empty($order) || (int) $order['status'] != 0 || $order['user_id'] != $this->uid) {
            $this->error('该订单不存在');
        }
        if (!$code = $this->_post('code')) {
            $this->error('请选择支付方式！');
        }
        $logs = D('Paymentlogs')->getLogsByOrderId('breaks', $order_id);
        if (empty($logs)) {
            $logs = array(
                'type' => 'breaks',
                'user_id' => $this->uid,
                'order_id' => $order_id,
                'code' => $code,
                'need_pay' => $order['need_pay']*100,
                'create_time' => NOW_TIME,
                'create_ip' => get_client_ip(),
                'is_paid' => 0
            );
            $logs['log_id'] = D('Paymentlogs')->add($logs);
        } else {
            $logs['need_pay'] = $order['need_pay']*100;
            $logs['code'] = $code;
            D('Paymentlogs')->save($logs);
        }
        
            header("Location:" . U('payment/payment', array('log_id' => $logs['log_id'])));
            die;
    }
	public function apply() {
        if (empty($this->uid)) {
            header("Location:" . U('passport/login'));
            die;
        }
        if (D('Shop')->find(array('where' => array('user_id' => $this->uid)))) {

            $this->error('您已经拥有一家店铺了！', U('shangjia/index/index'));
        }
        if ($this->isPost()) {
            $yzm = $this->_post('yzm');
            if (strtolower($yzm) != strtolower(session('verify'))) {
                session('verify', null);
                $this->baoError('验证码不正确!', 2000, true);
            }

            $data = $this->createCheck();

            $obj = D('Shop');
            $details = $this->_post('details', 'htmlspecialchars');
            if ($words = D('Sensitive')->checkWords($details)) {
                $this->baoError('商家介绍含有敏感词：' . $words, 2000, true);
            }

            $ex = array(
                'details' => $details,
                'near' => $data['near'],
                'price' => $data['price'],
                'business_time' => $data['business_time'],
            );
            unset($data['near'], $data['price'], $data['business_time']);
            if ($shop_id = $obj->add($data)) {
                $wei_pic = D('Weixin')->getCode($shop_id, 1);
                $ex['wei_pic'] = $wei_pic;
                D('Shopdetails')->upDetails($shop_id, $ex);
                $this->baoSuccess('恭喜您申请成功！', U('shop/index'));
            }
            $this->baoError('申请失败！');
        } else {
            $areas = D('Area')->fetchAll();
            $this->assign('cates', D('Shopcate')->fetchAll());
            $this->assign('areas', $areas);
            $this->display();
        }
    }
	private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), array('cate_id', 'tel', 'logo', 'photo', 'shop_name', 'contact', 'details', 'business_time','city_id', 'area_id','business_id', 'addr', 'lng', 'lat'));
        $data['shop_name'] = htmlspecialchars($data['shop_name']);
        if (empty($data['shop_name'])) {
            $this->baoError('店铺名称不能为空', 2000, true);
        }
        $data['lng'] = htmlspecialchars($data['lng']);
        $data['lat'] = htmlspecialchars($data['lat']);
        if (empty($data['lng']) || empty($data['lat'])) {
            $this->baoError('店铺坐标需要设置', 2000, true);
        }
        $data['cate_id'] = (int) $data['cate_id'];
        if (empty($data['cate_id'])) {
            $this->baoError('分类不能为空', 2000, true);
        }
        $data['city_id'] = (int) $data['city_id'];
        if (empty($data['city_id'])) {
            $this->baoError('城市不能为空', 2000, true);
        }
        $data['area_id'] = (int) $data['area_id'];
        if (empty($data['area_id'])) {
            $this->baoError('地区不能为空', 2000, true);
        }$data['business_id'] = (int) $data['business_id'];
        if (empty($data['business_id'])) {
            $this->baoError('商圈不能为空', 2000, true);
        }$data['contact'] = htmlspecialchars($data['contact']);
        if (empty($data['contact'])) {
            $this->baoError('联系人不能为空', 2000, true);
        }$data['business_time'] = htmlspecialchars($data['business_time']);
        if (empty($data['business_time'])) {
            $this->baoError('营业时间不能为空', 2000, true);
        }
        if (!isImage($data['logo'])) {
            $this->baoError('请上传正确的LOGO', 2000, true);
        }
        if (!isImage($data['photo'])) {
            $this->baoError('请上传正确的店铺图片', 2000, true);
        }
        $data['addr'] = htmlspecialchars($data['addr']);
        if (empty($data['addr'])) {
            $this->baoError('地址不能为空', 2000, true);
        }
        $data['tel'] = htmlspecialchars($data['tel']);
        if (empty($data['tel'])) {
            $this->baoError('联系方式不能为空', 2000, true);
        }
        if (!isPhone($data['tel']) && !isMobile($data['tel'])) {
            $this->baoError('联系方式格式不正确', 2000, true);
        }
        $detail = D('Shop')->where(array('user_id' => $this->uid))->find();
        if (!empty($detail)) {
            $this->baoError('您已经是商家了', 2000, true);
        }
        $data['user_id'] = $this->uid;
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        return $data;
    }


}
