<?php

class ServiceAction extends CommonAction {

    private $create_fields = array('title', 'intro','orderby');
    private $edit_fields = array('title', 'intro','orderby');

    public function index() {
        $service = D('Service');
        import('ORG.Util.Page'); // 导入分页类
        $map = array();
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        if ($keyword) {
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('title', $title);
        }
        $count = $service->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $service->where($map)->order(array('service_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    

    public function create() {
        if ($this->isPost()) {
            $data = $this->createCheck();
            $obj = D('Service');
            if ($obj->add($data)) {
                $this->baoSuccess('添加成功', U('service/index'));
            }
            $this->baoError('操作失败！');
        } else {
            $this->display();
        }
    }

    private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->baoError('标题不能为空');
        } $data['intro'] = htmlspecialchars($data['intro']);
        if (empty($data['intro'])) {
            $this->baoError('简介不能为空');
        }
        $data['orderby'] = (int) $data['orderby'];
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        return $data;
    }

    public function edit($service_id = 0) {
        if ($service_id = (int) $service_id) {
            $obj = D('Service');
            if (!$detail = $obj->find($service_id)) {
                $this->baoError('请选择要编辑的内容');
            }
            if ($this->isPost()) {
                $data = $this->editCheck();
                $data['service_id'] = $service_id;
                if (false !== $obj->save($data)) {
                    $this->baoSuccess('操作成功', U('service/index'));
                }
                $this->baoError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->baoError('请选择要编辑的内容');
        }
    }

    private function editCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->baoError('标题不能为空');
        } $data['intro'] = htmlspecialchars($data['intro']);
        if (empty($data['intro'])) {
            $this->baoError('简介不能为空');
        }
        $data['orderby'] = (int) $data['orderby'];
        return $data;
    }

    public function delete($service_id = 0) {
        if (is_numeric($service_id) && ($service_id = (int) $service_id)) {
            $obj = D('Service');
            $obj->delete($service_id);
            $this->baoSuccess('删除成功！', U('service/index'));
        } else {
            $service_id = $this->_post('service_id', false);
            if (is_array($service_id)) {
                $obj = D('Service');
                foreach ($service_id as $id) {
                    $obj->delete($id);
                }
                $this->baoSuccess('删除成功！', U('service/index'));
            }
            $this->baoError('请选择要删除的内容');
        }
    }

}
