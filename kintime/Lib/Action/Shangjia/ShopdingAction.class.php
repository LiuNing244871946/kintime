<?php
class ShopdingAction extends CommonAction {
    
    public function _initialize() {
        parent::_initialize();
        $this->shop = D('Shop')->where('mer_id='.$this->mer_id)->find();
        $this->shop_id = $this->shop['shop_id'];
        $dingtypes = D('Shopding')->getDingType();
        $this->assign('dingtypes',$dingtypes);
        $cates = D('Shopdingcate')->where(array('shop_id' => $this->shop_id))->select();
        foreach($cates as $k=>$val){
            $dingcates[$val['cate_id']] = $val;
        }
        $this->assign('dingcates', $dingcates);   
    }

    private function check_ding(){
        if (empty($this->shop_id) && ACTION_NAME != 'apply') {
            $this->error('您还没有入住美食频道', U('shop/about'));
        }
        $this->is_dianzicaidan = $this->shop['is_dianzicaidan'];
        if ($this->is_dianzicaidan==0) {
            $this->error('您还没有开通电子菜单', U('shop/about'));
        }
        $shopding = D('Shopding');
        $res =  $shopding->find($this->shop_id);
        if(!$res){
            $this->error('请先完善订座资料！',U('shopding/set_ding'));
        }elseif($res['audit'] == 0){
            $this->error('您的订座服务申请正在审核中，请耐心等待！',U('shopding/set_ding'));
        }elseif($res['audit'] == 2){
            $this->error('您的订座服务申请未通过审核！',U('shopding/set_ding'));
        }
    }
     
