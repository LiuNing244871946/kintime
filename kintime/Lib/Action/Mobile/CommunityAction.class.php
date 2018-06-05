<?php



class CommunityAction extends CommonAction {

    public function owner($community_id) {
        if (empty($this->uid)) {
           AppJump();
        }
        $community_id = (int) $community_id;

        if (empty($community_id)) {
            $this->error('小区不存在');
        }
        if (!$detail = D('Community')->find($community_id)) {
            $this->error('小区不存在');
        }
        if ($detail['closed'] != 0) {
            $this->error('小区不存在');
        }
        if ($this->isPost()) {
            $data['location'] = htmlspecialchars($_POST['location']);
            if (empty($data['location'])) {
                $this->baoError('具体地址不能为空');
            }
            $data['name'] = htmlspecialchars($_POST['name']);
            if (empty($data['name'])) {
                $this->baoError('称呼不能为空');
            }
            $data['user_id'] = $this->uid;
            $data['community_id'] = $community_id;
            $data['create_time'] = NOW_TIME;
            $data['create_ip'] = get_client_ip();
            $obj = D('Communityowner');
            if (!$res = $obj->where(array('community_id' => $community_id, 'user_id' => $this->uid))->find()) {
                if ($obj->add($data)) {
                    $this->baoSuccess('申请成功，请等待物业审核',U('community/detail',array('community_id'=>$community_id)),U('community/detail',array('community_id'=>$community_id)));
                }
                $this->baoError('申请失败');
            }
            $this->baoError('不能重复申请');
        } else {
            $this->assign('detail', $detail);
            $this->mobile_title = '入驻小区';
            $this->display();
        }
    }

    public function index() {
        $community_id = cookie('community_id');
        if ($community_id && empty($_GET['change'])) {
            $this->detail($community_id);
            die;
        }
       
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        //$areas = D('Area')->fetchAll();
        //$this->assign('areas', $areas);
        $area = (int) $this->_param('area');
        $this->assign('area_id', $area);
        $this->assign('nextpage', LinkTo('community/loaddata', array('area' => $area, 't' => NOW_TIME, 'change' => '1', 'keyword' => $keyword, 'p' => '0000')));
        $this->mobile_title = '全城';
        $this->display(); // 输出模板 
        exit;
    }

