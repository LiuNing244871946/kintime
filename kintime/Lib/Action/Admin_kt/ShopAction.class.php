<?php

class ShopAction extends CommonAction {

    private $create_fields = array('mer_id', 'cate_id','rate','city_id', 'area_id', 'business_id', 'shop_name', 'lao_shop_name', 'eng_shop_name', 'logo', 'areacode','mobile', 'photo', 'addr','lao_addr','eng_addr', 'tel','contact','lao_contact', 'eng_contact', 'tags','lao_tags','eng_tags','business_time','is_ele','is_dianzicaidan','orderby', 'lng', 'lat', 'price','rate');
    private $edit_fields = array('mer_id', 'cate_id','rate','city_id', 'area_id', 'business_id', 'shop_name', 'lao_shop_name', 'eng_shop_name', 'logo', 'areacode','mobile', 'photo', 'addr','lao_addr','eng_addr', 'tel','contact','lao_contact', 'eng_contact', 'tags','lao_tags','eng_tags','business_time','is_ele','is_dianzicaidan','orderby', 'lng', 'lat', 'price','rate');

    public function index() {
        $Shop = D('Shop');
        import('ORG.Util.Page'); // 导入分页类
        $map = array( 'audit' => 1);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['shop_name|tel|lao_shop_name|eng_shop_name'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if ($city_id = (int) $this->_param('city_id')) {
            $map['city_id'] = $city_id;
            $this->assign('city_id', $city_id);
        }
        if ($area_id = (int) $this->_param('area_id')) {
            $map['area_id'] = $area_id;
            $this->assign('area_id', $area_id);
        }
        if ($cate_id = (int) $this->_param('cate_id')) {
            $map['cate_id'] = array('IN', D('Tuancate')->getChildren($cate_id));
            $this->assign('cate_id', $cate_id);
        }

        $count = $Shop->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 5); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $Shop->order(array('orderby' => 'asc'))->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $ids = array();
        foreach ($list as $k => $val) {
            if ($val['mer_id']) {
                $ids[$val['mer_id']] = $val['mer_id'];
            }
        }
        if ($ids) {
            $rebate = D('Rebate')->select();
            foreach ($rebate as $key => $value) {
                $rebate2[$value['id']] = $value;
            }
            $this->assign('rate',$rebate2);
        }
        $data= D('Merchants')->where(array('mer_id'=>array('IN',$ids)))->select();
        $merchants = array();
        foreach($data as $val){
            $merchants[$val['mer_id']] = $val;
        };
        $this->assign('merchants', $merchants);
        $this->assign('citys', D('City')->fetchAll());
        $this->assign('areas', D('Area')->fetchAll());
        $this->assign('cates', D('Tuancate')->fetchAll());
        $this->assign('business', D('Business')->fetchAll());
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function apply() {
        $Shop = D('Shop');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0, 'audit' => 0);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['shop_name|tel'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if ($city_id = (int) $this->_param('city_id')) {
            $map['city_id'] = $city_id;
            $this->assign('city_id', $city_id);
        }
        if ($area_id = (int) $this->_param('area_id')) {
            $map['area_id'] = $area_id;
            $this->assign('area_id', $area_id);
        }
        if ($cate_id = (int) $this->_param('cate_id')) {
            $map['cate_id'] = array('IN', D('Tuancate')->getChildren($cate_id));
            $this->assign('cate_id', $cate_id);
        }

        $count = $Shop->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 25); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $Shop->order(array('shop_id' => 'asc'))->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $ids = array();
        foreach ($list as $k => $val) {

            if ($val['mer_id']) {
                $ids[$val['mer_id']] = $val['mer_id'];
            }
        }
        if ($ids) {
            $rebate = D('Rebate')->select();
            foreach ($rebate as $key => $value) {
                $rebate2[$value['id']] = $value;
            }
            $this->assign('rate',$rebate2);
        }
        $data= D('Merchants')->where(array('mer_id'=>array('IN',$ids)))->select();
        $merchants = array();
        foreach($data as $val){
            $merchants[$val['mer_id']] = $val;
        };
        $this->assign('merchants', $merchants);
        $this->assign('citys', D('City')->fetchAll());
        $this->assign('areas', D('Area')->fetchAll());
        $this->assign('cates', D('Tuancate')->fetchAll());
        $this->assign('business', D('Business')->fetchAll());
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function create() {
        if ($this->isPost()) {
            $data2=$data = $this->createCheck();
            $obj = D('Shop');
            $details = $this->_post('details', 'SecurityEditorHtml');
            $lao_details = $this->_post('lao_details', 'SecurityEditorHtml');
            $eng_details = $this->_post('eng_details', 'SecurityEditorHtml');
            if ($words = D('Sensitive')->checkWords($details)) {
                $this->baoError('商家介绍含有敏感词：' . $words);
            }
            if ($lao_words = D('Sensitive')->checkWords($lao_details)) {
                $this->baoError('商家介绍含有敏感词：' . $lao_words);
            }
            if ($eng_words = D('Sensitive')->checkWords($eng_details)) {
                $this->baoError('商家介绍含有敏感词：' . $eng_words);
            }
            unset($data['price'], $data['business_time']);
            if ($shop_id = $obj->add($data)) {
                $wei_pic = D('Weixin')->getCode($shop_id, 1);
                $ex = array(
                    'wei_pic' => $wei_pic,
                    'details' => $details,
                    'lao_details' => $lao_details,
                    'eng_details' => $eng_details,
                    'price' => $data2['price'],
                    'business_time' => $data2['business_time']
                );
                D('Shopdetails')->upDetails($shop_id, $ex);
                $this->baoSuccess('添加成功', U('shop/apply'));
            }
            $this->baoError('操作失败！');
        } else {
            $this->assign('rebate',D('rebate')->select());
            $this->assign('cates', D('Tuancate')->fetchAll());
            $this->assign('business', D('Business')->fetchAll());
            $this->display();
        }
    }

    public function select() {
        $Shop = D('Shop');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0, 'audit' => 1);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['shop_name|tel'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if ($city_id = (int) $this->_param('city_id')) {
            $map['city_id'] = $city_id;
            $this->assign('city_id', $city_id);
        }
        if ($area_id = (int) $this->_param('area_id')) {
            $map['area_id'] = $area_id;
            $this->assign('area_id', $area_id);
        }

        if ($cate_id = (int) $this->_param('cate_id')) {
            $map['cate_id'] = array('IN', D('Tuancate')->getChildren($cate_id));
            $this->assign('cate_id', $cate_id);
        }
        $count = $Shop->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $Shop->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $ids = array();
        foreach ($list as $k => $val) {

            if ($val['mer_id']) {
                $ids[$val['mer_id']] = $val['mer_id'];
            }
        }
        $data= D('Merchants')->where(array('mer_id'=>array('IN',$ids)))->select();
        $merchants = array();
        foreach($data as $val){
            $merchants[$val['mer_id']] = $val;
        };
        $this->assign('merchants', $merchants);
        $this->assign('citys', D('City')->fetchAll());
        $this->assign('areas', D('Area')->fetchAll());
        $this->assign('cates', D('Tuancate')->fetchAll());
        $this->assign('business', D('Business')->fetchAll());
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['mer_id'] = (int) $data['mer_id'];
        if (empty($data['mer_id'])) {
            $this->baoError('管理者不能为空');
        }
        $shop = D('Shop')->find(array('where' => array('mer_id' => $data['mer_id'])));
        if (!empty($shop)) {
            $this->baoError('该管理者已经拥有商铺了');
        }

        $data['cate_id'] = (int) $data['cate_id'];
        if (empty($data['cate_id'])) {
            $this->baoError('分类不能为空');
        } 
        $data['rate'] = (int) $data['rate'];
        if (empty($data['rate'])) {
            $this->baoError('平台折扣不能为空!');
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
        $data['shop_name'] = htmlspecialchars($data['shop_name']);
        if (empty($data['shop_name'])) {
            $this->baoError('中文商铺名称不能为空');
        }
        $data['lao_shop_name'] = htmlspecialchars($data['lao_shop_name']);
        if (empty($data['lao_shop_name'])) {
            $this->baoError('老文商铺名称不能为空');
        }
        $data['eng_shop_name'] = htmlspecialchars($data['eng_shop_name']);
        if (empty($data['eng_shop_name'])) {
            $this->baoError('英文商铺名称不能为空');
        }
        $data['logo'] = htmlspecialchars($data['logo']);
        if (empty($data['logo'])) {
            $this->baoError('请上传商铺LOGO');
        }
        if (!isImage($data['logo'])) {
            $this->baoError('商铺LOGO格式不正确');
        } $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->baoError('请上传店铺缩略图');
        }
        if (!isImage($data['photo'])) {
            $this->baoError('店铺缩略图格式不正确');
        }
        $data['addr'] = htmlspecialchars($data['addr']);
        if (empty($data['addr'])) {
            $this->baoError('中文店铺地址不能为空');
        }
        $data['lao_addr'] = htmlspecialchars($data['lao_addr']);
        if (empty($data['lao_addr'])) {
            $this->baoError('老文店铺地址不能为空');
        }
        $data['eng_addr'] = htmlspecialchars($data['eng_addr']);
        if (empty($data['eng_addr'])) {
            $this->baoError('英文店铺地址不能为空');
        }
        $data['tel'] = htmlspecialchars($data['tel']);
        $data['areacode'] = (int) $data['areacode'];
        $data['mobile'] = htmlspecialchars($data['mobile']);
        if (empty($data['tel']) && empty($data['mobile'])) {
            $this->baoError('店铺电话不能为空');
        }
        $data['mobile'] = $data['areacode'].$data['mobile'];
        $data['contact'] = htmlspecialchars($data['contact']);
        $data['lao_contact'] = htmlspecialchars($data['lao_contact']);
        $data['eng_contact'] = htmlspecialchars($data['eng_contact']);
        $data['tags'] = str_replace(',', '，', htmlspecialchars($data['tags']));
        $data['lao_tags'] = str_replace(',', '，', htmlspecialchars($data['lao_tags']));
        $data['eng_tags'] = str_replace(',', '，', htmlspecialchars($data['eng_tags']));
        $data['business_time'] = htmlspecialchars($data['business_time']);
        $data['is_ele'] = (int) $data['is_ele'];
        $data['is_dianzicaidan'] = (int) $data['is_dianzicaidan'];
        $data['orderby'] = (int) $data['orderby'];
        $data['price'] = (int) $data['price'];
        $data['lng'] = htmlspecialchars($data['lng']);
        $data['lat'] = htmlspecialchars($data['lat']);
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        return $data;
    }

    public function edit($shop_id = 0) {

        if ($shop_id = (int) $shop_id) {
            $obj = D('Shop');
            if (!$detail = $obj->find($shop_id)) {
                $this->baoError('请选择要编辑的商家');
            }
            if ($this->isPost()) {
                $data = $this->editCheck($shop_id);
                $data['shop_id'] = $shop_id;
                $details = $this->_post('details', 'SecurityEditorHtml');
                $lao_details = $this->_post('lao_details', 'SecurityEditorHtml');
                $eng_details = $this->_post('eng_details', 'SecurityEditorHtml');
                if ($words = D('Sensitive')->checkWords($details)) {
                    $this->baoError('商家介绍含有敏感词：' . $words);
                }
                if ($lao_words = D('Sensitive')->checkWords($lao_details)) {
                    $this->baoError('商家介绍含有敏感词：' . $lao_words);
                }
                if ($eng_words = D('Sensitive')->checkWords($eng_details)) {
                    $this->baoError('商家介绍含有敏感词：' . $eng_words);
                }
                $shopdetails = D('Shopdetails')->find($shop_id);

                $ex = array(
                    'details' => $details,
                    'lao_details' => $lao_details,
                    'eng_details' => $eng_details,
                    'price' => $data['price'],
                    'business_time' => $data['business_time'],
                );
                if (!empty($shopdetails['wei_pic'])) {
                    if (true !== strpos($shopdetails['wei_pic'], "https://mp.weixin.qq.com/")) {
                        $wei_pic = D('Weixin')->getCode($shop_id, 1);
                        $ex['wei_pic'] = $wei_pic;
                    }
                } else {
                    $wei_pic = D('Weixin')->getCode($shop_id, 1);
                    $ex['wei_pic'] = $wei_pic;
                }
                unset($data['price'], $data['business_time']);
                if (false !== $obj->save($data)) {
                    D('Shopdetails')->upDetails($shop_id, $ex);
                    $this->baoSuccess('操作成功', U('shop/index'));
                }
                $this->baoError('操作失败');
            } else {
                if (strlen($detail['mobile'])===13) {
                    $detail['areacode']=substr($detail['mobile'],0,2);
                    $detail['mobile']=substr($detail['mobile'],2,11);
                }else if (strlen($detail['mobile'])===14) {
                    $detail['areacode']=substr($detail['mobile'],0,3);
                    $detail['mobile']=substr($detail['mobile'],3,11);
                }
                $this->assign('areas', D('Area')->fetchAll());
                $this->assign('cates', D('Tuancate')->fetchAll());
                $this->assign('business', D('Business')->fetchAll());
                $this->assign('rebate',D('rebate')->select());
                $this->assign('ex', D('Shopdetails')->find($shop_id));
                $this->assign('merchants', D('Merchants')->find($detail['mer_id']));
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->baoError('请选择要编辑的商家');
        }
    }

    private function editCheck($shop_id) {
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['mer_id'] = (int) $data['mer_id'];
        if (empty($data['mer_id'])) {
            $this->baoError('管理者不能为空');
        }
        $shop = D('Shop')->find(array('where' => array('mer_id' => $data['mer_id'])));
        if (!empty($shop) && $shop['shop_id'] != $shop_id) {
            $this->baoError('该管理者已经拥有商铺了');
        }

        $data['cate_id'] = (int) $data['cate_id'];
        if (empty($data['cate_id'])) {
            $this->baoError('分类不能为空');
        } 
        $data['rate'] = (int) $data['rate'];
        if (empty($data['rate'])) {
            $this->baoError('平台折扣不能为空!');
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
        $data['shop_name'] = htmlspecialchars($data['shop_name']);
        if (empty($data['shop_name'])) {
            $this->baoError('中文商铺名称不能为空');
        }
        $data['lao_shop_name'] = htmlspecialchars($data['lao_shop_name']);
        if (empty($data['lao_shop_name'])) {
            $this->baoError('老文商铺名称不能为空');
        }
        $data['eng_shop_name'] = htmlspecialchars($data['eng_shop_name']);
        if (empty($data['eng_shop_name'])) {
            $this->baoError('英文商铺名称不能为空');
        }
        $data['logo'] = htmlspecialchars($data['logo']);
        if (empty($data['logo'])) {
            $this->baoError('请上传商铺LOGO');
        }
        if (!isImage($data['logo'])) {
            $this->baoError('商铺LOGO格式不正确');
        } $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->baoError('请上传店铺缩略图');
        }
        if (!isImage($data['photo'])) {
            $this->baoError('店铺缩略图格式不正确');
        }
        $data['addr'] = htmlspecialchars($data['addr']);
        if (empty($data['addr'])) {
            $this->baoError('中文店铺地址不能为空');
        }
        $data['lao_addr'] = htmlspecialchars($data['lao_addr']);
        if (empty($data['lao_addr'])) {
            $this->baoError('老文店铺地址不能为空');
        }
        $data['eng_addr'] = htmlspecialchars($data['eng_addr']);
        if (empty($data['eng_addr'])) {
            $this->baoError('英文店铺地址不能为空');
        }
        $data['tel'] = htmlspecialchars($data['tel']);
        $data['areacode'] = (int) $data['areacode'];
        $data['mobile'] = htmlspecialchars($data['mobile']);
        if (empty($data['tel']) && empty($data['mobile'])) {
            $this->baoError('店铺电话不能为空');
        }
        $data['mobile'] = $data['areacode'].$data['mobile'];
        $data['contact'] = htmlspecialchars($data['contact']);
        $data['lao_contact'] = htmlspecialchars($data['lao_contact']);
        $data['eng_contact'] = htmlspecialchars($data['eng_contact']);
        $data['tags'] = str_replace(',', '，', htmlspecialchars($data['tags']));
        $data['lao_tags'] = str_replace(',', '，', htmlspecialchars($data['lao_tags']));
        $data['eng_tags'] = str_replace(',', '，', htmlspecialchars($data['eng_tags']));
        $data['business_time'] = htmlspecialchars($data['business_time']);
        $data['is_ele'] = (int) $data['is_ele'];
        $data['is_dianzicaidan'] = (int) $data['is_dianzicaidan'];
        $data['orderby'] = (int) $data['orderby'];
        $data['price'] = (int) $data['price'];
        $data['lng'] = htmlspecialchars($data['lng']);
        $data['lat'] = htmlspecialchars($data['lat']);
        return $data;
    }

    public function delete($shop_id = 0) {
        if (is_numeric($shop_id) && ($shop_id = (int) $shop_id)) {
            $obj = D('Shop');
            $obj->save(array('shop_id' => $shop_id, 'closed' => 1));
            $this->baoSuccess('关闭成功！', U('shop/index'));
        } else {
            $shop_id = $this->_post('shop_id', false);
            if (is_array($shop_id)) {
                $obj = D('Shop');
                foreach ($shop_id as $id) {
                    $obj->save(array('shop_id' => $id, 'closed' => 1));
                }
                $this->baoSuccess('关闭成功！', U('shop/index'));
            }
            $this->baoError('请选择要关闭的商家');
        }
    }
    
    public function open($shop_id = 0) {
        if (is_numeric($shop_id) && ($shop_id = (int) $shop_id)) {
            $obj = D('Shop');
            $obj->save(array('shop_id' => $shop_id, 'closed' => 0));
            $this->baoSuccess('开启成功！', U('shop/index'));
        } else {
            $shop_id = $this->_post('shop_id', false);
            if (is_array($shop_id)) {
                $obj = D('Shop');
                foreach ($shop_id as $id) {
                    $obj->save(array('shop_id' => $id, 'closed' => 0));
                }
                $this->baoSuccess('开启成功！', U('shop/index'));
            }
            $this->baoError('请选择要开启的商家');
        }
    }

    public function audit($shop_id = 0) {
        if (is_numeric($shop_id) && ($shop_id = (int) $shop_id)) {
            $obj = D('Shop');
            $obj->save(array('shop_id' => $shop_id, 'audit' => 1));
            $this->baoSuccess('审核成功！', U('shop/apply'));
        } else {
            $shop_id = $this->_post('shop_id', false);
            if (is_array($shop_id)) {
                $obj = D('Shop');
                foreach ($shop_id as $id) {
                    $obj->save(array('shop_id' => $id, 'audit' => 1));
                }
                $this->baoSuccess('审核成功！', U('shop/apply'));
            }
            $this->baoError('请选择要审核的商家');
        }
    }

    // public function login($shop_id) {
    //     $obj = D('Shop');
    //     if (!$detail = $obj->find($shop_id)) {
    //         $this->error('请选择要编辑的商家');
    //     }
    //     if (empty($detail['mer_id'])) {
    //         $this->error('该用户没有绑定管理者');
    //     }
    //     setUid($detail['mer_id']);
    //     header("Location:" . U('shangjia/index/index'));
    //     die;
    // }
    
    
    public function ele($shop_id){
        $obj = D('Shop');
        if (!$detail = $obj->find($shop_id)) {
            $this->error('请选择要编辑的商家');
        }
        $data = array('is_ele'=>0,'shop_id'=>$shop_id);
        if($detail['is_ele'] == 0){
            $data['is_ele'] = 1;
        }
        $obj->save($data);
        D('Ele')->save(array('shop_id'=>$shop_id,'is_open'=>$data['is_ele']));

        $this->baoSuccess('操作成功',U('shop/index'));
    }


    public function dianzicaidan($shop_id){
        $obj = D('Shop');
        if (!$detail = $obj->find($shop_id)) {
            $this->error('请选择要编辑的商家');
        }
        $data = array('is_dianzicaidan'=>0,'shop_id'=>$shop_id);
        if($detail['is_dianzicaidan'] == 0){
            $data['is_dianzicaidan'] = 1;
        }
        $obj->save($data);

        D('Shopding')->save(array('shop_id'=>$shop_id,'closed'=>$detail['is_dianzicaidan']));
        $this->baoSuccess('操作成功',U('shop/index'));
    }
  

}
