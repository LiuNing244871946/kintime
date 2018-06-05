<?php

class ElesettingAction extends CommonAction {

    private $create_fields = array('elesetting_id', 'mileage','delivery','rider');
    private $edit_fields = array('elesetting_id', 'mileage','delivery','rider');

    public function index() {
        $Elesetting = D('Elesetting');
        import('ORG.Util.Page'); // 导入分页类

        $count = $Elesetting->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 5); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $Elesetting->order(array('elesetting_id' => 'asc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function create() {
        if ($this->isPost()) {
            $data2=$data = $this->createCheck();
            $obj = D('Elesetting');
            if ($elesetting_id = $obj->add($data)) {
                $this->baoSuccess('添加成功', U('elesetting/index'));
            }
            $this->baoError('操作失败！');
        } else {
            $this->display();
        }
    }

    private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['mileage'] = (int) $data['mileage'];
        if (empty($data['mileage'])) {
            $this->baoError('公里范围上限不能为空!');
        }
        $data['delivery'] = (int) $data['delivery'];
        if (empty($data['delivery'])) {
            $this->baoError('配送费不能为空');
        }
        $data['rider'] = (int) $data['rider'];
        if (empty($data['rider'])) {
            $this->baoError('骑手所得提成不能为空');
        }
        return $data;
    }

    public function edit($elesetting_id = 0) {

        if ($elesetting_id = (int) $elesetting_id) {
            $obj = D('elesetting');
            if (!$detail = $obj->find($elesetting_id)) {
                $this->baoError('请选择要编辑的外卖配置');
            }
            if ($this->isPost()) {
                $data = $this->editCheck($elesetting_id);
                $data['elesetting_id'] = $elesetting_id;
                if (false !== $obj->save($data)) {
                    $this->baoSuccess('操作成功', U('elesetting/index'));
                }
                $this->baoError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->baoError('请选择要编辑的外卖配置');
        }
    }

    private function editCheck($elesetting_id) {
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['mileage'] = (int) $data['mileage'];
        if (empty($data['mileage'])) {
            $this->baoError('公里范围上限不能为空!');
        }
        $data['delivery'] = (int) $data['delivery'];
        if (empty($data['delivery'])) {
            $this->baoError('配送费不能为空');
        }
        $data['rider'] = (int) $data['rider'];
        if (empty($data['rider'])) {
            $this->baoError('骑手所得提成不能为空');
        }
        return $data;
    }

    public function delete($elesetting_id = 0) {
        if (is_numeric($elesetting_id) && ($elesetting_id = (int) $elesetting_id)) {
            $obj = D('Elesetting');
            $obj->delete($elesetting_id);
            $this->baoSuccess('删除成功！', U('elesetting/index'));
        } else {
            $elesetting_id = $this->_post('elesetting_id', false);
            if (is_array($elesetting_id)) {
                $obj = D('Elesetting');
                foreach ($elesetting_id as $id) {
                    $obj->delete($id);
                }
                $this->baoSuccess('删除成功！', U('elesetting/index'));
            }
            $this->baoError('请选择要删除的外卖配置');
        }
    }
  

}
