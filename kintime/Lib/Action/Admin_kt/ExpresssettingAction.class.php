<?php

class ExpresssettingAction extends CommonAction {

    private $create_fields = array('name','lao_name','eng_name','city_id','area_id','business_id','fee','lng','lat');
    private $edit_fields = array('name','lao_name','eng_name','city_id','area_id','business_id','fee','lng','lat');

    public function index() {
        $express = D('express');
        import('ORG.Util.Page'); // 导入分页类

        $count = $express->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 5); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $express->order(array('id' => 'asc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('citys', D('City')->fetchAll());
        $this->assign('areas', D('Area')->fetchAll());
        $this->assign('business', D('Business')->fetchAll());
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function create() {
        if ($this->isPost()) {
            $data2=$data = $this->createCheck();
            $obj = D('express');
            if ($id = $obj->add($data)) {
                $this->baoSuccess('添加成功', U('expresssetting/index'));
            }
            $this->baoError('操作失败！');
        } else {
            $this->display();
        }
    }

    private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['name'] = htmlspecialchars($data['name']);
        if (empty($data['name'])) {
            $this->baoError('快递商中文名称不能为空');
        }
        $data['lao_name'] = htmlspecialchars($data['lao_name']);
        if (empty($data['lao_name'])) {
            $this->baoError('快递商老文名称不能为空!');
        }
        $data['eng_name'] = htmlspecialchars($data['eng_name']);
        if (empty($data['eng_name'])) {
            $this->baoError('快递商英文名称不能为空!');
        }
        $data['city_id'] = (int) $data['city_id'];
        $data['area_id'] = (int) $data['area_id'];
        if (empty($data['area_id'])) {
            $this->baoError('所在区域不能为空');
        } 
        $data['business_id'] = (int) $data['business_id'];
        if (empty($data['business_id'])) {
            $this->baoError('所在商圈不能为空');
        }
        $data['fee'] = (int) $data['fee'];
        if (empty($data['fee'])) {
            $this->baoError('单价不能为空');
        }
        $data['lng'] = htmlspecialchars($data['lng']);
        $data['lat'] = htmlspecialchars($data['lat']);
        if (empty($data['lng'])||empty($data['lat'])) {
            $this->baoError('快递商坐标不能为空');
        }
        return $data;
    }

    public function edit($id = 0) {

        if ($id = (int) $id) {
            $obj = D('express');
            if (!$detail = $obj->find($id)) {
                $this->baoError('请选择要编辑的快递商');
            }
            if ($this->isPost()) {
                $data = $this->editCheck($id);
                $data['id'] = $id;
                if (false !== $obj->save($data)) {
                    $this->baoSuccess('操作成功', U('expresssetting/index'));
                }
                $this->baoError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->assign('business', D('Business')->fetchAll());
                $this->display();
            }
            
        } else {
            $this->baoError('请选择要编辑的快递商');
        }
    }

    private function editCheck($id) {
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
       $data['name'] = htmlspecialchars($data['name']);
        if (empty($data['name'])) {
            $this->baoError('快递商中文名称不能为空');
        }
        $data['lao_name'] = htmlspecialchars($data['lao_name']);
        if (empty($data['lao_name'])) {
            $this->baoError('快递商老文名称不能为空!');
        }
        $data['eng_name'] = htmlspecialchars($data['eng_name']);
        if (empty($data['eng_name'])) {
            $this->baoError('快递商英文名称不能为空!');
        }
        $data['city_id'] = (int) $data['city_id'];
        $data['area_id'] = (int) $data['area_id'];
        if (empty($data['area_id'])) {
            $this->baoError('所在区域不能为空');
        } 
        $data['business_id'] = (int) $data['business_id'];
        if (empty($data['business_id'])) {
            $this->baoError('所在商圈不能为空');
        }
        $data['fee'] = (int) $data['fee'];
        if (empty($data['fee'])) {
            $this->baoError('单价不能为空');
        }
        $data['lng'] = htmlspecialchars($data['lng']);
        $data['lat'] = htmlspecialchars($data['lat']);
        if (empty($data['lng'])||empty($data['lat'])) {
            $this->baoError('快递商坐标不能为空');
        }
        return $data;
    }

    public function delete($id = 0) {
        if (is_numeric($id) && ($id = (int) $id)) {
            $obj = D('express');
            $obj->delete($id);
            $this->baoSuccess('删除成功！', U('expresssetting/index'));
        } else {
            $id = $this->_post('id', false);
            if (is_array($id)) {
                $obj = D('express');
                foreach ($id as $id) {
                    $obj->delete($id);
                }
                $this->baoSuccess('删除成功！', U('expresssetting/index'));
            }
            $this->baoError('请选择要删除的快递商');
        }
    }

    public function child($city_id = 0){
        $datas = D('Express')->fetchAll();
        $str ='<option value="0">请选择</option>';
        foreach($datas as $val){
            if($val['city_id'] == $city_id){
                $str.='<option value="'.$val['id'].'">'.$val['name'].'--'.$val['fee'].'₭/Kg</option>';                
            }            
        }
        echo $str;die;
    }
  

}