    public function index(){
        $this->check_ding();
        $shopdingorder = D('Shopdingorder');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('shop_id' => $this->shop_id,'closed'=>0);
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
            $map['order_id|name|mobile'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        
        $count = $shopdingorder->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $shopdingorder->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }
    
    
    public function set_ding(){
        $this->is_dianzicaidan = $this->shop['is_dianzicaidan'];
        if ($this->is_dianzicaidan==0) {
            $this->error('您还没有开通电子菜单', U('shop/about'));
        }
        $obj = D('Shopding');
        $shopding = $obj->find($this->shop_id);
        if ($this->isPost()) { 
           $data = $this->createCheck();
            if (empty($shopding)) {
                $data['create_time'] = NOW_TIME;
                $data['create_ip'] = get_client_ip();
                $data['shop_id'] = $this->shop_id;
                if($obj->add($data)){
                    $this->baoSuccess('设置成功', U('shopding/index'));
                }else{
                    $this->baoError('设置失败');
                }
            }else{
                $data['update_time'] = NOW_TIME;
                $data['update_ip'] = get_client_ip();
                $data['audit'] = 0;
                $data['shop_id'] = $this->shop_id;
                if(false !== $obj->save($data)){
                    $this->baoSuccess('修改成功', U('shopding/index'));
                }else{
                    $this->baoError('修改失败');
                }
            }
        } else {
            $this->assign('shopding',$shopding);
            $this->display();
        }
    }
    
    private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), array('price','table_num','details', 'lao_details','eng_details','stime','ltime'));
        $data['price'] = (int)$data['price'];
        if (empty($data['price'])) {
            $this->baoError('商品价格不能为空');
        }
        $data['table_num'] = (int)$data['table_num'];
        $data['stime'] = htmlspecialchars($data['stime']);
        $data['ltime'] = htmlspecialchars($data['ltime']);
        $data['details'] = SecurityEditorHtml($data['details']);
        $data['lao_details'] = SecurityEditorHtml($data['lao_details']);
        $data['eng_details'] = SecurityEditorHtml($data['eng_details']);
        if (empty($data['details'])) {
            $this->baoError('中文详情不能为空');
        }
        if (empty($data['lao_details'])) {
            $this->baoError('老文详情不能为空');
        }
        if (empty($data['eng_details'])) {
            $this->baoError('英文详情不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['details'])) {
            $this->baoError('中文详情含有敏感词：' . $words);
        }
        if ($words = D('Sensitive')->checkWords($data['lao_details'])) {
            $this->baoError('老文详情含有敏感词：' . $words);
        }
        if ($words = D('Sensitive')->checkWords($data['eng_details'])) {
            $this->baoError('英文详情含有敏感词：' . $words);
        }
        return $data;
    }
    
    public function cate() {   //菜品分类列表
        $this->check_ding();
        $dingcate = D('Shopdingcate');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('shop_id'=>$this->shop_id);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['cate_name|lao_cate_name|eng_cate_name'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $dingcate->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 25); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $dingcate->where($map)->order(array('cate_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function catecreate() {
        if ($this->isPost()) {
            $data = $this->cateCreateCheck();
            $obj = D('Shopdingcate');
            if ($obj->add($data)) {
                $this->baoSuccess('添加成功', U('shopding/cate'));
            }
            $this->baoError('操作失败！');
        } else {
            $this->display();
        }
    }

    private function cateCreateCheck() {
        $data = $this->checkFields($this->_post('data', false), array('cate_name','lao_cate_name','eng_cate_name','orderby'));
        $data['shop_id'] = $this->shop_id;
        $data['cate_name'] = htmlspecialchars($data['cate_name']);
        $data['lao_cate_name'] = htmlspecialchars($data['lao_cate_name']);
        $data['eng_cate_name'] = htmlspecialchars($data['eng_cate_name']);
        if (empty($data['cate_name'])) {
            $this->baoError('分类中文名称不能为空');
        }
        if (empty($data['lao_cate_name'])) {
            $this->baoError('分类老文名称不能为空');
        }
        if (empty($data['eng_cate_name'])) {
            $this->baoError('分类英文名称不能为空');
        }
        $data['orderby'] = (int)$data['orderby'];
        return $data;
    }

    public function cateedit($cate_id = 0) {
        if ($cate_id = (int) $cate_id) {
            $obj = D('Shopdingcate');
            if (!$detail = $obj->find($cate_id)) {
                $this->error('请选择要编辑的菜品分类');
            }
            if ($detail['shop_id'] != $this->shop_id) {
                $this->error('请不要操作其他商家的菜品分类');
            }
            if ($this->isPost()) {
                $data = $this->cateEditCheck();
                $data['cate_id'] = $cate_id;
                if (false !== $obj->save($data)) {
                    $this->baoSuccess('操作成功', U('shopding/cate'));
                }
                $this->baoError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->error('请选择要编辑的菜品分类');
        }
    }

    private function cateEditCheck() {
        $data = $this->checkFields($this->_post('data', false), array('cate_name','lao_cate_name','eng_cate_name','orderby'));
        $data['cate_name'] = htmlspecialchars($data['cate_name']);
        $data['lao_cate_name'] = htmlspecialchars($data['lao_cate_name']);
        $data['eng_cate_name'] = htmlspecialchars($data['eng_cate_name']);
        if (empty($data['cate_name'])) {
            $this->baoError('分类中文名称不能为空');
        }
        if (empty($data['lao_cate_name'])) {
            $this->baoError('分类老文名称不能为空');
        }
        if (empty($data['eng_cate_name'])) {
            $this->baoError('分类英文名称不能为空');
        }
        $data['orderby'] = (int)$data['orderby'];
        return $data;
    }

    public function catedelete($cate_id = 0) {
        if (is_numeric($cate_id) && ($cate_id = (int) $cate_id)) {
            $obj = D('Shopdingcate');
            if (!$detail = $obj->where(array('shop_id' => $this->shop_id, 'cate_id' => $cate_id))->find()) {
                $this->baoError('请选择要删除的菜品分类');
            }
            $obj->delete($cate_id);
            $this->baoSuccess('删除成功！',U('shopding/cate'));
        }
        $this->baoError('请选择要删除的菜品分类');
    }
    
    //菜品配置 
    
    public function menu(){
        $this->check_ding();
        $dingmenu = D('Shopdingmenu');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['menu_name|lao_menu_name|eng_menu_name'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if ($shop_id = $this->shop_id) {
            $map['shop_id'] = $shop_id;
            $this->assign('shop_id', $shop_id);
        }
        $count = $dingmenu->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 25); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $dingmenu->where($map)->order(array('menu_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function menucreate() {
        if ($this->isPost()) {
            $data = $this->menuCreateCheck();
            $obj = D('Shopdingmenu');
            if ($obj->add($data)) {
                $this->baoSuccess('添加成功', U('shopding/menu'));
            }
            $this->baoError('操作失败！');
        } else {
            $this->display();
        }
    }

    private function menuCreateCheck() {
        $data = $this->checkFields($this->_post('data', false), array('menu_name', 'lao_menu_name','eng_menu_name','cate_id', 'photo','ding_price', 'is_new', 'is_sale', 'is_tuijian'));
        $data['menu_name'] = htmlspecialchars($data['menu_name']);
        $data['lao_menu_name'] = htmlspecialchars($data['lao_menu_name']);
        $data['eng_menu_name'] = htmlspecialchars($data['eng_menu_name']);
        if (empty($data['menu_name'])) {
            $this->baoError('中文菜品名称不能为空');
        }
         if (empty($data['lao_menu_name'])) {
            $this->baoError('老文菜品名称不能为空');
        }
         if (empty($data['eng_menu_name'])) {
            $this->baoError('英文菜品名称不能为空');
        }
        $data['shop_id'] = $this->shop_id;
        $data['cate_id'] = (int) $data['cate_id'];
        if (empty($data['cate_id'])) {
            $this->baoError('菜品分类不能为空');
        }
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->baoError('请上传缩略图');
        }
        if (!isImage($data['photo'])) {
            $this->baoError('缩略图格式不正确');
        }
        $data['ding_price'] = (int) ($data['ding_price']);
        if (empty($data['ding_price'])) {
            $this->baoError('价格不能为空');
        }
        $data['is_new'] = (int) $data['is_new'];
        $data['is_sale'] = (int) $data['is_sale'];
        $data['is_tuijian'] = (int) $data['is_tuijian'];
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        return $data;
    }

    public function menuedit($menu_id = 0) {
        if ($menu_id = (int) $menu_id) {
            $obj = D('Shopdingmenu');
            if (!$detail = $obj->find($menu_id)) {
                $this->baoError('请选择要编辑的菜品设置');
            }
            if ($detail['shop_id'] != $this->shop_id) {
                $this->baoError('请不要操作其他商家的菜品设置');
            }
            if ($this->isPost()) {
                $data = $this->menuEditCheck();
                $data['menu_id'] = $menu_id;
                if (false !== $obj->save($data)) {
                    $this->baoSuccess('操作成功', U('shopding/menu'));
                }
                $this->baoError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->baoError('请选择要编辑的菜品设置');
        }
    }

    private function menuEditCheck() {
        $data = $this->checkFields($this->_post('data', false), array('menu_name','lao_menu_name','eng_menu_name', 'cate_id', 'photo','ding_price', 'is_new', 'is_sale', 'is_tuijian'));
        $data['menu_name'] = htmlspecialchars($data['menu_name']);
        if (empty($data['menu_name'])) {
            $this->baoError('菜品中文名称不能为空');
        }
        $data['lao_menu_name'] = htmlspecialchars($data['lao_menu_name']);
        if (empty($data['lao_menu_name'])) {
            $this->baoError('菜品老文名称不能为空');
        }
        $data['eng_menu_name'] = htmlspecialchars($data['eng_menu_name']);
        if (empty($data['eng_menu_name'])) {
            $this->baoError('菜品英文名称不能为空');
        }
        $data['cate_id'] = (int) $data['cate_id'];
        if (empty($data['cate_id'])) {
            $this->baoError('菜品分类不能为空');
        } 
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->baoError('请上传缩略图');
        }
        if (!isImage($data['photo'])) {
            $this->baoError('缩略图格式不正确');
        }
        $data['ding_price'] = (int) ($data['ding_price']);
        if (empty($data['ding_price'])) {
            $this->baoError('价格不能为空');
        }
        $data['is_new'] = (int) $data['is_new'];
        $data['is_sale'] = (int) $data['is_sale'];
        $data['is_tuijian'] = (int) $data['is_tuijian'];
        $data['audit'] = 0;
        return $data;
    }

    public function menudelete($menu_id = 0) {
        if (is_numeric($menu_id) && ($menu_id = (int) $menu_id)) {
            $obj = D('Shopdingmenu');
            if (!$detail = $obj->where(array('shop_id' => $this->shop_id, 'menu_id' => $menu_id))->find()) {
                $this->baoError('请选择要删除的菜品设置');
            }
            $obj->save(array('menu_id' => $menu_id, 'closed' => 1));
            $this->baoSuccess('删除成功！', U('shopding/menu'));
        }
        $this->baoError('请选择要删除的菜品设置');
    }
    
    public function cancel($order_id){
        $this->check_ding();
        if($order_id = (int) $order_id){
            if(!$order = D('Shopdingorder')->find($order_id)){
                $this->baoError('订单不存在');
            }elseif($order['shop_id'] != $this->shop_id){
                $this->baoError('非法操作订单');
            }elseif($order['order_status'] == -1){
                $this->baoError('该订单已取消');
            }else{
                if(false !== D('Shopdingorder')->cancel($order_id)){
                    $this->baoSuccess('订单取消成功',U('shopding/index'));
                }else{
                    $this->baoError('订单取消失败');
                }
            }
        }else{
            $this->baoError('请选择要取消的订单');
        }
    }
    
    
    public function complete($order_id){
        $this->check_ding();
        if($order_id = (int) $order_id){
            if(!$order = D('Shopdingorder')->find($order_id)){
                $this->baoError('订单不存在');
            }elseif($order['shop_id'] != $this->shop_id){
                $this->baoError('非法操作订单');
            }elseif($order['order_status'] != 0&&$order['order_status'] != 1){
                $this->baoError('该订单无法完成');
            }else{
                if(false !== D('Shopdingorder')->complete($order_id)){
                    $this->baoSuccess('订单操作成功',U('shopding/index'));
                }else{
                    $this->baoError('订单操作失败');
                }
            }
        }else{
            $this->baoError('请选择要完成的订单');
        }
    }
    
    
    public function delete($order_id){
        $this->check_ding();
        if($order_id = (int) $order_id){
            if(!$order = D('Shopdingorder')->find($order_id)){
                $this->baoError('订单不存在');
            }elseif($order['shop_id'] != $this->shop_id){
                $this->baoError('非法操作订单');
            }elseif($order['order_status'] != -1){
                $this->baoError('订单状态不正确');
            }else{
                if(false !== D('Shopdingorder')->delete($order_id)){
                    $this->baoSuccess('订单删除成功',U('shopding/index'));
                }else{
                    $this->baoError('订单删除失败');
                }
            }
        }else{
            $this->baoError('请选择要删除的订单');
        }
    }
    
    public function detail($order_id=null){
        $this->check_ding();
        if(!$order_id = (int)$order_id){
            $this->error('订单不存在');
        }elseif(!$detail = D('Shopdingorder')->find($order_id)){
             $this->error('订单不存在');
        }elseif($detail['closed'] == 1){
             $this->error('订单已删除');
        }elseif($detail['shop_id'] != $this->shop_id){
             $this->error('非法的订单操作');
        }else{
            $logs = D('Paymentlogs')->getLogsByOrderId('ding', $order_id);
            $payments = D('Payment')->getPayments();
            $list = D('Shopdingordermenu')->where(array('order_id'=>$order_id))->select();
            $menu_ids = array();
            foreach($list as $k=>$val){
                $menu_ids[$val['menu_id']] = $val['menu_id'];
            }
            if($menu_ids){
                $this->assign('menus',D('Shopdingmenu')->itemsByIds($menu_ids));
            }
            $this->assign('list',$list);
            
            $this->assign('type',$payments[$logs['code']]);
            $this->assign('detail',$detail);
            $this->display();
        }
    }
    
        function diffBetweenTwoDays ($day1, $day2){
          $second1 = strtotime($day1);
          $second2 = strtotime($day2);

          if ($second1 < $second2) {
            $tmp = $second2;
            $second2 = $second1;
            $second1 = $tmp;
          }
          return ($second1 - $second2) / 86400;
    }

  
}
