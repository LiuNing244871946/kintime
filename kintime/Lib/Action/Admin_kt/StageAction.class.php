<?php

class StageAction extends CommonAction {

    private $create_fields = array('id', 'periods');
    private $edit_fields = array('id', 'periods');

    public function index() {
        $Stage = D('Stage');
        import('ORG.Util.Page'); // 导入分页类

        $count = $Stage->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 5); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $Stage->order(array('id' => 'asc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function create() {
        if ($this->isPost()) {
            $data2=$data = $this->createCheck();
            $obj = D('Stage');
            $res = $obj->find(array('where' => array('periods' => $data['periods'])));
            if (!empty($res)) {
                $this->baoError('该分期数已存在');
            }
            if ($obj->add($data)) {
                $this->baoSuccess('添加成功', U('stage/index'));
            }
            $this->baoError('操作失败！');
        } else {
            $this->display();
        }
    }


    private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['periods'] = htmlspecialchars($data['periods']);
        if (empty($data['periods'])) {
            $this->baoError('分期数不能为空');
        }
        return $data;
    }

    public function edit($id = 0) {

        if ($id = (int) $id) {
            $obj = D('Stage');
            if (!$detail = $obj->find($id)) {
                $this->baoError('请选择要编辑的分期');
            }
            if ($this->isPost()) {
                $data = $this->editCheck();
                $data['id'] = $id;
                $res = $obj->find(array('where' => array('periods' => $data['periods'])));
                if (!empty($res)) {
                    $this->baoError('该分期数已存在');
                }
                if (false !== $obj->save($data)) {
                    $this->baoSuccess('操作成功', U('stage/index'));
                }
                $this->baoError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->baoError('请选择要编辑的分期');
        }
    }

    private function editCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        if (empty($data['periods'])) {
            $this->baoError('分期数不能为空');
        } 
        return $data;
    }

    public function delete($id = 0) {
        $obj = D('Stage');
        $obj->delete($id);
        // $obj->cleanCache();
        $this->baoSuccess('删除成功！', U('stage/index'));
  

    }
}