<?php

class EleAction extends CommonAction {

    private $create_fields = array('shop_id', 'cate', 'is_open','since_money', 'sold_num', 'month_num', 'intro', 'lao_intro', 'eng_intro', 'audit', 'orderby','package_money');
    private $edit_fields = array('shop_id', 'cate', 'is_open','since_money', 'sold_num', 'month_num', 'intro', 'lao_intro', 'eng_intro', 'audit', 'orderby','package_money');

    public function _initialize() {
        parent::_initialize();
        $getEleCate = D('Ele')->getEleCate();
        $this->assign('getEleCate', $getEleCate);
    }

    public function index() {
        $Ele = D('Ele');
        import('ORG.Util.Page'); // 导入分页类
        $map = array();
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $mmp['shop_name|lao_shop_name|eng_shop_name'] = array('LIKE', '%' . $keyword . '%');
            $mmp['is_ele'] = 1;
            $mm_row = D('shop')->where($mmp)->select();
            $mpp = '';
            foreach ($mm_row as $key => $value) {
                $mpp .= $value['shop_id'].',';
            }
            $mpp = trim($mpp,",");
            $map['shop_id'] = array('exp',' IN ('.$mpp.') ');
            $this->assign('keyword', $keyword);
        }
        $count = $Ele->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 25); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $Ele->where($map)->order(array('shop_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $ids = array();
        foreach ($list as $k => $val) {
            if ($val['shop_id']) {
                $ids[$val['shop_id']] = $val['shop_id'];
            }
        }
        if ($ids) {
            $rebate = D('Rebate')->select();
            foreach ($rebate as $key => $value) {
                $rebate2[$value['id']] = $value;
            }
            $this->assign('rate',$rebate2);
        }
        $data= D('Shop')->where(array('shop_id'=>array('IN',$ids)))->select();
        $shops = array();
        foreach($data as $val){
            $shops[$val['shop_id']] = $val;
        };
        $this->assign('shops', $shops);
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('areas', D('Area')->fetchAll());
        $this->assign('cates', D('Tuancate')->fetchAll());
        $this->assign('business', D('Business')->fetchAll());
        $this->display(); // 输出模板
    }

    public function create() {
        if ($this->isPost()) {
            $data = $this->createCheck();
            $obj = D('Ele');
            $cate = $this->_post('cate', false);
            $cate = implode(',', $cate);
            $data['cate'] = $cate;
            if ($obj->add($data)) {
                $this->baoSuccess('添加成功', U('ele/index'));
            }
            $this->baoError('操作失败！');
        } else {
            $this->display();
        }
    }

    private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['shop_id'] = (int) $data['shop_id'];
        if (empty($data['shop_id'])) {
            $this->baoError('ID不能为空');
        }
        if (!$shop = D('Shop')->find($data['shop_id'])) {
            $this->baoError('商家不存在');
        }
        if ($shop['is_ele'] == 0) {
            $this->baoError('商家未开通外卖功能');
        }

        $data['is_open'] = (int) $data['is_open'];
        $data['since_money'] = (int) ($data['since_money']);
        $data['package_money'] = (int) ($data['since_money']);
        $data['sold_num'] = (int) $data['sold_num'];
        $data['month_num'] = (int) $data['month_num'];
        $data['audit'] = (int) $data['audit'];
        $data['intro'] = htmlspecialchars($data['intro']);
        if (empty($data['intro'])) {
            $this->baoError('中文说明不能为空');
        }$data['lao_intro'] = htmlspecialchars($data['lao_intro']);
        if (empty($data['lao_intro'])) {
            $this->baoError('老文说明不能为空');
        }$data['eng_intro'] = htmlspecialchars($data['eng_intro']);
        if (empty($data['eng_intro'])) {
            $this->baoError('英文说明不能为空');
        }
        $data['orderby'] = (int) $data['orderby'];
        return $data;
    }

    public function edit($shop_id = 0) {
        if ($shop_id = (int) $shop_id) {
            $obj = D('Ele');
            if (!$detail = $obj->find($shop_id)) {
                $this->baoError('请选择要编辑的餐饮商家');
            }
            if ($this->isPost()) {
                $data = $this->editCheck();
                $data['shop_id'] = $shop_id;
                $shop = D('Shop')->find($shop_id);
                $cate = $this->_post('cate', false);
                $cate = implode(',', $cate);
                $data['cate'] = $cate;
                if (false !== $obj->save($data)) {
                    $this->baoSuccess('操作成功', U('ele/index'));
                }
                $this->baoError('操作失败');
            } else {
                $cate = explode(',', $detail['cate']);
                $this->assign('cate', $cate);
                $this->assign('detail', $detail);
                $this->assign('shops', D('Shop')->find($detail['shop_id']));
                $this->display();
            }
        } else {
            $this->baoError('请选择要编辑的餐饮商家');
        }
    }

    private function editCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);

        $data['is_open'] = (int) $data['is_open'];
        $data['package_money'] = (int) ($data['package_money']);
        $data['since_money'] = (int) ($data['since_money']);
        $data['audit'] = (int) $data['audit'];
        $data['intro'] = htmlspecialchars($data['intro']);
        if (empty($data['intro'])) {
            $this->baoError('说明不能为空');
        }
        $data['lao_intro'] = htmlspecialchars($data['lao_intro']);
        if (empty($data['lao_intro'])) {
            $this->baoError('老文说明不能为空');
        }$data['eng_intro'] = htmlspecialchars($data['eng_intro']);
        if (empty($data['eng_intro'])) {
            $this->baoError('英文说明不能为空');
        }
        $data['orderby'] = (int) $data['orderby'];
        return $data;
    }

    public function delete($shop_id = 0) {
        if (is_numeric($shop_id) && ($shop_id = (int) $shop_id)) {
            $obj = D('Ele');
            $obj->delete($shop_id);
            $this->baoSuccess('删除成功！', U('ele/index'));
        } else {
            $shop_id = $this->_post('shop_id', false);
            if (is_array($shop_id)) {
                $obj = D('Ele');
                foreach ($shop_id as $id) {
                    $obj->delete($id);
                }
                $this->baoSuccess('删除成功！', U('ele/index'));
            }
            $this->baoError('请选择要删除的餐饮商家');
        }
    }
    
    public function audit($shop_id = 0) {
        if (is_numeric($shop_id) && ($shop_id = (int) $shop_id)) {
            $obj = D('Ele');
            $obj->save(array('shop_id'=>$shop_id,'audit'=>1));
            $this->baoSuccess('审核成功！', U('ele/index'));
        } else {
            $shop_id = $this->_post('shop_id', false);
            if (is_array($shop_id)) {
                $obj = D('Ele');
                foreach ($shop_id as $id) {
                    $obj->save(array('shop_id'=>$id,'audit'=>1));
                }
                $this->baoSuccess('审核成功！', U('ele/index'));
            }
            $this->baoError('请选择要审核的餐饮商家');
        }
    }
    

    public function opened($shop_id = 0, $type = 'open') {
        if (is_numeric($shop_id) && ($shop_id = (int) $shop_id)) {


            $obj = D('Ele');
            $is_open = 0;
            if ($type == 'open') {
                $is_open = 1;
            }
            $obj->save(array('shop_id' => $shop_id, 'is_open' => $is_open));

            $this->baoSuccess('操作成功！', U('ele/index'));
        }
    }

    public function select(){
        $ele = D('Ele');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('audit'=>1);
        if($keyword = $this->_param('keyword','htmlspecialchars')){
            $map['shop_name|intro'] = array('LIKE','%'.$keyword.'%');
            $this->assign('keyword',$keyword);
        }
        $count = $ele->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $pager = $Page->show(); // 分页显示输出
        $list = $ele->where($map)->order(array('shop_id'=>'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $pager); // 赋值分页输出
        $this->display(); // 输出模板
        
    }
    
}
