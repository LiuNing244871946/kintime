<?php

class GoodscateAction extends CommonAction {

    private $create_fields = array('cate_name','lao_cate_name','eng_cate_name', 'orderby');
    private $edit_fields = array('cate_name','lao_cate_name','eng_cate_name', 'orderby');

    public function index() {
        $Goodscate = D('Goodscate');
        $list = $Goodscate->fetchAll();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function create($parent_id=0) {
        if ($this->isPost()) {
            $data = $this->createCheck();
            $obj = D('Goodscate');
            $data['parent_id'] = $parent_id;
            if ($obj->add($data)) {
                $obj->cleanCache();
                $this->baoSuccess('添加成功', U('goodscate/index'));
            }
            $this->baoError('操作失败！');
        } else {
            $this->assign('parent_id',$parent_id);
            $this->display();
        }
    }
    
    
 
    
    private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['cate_name'] = htmlspecialchars($data['cate_name']);
        if (empty($data['cate_name'])) {
            $this->baoError('中文分类不能为空');
        }
        $data['lao_cate_name'] = htmlspecialchars($data['lao_cate_name']);
        if (empty($data['lao_cate_name'])) {
            $this->baoError('老文分类不能为空');
        }
        $data['eng_cate_name'] = htmlspecialchars($data['eng_cate_name']);
        if (empty($data['eng_cate_name'])) {
            $this->baoError('英文分类不能为空');
        }
        $data['orderby'] = (int) $data['orderby'];
        if (empty($data['orderby'])) {
            $this->baoError('排序不能为空');
        }
        return $data;
    }

    public function edit($cate_id = 0) {
        if ($cate_id = (int) $cate_id) {
            $obj = D('Goodscate');
            if (!$detail = $obj->find($cate_id)) {
                $this->baoError('请选择要编辑的商品分类');
            }
            if ($this->isPost()) {
                $data = $this->editCheck();
                $data['cate_id'] = $cate_id;
                if (false !== $obj->save($data)) {
                    $obj->cleanCache();
                    $this->baoSuccess('操作成功', U('goodscate/index'));
                }
                $this->baoError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->baoError('请选择要编辑的商品分类');
        }
    }

    private function editCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['cate_name'] = htmlspecialchars($data['cate_name']);
        if (empty($data['cate_name'])) {
            $this->baoError('中文分类不能为空');
        }
        $data['lao_cate_name'] = htmlspecialchars($data['lao_cate_name']);
        if (empty($data['lao_cate_name'])) {
            $this->baoError('老文分类不能为空');
        }
        $data['eng_cate_name'] = htmlspecialchars($data['eng_cate_name']);
        if (empty($data['eng_cate_name'])) {
            $this->baoError('英文分类不能为空');
        }
        $data['orderby'] = (int) $data['orderby'];
        if (empty($data['orderby'])) {
            $this->baoError('排序不能为空');
        }
        return $data;
    }

    public function delete($cate_id = 0) {
        if (is_numeric($cate_id) && ($cate_id = (int) $cate_id)) {
           $obj = D('Goodscate');
           $result=$obj->where("parent_id=".$cate_id)->find();
           if($result){
            $this->baoError('存在子分类，不能删除');
           }else{
          
            $obj->delete($cate_id);
            $obj->cleanCache();
            $this->baoSuccess('删除成功！', U('goodscate/index'));
           }
        } else {
            $cate_id = $this->_post('cate_id', false);
            if (is_array($cate_id)) {
                $obj = D('Goodscate');
                foreach ($cate_id as $id) {
                    $result=$obj->where("parent_id=".$id)->find();
                   if($result){
                      $this->baoError('存在子分类，不能删除');
                   }else{
                  
                    $obj->delete($id);
                   
                   }
                }
			    $obj->cleanCache();
				$this->baoSuccess('删除成功！', U('goodscate/index'));
            }
            $this->baoError('请选择要删除的商家分类');
        }
    }
    

}