    public function loaddata() {

        $community = D('Community');
        import('ORG.Util.Page'); // 导入分页类
        //初始数据
        $map = array('closed' => 0, 'city_id' => $this->city_id);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['name|addr'] = array('LIKE', '%' . $keyword . '%');
        }
        $area = (int) $this->_param('area');
        if ($area) {
            $map['area_id'] = $area;
        }
        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if (empty($lat) || empty($lng)) {
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        $orderby = " (ABS(lng - '{$lng}') +  ABS(lat - '{$lat}') ) asc ";
        $count = $community->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 8); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出

        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $community->order($orderby)->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $val) {
            $list[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);
        }
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板   
    }

    public function detail($community_id) {
        $community_id = (int) $community_id;
        $community = D('Community');
        if (empty($community_id)) {
            $this->error('没有该小区');
            die;
        }
        if (!$detail = $community->find($community_id)) {
            $this->error('没有该小区');
            die;
        }
        if ($detail['closed']) {
            $this->error('该小区已经被删除');
            die;
        }
        cookie('community_id', $community_id, 365 * 86400);
        $lat = $detail['lat'];
        $lng = $detail['lng'];
        if (empty($lat) || empty($lng)) {
            $lat = addslashes(cookie('lat'));
            $lng = addslashes(cookie('lng'));
        }
        $orderby = " (ABS(lng - '{$lng}') +  ABS(lat - '{$lat}') ) asc ";
        $eles = D('Ele')->order($orderby)->where(array('audit' => 1, 'city_id' => $this->city_id))->limit(2)->select();
        $shop_ids = array();
        foreach ($eles as $k => $val) {
            $eles[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);
            $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
        $this->assign('eles', $eles);
        $this->assign('eshop', D('Shop')->itemsByIds($shop_ids));
        //商家
        $shops = D('Shop')->order($orderby)->where(array('audit' => 1, 'closed' => 0, 'city_id' => $this->city_id))->limit(2)->select();
        foreach ($shops as $k => $val) {
            $shops[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);
        }
        $this->assign('shops', $shops);

        $this->assign('count', D('Shop')->where(array('audit' => 1, 'closed' => 0, 'city_id' => $this->city_id))->count());
        $weicates = D('Weidiancate')->fetchAll();
        $this->assign('weicates', $weicates);
        
        //微店
        $weidian = D('Weidiandetails')->select();
        foreach ($weicates as $k => $val) {
            foreach ($weidian as $kk => $v) {
                if ($v['cate_id'] == $val['cate_id']) {
                    $weicates[$k]['res'][] = $v;
                }
            }
        }
        //ad
        $ads = D('Communityad')->order(array('orderby' => 'asc'))->where(array('community_id' => $community_id))->limit(5)->select();
        $this->assign('ads', $ads);
        $this->assign('weicates', $weicates);
        //print_r($weicates);exit;
        $this->assign('detail', $detail);
        $this->mobile_title = $detail['name'];
        $this->display('detail');
    }

    public function newslist() {
        $community_id = (int) $this->_param('community_id');
        $detail = D('Community')->find($community_id);
        $ads = D('Communityad')->order(array('orderby' => 'asc'))->where(array('community_id' => $community_id))->limit(5)->select();
        $this->assign('ads', $ads);
        $this->assign('next', LinkTo('community/load', array('t' => NOW_TIME, 'community_id' => $community_id, 'p' => '0000')));
        $this->assign('detail', $detail);
        $this->mobile_title = '物业通知';
        $this->display(); // 输出模板
    }

    public function load() {
        $community_id = (int) $this->_param('community_id');
        $news = D('Communitynews');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0, 'audit' => 1);
        $map['community_id'] = $community_id;
        $count = $news->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 8); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出

        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $news->order(array('news_id' => 'desc'))->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板   
    }

    public function news() {
        $news_id = (int) $this->_param('news_id');
        if (!$detail = D('Communitynews')->find($news_id)) {
            $this->error('没有该物业通知');
            die;
        }
        if ($detail['closed']) {
            $this->error('该物业通知已经被删除');
            die;
        }
        if (!$detail['audit']) {
            $this->error('该物业通知未通过审核');
            die;
        }
        D('Communitynews')->updateCount($news_id, 'views');
        $this->assign('detail', $detail);
        $this->mobile_title = '通知详情';
        $this->display();
    }

    public function feedback($community_id) {
        if (empty($this->uid)) {
            AppJump();
        }
        $community_id = (int) $community_id;
        if (!$detail = D('Community')->find($community_id)) {
            $this->error('要反馈的小区不存在');
        }
        if (!empty($detail['closed'])) {
            $this->error('要反馈的小区不存在');
        }
        if ($this->isPost()) {
            $data = $this->checkFeed();
            $data['community_id'] = $community_id;
            $data['create_time'] = NOW_TIME;
            $data['create_ip'] = get_client_ip();
            $obj = D('Feedback');
            if ($obj->add($data)) {
                $this->success('反馈提交成功', U('community/detail', array('community_id' => $community_id)));
            }
            $this->error('操作失败！');
        } else {
            $this->assign('community_id', $community_id);
            $this->mobile_title = '信息反馈';
            $this->display();
        }
    }

    public function checkFeed() {
        $data = $this->checkFields($this->_post('data', false), array('title', 'content'));
        $data['user_id'] = (int) $this->uid;
        $data['title'] = $data['title'];
        if (empty($data['title'])) {
            $this->error('标题不能为空');
        }
        $data['content'] = htmlspecialchars($data['content']);
        if (empty($data['content'])) {
            $this->error('反馈内容不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['content'])) {
            $this->baoError('反馈内容含有敏感词：' . $words);
        }
        return $data;
    }

    //申请合作
    public function together($community_id = null) {
        if (!$community_id) {
            $this->error('参数不正确！');
        } else if ($data = $this->_post('data', false)) {
            $data['expiry_date'] = NOW_TIME;
            $data['orderby'] = 0;
            if (empty($data['name'])) {
                $this->error('项目名称不能为空！');
            }
            if (empty($data['phone'])) {
                $this->error('手机号码不能为空！');
            }
            if ($phone_id = D('Convenientphone')->add($data)) {
                if (D('Convenientphonemaps')->add(array('phone_id' => $phone_id, 'community_id' => $community_id))) {
                    $this->success('您的申请提交成功,等待审核', U('community/index'));
                } else {
                    $this->error('申请失败');
                }
            } else {
                $this->error('申请失败');
            }
        } else {
            $this->assign('community_id', $community_id);
            $this->mobile_title = '申请合作';
            $this->display();
        }
    }

    //News详情
    public function newsdetail($news_id = null) {
        if (!$news_id) {
            $this->error('参数不正确!');
        } else {
            $model = D('Communitynews');
            if ($detail = $model->find($news_id)) {
                $model->updateCount($news_id, 'views');
            }
            $community = D('Community')->find($detail['community_id']);
            $this->assign('community', $community);
            $this->assign('detail', $detail);
            $this->mobile_title = '通知详情';
            $this->display();
        }
    }

    public function tuan() {
        $community_id = (int) $this->_param('community_id');
        $detail = D('Community')->find($community_id);
        $this->assign('nextpage', LinkTo('community/tuanload', array('t' => NOW_TIME, 'community_id' => $community_id, 'p' => '0000')));
        $this->assign('detail', $detail);
        $this->mobile_title = '小区抢购';
        $this->display(); // 输出模板
    }

    public function tuanload() {
        $community_id = (int) $this->_param('community_id');
        $detail = D('Community')->find($community_id);
        $tuan = D('Tuan');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('audit' => 1, 'closed' => 0, 'city_id' => $this->city_id, 'end_date' => array('EGT', NOW));
        $lat = $detail['lat'];
        $lng = $detail['lng'];
        if (empty($lat) || empty($lng)) {
            $lat = addslashes(cookie('lat'));
            $lng = addslashes(cookie('lng'));
        }
        $orderby = " (ABS(lng - '{$lng}') +  ABS(lat - '{$lat}') ) asc ";

        $count = $tuan->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出

        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $tuan->order($orderby)->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $k => $val) {
            $list[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);
        }
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板   
    }

    public function ele() {
        $community_id = (int) $this->_param('community_id');
      //  echo $community_id;die;
        $detail = D('Community')->find($community_id);
        $this->assign('nextpage', LinkTo('community/eleload', array('t' => NOW_TIME, 'community_id' => $community_id, 'p' => '0000')));
        $this->assign('detail', $detail);
        $this->mobile_title = '小区外卖';
        $this->display(); // 输出模板
    }

    public function eleload() {
        $community_id = (int) $this->_param('community_id');
        $community = D('Community')->find($community_id);
        $ele = D('Ele');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('audit' => 1, 'city_id' => $this->city_id);
        $lat = $community['lat'];
        $lng = $community['lng'];
        if (empty($lat) || empty($lng)) {
            $lat = addslashes(cookie('lat'));
            $lng = addslashes(cookie('lng'));
        }
        $orderby = " (ABS(lng - '{$lng}') +  ABS(lat - '{$lat}') ) asc ";

        $count = $ele->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出

        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $ele->order($orderby)->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $shop_ids = array();
        foreach ($list as $k => $val) {
            $shop_ids[$val['shop_id']] = $val['shop_id'];
            $list[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);
        }
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板   
    }

    public function weidian() {
        $community_id = (int) $this->_param('community_id');
        $detail = D('Community')->find($community_id);
        $this->assign('nextpage', LinkTo('community/weiload', array('t' => NOW_TIME, 'community_id' => $community_id, 'p' => '0000')));
        $this->assign('detail', $detail);
        $this->mobile_title = '小区微店';
        $this->display(); // 输出模板
    }

    public function weiload() {
        $community_id = (int) $this->_param('community_id');
        $detail = D('Community')->find($community_id);
        $weidian = D('Weidiandetails');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('audit' => 1, 'city_id' => $this->city_id);
        $lat = $detail['lat'];
        $lng = $detail['lng'];
        if (empty($lat) || empty($lng)) {
            $lat = addslashes(cookie('lat'));
            $lng = addslashes(cookie('lng'));
        }
        $orderby = " (ABS(lng - '{$lng}') +  ABS(lat - '{$lat}') ) asc ";

        $count = $weidian->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出

        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $weidian->order($orderby)->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $k => $val) {
            $list[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);
        }
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $cates = D('Weidiancate')->fetchAll();
        $this->assign('cates', $cates);
        $this->display(); // 输出模板   
    }

    public function order($community_id) {  //物业缴费
        if (empty($this->uid)) {
            AppJump();
        }
        if (empty($community_id)) {
            $this->error('小区不存在');
        }
        if (!$detail = D('Community')->find($community_id)) {
            $this->error('没有该小区');
            die;
        }
        if ($detail['closed']) {
            $this->error('该小区已经被删除');
            die;
        }
        if (!$res = D('Communityowner')->where(array('community_id' => $community_id, 'user_id' => $this->uid))->find()) {
            redirect(U('community/owner', array('community_id' => $community_id)));
        }
        $obj = D('Communityorder');
        $map = array('user_id' => $this->uid, 'community_id' => $community_id);
        $order_date = $this->_param('order_date', 'htmlspecialchars');
        $now_date = date('Y-m', NOW_TIME);
        if (!empty($order_date)) {
            $map['order_date'] = $order_date;
            $this->assign('order_date', $order_date);
        } else {
            $this->assign('order_date', $now_date);
            $map['order_date'] = $now_date;
        }
        $list = $obj->where($map)->find();

        $products = D('Communityorderproducts')->where(array('order_id' => $list['order_id']))->select();
        $types = D('Communityorder')->getType();
        foreach ($products as $k => $val) {
            $products[$k]['type_name'] = $types[$val['type']];
        }
        //dump($products);
        $days = array();
        $Y = date('Y', NOW_TIME);
        $m = date('m', NOW_TIME);
        $d = 12 - $m;
        for ($k = 1; $k <= $d; $k++) {
            if($k>2){
                $days[$k] = ($Y - 1) . '-0' . (12 - $k);
            }else{
                $days[$k] = ($Y - 1) . '-' . (12 - $k);
            }
        }
        for ($i = 1; $i <= $m; $i++) {
            if($i<10){
                $days[$d + $i] = $Y . '-0' . $i;                
            }else{
                $days[$d + $i] = $Y . '-' . $i;             
            }
        }
        $this->assign('days', $days);
        $this->assign('products', $products);
        $this->assign('detail', $detail);
        $this->mobile_title = '生活缴费';
        $this->display();
    }

    public function orderpay() {
        if (empty($this->uid)) {
            $this->ajaxReturn(array('status' => 'login'));
        }
        if (IS_AJAX) {
            //dump($_POST);
            $community_id = (int) $_POST['community_id'];
            if (empty($community_id)) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '小区不存在'));
            }
            $order_date = htmlspecialchars($_POST['order_date']);
            $detail = D('Communityorder')->where(array('community_id' => $community_id, 'order_date' => $order_date, 'user_id' => $this->uid))->find();
            if (empty($detail)) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '账单不存在'));
            }
            $products = D('Communityorderproducts')->order(array('type' => 'asc'))->where(array('order_id' => $detail['order_id']))->select();
            $pp = array();
            foreach ($products as $k => $val) {
                $pp[$val['type']] = round($val['money'] / 100, 2);
            }
            $type = $_POST['type'];
            foreach ($type as $k => $val) {
                if (empty($val)) {
                    unset($type[$k]);
                } else {
                    $type[$k] = floatval($val);
                }
            }
            $res =  array_uintersect($type,$pp,"array_comparison");
            if ($res === $type) {
                $total2 = 0;
                foreach($type as $k=>$val){
                    $total2+=$val;
                }
                $total = $_POST['total'];
                if($total2 == $total){
                    if($this->member['money']<$total*100){
                        $this->ajaxReturn(array('status'=>'error','msg'=>'账户余额不足','url'=>U('mcenter/money/index')));
                    }else{
                        if(false != D('Communityorder')->orderpay($detail['order_id'],$this->uid,$type,$total*100)){
                            $this->ajaxReturn(array('status'=>'success','msg'=>'缴费成功'));
                        }else{
                            $this->ajaxReturn(array('status'=>'error','msg'=>'缴费失败'));
                        }
                    }
                }else{
                    $this->ajaxReturn(array('status'=>'error','msg'=>'金额不正确'));
                }
                
            } else {
                $this->ajaxReturn(array('status'=>'error','msg'=>'账单不合法'));
            }
        }
    }

    public function post() {
        $community_id = (int) $this->_param('community_id');
        if (empty($community_id)) {
            $this->error('该小区不存在');
        }
        if (!$detail = D('Community')->find($community_id)) {
            $this->error('没有该小区');
            die;
        }
        if ($detail['closed']) {
            $this->error('该小区已经被删除');
            die;
        }
        $this->assign('nextpage', LinkTo('community/postload', array('t' => NOW_TIME, 'community_id' => $community_id, 'p' => '0000')));
        $this->assign('detail', $detail);
        $this->mobile_title = '小区论坛';
        $this->display();
    }

    public function postload() {
        $community_id = (int) $this->_param('community_id');
        $post = D('Post');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('community_id' => $community_id, 'audit' => 1, 'closed' => 0);
        $count = $post->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $post->order(array('last_time' => 'desc'))->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $post_ids = array();
        foreach ($list as $k => $val) {
            if (!empty($val['user_id'])) {
                $user_ids[$val['user_id']] = $val['user_id'];
            }
            $post_ids[$val['post_id']] = $val['post_id'];
        }
        if (!empty($post_ids)) {
            $this->assign('pics', D('Postpics')->where(array('post_id' => array('IN', $post_ids)))->select());
        }
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板   
    }

    public function postdetail($post_id) {
        $post_id = (int) $post_id;
        if (empty($post_id)) {
            $this->error('该帖不存在');
        }
        if (!$detail = D('Post')->find($post_id)) {
            $this->error('该帖不存在');
        }
        if ($detail['audit'] != 1 || $detail['closed'] != 0) {
            $this->error('该帖不合法或已被删除');
        }
        $pics = D('Postpics')->where(array('post_id' => $post_id))->limit(3)->select();

        $user = D('Users')->find($detail['user_id']);
        if (!$res = D('Postzan')->where(array('create_ip' => get_client_ip(), 'post_id' => $post_id))->find()) {
            $detail['is_zan'] = 0;
        } else {
            $detail['is_zan'] = 1;
        }
        $list = D('Postreply')->order(array('create_time' => 'asc'))->where(array('post_id' => $post_id))->select();
        $user_ids = array();
        $a = 2;
        foreach ($list as $k => $val) {
            if (!empty($val['user_id'])) {
                $user_ids[$val['user_id']] = $val['user_id'];
            }
            $list[$k]['floor'] = $a;
            $a++;
        }
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('a', $a - 2);
        $this->assign('list', $list);
        $this->assign('user', $user);
        $this->assign('pics', $pics);
        $this->assign('detail', $detail);
        $this->mobile_title = '帖子详情';
        $this->display();
    }

    public function fabu($community_id) {
        if (empty($this->uid)) {
            AppJump();
        }
        $community_id = (int) $community_id;
        if (empty($community_id)) {
            $this->error('该小区不存在');
        }
        if (!$detail = D('Community')->find($community_id)) {
            $this->error('没有该小区');
            die;
        }
        if ($detail['closed']) {
            $this->error('该小区已经被删除');
            die;
        }
        $obj = D('Post');
        if ($this->isPost()) {
            $data['title'] = htmlspecialchars($_POST['title']);
            if (empty($data['title'])) {
                $this->baoError('标题不能为空');
            }
            if ($words = D('Sensitive')->checkWords($data['title'])) {
                $this->baoAlert('标题含有敏感词：' . $words);
            }
            $data['details'] = htmlspecialchars($_POST['details']);
            if (empty($data['details'])) {
                $this->baoError('标题不能为空');
            }
            if ($words = D('Sensitive')->checkWords($data['details'])) {
                $this->baoAlert('详情含有敏感词：' . $words);
            }
            $data['audit'] = $this->_CONFIG['site']['postaudit'];
            $data['create_time'] = NOW_TIME;
            $data['create_ip'] = get_client_ip();
            $data['community_id'] = $community_id;
            $data['user_id'] = $this->uid;
            $data['last_time'] = NOW_TIME;
            if ($post_id = $obj->add($data)) {
                $photos = $this->_post('photos', false);
                $local = array();
                foreach ($photos as $val) {
                    if (isImage($val))
                        $local[] = $val;
                }
                if (!empty($local)) {
                    D('Postpics')->upload($post_id, $local);
                }
                $this->baoSuccess('恭喜您发帖成功!', U('community/post', array('community_id' => $community_id)));
            }
            $this->baoError('发帖失败');
        } else {
            $this->assign('community_id', $community_id);
            $this->mobile_title = '发帖子';
            $this->display();
        }
    }

    public function reply() {
        if (empty($this->uid)) {
            $this->ajaxReturn(array('status' => 'login', 'msg' => '登录状态失效'));
        }
        if (IS_AJAX) {
            $data['contents'] = htmlspecialchars($_POST['contents']);
            if (empty($data['contents'])) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '回复内容不能为空'));
            }
            if ($words = D('Sensitive')->checkWords($data['contents'])) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '标题含有敏感词：' . $words));
            }
            $data['post_id'] = (int) $_POST['post_id'];
            if (empty($data['post_id'])) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '回复的帖子不存在'));
            }
            $data['user_id'] = $this->uid;
            $data['create_time'] = NOW_TIME;
            $data['create_ip'] = get_client_ip();
            $data['audit'] = 1;
            if (D('Postreply')->add($data)) {
                D('Post')->updateCount($data['post_id'], 'reply_num');
                D('Post')->save(array('post_id' => $data['post_id'], 'last_ip' => $data['create_ip'], 'last_time' => $data['create_time']));
                $this->ajaxReturn(array('status' => 'success', 'msg' => '回复成功'));
            } else {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '回复失败'));
            }
        }
    }

    public function zan() {
        if (IS_AJAX) {
            $post_id = (int) $_POST['post_id'];
            if (empty($post_id)) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '要赞的帖子不存在'));
            }
            $user_id = $this->uid;
            if ($res = D('Postzan')->where(array('post_id' => $post_id, 'create_ip' => get_client_ip()))->find()) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '您已经点过赞了'));
            } else {
                if (D('Postzan')->add(array('post_id' => $post_id, 'user_id' => $user_id, 'create_time' => NOW_TIME, 'create_ip' => get_client_ip()))) {
                    D('Post')->updateCount($post_id, 'zan_num');
                    $this->ajaxReturn(array('status' => 'success', 'msg' => '点赞成功'));
                } else {
                    $this->ajaxReturn(array('status' => 'error', 'msg' => '点赞失败'));
                }
            }
        }
    }

    public function tellist() {
        $community_id = (int) $this->_param('community_id');
        $detail = D('Community')->find($community_id);
        $this->assign('detail', $detail);
        $phone = D('Convenientphone');
        import('ORG.Util.Page');
        $map = array('expiry_date' => array('EGT', TODAY));
		$map['community_id'] = array('in',array($community_id,0));
        $list = $phone->where($map)->order(array('phone_id' => 'desc'))->select();
        $this->assign('list', $list);
        $this->mobile_title = '便民电话';
        $this->display();
    }
    
     public function msg2($community_id) {
         $community_id = (int) $community_id;
        if (empty($this->uid)) {
            AppJump();
        }
        $obj = D('Feedback');
        $list = $obj->where(array('community_id'=>$community_id,'user_id'=>$this->uid))->select();
         $this->assign('community_id', $community_id);
        $this->assign('list', $list);
        $this->mobile_title = '反馈信息';
        $this->display();
     }

}
