<?php

class WeidiancateAction extends CommonAction {

    private $create_fields = array('cate_name','lao_cate_name','eng_cate_name', 'orderby');
    private $edit_fields = array('cate_name','lao_cate_name','eng_cate_name', 'orderby');

    public function index() {
        $weidiancate = D('Weidiancate');
        $list = $weidiancate->fetchAll();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function create() {
        if ($this->isPost()) {
            $data = $this->createCheck();
            $obj = D('Weidiancate');
            if ($obj->add($data)) {
                $obj->cleanCache();
                $this->baoSuccess('添加成功', U('weidiancate/index'));
            }
            $this->baoError('操作失败！');
        } else {
            $this->display();
        }
    }
    
    private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['cate_name'] = htmlspecialchars($data['cate_name']);
        $data['lao_cate_name'] = htmlspecialchars($data['lao_cate_name']);
        $data['eng_cate_name'] = htmlspecialchars($data['eng_cate_name']);
        if (empty($data['cate_name'])) {
            $this->baoError('分类中文名不能为空');
        }
        if (empty($data['lao_cate_name'])) {
            $this->baoError('分类老文名不能为空');
        }
        if (empty($data['eng_cate_name'])) {
            $this->baoError('分类英文名不能为空');
        }
        $data['orderby'] = (int) $data['orderby'];
        return $data;
    }

    public function edit($cate_id = 0) {
        if ($cate_id = (int) $cate_id) {
            $obj = D('Weidiancate');
            if (!$detail = $obj->find($cate_id)) {
                $this->baoError('请选择要编辑的商店分类');
            }
            if ($this->isPost()) {
                $data = $this->editCheck();
                $data['cate_id'] = $cate_id;
                if (false !== $obj->save($data)) {
                    $obj->cleanCache();
                    $this->baoSuccess('操作成功', U('weidiancate/index'));
                }
                $this->baoError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->baoError('请选择要编辑的商店分类');
        }
    }

    private function editCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['cate_name'] = htmlspecialchars($data['cate_name']);
        $data['lao_cate_name'] = htmlspecialchars($data['lao_cate_name']);
        $data['eng_cate_name'] = htmlspecialchars($data['eng_cate_name']);
        if (empty($data['cate_name'])) {
            $this->baoError('分类中文名不能为空');
        }
        if (empty($data['lao_cate_name'])) {
            $this->baoError('分类老文名不能为空');
        }
        if (empty($data['eng_cate_name'])) {
            $this->baoError('分类英文名不能为空');
        }
        $data['orderby'] = (int) $data['orderby'];
        return $data;
    }

    public function delete($cate_id = 0) {
        if (is_numeric($cate_id) && ($cate_id = (int) $cate_id)) {
            $obj = D('Weidiancate');
            $obj->delete($cate_id);
            $obj->cleanCache();
            $this->baoSuccess('删除成功！', U('weidiancate/index'));
        } else {
            $cate_id = $this->_post('cate_id', false);
            if (is_array($cate_id)) {
                $obj = D('Weidiancate');
                foreach ($cate_id as $id) {
                    $obj->delete($id);
                }
                $obj->cleanCache();
                $this->baoSuccess('删除成功！', U('weidiancate/index'));
            }
            $this->baoError('请选择要删除的商店分类');
        }
    }
    


}

