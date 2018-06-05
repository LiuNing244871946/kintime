<?php

class DingAction extends CommonAction {

    public function _initialize() {
        parent::_initialize();
        $this->types = D('Shopding')->getDingType();
        $this->assign('dingtypes',$this->types);
    }
    
    
    public function index() {
        $shopding = D('Shopding');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0, 'audit' => 1);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $mmp['shop_name|lao_shop_name|eng_shop_name'] = array('LIKE', '%' . $keyword . '%');
            $mmp['is_dianzicaidan'] = 1;
            $mm_row = D('shop')->where($mmp)->select();
            $mpp = '';
            foreach ($mm_row as $key => $value) {
                $mpp .= $value['shop_id'].',';
            }
            $mpp = trim($mpp,",");
            $map['shop_id'] = array('exp',' IN ('.$mpp.') ');
            $this->assign('keyword', $keyword);
        }
        $count = $shopding->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 25); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $shopding->where($map)->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $ids = array();
        foreach ($list as $k => $val) {
            if ($val['shop_id']) {
                $ids[$val['shop_id']] = $val['shop_id'];
            }
        }
        $data= D('Shop')->where(array('shop_id'=>array('IN',$ids)))->select();
        $shops = array();
        foreach($data as $val){
            $shops[$val['shop_id']] = $val;
        };
        $this->assign('shops', $shops);
        $this->assign('citys', D('City')->fetchAll());
        $this->assign('areas', D('Area')->fetchAll());
        $this->assign('business', D('Business')->fetchAll());
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }
    
    public function order() {
        $shopdingorder = D('Shopdingorder');
        import('ORG.Util.Page'); // 导入分页类
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['order_id|name|mobile'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if (isset($_GET['st']) || isset($_POST['st'])) {
            $st = (int) $this->_param('st');
            if ($st != 999) {
                $map['order_status'] = $st;
            }
            $this->assign('st', $st);
        } else {
            $this->assign('st', 999);
        }
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
        $count = $shopdingorder->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 25); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $shopdingorder->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $shop_ids = $user_ids = array();
        foreach ($list as $k=>$val){
            $shop_ids[$val['shop_id']] = $val['shop_id'];
            $user_ids[$val['user_id']] = $val['user_id'];
        }
        $this->assign('shops',D('Shopding')->itemsByIds($shop_ids));
        $this->assign('shopinfo',D('Shop')->itemsByIds($shop_ids));
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('status', D('Shopdingorder')->getStatus());
        $this->display(); // 输出模板
    }


    public function noaudit(){
        $shopding = D('Shopding');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0, 'audit' => array('IN',array(0,2)));
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['shop_name'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        
        $count = $shopding->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 25); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $shopding->where($map)->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $ids = array();
        foreach ($list as $k => $val) {
            if ($val['shop_id']) {
                $ids[$val['shop_id']] = $val['shop_id'];
            }
        }
        $data= D('Shop')->where(array('shop_id'=>array('IN',$ids)))->select();
        $shops = array();
        foreach($data as $val){
            $shops[$val['shop_id']] = $val;
        };
        $this->assign('shops', $shops);
        $this->assign('citys', D('City')->fetchAll());
        $this->assign('areas', D('Area')->fetchAll());
        $this->assign('business', D('Business')->fetchAll());
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }
    
    public function create() {
        $obj = D('Shopding');
        if ($this->isPost()) {
            $data = $this->createCheck();
            $data['create_time'] = NOW_TIME;
            $data['create_ip'] = get_client_ip();
            if ($obj->add($data)) {
                $this->baoSuccess('操作成功', U('ding/index'));
            }
            $this->baoError('操作失败');
        } else {
            $this->display();
        }
       
    }
    
    private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), array('shop_id','price','details','lao_details','eng_details','stime','ltime','table_num'));
        $data['price'] = (int)$data['price'];
        if (empty($data['price'])) {
            $this->baoError('平均消费不能为空');
        }
        $data['shop_id'] = (int)$data['shop_id'];
        if(empty($data['shop_id'])){
            $this->baoError('商家不能为空');
        }elseif(!$shop = D('Shop')->find($data['shop_id'])){
            $this->baoError('商家不存在');
        }
        if ($shop['is_dianzicaidan'] == 0) {
            $this->baoError('商家未开通电子菜单及订座');
        }
        $data['table_num'] = (int)$data['table_num'];
        if (empty($data['table_num'])) {
            $this->baoError('桌数不能为空');
        }
        $data['details'] = SecurityEditorHtml($data['details']);
        if (empty($data['details'])) {
            $this->baoError('商家中文详情不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['details'])) {
            $this->baoError('商家中文详情含有敏感词：' . $words);
        } 
        $data['lao_details'] = SecurityEditorHtml($data['lao_details']);
        if (empty($data['lao_details'])) {
            $this->baoError('商家老文详情不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['lao_details'])) {
            $this->baoError('商家老文详情含有敏感词：' . $words);
        } 
        $data['eng_details'] = SecurityEditorHtml($data['eng_details']);
        if (empty($data['eng_details'])) {
            $this->baoError('商家英文详情不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['eng_details'])) {
            $this->baoError('商家英文详情含有敏感词：' . $words);
        }  
        $data['stime'] = htmlspecialchars($data['stime']);
        $data['ltime'] = htmlspecialchars($data['ltime']);
        if (empty($data['stime'])&&empty($data['ltime'])) {
            $this->baoError('开始营业时间和结束营业时间不能为空');
        }
        $data['audit'] = 0;
        return $data;
    }
    
    
    public function edit($shop_id = 0) {

        if ($shop_id = (int) $shop_id) {
            $obj = D('Shopding');
            if (!$detail = $obj->find($shop_id)) {
                $this->baoError('请选择要编辑的订座商家');
            }
            if ($this->isPost()) {
                $data = $this->editCheck();
                $data['shop_id'] = $shop_id;
                $data['update_time'] = NOW_TIME;
                $data['update_ip'] = get_client_ip();
                if (false !== $obj->save($data)) {
                    $this->baoSuccess('操作成功', U('ding/index'));
                }
                $this->baoError('操作失败');
            } else {
                $this->assign('shops', D('Shop')->find($shop_id));
                $this->assign('detail',$detail);
                $this->display();
            }
        } else {
            $this->baoError('请选择要编辑的订座商家');
        }
    }
    
    private function editCheck() {
        $data = $this->checkFields($this->_post('data', false), array('shop_id','price','details','lao_details','eng_details','stime','ltime','table_num'));
        $data['price'] = (int)$data['price'];
        if (empty($data['price'])) {
            $this->baoError('平均消费不能为空');
        }
        $data['shop_id'] = (int)$data['shop_id'];
        if(empty($data['shop_id'])){
            $this->baoError('商家不能为空');
        }elseif(!$shop = D('Shop')->find($data['shop_id'])){
            $this->baoError('商家不存在');
        }
        if ($shop['is_dianzicaidan'] == 0) {
            $this->baoError('商家未开通电子菜单及订座');
        }
        $data['table_num'] = (int)$data['table_num'];
        if (empty($data['table_num'])) {
            $this->baoError('桌数不能为空');
        }
        
        $data['details'] = SecurityEditorHtml($data['details']);
        if (empty($data['details'])) {
            $this->baoError('酒店中文详情不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['details'])) {
            $this->baoError('酒店中文详情含有敏感词：' . $words);
        } 
        $data['lao_details'] = SecurityEditorHtml($data['lao_details']);
        if (empty($data['lao_details'])) {
            $this->baoError('酒店老文详情不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['lao_details'])) {
            $this->baoError('酒店老文详情含有敏感词：' . $words);
        } 
        $data['eng_details'] = SecurityEditorHtml($data['eng_details']);
        if (empty($data['eng_details'])) {
            $this->baoError('酒店英文详情不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['eng_details'])) {
            $this->baoError('酒店英文详情含有敏感词：' . $words);
        } 
        $data['stime'] = htmlspecialchars($data['stime']);
        $data['ltime'] = htmlspecialchars($data['ltime']);
        if (empty($data['stime'])&&empty($data['ltime'])) {
            $this->baoError('开始营业时间和结束营业时间不能为空');
        }
        $data['audit'] = 0;
        return $data;
    }
    
    
    public function cancel($order_id=0){
        $obj = D('Shopdingorder');
        if (is_numeric($order_id) && ($order_id = (int) $order_id)) {
            $obj->cancel($order_id);
            $this->baoSuccess('取消成功！', U('ding/order'));
        } else {
            $order_id = $this->_post('order_id', false);
            if (is_array($order_id)) {
                foreach ($order_id as $id) {
                    $obj->cancel($id);
                }
                $this->baoSuccess('取消成功！', U('ding/order'));
            }
            $this->baoError('请选择要取消的订单');
        }
    }
    
    public function complete($order_id=0){
        $obj = D('Shopdingorder');
        if (is_numeric($order_id) && ($order_id = (int) $order_id)) {
            $obj->complete($order_id);
            $this->baoSuccess('订单确认完成！', U('ding/order'));
        } else {
            $order_id = $this->_post('order_id', false);
            if (is_array($order_id)) {
                foreach ($order_id as $id) {
                    $obj->complete($id);
                }
                $this->baoSuccess('订单确认完成！', U('ding/order'));
            }
            $this->baoError('请选择要完成的订单');
        }
    }

    public function orderdelete($order_id = 0) {
        $obj = D('Shopdingorder');
        if (is_numeric($order_id) && ($order_id = (int) $order_id)) {
            $obj->delete($order_id);
            $this->baoSuccess('删除成功！', U('ding/order'));
        } else {
            $order_id = $this->_post('order_id', false);
            if (is_array($order_id)) {
                foreach ($order_id as $id) {
                    $obj->delete($id);
                }
                $this->baoSuccess('删除成功！', U('ding/order'));
            }
            $this->baoError('请选择要删除的订单');
        }
    }
    

    public function delete($shop_id = 0) {
        $obj = D('Shopding');
        if (is_numeric($shop_id) && ($shop_id = (int) $shop_id)) {
            $obj->save(array('shop_id' => $shop_id, 'closed' => 1));
            $this->baoSuccess('删除成功！', U('ding/index'));
        } else {
            $shop_id = $this->_post('shop_id', false);
            if (is_array($shop_id)) {
                foreach ($shop_id as $id) {
                    $obj->save(array('shop_id' => $id, 'closed' => 1));
                }
                $this->baoSuccess('删除成功！', U('ding/index'));
            }
            $this->baoError('请选择要删除的商家');
        }
    }

    public function audit($shop_id = 0) {
        $obj = D('Shopding');
        if (is_numeric($shop_id) && ($shop_id = (int) $shop_id)) {
            $obj->save(array('shop_id' => $shop_id, 'audit' => 1));
            $this->baoSuccess('审核成功！', U('ding/index'));
        } else {
            $shop_id = $this->_post('shop_id', false);
            if (is_array($shop_id)) {
                foreach ($shop_id as $id) {
                    $obj->save(array('shop_id' => $id, 'audit' => 1));
                }
                $this->baoSuccess('审核成功！', U('ding/index'));
            }
            $this->baoError('请选择要审核的订座商家');
        }
    }

    public function refuse($shop_id){
        $obj = D('Shopding');
         if (is_numeric($shop_id) && ($shop_id = (int) $shop_id)) {
            $obj->save(array('shop_id' => $shop_id, 'audit' => 2));
            $this->baoSuccess('操作成功！', U('ding/index'));
         }
    }
    
}
