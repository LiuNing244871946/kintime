<?php

class FunctionAction extends CommonAction {

    private $create_fields = array('title','lao_title','eng_title', 'photo','url','is_index','orderby');
    private $edit_fields = array('title','lao_title','eng_title', 'photo','url','is_index','orderby');

    public function index() {
        $func = D('Function');
        import('ORG.Util.Page'); // 导入分页类
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        if ($keyword) {
            $map['title|lao_title|eng_title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('title', $title);
        }
        $count = $func->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 25); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $func->where($map)->order(array('orderby' => 'asc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }


    public function create() {
        if ($this->isPost()) {
            $data = $this->createCheck();
            $obj = D('Function');
            if ($obj->add($data)) {
                $this->baoSuccess('添加成功', U('function/index'));
            }
            $this->baoError('操作失败！');
        } else {
            $this->display();
        }
    }

    private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['title'] = htmlspecialchars($data['title']);
        $data['lao_title'] = htmlspecialchars($data['lao_title']);
        $data['eng_title'] = htmlspecialchars($data['eng_title']);
        if (empty($data['title'])) {
            $this->baoError('功能标题不能为空');
        }
        if (empty($data['lao_title'])) {
            $this->baoError('功能标题不能为空');
        }
        if (empty($data['eng_title'])) {
            $this->baoError('功能标题不能为空');
        }
         $data['url'] = htmlspecialchars($data['url']);
        if (empty($data['url'])) {
            $this->baoError('功能链接不能为空');
        }
        $data['photo'] = htmlspecialchars($data['photo']);
        if(empty($data['photo'])){
            $this->baoError('请上传功能图标');
        }
        if (!isImage($data['photo'])) {
            $this->baoError('请上传正确的图片');
        }
        $data['orderby'] = (int) $data['orderby'];
        $data['create_time'] = NOW_TIME;
        $data['is_index'] = (int) $data['is_index'];
        $data['create_ip'] = get_client_ip();
        return $data;
    }

    public function edit($func_id = 0) {
        if ($func_id = (int) $func_id) {
            $obj = D('Function');
            if (!$detail = $obj->find($func_id)) {
                $this->baoError('请选择要编辑的功能');
            }
            if ($this->isPost()) {
                $data = $this->editCheck();
                $data['func_id'] = $func_id;
                if (false !== $obj->save($data)) {
                    $this->baoSuccess('操作成功', U('function/index'));
                }
                $this->baoError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->baoError('请选择要编辑的功能');
        }
    }

    
    private function editCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['title'] = htmlspecialchars($data['title']);
        $data['lao_title'] = htmlspecialchars($data['lao_title']);
        $data['eng_title'] = htmlspecialchars($data['eng_title']);
        if (empty($data['title'])) {
            $this->baoError('功能标题不能为空');
        }
        if (empty($data['lao_title'])) {
            $this->baoError('功能标题不能为空');
        }
        if (empty($data['eng_title'])) {
            $this->baoError('功能标题不能为空');
        }
         $data['url'] = htmlspecialchars($data['url']);
        if (empty($data['url'])) {
            $this->baoError('功能链接不能为空');
        }
        $data['photo'] = htmlspecialchars($data['photo']);
        if(empty($data['photo'])){
            $this->baoError('请上传功能图标');
        }
        if (!isImage($data['photo'])) {
            $this->baoError('请上传正确的图片');
        }
        $data['orderby'] = (int) $data['orderby'];
        $data['create_time'] = NOW_TIME;
        $data['is_index'] = (int) $data['is_index'];
        $data['create_ip'] = get_client_ip();
        return $data;
    }
    

    public function delete($func_id = 0) {
        if (is_numeric($func_id) && ($func_id = (int) $func_id)) {
            $obj = D('Function');
            $obj->delete($func_id);
            $this->baoSuccess('删除成功！', U('function/index'));
        } else {
            $func_id = $this->_post('func_id', false);
            if (is_array($func_id)) {
                $obj = D('Function');
                foreach ($func_id as $id) {
                    $obj->delete($id);
                }
                $this->baoSuccess('删除成功！', U('function/index'));
            }
            $this->baoError('请选择要删除的功能');
        }
    }

    public function close($func_id = 0) {
        if (is_numeric($func_id) && ($func_id = (int) $func_id)) {
            $obj = D('Function');
            $obj->save(array('func_id' => $func_id, 'is_index' =>0));
            $this->baoSuccess('关闭成功！', U('function/index'));
        } else {
            $func_id = $this->_post('func_id', false);
            if (is_array($func_id)) {
                $obj = D('Function');
                foreach ($func_id as $id) {
                    $obj->save(array('func_id' => $id, 'is_index' => 0));
                }
                $this->baoSuccess('关闭成功！', U('function/index'));
            }
            $this->baoError('请选择要在首页关闭的功能');
        }
    }
    
    public function open($func_id = 0) {
        if (is_numeric($func_id) && ($func_id = (int) $func_id)) {
            $obj = D('Function');
            $obj->save(array('func_id' => $func_id, 'is_index' =>1));
            $this->baoSuccess('开启成功！', U('function/index'));
        } else {
            $func_id = $this->_post('func_id', false);
            if (is_array($func_id)) {
                $obj = D('Function');
                foreach ($func_id as $id) {
                    $obj->save(array('func_id' => $id, 'is_index' => 1));
                }
                $this->baoSuccess('开启成功！', U('function/index'));
            }
            $this->baoError('请选择要在首页开启的功能');
        }
    }

}
