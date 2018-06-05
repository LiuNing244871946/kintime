<?php



class GoodsAction extends CommonAction {

    public function index() {
        $aready = (int) $this->_param('aready');
        $this->assign('aready', $aready);
        $this->mobile_title = '商城订单';
        $this->display(); // 输出模板
    }

    public function goodsloaddata() {
        $Order = D('Order');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0, 'user_id' => $this->uid);

        $aready = (int) $this->_param('aready');
        if ($aready == 1) {
            $map['status'] = 0;
        } elseif ($aready == 3) {
            $map['status'] = array('IN',array(1,2));
        }  elseif($aready == 4) {
            $map['status'] = 3;
        }


        $count = $Order->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $Order->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $order_ids = $addr_ids = array();
        foreach ($list as $key => $val) {
            $user_ids[$val['user_id']] = $val['user_id'];
            $order_ids[$val['order_id']] = $val['order_id'];
            $addr_ids[$val['addr_id']] = $val['addr_id'];
        }
        if (!empty($order_ids)) {
            $goods = D('Ordergoods')->where(array('order_id' => array('IN', $order_ids)))->select();
            $goods_ids = $shop_ids = array();
            foreach ($goods as $val) {
                $goods_ids[$val['goods_id']] = $val['goods_id'];
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $this->assign('goods', $goods);
            //$this->assign('products', D('Goods')->itemsByIds($goods_ids));
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }
        $this->assign('addrs', D('Useraddr')->itemsByIds($addr_ids));
        $this->assign('areas', D('Area')->fetchAll());
        $this->assign('business', D('Business')->fetchAll());
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('types', D('Order')->getType());
        $this->assign('goodtypes', D('Ordergoods')->getType());
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function detail($order_id){
            $order_id = (int) $order_id;
            if(empty($order_id) || !$detail = D('Order')->find($order_id)){
                $this->error('该订单不存在');
            }
            if($detail['user_id'] != $this->uid){
                $this->error('请不要操作他人的订单');
            }
            $order_goods = D('Ordergoods')->where(array('order_id'=>$order_id))->select(); 
            $goods_ids = array();
            foreach($order_goods as $k=>$val){
                $goods_ids[$val['goods_id']] = $val['goods_id'];
            }
            if(!empty($goods_ids)){
                $this->assign('goods',D('Goods')->itemsByIds($goods_ids));
            }
            $this->assign('ordergoods',$order_goods);
            $this->assign('addr',D('Useraddr')->find($detail['addr_id']));
            $this->assign('types', D('Order')->getType());
            $this->assign('goodtypes', D('Ordergoods')->getType());
            $this->assign('detail',$detail);
            $this->mobile_title = '订单详情';
            $this->display();
        }

    public function queren($order_id){
        if (is_numeric($order_id) && ($order_id = (int) $order_id)) {
            $obj = D('Order');
            if (!$detial = $obj->find($order_id)) {
                $this->error('该订单不存在');
            }
            if ($detial['user_id'] != $this->uid) {
                $this->error('请不要操作他人的订单');
            }
            if ($detial['status'] != 3) {
                $this->error('该订单暂时不能确定收货');
            }
            $weidian = D('WeidianDetalis')->find($detial['weidian_id']);
            $data = array(
                'order_id'=>$order_id,
                'mer_id'=>$weidian['mer_id'],
                'user_id'=>$this->uid,
                'price'=>$detial['total_price'],
                'type'=>'ele',
                'time'=>time()
            );
            D('AllOrder')->add($data);
            if($obj->save(array('order_id'=>$order_id,'status'=>8))){
                if($detial['mall_type'] == 0){
                    D('DeliveryOrder')->where("type_order_id = {$order_id} and type = 0")->save(array('status'=>3));
                }elseif($detial['mall_type'] == 1){
                    D('DeliveryOrder')->where("type_order_id = {$order_id} and type = 2")->save(array('status'=>3));
                    D('MailOrder')->where("mail_order_id = {$order_id}")->save(array('status'=>2));
                }
                $mer = D('Merchants')->find($weidian['mer_id']);
                D('Users')->addMoney($mer['user_id'], $detial['total_price'], '购物订单:' . $order_id);
                $rebate = D('rebate')->find($weidian['rate']);
                $user_money= $detial['total_price']*$rebate['rebate']*$rebate['user']/10000; //用户返利金额
                D('Users')->addMoney($detial['user_id'], $user_money, '购物订单完成,ID:'.$order_id.'，用户消费返利'); 

                $invite = D('users')->find($detial['user_id'])['invite1'];
                if($invite != 0){
                    $tui_user_money= $detial['total_price']*$rebate['rebate']*$rebate['tui_user']/10000;  // 推荐人返利金额
                    D('Users')->addMoney($invite, $tui_user_money, '购物订单完成,ID:'.$order_id.'，推荐人消费返利'); 
                    $platform_money= $detial['total_price']*$rebate['rebate']*$rebate['platform']/10000;  // 平台扣除金额
                }else{
                    $platform_money= $detial['total_price']*$rebate['rebate']*($rebate['platform']+$rebate['tui_user'])/10000;  // 平台扣除金额
                }
                D('Users')->addMoney2($detial['user_id'], $platform_money, '购物订单返利,ID:'.$order_id);

                
                $this->success('确认订单成功！', U('goods/index'));
            }
        } else {
            $this->error('请选择要确认收货的订单');
        }
    }
    

    public function orderdel($order_id = 0) {
        if (is_numeric($order_id) && ($order_id = (int) $order_id)) {
            $obj = D('Order');
            if (!$detial = $obj->find($order_id)) {
                $this->error('该订单不存在');
            }
            if ($detial['user_id'] != $this->uid) {
                $this->error('请不要操作他人的订单');
            }
            if ($detial['status'] != 0) {
                $this->error('该订单暂时不能取消');
            }

            if($obj->save(array('order_id' => $order_id, 'closed' => 1))){
                if($detail['use_integral']){
                    D('Users')->addIntegral($detail['user_id'],$detail['use_integral'],'取消订单'.$detail['order_id'].'积分退还');
                }
                
            }
            $this->success('取消成功！', U('goods/index'));
        } else {
            $this->error('请选择要取消的订单');
        }
    }

}
