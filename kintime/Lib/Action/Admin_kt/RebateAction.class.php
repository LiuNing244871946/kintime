<?php

class RebateAction extends CommonAction {
    public function create() {
        if ($this->isPost()) {
            $data = $this->createCheck();

            if (false !== D('Rebate')->add($data)) {
                $this->baoSuccess('操作成功', U('rebate/show'));
            }
            $this->baoError('操作失败');
        } else {
            $this->display();
        }
    }

    private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), array('name', 'user','tui_user','platform','rebate'));

        $data['name'] = htmlspecialchars($data['name']);
        if (empty($data['name'])) {
            $this->baoError('商家折扣不能为空');
        } $data['user'] = (int)$data['user'];
        if (!isRebate($data['user'])) {
            $this->baoError('用户返利格式不正确');
        } $data['tui_user'] = (int)$data['tui_user'];
        if (!isRebate($data['tui_user'])) {
            $this->baoError('用户返利格式不正确');
        } $data['platform'] = (int)$data['platform'];
        if (!isRebate($data['platform'])) {
            $this->baoError('用户返利格式不正确');
        }
        if(($data['user']+$data['tui_user']+$data['platform']) !== 100){
            $this->baoError('返利比例不合理');
        }
        return $data;
    }

    public function edit($id = 0) {
        if ($id = (int) $id) {
            $obj = D('Rebate');
            if (!$detail = $obj->find($id)) {
                $this->baoError('请选择要编辑的比例');
            }

            if ($this->isPost()) {
                $data = $this->editCheck();
                
                $data['id'] = $id;
                if (false !== $obj->save($data)) {
                    $this->baoSuccess('操作成功', U('rebate/show'));
                }
                $this->baoError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->baoError('请选择要编辑的比例');
        }
    }

    private function editCheck() {
        $data = $this->editFields($this->_post('data', false), array('name', 'user','tui_user','platform','rebate'));
        $data['name'] = htmlspecialchars($data['name']);
        if (empty($data['name'])) {
            $this->baoError('商家折扣不能为空');
        } $data['user'] = (int)$data['user'];
        if (!isRebate($data['user'])) {
            $this->baoError('用户返利格式不正确');
        } $data['tui_user'] = (int)$data['tui_user'];
        if (!isRebate($data['tui_user'])) {
            $this->baoError('用户返利格式不正确');
        } $data['platform'] = (int)$data['platform'];
        if (!isRebate($data['platform'])) {
            $this->baoError('用户返利格式不正确');
        }
        if(($data['user']+$data['tui_user']+$data['platform']) !== 100){
            $this->baoError('返利比例不合理');
        }
        return $data;
    }

    public function delete($admin_id = 0) {
        if (is_numeric($admin_id) &&($admin_id = (int) $admin_id)) {
            $obj = D('Admin');
            $obj->save(array('admin_id' => $admin_id, 'closed' => 1));
            $this->baoSuccess('删除成功！', U('admin/index'));
        } else {
            $admin_id = $this->_post('admin_id', false);
            if (is_array($admin_id)) {
                $obj = D('Admin');
                foreach ($admin_id as $id) {
                    $obj->save(array('admin_id' => $id, 'closed' => 1));
                }
                $this->baoSuccess('删除成功！', U('admin/index'));
            }
            $this->baoError('请选择要删除的管理员');
        }
    }

    public function show() {

        $this->assign('qp',D('Rebate')->select());
        $this->display();
    }
}
