<?php

class ShopcateAction extends CommonAction {

    private $create_fields = array('cate_name','lao_cate_name','eng_cate_name','d1','lao_d1','eng_d1','d2','lao_d2','eng_d2','d3','lao_d3','eng_d3','title','lao_title','eng_title', 'orderby');
    private $edit_fields = array('cate_name','lao_cate_name','eng_cate_name','d1','lao_d1','eng_d1','d2','lao_d2','eng_d2','d3','lao_d3','eng_d3','title','lao_title','eng_title', 'orderby');

    public function index() {
        $Shopcate = D('Shopcate');
        $list = $Shopcate->fetchAll();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function create($parent_id=0) {
        if ($this->isPost()) {
            $data = $this->createCheck();
            $obj = D('Shopcate');
            $data['parent_id'] = $parent_id;
            if ($obj->add($data)) {
                $obj->cleanCache();
                $this->baoSuccess('添加成功', U('shopcate/index'));
            }
            $this->baoError('操作失败！');
        } else {
            $this->assign('parent_id',$parent_id);
            $this->display();
        }
    }
    
    public function hots($cate_id){
        if ($cate_id = (int) $cate_id) {
            $obj = D('Shopcate');
            if (!$detail = $obj->find($cate_id)) {
                $this->baoError('请选择要编辑的商家分类');
            }
            $detail['is_hot'] = $detail['is_hot']==0 ? 1 : 0;
            $obj->save(array('cate_id'=>$cate_id,'is_hot'=>$detail['is_hot'])); 
            $obj->cleanCache();
            $this->baoSuccess('操作成功', U('shopcate/index'));
        } else {
            $this->baoError('请选择要编辑的商家分类');
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
        $data['d1'] = htmlspecialchars($data['d1']);
        $data['lao_d1'] = htmlspecialchars($data['lao_d1']);
        $data['eng_d1'] = htmlspecialchars($data['eng_d1']);
        $data['d2'] = htmlspecialchars($data['d2']);
        $data['lao_d2'] = htmlspecialchars($data['lao_d2']);
        $data['eng_d2'] = htmlspecialchars($data['eng_d2']);
        $data['d3'] = htmlspecialchars($data['d3']);
        $data['lao_d3'] = htmlspecialchars($data['lao_d3']);
        $data['eng_d3'] = htmlspecialchars($data['eng_d3']);
        $data['title'] = htmlspecialchars($data['title']);
        $data['lao_title'] = htmlspecialchars($data['lao_title']);
        $data['eng_title'] = htmlspecialchars($data['eng_title']);
        $data['orderby'] = (int) $data['orderby'];
        return $data;
    }

    public function edit($cate_id = 0) {
        if ($cate_id = (int) $cate_id) {
            $obj = D('Shopcate');
            if (!$detail = $obj->find($cate_id)) {
                $this->baoError('请选择要编辑的商家分类');
            }
            if ($this->isPost()) {
                $data = $this->editCheck();
                $data['cate_id'] = $cate_id;
                if (false !== $obj->save($data)) {
                    $obj->cleanCache();
                    $this->baoSuccess('操作成功', U('shopcate/index'));
                }
                $this->baoError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->baoError('请选择要编辑的商家分类');
        }
    }

    private function editCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['eng_cate_name'] = htmlspecialchars($data['eng_cate_name']);
        if (empty($data['eng_cate_name'])) {
            $this->baoError('英文分类不能为空');
        }
        $data['d1'] = htmlspecialchars($data['d1']);
        $data['lao_d1'] = htmlspecialchars($data['lao_d1']);
        $data['eng_d1'] = htmlspecialchars($data['eng_d1']);
        $data['d2'] = htmlspecialchars($data['d2']);
        $data['lao_d2'] = htmlspecialchars($data['lao_d2']);
        $data['eng_d2'] = htmlspecialchars($data['eng_d2']);
        $data['d3'] = htmlspecialchars($data['d3']);
        $data['lao_d3'] = htmlspecialchars($data['lao_d3']);
        $data['eng_d3'] = htmlspecialchars($data['eng_d3']);
        $data['title'] = htmlspecialchars($data['title']);
        $data['lao_title'] = htmlspecialchars($data['lao_title']);
        $data['eng_title'] = htmlspecialchars($data['eng_title']);
        $data['orderby'] = (int) $data['orderby'];
        return $data;
    }

    public function delete($cate_id = 0) {
        if (is_numeric($cate_id) && ($cate_id = (int) $cate_id)) {
            $obj = D('Shopcate');
			$result=$obj->where("parent_id=".$cate_id)->find();
            if($result){
            $this->baoError('存在子分类，不能删除');
           }else{
          
            $obj->delete($cate_id);
            $obj->cleanCache();
            $this->baoSuccess('删除成功！', U('shopcate/index'));
           }
        } else {
            $cate_id = $this->_post('cate_id', false);
            if (is_array($cate_id)) {
                $obj = D('Shopcate');
                foreach ($cate_id as $id) {
                     $result=$obj->where("parent_id=".$id)->find();
                   if($result){
                      $this->baoError('存在子分类，不能删除');
                   }else{
                  
                    $obj->delete($id);
                  
                   }
                }
				$obj->cleanCache();
				$this->baoSuccess('删除成功！', U('shopcate/index'));
            }
            $this->baoError('请选择要删除的商家分类');
        }
    }
    
    public function update() {
        $orderby = $this->_post('orderby', false);
        $obj = D('Shopcate');
        foreach ($orderby as $key => $val) {
            $data = array(
                'cate_id' => (int) $key,
                'orderby' => (int) $val
            );
            $obj->save($data);
        }
        $obj->cleanCache();
        $this->baoSuccess('更新成功', U('shopcate/index'));
    }

}
