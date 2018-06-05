<?php



class ElecateAction extends CommonAction {

    private $create_fields = array('cate_name','lao_cate_name','eng_cate_name');
    private $edit_fields = array('cate_name','lao_cate_name','eng_cate_name');

    
     public function _initialize() {
        parent::_initialize();
        $this->shop_id = D('Shop')->where('mer_id='.$this->mer_id)->find()['shop_id'];
        if (empty($this->shop_id) && ACTION_NAME != 'apply') {
            $this->error('您还没有入住美食频道', U('shop/about'));
        }
        $this->is_ele = D('Shop')->where('mer_id='.$this->mer_id)->find()['is_ele'];
        if ($this->is_ele==0) {
            $this->error('您还没有开通外卖频道', U('shop/about'));
        }
        $getEleCate = D('Ele')->getEleCate();
        $this->assign('getEleCate', $getEleCate);
        $this->ele = D('Ele')->find($this->shop_id);
        if (empty($this->ele) && ACTION_NAME != 'apply') {
            $this->error('您还没有填写外卖资料', U('ele/apply'));
        }
        if (!empty($this->ele) && $this->ele['audit'] == 0) {
            $this->error("亲，您的资料正在审核中！");
        }
        $this->assign('ele', $this->ele);
    }

     public function index() {
        $Elecate = D('Elecate');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed'=>'0');
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['cate_name|lao_cate_name|eng_cate_name'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if ($shop_id = $this->shop_id) {
            $map['shop_id'] = $shop_id;
        }

        $count = $Elecate->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 25); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $Elecate->where($map)->order(array('cate_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function create() {
        if ($this->isPost()) {
            $data = $this->createCheck();
            $obj = D('Elecate');
            if ($obj->add($data)) {
                $this->baoSuccess('添加成功', U('elecate/index'));
            }
            $this->baoError('操作失败！');
        } else {
            $this->display();
        }
    }

    private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['shop_id'] = $this->shop_id;
        $data['cate_name'] = htmlspecialchars($data['cate_name']);
        $data['lao_cate_name'] = htmlspecialchars($data['lao_cate_name']);
        $data['eng_cate_name'] = htmlspecialchars($data['eng_cate_name']);
        if (empty($data['cate_name'])) {
            $this->baoError('分类名称不能为空');
        }
        if (empty($data['lao_cate_name'])) {
            $this->baoError('老文分类名称不能为空');
        }
        if (empty($data['eng_cate_name'])) {
            $this->baoError('英文分类名称不能为空');
        }
        return $data;
    }

    public function edit($cate_id = 0) {
        if ($cate_id = (int) $cate_id) {
            $obj = D('Elecate');
            if (!$detail = $obj->find($cate_id)) {
                $this->error('请选择要编辑的菜单分类');
            }
            if ($detail['shop_id'] != $this->shop_id) {
                $this->error('请不要操作其他商家的菜单分类');
            }
            if ($this->isPost()) {
                $data = $this->editCheck();
                $data['cate_id'] = $cate_id;
                if (false !== $obj->save($data)) {
                    $this->baoSuccess('修改成功', U('elecate/index'));
                }
                $this->baoError('修改失败');
            } else {
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->error('请选择要编辑的菜单分类');
        }
    }

    private function editCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['cate_name'] = htmlspecialchars($data['cate_name']);
        $data['lao_cate_name'] = htmlspecialchars($data['lao_cate_name']);
        $data['eng_cate_name'] = htmlspecialchars($data['eng_cate_name']);
        if (empty($data['cate_name'])) {
            $this->baoError('分类名称不能为空');
        }
        if (empty($data['lao_cate_name'])) {
            $this->baoError('老文分类名称不能为空');
        }
        if (empty($data['eng_cate_name'])) {
            $this->baoError('英文分类名称不能为空');
        }
        return $data;
    }

    public function delete($cate_id = 0) {
        if (is_numeric($cate_id) && ($cate_id = (int) $cate_id)) {
            $obj = D('Elecate');
            if (!$detail = $obj->where(array('shop_id' => $this->shop_id, 'cate_id' => $cate_id))->find()) {
                $this->baoError('请选择要删除的菜单分类');
            }
            if (D('EleProduct')->where(array('cate_id' => $cate_id, 'shop_id' => $this->shop_id, 'colsed' => 0))->select()) {
                $this->baoError('请先删除菜单分类下的菜品');
            }
            $obj->save(array('cate_id' => $cate_id, 'closed' => 1));
            $this->baoSuccess('删除成功！', U('elecate/index'));
        }
        $this->baoError('请选择要删除的菜单分类');
    }

}
