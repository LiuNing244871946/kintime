<?php

class DingrebateAction extends CommonAction {

    private $create_fields = array('menu_name' ,'lao_menu_name','eng_menu_name','shop_id', 'cate_id', 'photo','ding_price', 'is_new', 'is_sale', 'is_tuijian', 'sold_num','create_time', 'create_ip');
    private $edit_fields = array('menu_name' ,'lao_menu_name','eng_menu_name','shop_id', 'cate_id', 'photo','ding_price', 'is_new', 'is_sale', 'is_tuijian', 'sold_num');

    public function index() {
        $Dingrebate = D('ShopDingMenu');
        import('ORG.Util.Page'); // 导入分页类
        $map = array();
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $mmp['shop_name|lao_shop_name|eng_shop_name'] = array('LIKE', '%' . $keyword . '%');
            $mmp['is_dianzicaidan'] = 1;
            $mm_row = D('shop')->where($mmp)->select();
            $mpp = '';
            foreach ($mm_row as $key => $value) {
                $mpp .= $value['shop_id'].',';
            }
            $mpp = trim($mpp,",");
            $map['shop_id'] = array('exp',' IN ('.$mpp.') ');
            $this->assign('keyword', $keyword);
        }
        if ($shop_id = (int) $this->_param('shop_id')) {
            $map['shop_id'] = $shop_id;
            $shop = D('Shop')->find($shop_id);
            $this->assign('shop_name', $shop['shop_name']);
            $this->assign('shop_id', $shop_id);
        }
        $count = $Dingrebate->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 25); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $Dingrebate->where($map)->order(array('menu_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $shop_ids = $cate_ids= array();
        foreach ($list as $k => $val) {
            if ($val['shop_id']) {
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            if($val['cate_id']){
                $cate_ids[$val['cate_id']] = $val['cate_id'];
            }
        }
        if ($shop_ids) {
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
            $rebate = D('Rebate')->select();
            foreach ($rebate as $key => $value) {
                $rebate2[$value['id']] = $value;
            }
            $this->assign('rate',$rebate2);
        }

        if($cate_ids){
            $this->assign('cates',D('Shopdingcate')->itemsByIds($cate_ids));
        }

        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模
        // var_dump($list);
    }

    public function create() {
        if ($this->isPost()) {
            $data = $this->createCheck();
            $obj = D('ShopDingMenu');
            if ($obj->add($data)) {
                $this->baoSuccess('添加成功', U('dingrebate/index'));
            }
            $this->baoError('操作失败！');
        } else {
            $this->display();
        }
    }

    private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['menu_name'] = htmlspecialchars($data['menu_name']);
        if (empty($data['menu_name'])) {
            $this->baoError('中文菜名不能为空');
        } 
        $data['lao_menu_name'] = htmlspecialchars($data['lao_menu_name']);
        if (empty($data['lao_menu_name'])) {
            $this->baoError('老文菜名不能为空');
        } 
        $data['eng_menu_name'] = htmlspecialchars($data['eng_menu_name']);
        if (empty($data['eng_menu_name'])) {
            $this->baoError('英文菜名不能为空');
        } 
        $data['shop_id'] = (int) $data['shop_id'];
        if (empty($data['shop_id'])) {
            $this->baoError('商家不能为空');
        }
        $data['cate_id'] = (int) $data['cate_id'];
        if (empty($data['cate_id'])) {
            $this->baoError('分类不能为空');
        }
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->baoError('请上传缩略图');
        }
        if (!isImage($data['photo'])) {
            $this->baoError('缩略图格式不正确');
        }
        $data['ding_price'] = (int) ($data['ding_price']);
        if (empty($data['ding_price'])) {
            $this->baoError('价格不能为空');
        }
     
        $data['is_new'] = (int) $data['is_new'];
        $data['is_sale'] = (int) $data['is_sale'];
        $data['is_tuijian'] = (int) $data['is_tuijian'];
        $data['sold_num'] = (int) $data['sold_num'];
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();

        return $data;
    }

    public function edit($menu_id = 0) {
        if ($menu_id = (int) $menu_id) {
            $obj = D('ShopDingMenu');
            if (!$detail = $obj->find($menu_id)) {
                $this->baoError('请选择要编辑的菜单管理');
            }
            if ($this->isPost()) {
                $data = $this->editCheck();
                $data['menu_id'] = $menu_id;
                if (false !== $obj->save($data)) {
                    $this->baoSuccess('操作成功', U('dingrebate/index'));
                }
                $this->baoError('操作失败');
            } else {
                $this->assign('shops', D('Shop')->find($detail['shop_id']));
                $this->assign('cates', D('Shopdingcate')->where('shop_id='.$detail['shop_id'])->select());
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {

            var_dump($menu_id);die;
            $this->baoError('请选择要编辑的菜单管理');
        }
    }

    private function editCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['menu_name'] = htmlspecialchars($data['menu_name']);
        if (empty($data['menu_name'])) {
            $this->baoError('中文菜名不能为空');
        } 
        $data['lao_menu_name'] = htmlspecialchars($data['lao_menu_name']);
        if (empty($data['lao_menu_name'])) {
            $this->baoError('老文菜名不能为空');
        } 
        $data['eng_menu_name'] = htmlspecialchars($data['eng_menu_name']);
        if (empty($data['eng_menu_name'])) {
            $this->baoError('英文菜名不能为空');
        }  
        $data['shop_id'] = (int) $data['shop_id'];
        if (empty($data['shop_id'])) {
            $this->baoError('商家不能为空');
        } 
        $data['cate_id'] = (int) $data['cate_id'];
        if (empty($data['cate_id'])) {
            $this->baoError('分类不能为空');
        } 
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->baoError('请上传缩略图');
        }
        if (!isImage($data['photo'])) {
            $this->baoError('缩略图格式不正确');
        }
        $data['ding_price'] = (int) ($data['ding_price']);
        if (empty($data['ding_price'])) {
            $this->baoError('价格不能为空');
        }
        

        $data['is_new'] = (int) $data['is_new'];
        $data['is_sale'] = (int) $data['is_sale'];
        $data['is_tuijian'] = (int) $data['is_tuijian'];
        $data['sold_num'] = (int) $data['sold_num'];
        return $data;
    }

    public function delete($menu_id = 0) {
        if (is_numeric($menu_id) && ($menu_id = (int) $menu_id)) {
            $obj = D('ShopDingMenu');
            $obj->delete($menu_id);
            $this->baoSuccess('删除成功！', U('dingrebate/index'));
        } else {
            $menu_id = $this->_post('menu_id', false);
            if (is_array($menu_id)) {
                $obj = D('ShopDingMenu');
                foreach ($menu_id as $id) {
                    $obj->delete($id);
                }
                $this->baoSuccess('删除成功！', U('dingrebate/index'));
            }
            $this->baoError('请选择要删除的菜单管理');
        }
    }
    
    
    
    public function audit($menu_id = 0) {
        if (is_numeric($menu_id) && ($menu_id = (int) $menu_id)) {
            $obj = D('ShopDingMenu');
            $r = $obj -> where('menu_id ='.$menu_id) -> find();

            $obj->save(array('menu_id' => $menu_id, 'audit' => 1));
            $this->baoSuccess('审核成功！', U('dingrebate/index'));
        } else {
            $menu_id = $this->_post('menu_id', false);
            if (is_array($menu_id)) {
                $obj = D('ShopDingMenu');
                foreach ($menu_id as $id) {
                    $r = $obj -> where('menu_id ='.$id) -> find();
        
                    $obj->save(array('menu_id' => $id, 'audit' => 1));
                }
                $this->baoSuccess('审核成功！', U('dingrebate/index'));
            }
            $this->baoError('请选择要审核的商品');
        }
    }
    
    public function cate(){
        $shop_id = $_POST['shop_id'];
        $shop_id = (int) $shop_id;
        $cates = D('ShopDingCate')->where('shop_id='.$shop_id)->select();
        foreach($cates as $k=>$val){
            $dingcates[$val['cate_id']] = $val;
        }
        echo json_encode($dingcates);
    }
}
