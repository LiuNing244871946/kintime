<?php



class EleorderAction extends CommonAction {

    public function index() {
        $this->mobile_title = '订餐订单';
        $this->display();
    }

    public function loading() {
        $s = I('s', '', 'trim,intval');
        $Eleorder = D('Eleorder');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('user_id' => $this->uid, 'closed' => 0); //这里只显示 实物
        if ($s == 0  || $s == 3) {
            $map['status'] = $s;
        }else{
            $map['status'] = array('IN',array(1,2,4,8));
        }
        $count = $Eleorder->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 25); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $Eleorder->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $order_ids = $addr_ids = array();
        foreach ($list as $k => $val) {
            $order_ids[$val['order_id']] = $val['order_id'];
            $addr_ids[$val['addr_id']] = $val['addr_id'];
            $user_ids[$val['user_id']] = $val['user_id'];
        }
        $eledianping = D('Eledianping')->where(array('user_id'=>$this->uid))->select();
        foreach($list as $k=>$val){
            foreach($eledianping as $kk=>$v){
                if($val['order_id'] == $v['order_id']){
                    $list[$k]['is_dianping'] = 1;
                }
            }
        }
        if (!empty($order_ids)) {
            $products = D('Eleorderproduct')->where(array('order_id' => array('IN', $order_ids)))->select();
            $product_ids = $shop_ids = array();
            foreach ($products as $val) {
                $product_ids[$val['product_id']] = $val['product_id'];
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $this->assign('products', $products);
            $this->assign('eleproducts', D('Eleproduct')->itemsByIds($product_ids));
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }
        $this->assign('addrs', D('Useraddr')->itemsByIds($addr_ids));
        $this->assign('areas', D('Area')->fetchAll());
        $this->assign('business', D('Business')->fetchAll());
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('cfg', D('Eleorder')->getCfg());
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出  
        $this->display();
    }

    public function detail($order_id) {
        $order_id = (int) $order_id;
        if (empty($order_id) || !$detail = D('Eleorder')->find($order_id)) {
            $this->error('该订单不存在');
        }
        if ($detail['user_id'] != $this->uid) {
            $this->error('请不要操作他人的订单');
        }
        $ele_products = D('Eleorderproduct')->where(array('order_id' => $order_id))->select();
        $product_ids = array();
        foreach ($ele_products as $k => $val) {
            $product_ids[$val['product_id']] = $val['product_id'];
        }
        if (!empty($product_ids)) {
            $this->assign('products', D('Eleproduct')->itemsByIds($product_ids));
        }
        $this->assign('eleproducts', $ele_products);
        $this->assign('addr', D('Useraddr')->find($detail['addr_id']));
        $this->assign('cfg', D('Eleorder')->getCfg());
        $this->assign('detail', $detail);
        $this->mobile_title = '订单详情';
        $this->display();
    }
    
    public function yes($order_id=0){
        if (is_numeric($order_id) && ($order_id = (int) $order_id)) {
            $obj = D('Eleorder');
            if (!$detial = $obj->find($order_id)) {
                $this->error('您确认收货的订单不存在');
            }
            if ($detial['user_id'] != $this->uid) {
                $this->error('请不要操作别人的订单');
            }
            if ($detial['status'] != 8) {
                $this->error('当前状态不能确认收货');
            }
            $mer = D('Shop')->find($detial['shop_id']);
            $data = array(
                'order_id'=>$order_id,
                'mer_id'=>$mer['mer_id'],
                'user_id'=>$this->uid,
                'price'=>$detial['need_pay'],
                'type'=>'ele',
                'time'=>time()
            );
            D('AllOrder')->add($data);
            if ($obj->save(array('order_id' => $order_id, 'status' => 3)) && D('DeliveryOrder')->where('type_order_id='.$order_id)->save(array('status' => 3))) { //防止并发请求
                $shop = D('Shop')->find($detial['shop_id']);
                $settlement_price = $detial['total_price'];
                D('Users')->addMoney($shop['user_id'], $settlement_price, '外卖订单:' . $order_id);

            
                $rebate = D('rebate')->find($shop['rate']);
                $user_money= $settlement_price*$rebate['rebate']*$rebate['user']/10000; //用户返利金额
                D('Users')->addMoney($detial['user_id'], $user_money, '外卖订单完成,ID:'.$order_id.'，用户消费返利'); 

                $invite = D('users')->find($detial['user_id'])['invite1'];
                if($invite != 0){
                    $tui_user_money= $settlement_price*$rebate['rebate']*$rebate['tui_user']/10000;  // 推荐人返利金额
                    D('Users')->addMoney($invite, $tui_user_money, '外卖订单完成,ID:'.$order_id.'，推荐人消费返利'); 
                    $platform_money= $settlement_price*$rebate['rebate']*$rebate['platform']/10000;  // 平台扣除金额
                }else{
                    $platform_money= $settlement_price*$rebate['rebate']*($rebate['platform']+$rebate['tui_user'])/10000;  // 平台扣除金额
                }
                D('Users')->addMoney2($detial['user_id'], $platform_money, '外卖订单返利,ID:'.$order_id); 
            }
            $this->success('确认收货成功！', U('eleorder/index',array('s'=>1)));
        } else {
            $this->error('请选择要确认收货的订单');
        }
    }
    
