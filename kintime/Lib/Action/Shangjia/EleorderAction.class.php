<?php



class EleorderAction extends CommonAction {

    protected $status = 0;
    protected $ele;

     public function _initialize() {
        parent::_initialize();
        $this->shop_id = D('Shop')->where('mer_id='.$this->mer_id)->find()['shop_id'];
        if (empty($this->shop_id) && ACTION_NAME != 'apply') {
            $this->error('您还没有入住美食频道', U('shop/about'));
        }
        $getEleCate = D('Ele')->getEleCate();
        $this->assign('getEleCate', $getEleCate);
        $this->ele = D('Ele')->find($this->shop_id);
        if (empty($this->ele) && ACTION_NAME != 'apply') {
            $this->error('您还没有入住外卖频道', U('ele/apply'));
        }
        if (!empty($this->ele) && $this->ele['audit'] == 0) {
            $this->error("亲，您的申请正在审核中！");
        }
        
        $this->assign('ele', $this->ele);
    }
    
    
    public function index() {
        $this->status = 1;
        $this->showdata();
        $this->display(); // 输出模板
    }

    public function wait() {
        $this->status = array('IN',array(2,4,8));
        $this->showdata();
        $this->display(); // 输出模板
    }

    public function over() {
        $this->status = 3;
        $this->showdata();
        $this->display(); // 输出模板
    }
    
    public function jqprint($order_id){
        $order = D('EleOrder')->find($order_id);
        $shop = D('Shop')->find($order['shop_id']);
		$shopsetting = D('Shopsetting')->find($order['shop_id']);
        $orderp = D('EleOrderProduct')->where('order_id ='.$order['order_id'])->select();
        $ids = array();
        foreach($orderp as $k => $v){
            $ids[$v['product_id']] = $v['product_id'];
        }
        $ep = D('EleProduct')->where(array('product_id'=>array('in',$ids)))->select();
        $ep2 = array();
        foreach($ep as $k => $v){
            $ep2[$v['product_id']] = $v;
        }
        $u = D('Users') -> find($order['user_id']);
        $pl = D('PaymentLogs')->where(array('type'=>'ele','order_id'=>$order['order_id']))->find();
        $addr = D('UserAddr')->find($order['addr_id']);
        $ele = D('Ele')->find($order['shop_id']);
        $this->assign('addr',$addr);
        $this->assign('ep',$ep2);
        $this->assign('pl',$pl);
        $this->assign('order',$order);
        $this->assign('shop',$shop);
        $this->assign('ele',$ele);
        $this->assign('orderp',$orderp);
        $this->assign('u',$u);
		$this->assign('shopsetting',$shopsetting);
        
        $this->display();
    }
    
    public function jq_print($order_id){
       if(IS_AJAX){   
           $num = (int)$this->_param('num');
           if(D('Eleorder')->order_print($order_id, $num)){
               $this->ajaxReturn(array('status'=>'success','msg'=>'打印成功'));
           }else{
               $this->ajaxReturn(array('status'=>'error','msg'=>'打印失败'));
           }           
       } 
    }

   

    
    public function count(){
        
        $dvo = D('DeliveryOrder'); // 实例化User对象
        $bg_date = strtotime(I('bg_date',0,'trim'));
        $end_date = strtotime(I('end_date',0,'trim'));
        $this->assign('btime',$bg_date);
        $this->assign('etime',$end_date);
        
        if($bg_date && $end_date){
            $pre_btime = date('Y-m-d H:i:s',$bg_date);
            $pre_etime = date('Y-m-d H:i:s',$end_date);
            $this->assign('pre_btime',$pre_btime);
            $this->assign('pre_etime',$pre_etime);
        }
        
        $map = array();
        $map['mer_id'] = $this->mer_id;
        $map['type'] = 1;
        if($bg_date && $end_date){
           $map['create_time'] = array('between',array($bg_date,$end_date)); 
        }
        
        import('ORG.Util.Page');// 导入分页类
        $count      = $dvo->where($map)->count();// 查询满足要求的总记录数
        $Page       = new Page($count,25);// 实例化分页类 传入总记录数和每页显示的记录数
        $show       = $Page->show();// 分页显示输出
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $list = $dvo->where($map)->order('order_id desc')->limit($Page->firstRow.','.$Page->listRows)->relation(true)->select();
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出

        $this->display();
        
    }
    
    
    function delivery_count(){
        
        $delivery_id = I('did',0,'intval,trim');
        $btime = I('btime',0,'trim');
        $etime = I('etime',0,'trim');
        $map = array();
        if($btime && $etime){
           $map['create_time'] = array('between',array($btime,$etime)); 
        }
        
        if(!$delivery_id || !($this->shop_id)){
            $this->ajaxReturn(array('status'=>'error','message'=>'错误'));
        }else{
            $map['delivery_id'] = $delivery_id;
            $map['mer_id'] = $this->mer_id;
            $map['type'] = 1;
            $count = D('DeliveryOrder') ->where($map)-> count();
            if($count){
                $this->ajaxReturn(array('status'=>'success','count'=>$count));
            }else{
                $this->ajaxReturn(array('status'=>'error','message'=>'错误'));
            }
        }
    }

