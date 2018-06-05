<?php



class DianpingAction extends CommonAction {

    public function index() {
        $tuandianping = D('Tuandianping');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0, 'shop_id' => $this->shop_id, 'show_date' => array('ELT', TODAY));
        $count = $tuandianping->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $tuandianping->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
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
            $this->assign('pics', D('Tuandianpingpics')->where(array('order_id' => array('IN', $order_ids)))->select());
        }
        foreach($list as $key=>$v){
            if(in_array($v['order_id'], $order_ids)){
                $list[$key]['pichave'] = 1;
            }
        }
        
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function tuanreply($order_id) {
        $order_id = (int) $order_id;
        $detail = D('Tuandianping')->find($order_id);
        if (empty($detail) || $detail['shop_id'] != $this->shop_id) {
            $this->baoError('没有该内容');
        }
        if ($this->isPost()) {
            if ($reply = $this->_param('reply', 'htmlspecialchars')) {
                $data = array('order_id' => $order_id, 'reply' => $reply);
                if (D('Tuandianping')->save($data)) {
                    $this->baoSuccess('回复成功', U('dianping/tuan'));
                }
            }
            $this->baoError('请填写回复');
        } else {
            $this->assign('detail', $detail);
            $this->display();
        }
    }

    

    public function waimai() {
        $eledianping = D('Eledianping');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0, 'shop_id' => $this->shop_id, 'show_date' => array('ELT', TODAY));
        $count = $eledianping->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $eledianping->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        //dump($eledianping->getLastSql());
        
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
        
        foreach($list as $key=>$v){
            if(in_array($v['order_id'], $order_ids)){
                $list[$key]['pichave'] = 1;
            }
        }
        
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }
    
    public function elereply($order_id) {
        $order_id = (int) $order_id;
        $detail = D('Eledianping')->find($order_id);
        if (empty($detail) || $detail['shop_id'] != $this->shop_id) {
            $this->baoError('没有该内容');
        }
        if ($this->isPost()) {
            if ($reply = $this->_param('reply', 'htmlspecialchars')) {
                $data = array('order_id' => $order_id, 'reply' => $reply);
                if (D('Eledianping')->save($data)) {
                    $this->baoSuccess('回复成功', U('dianping/waimai'));
                }
            }
            $this->baoError('请填写回复');
        } else {
            $this->assign('detail', $detail);
            $this->display();
        }
    }
    
    public function hotel() {
        $Hoteldianping = D('hotelcomment');
        $Hotel = D('hotel');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('shop_id' => $this->shop_id);
        $hotel = $Hotel->where($map)->select();
        if(empty($hotel)){
            $this->error('您未开通酒店功能，请前往电脑端开通相应功能！');
        }
        $hotel_id = $hotel[0]['hotel_id'];
        $map = array('hotel_id' => $hotel_id );
        $count = $Hoteldianping->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 5); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $Hoteldianping->where($map)->order(array('comment_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $shop_ids = array();
        foreach ($list as $k => $val) {
            $val['create_ip_area'] = $this->ipToArea($val['create_ip']);
            $list[$k] = $val;
            $user_ids[$val['user_id']] = $val['user_id'];
        }
        if (!empty($user_ids)) {
            $this->assign('users', D('Users')->itemsByIds($user_ids));
        }
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function hotelreply($order_id) {
        $order_id = (int) $order_id;
        $detail = D('hotelcomment')->find($order_id);
        $hotel = D('hotel')->find($this->shop_id);
        if (empty($detail) || $detail['hotel_id'] != $hotel['hotel_id']) {
            $this->baoError('没有该内容');
        }
        if ($this->isPost()) {
            if ($reply = $this->_param('reply', 'htmlspecialchars')) {
                $data = array('order_id' => $order_id, 'reply' => $reply);
                if (D('hotelcomment')->save($data)) {
                    $this->baoSuccess('回复成功', U('dianping/hotel'));
                }
            }
            $this->baoError('请填写回复');
        } else {
            $this->assign('detail', $detail);
            $this->display();
        }
    }
	 public function shopping() {
        $dianping = D('goodsdianping');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0, 'shop_id' => $this->shop_id, 'show_date' => array('ELT', TODAY));
        $count = $dianping->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $dianping->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $order_ids = array();
        foreach ($list as $k => $val) {
            $val['create_ip_area'] = $this->ipToArea($val['create_ip']);
            $list[$k] = $val;
            $user_ids[$val['user_id']] = $val['user_id'];
            // $dianping_ids[$val['dianping_id']] = $val['dianping_id'];
        }
        if (!empty($user_ids)) {
            $this->assign('users', D('Users')->itemsByIds($user_ids));
        }
        // if (!empty($dianping_ids)) {
        //     $this->assign('pics', D('goodsdianpingpic')->where(array('dianping_id' => array('IN', $dianping_ids)))->select());
        // }
        foreach($list as $key=>$v){
            if(in_array($v['order_id'], $order_ids)){
                $list[$key]['pichave'] = 1;
            }
        }
        
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

	

	

	public function shoppingreply($order_id) {
        $order_id = (int) $order_id;
        $detail = D('goodsdianping')->find($order_id);
        if (empty($detail) || $detail['shop_id'] != $this->shop_id) {
            $this->baoError('没有该内容');
        }
        if ($this->isPost()) {
            if ($reply = $this->_param('reply', 'htmlspecialchars')) {
                $data = array('order_id' => $order_id, 'reply' => $reply);
                if (D('goodsdianping')->save($data)) {
                    $this->baoSuccess('回复成功', U('dianping/shopping'));
                }
            }
            $this->baoError('请填写回复');
        } else {
            $this->assign('detail', $detail);
            $this->display();
        }
    }

}
