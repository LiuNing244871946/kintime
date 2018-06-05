<?php

class QuotepriceAction extends CommonAction {

    public function edit($id = 0) {
        if ($id = (int) $id) {
            $obj = D('Quoteprice');
            if (!$detail = $obj->find($id)) {
                $this->baoError('请选择要编辑的币种');
            }

            if ($this->isPost()) {
                $data = $this->editCheck();
                
                $data['id'] = $id;
                if ($obj->save($data)) {
                    $this->baoSuccess('操作成功', U('quoteprice/show'));
                }
                $this->baoError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->baoError('请选择要编辑的币种');
        }
    }

    private function editCheck() {
        $data = $_POST;
        if (empty($data['lv'])) {
            $this->baoError('牌价不能为空');
        }
        if (!isQprice($data['lv'])) {
            $this->baoError('牌价格式不正确');
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

        $this->assign('qp',D('Quoteprice')->select());
        // var_dump(D('Quoteprice')->find(1)['lv']);
        $this->display();
    }
}
