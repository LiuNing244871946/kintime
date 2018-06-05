<?php



class EleAction extends CommonAction {

    
	public function _initialize() {
        parent::_initialize();
        $this->ele = D('Ele')->find($this->shop_id);
        if(empty($this->ele)&& ACTION_NAME != 'apply'){
            $this->error('您还没有入住外卖频道');
        }
        $this->assign('ele',  $this->ele);
    }
	
	
	
	public function elecate() {
        $Elecate = D('Elecate');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed'=>'0');
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['cate_name'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if ($shop_id = $this->shop_id) {
            $map['shop_id'] = $shop_id;
            $shop = D('Shop')->find($shop_id);
            $this->assign('shop_name', $shop['shop_name']);
            $this->assign('shop_id', $shop_id);
        }

        $count = $Elecate->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 25); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $Elecate->where($map)->order(array('cate_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $shop_ids = array();
        foreach ($list as $k => $val) {
            if ($val['shop_id']) {
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
        }
        if ($shop_ids) {
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出

        $this->display(); // 输出模板
		
    }
	
	
	
	public function create() {
        if (IS_AJAX) {
            $shop_id = $this->shop_id;
			$cate_name = I('cate_name','','trim,htmlspecialchars');
			if(empty($cate_name)){
				$this->ajaxReturn(array('status'=>'error','message'=>'分类名称不能为空！'));
			}
            $obj = D('Elecate');
			$data = array(
				'shop_id'=>$shop_id,
				'cate_name'=>$cate_name,
				'num'=>0,
				'closed'=>0
			);
            if ($obj->add($data)) {
				$this->ajaxReturn(array('status'=>'success','message'=>'添加成功！'));
            }
            $this->ajaxReturn(array('status'=>'error','message'=>'添加失败！'));
        }
    }



	public function edit(){

	    if(IS_AJAX){
			
			$cate_id = I('v','','intval,trim');
			
			if ($cate_id) {
				
				$obj = D('Elecate');
				
				if (!$detail = $obj->find($cate_id)) {
					$this->ajaxReturn(array('status'=>'error','message'=>'请选择要编辑的菜单分类！'));
				}
				if ($detail['shop_id'] != $this->shop_id) {
					$this->ajaxReturn(array('status'=>'error','message'=>'请不要操作其他商家的菜单分类！'));
				}
				$cate_name = I('cate_name','','trim,htmlspecialchars');
				if (empty($cate_name)) {
					$this->ajaxReturn(array('status'=>'error','message'=>'分类名称不能为空！'));
				}
				
				$data = array(
					'cate_name'=>$cate_name,
				);
				if (false !== $obj->where('cate_id ='.$cate_id)->setField($data)) {
					$this->ajaxReturn(array('status'=>'success','message'=>'操作成功！'));
				}
				$this->ajaxReturn(array('status'=>'error','message'=>'操作失败！'));
			} else {
				$this->ajaxReturn(array('status'=>'error','message'=>'请选择要编辑的菜单分类！'));
			}
		
		}
	
    }
	
	
	public function eleproduct() {
        $Eleproduct = D('Eleproduct');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed'=>0);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['product_name'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if ($shop_id = $this->shop_id) {
            $map['shop_id'] = $shop_id;
            $this->assign('shop_id', $shop_id);
        }
        $count = $Eleproduct->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $Eleproduct->where($map)->order(array('product_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $cate_ids= array();
        foreach ($list as $k => $val) {

            if($val['cate_id']){
                $cate_ids[$val['cate_id']] = $val['cate_id'];
            }
        }

        if($cate_ids){
            $this->assign('cates',D('Elecate')->itemsByIds($cate_ids));
        }
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }
	
	
	public function index() {
		$status = I('status','','intval,trim');
        $this->status = $status;
		$this->assign('status',$status);
        $this->showdata();
        $this->display(); // 输出模板
    }


	public function status(){
		$status = I('s','','trim,intval');
		$order_id = I('o','','trim,intval');
		$eo = D('EleOrder');
		$reo = $eo -> where('order_id ='.$order_id)-> find();
		if($status == 2){
			if($reo['status'] == 1){
				$up = $eo -> where('order_id ='.$order_id) -> setField('status',2);
			}else{
				$this->error('错误!');
			}
		}
  //       elseif($status == 4){
		// 	if($reo['status'] == 2){
		// 		$up = $eo -> where('order_id ='.$order_id) -> setField('status',4);
		// 	}else{
		// 		$this->error('错误!');
		// 	}
		// }
        else{
			$this->error('错误!');
		}
		
		if($up){
			$this->success('接单成功!');
		}else{
			$this->error('接单失败!');
		}
		
	}
    
    public function detail(){
        
        $order_id = I('order_id','','intval,trim');
        if(!$order = D('EleOrder')->find($order_id)){
            $this->error('订单信息错误');
        }else{
            $op = D('EleOrderProduct') -> where('order_id ='.$order['order_id']) -> select();
            if($op){
                $ids = array();
                foreach($op as $k => $v){
                    $ids[$v['product_id']] = $v['product_id'];
                }
                $ep = D('EleProduct')->where(array('product_id'=>array('in',$ids)))->select();
                $ep2 = array();
                foreach($ep as $kk => $vv){
                   $ep2[$vv['product_id']] = $vv;
                }
                $this->assign('ep',$ep2);
                $this->assign('op',$op);
                $addr = D('UserAddr')->find($order['addr_id']);
                $this->assign('addr',$addr);
                $do = D('DeliveryOrder')->where(array('type'=>1,'type_order_id'=>$order['order_id']))->find();
                if($do){
                    if($do['delivery_id']>0){
                        $delivery = D('Delivery')->find($do['delivery_id']);
                        $this->assign('delivery',$delivery);
                    }
                    
                    $this->assign('do',$do);
                }
            }
            
            
            $this->assign('order',$order);
            $this->display();
        }
        
    }
    
	
	
	private function showdata() {
        $Eleorder = D('Eleorder');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0, 'shop_id' => $this->shop_id, 'status' => $this->status);
        if ($this->status==='') {
            $map['status']=1;
        };
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
		if (isset($_GET['st']) || isset($_POST['st'])) {
            $st = (int) $this->_param('st');
            if ($st != 999) {
                $map['status'] = $st;
            }
            $this->assign('st', $st);
        } else {
            $this->assign('st', 999);
        }
		
        $count = $Eleorder->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $Eleorder->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $user_ids = $order_ids = $addr_ids = array();
        foreach ($list as $key => $val) {
            $user_ids[$val['user_id']] = $val['user_id'];
            $order_ids[$val['order_id']] = $val['order_id'];
            $addr_ids[$val['addr_id']] = $val['addr_id'];
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

	
	
}