    public function del() {
        $order_id = I('order_id', 0, 'trim,intval');
        $o = D('EleOrder');
        $f = $o->where('order_id =' . $order_id)->find();
        if (!$f) {
            $this->ajaxReturn(array('status'=>'error','msg'=>'错误'));
        } else {
            if ($f['user_id'] != $this->uid) {
                 $this->ajaxReturn(array('status'=>'error','msg'=>'请不要操作他人的订单'));
            }
            if ($detial['status'] != 0) {
                $this->ajaxReturn(array('status'=>'error','msg'=>'该订单暂时不能取消'));
            }
            $r = $o->where('order_id =' . $order_id)->setField('closed', 1);
            $fan_money = $f['need_pay'];
            D('Users')->addMoney($f['user_id'],$fan_money,'外卖订单'.$order_id.'取消，退款');
            $this->ajaxReturn(array('status'=>'success','msg'=>'取消订单成功'));
        }
    }

    public function dianping($order_id) {
        $order_id = (int) $order_id;
        if (!$detail = D('Eleorder')->find($order_id)) {
            $this->error('没有该订单');
        } else {
            if ($detail['user_id'] != $this->uid) {
                $this->error('不要评价别人的订餐订单');
                die();
            }
        }
        if (D('Eledianping')->check($order_id, $this->uid)) {
            $this->error('已经评价过了');
        }
        if ($this->_Post()) {
            $data = $this->checkFields($this->_post('data', false), array('score', 'speed', 'contents'));
            $data['user_id'] = $this->uid;
            $data['shop_id'] = $detail['shop_id'];
            $data['order_id'] = $order_id;
            $data['score'] = (int) $data['score'];
            if (empty($data['score'])) {
                $this->baoError('评分不能为空');
            }
            if ($data['score'] > 5 || $data['score'] < 1) {
                $this->baoError('评分为1-5之间的数字');
            }
            $data['speed'] = (int) $data['speed'];
            if(empty($data['speed'])){
                $this->baoError('送餐时间不能为空');
            }
            $data['contents'] = htmlspecialchars($data['contents']);
            if (empty($data['contents'])) {
                $this->baoError('评价内容不能为空');
            }
            if ($words = D('Sensitive')->checkWords($data['contents'])) {
                $this->baoError('评价内容含有敏感词：' . $words);
            }
            $data['show_date'] = date('Y-m-d', NOW_TIME); //15天生效
            $data['create_time'] = NOW_TIME;
            $data['create_ip'] = get_client_ip();
            if (D('Eledianping')->add($data)) {
                $photos = $this->_post('photos', false);
                $local = array();
                foreach ($photos as $val) {
                    if (isImage($val))
                        $local[] = $val;
                }
                if (!empty($local))
                    D('Eledianpingpics')->upload($order_id, $local);
                //D('Users')->prestige($this->uid, 'dianping');
                D('Users')->updateCount($this->uid, 'ping_num');
                $this->baoSuccess('恭喜您点评成功!', U('eleorder/index'));
            }
            $this->baoError('点评失败！');
        }else {
            $this->assign('detail',$detail);
            $details = D('Shop')->find($detail['shop_id']);
            $this->assign('details', $details);
            $this->assign('order_id', $order_id);
            $this->mobile_title = '外卖评价';
            $this->display();
        }
    }

}
