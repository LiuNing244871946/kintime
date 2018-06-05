<?php

class PcfunctionAction extends CommonAction {

    private $create_fields = array('title','url','is_nav','is_show','is_system','orderby');
    private $edit_fields = array('title','url','is_nav','is_show','is_system','orderby');

    public function index() {
        $this->assign('list', D('Pcfunction')->fetchAll()); // 赋值数据集
        $this->display(); // 输出模板

    }
    
    public function update(){
        $func = D('Pcfunction');
        $datas = $this->_param('data',false);
        foreach($datas as $k=>$val){
            $data = array(
                'function_id' => (int)$k, 
                'title' => htmlspecialchars($val['title']),
                'url' => htmlspecialchars($val['url']),
                'is_show' =>(int) $val['is_show'],
                'is_nav' => (int)$val['is_nav'],
                'orderby' => (int)$val['orderby'],
            );
            $func->save($data);
        }
        $func->cleanCache();
        $this->baoSuccess('更新成功', U('pcfunction/index'));
    }

    public function create() {
        if ($this->isPost()) {
            $data = $this->createCheck();
            $obj = D('Pcfunction');
            if ($obj->add($data)) {
                $obj->cleanCache();
                $this->baoSuccess('添加成功', U('pcfunction/index'));
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
            $this->baoError('功能标题不能为空');
        } $data['url'] = htmlspecialchars($data['url']);
        if (empty($data['url'])) {
            $this->baoError('功能链接不能为空');
        }
        $data['orderby'] = (int) $data['orderby'];
        $data['is_nav'] = (int) $data['is_nav'];
        $data['is_show'] = (int) $data['is_show'];
        return $data;
    }

    public function show($function_id = 0){
        $obj = D('Pcfunction');
        if (is_numeric($function_id) && ($function_id = (int) $function_id)) {
            $obj->save(array('function_id'=>$function_id,'is_show'=>1));
            $obj->cleanCache();
            $this->baoSuccess('操作成功！', U('pcfunction/index'));
        } 
    }

    public function closed($function_id = 0){
        $obj = D('Pcfunction');
        if (is_numeric($function_id) && ($function_id = (int) $function_id)) {
            $obj->save(array('function_id'=>$function_id,'is_show'=>0));
            $obj->cleanCache();
            $this->baoSuccess('操作成功！', U('pcfunction/index'));
        } 
    }
    
    
    public function navoff($function_id = 0){
        $obj = D('Pcfunction');
        if (is_numeric($function_id) && ($function_id = (int) $function_id)) {
            $obj->save(array('function_id'=>$function_id,'is_nav'=>0));
            $obj->cleanCache();
            $this->baoSuccess('操作成功！', U('pcfunction/index'));
        } 
    }
    
    public function navon($function_id = 0){
        $obj = D('Pcfunction');
        if (is_numeric($function_id) && ($function_id = (int) $function_id)) {
            $obj->save(array('function_id'=>$function_id,'is_nav'=>1));
            $obj->cleanCache();
            $this->baoSuccess('操作成功！', U('pcfunction/index'));
        } 
    }
    
    public function newoff($function_id = 0){
        $obj = D('Pcfunction');
        if (is_numeric($function_id) && ($function_id = (int) $function_id)) {
            $obj->save(array('function_id'=>$function_id,'is_new'=>0));
            $obj->cleanCache();
            $this->baoSuccess('操作成功！', U('pcfunction/index'));
        } 
    }
    
    public function set_new($function_id = 0){
        $obj = D('Pcfunction');
        if (is_numeric($function_id) && ($function_id = (int) $function_id)) {
            $obj->save(array('function_id'=>$function_id,'is_new'=>1));
            $obj->cleanCache();
            $this->baoSuccess('操作成功！', U('pcfunction/index'));
        } 
    }
    
    public function cancel($function_id = 0){
        $obj = D('Pcfunction');
        if (is_numeric($function_id) && ($function_id = (int) $function_id)) {
            $obj->save(array('function_id'=>$function_id,'is_system'=>0));
            $obj->cleanCache();
            $this->baoSuccess('操作成功！', U('pcfunction/index'));
        } 
    }
    
    public function system($function_id = 0){
        $obj = D('Pcfunction');
        if (is_numeric($function_id) && ($function_id = (int) $function_id)) {
            $obj->save(array('function_id'=>$function_id,'is_system'=>1));
            $obj->cleanCache();
            $this->baoSuccess('操作成功！', U('pcfunction/index'));
        } 
    }
    
    public function delete($function_id = 0) {
        $obj = D('Pcfunction');
        if (is_numeric($function_id) && ($function_id = (int) $function_id)) {
            $obj->delete($function_id);
            $obj->cleanCache();
            $this->baoSuccess('删除成功！', U('pcfunction/index'));
        } else {
            $function_id = $this->_post('func_id', false);
            if (is_array($function_id)) {
                foreach ($function_id as $id) {
                    $obj->delete($id);
                }
                $obj->cleanCache();
                $this->baoSuccess('删除成功！', U('pcfunction/index'));
            }
            $this->baoError('请选择要删除的功能');
        }
    }


}