    private function showdata() {
        $Eleorder = D('Eleorder');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0, 'shop_id' => $this->shop_id, 'status' => $this->status);
        if (($bg_date = $this->_param('bg_date', 'htmlspecialchars') ) && ($end_date = $this->_param('end_date', 'htmlspecialchars'))) {
            $bg_time = strtotime($bg_date);
            $end_time = strtotime($end_date);
            $map['create_time'] = array(array('ELT', $end_time), array('EGT', $bg_time));
            $this->assign('bg_date', $bg_date);
            $this->assign('end_date', $end_date);
        } else {
            if ($bg_date = $this->_param('bg_date', 'htmlspecialchars')) {
                $bg_time = strtotime($bg_date);
                $this->assign('bg_date', $bg_date);
                $map['create_time'] = array('EGT', $bg_time);
            }
            if ($end_date = $this->_param('end_date', 'htmlspecialchars')) {
                $end_time = strtotime($end_date);
                $this->assign('end_date', $end_date);
                $map['create_time'] = array('ELT', $end_time);
            }
        }

        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['order_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $Eleorder->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 25); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $Eleorder->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $config = D('Setting')->fetchAll();
        $h = isset($config['site']['ele']) ? (int)$config['site']['ele'] : 6;
        $t = NOW_TIME - $h*86400;
        $user_ids = $order_ids = $addr_ids = array();
        foreach ($list as $key => $val) {
            $user_ids[$val['user_id']] = $val['user_id'];
            $order_ids[$val['order_id']] = $val['order_id'];
            $addr_ids[$val['addr_id']] = $val['addr_id'];
            if(($val['create_time'] <$t) && ($val['status'] ==8)){ //如果超过了15天商家可以自动确定客户收货
                $list[$key]['status'] = 3;
            }
        }
        if (!empty($order_ids)) {
            $goods = D('Eleorderproduct')->where(array('order_id' => array('IN', $order_ids)))->select();
            $goods_ids = array();
            foreach ($goods as $val) {
                $goods_ids[$val['product_id']] = $val['product_id'];
            }
            $this->assign('goods', $goods);
            $this->assign('products', D('Eleproduct')->itemsByIds($goods_ids));
        }
        $this->assign('addrs', D('Useraddr')->itemsByIds($addr_ids));
        $this->assign('areas', D('Area')->fetchAll());
        $this->assign('business', D('Business')->fetchAll());

        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
    }

    public function comfirm($order_id){
        $setting = D('Shopsetting')->where(array('shop_id'=>$this->shop_id))->find();
        if(is_array($order_id)){
            D('Eleorder')->confirm($order_id,$setting['auto_print'],$this->shop_id,$setting['num']);
        }
        $this->baoSuccess('确认成功', U('eleorder/index'));
    }

    public function queren($order_id) {
        $order_id = (int) $order_id;
        if (!$detail = D('Eleorder')->find($order_id)) {
            $this->baoError('没有该订单');
        }
        if ($detail['shop_id'] != $this->shop_id) {
            $this->baoError('您无权管理该商家');
        }
        if ($detail['status'] != 1) {
            $this->baoError('该订单状态不正确');
        }
        D('Eleorder')->save(array(
            'order_id' => $order_id,
            'status' => 2,
            'audit_time' => NOW_TIME
        ));
        D('DeliveryOrder')->where("type_order_id=".$order_id.' and type=1')->save(array('status' => 1));
        $setting = D('Shopsetting')->where(array('shop_id'=>$this->shop_id))->find();
        if($setting['auto_print'] == 1){
            $num = $setting['num'] ? $setting['num']:1;
            D('Eleorder')->auto_print($order_id.$num);
        }

        $product_ids  = D('Eleorderproduct')->where("order_id=".$order_id)->getField('product_id',true);
        $product_ids  = implode(',', $product_ids);
        $map          = array('product_id'=>array('in',$product_ids));
        $product_name = D('Eleproduct')->where($map)->getField('product_name',true);
        $product_name = implode(',', $product_name);
        //====================微信支付通知===========================

        include_once "Kintime/Lib/Net/Wxmesg.class.php";
        $_data_sure = array(
            'url'       =>  "http://".$_SERVER['HTTP_HOST']."/mcenter/eleorder/detail/order_id/".$detail['order_id'].".html",
            'topcolor'  =>  '#F55555',
            'first'     =>  '亲,卖家已经收到您的订单！',
            'remark'    =>  '更多信息,请登录http://'.$_SERVER['HTTP_HOST'].'！再次感谢您的惠顾！',
            'orderNum'  =>  $detail['order_id'],
            'money'     =>  round($detail['need_money'],2).'元',
            'orderDate' =>  $detail['create_time']
        );
        $sure_data = Wxmesg::pay($_data_sure);
        $return    = Wxmesg::net($detail['user_id'], 'OPENTM202297555', $sure_data);

        //====================微信支付通知==============================
        
        $this->baoSuccess('已确认', U('eleorder/index'));
    }

    public function send($order_id) { //发货
        $order_id = (int) $order_id;
		    
        $dvo = D('DeliveryOrder');
        $f = $dvo->where('type_order_id =' . $order_id)->find();

        if($f){
           if($f['status']!=8){
            $this->baoError('配送员确认之后才能完成配送');
           } 
        }

        if (!$detail = D('Eleorder')->find($order_id)) {
            $this->baoError('没有该订单');
        }
        if ($detail['shop_id'] != $this->shop_id) {
            $this->baoError('您无权管理该商家');
        }
        if ($detail['status'] != 8&&$detail['status'] != 2) {
            $this->baoError('该订单状态不正确');
        }
        D('Eleorder')->overOrder($order_id);
        $mer = D('Shop')->find($detail['shop_id']);
            $data = array(
                'order_id'=>$order_id,
                'mer_id'=>$mer['mer_id'],
                'user_id'=>$this->uid,
                'price'=>$detail['need_pay'],
                'type'=>'ele',
                'time'=>time()
            );
        D('AllOrder')->add($data);
        $this->baoSuccess('配送完成，资金已经结算到账户！', U('eleorder/wait'));
    }

}