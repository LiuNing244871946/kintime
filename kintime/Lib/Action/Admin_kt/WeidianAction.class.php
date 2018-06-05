<?php

class WeidianAction extends CommonAction {

    private $edit_fields = array('mer_id', 'cate_id',  'weidian_name', 'lao_weidian_name','eng_weidian_name','logo', 'pic','tel','areacode','mobile','contact','lao_contact','eng_contact','addr', 'lao_addr','eng_addr','lng','lat','orderby', 'details','lao_details','eng_details', 'audit','rate','update_time','update_ip','city_id','area_id','business_id','express_id');

    private $create_fields = array('mer_id', 'cate_id',  'weidian_name', 'lao_weidian_name','eng_weidian_name','logo', 'pic','tel','areacode','mobile','contact','lao_contact','eng_contact','addr', 'lao_addr','eng_addr','lng','lat','orderby', 'details','lao_details','eng_details', 'audit','rate','create_time','create_ip','city_id','area_id','business_id','express_id');

    public function _initialize() {
        parent::_initialize();
        $cates = D('Weidiancate')->fetchAll();
        $this->assign('cates', $cates);
    }

    public function index() {
        $wd = D('WeidianDetails');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('close' => 0);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['weidian_name|lao_weidian_name|eng_weidian_name'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if ($cate_id = (int) $this->_param('cate_id')) {
            $map['cate_id'] = $cate_id;
            $this->assign('cate_id', $cate_id);
        }
        $count = $wd->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 25); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $wd->order(array('id' => 'desc'))->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();

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
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function audit($wd_id = 0) {
        if (is_numeric($wd_id) && ($wd_id = (int) $wd_id)) {
            $obj = D('WeidianDetails');

            $obj->save(array('id' => $wd_id, 'audit' => 1,'update_time' => time()));

            $this->baoSuccess('审核成功！', U('weidian/index'));
        } else {
            $error = 0;
            $wd_id = $this->_post('id', false);

            if (is_array($wd_id)) {
                $obj = D('WeidianDetails');
                foreach ($wd_id as $id) {
                    $r = $obj->save(array('id' => $id,'audit' => 1, 'update_time' => time()));

                    if (!$r) {
                        $error = $error + 1;
                    }
                }
                if ($error > 0) {
                    $this->baoSuccess($error . '条审核失败！', U('weidian/index'));
                } else {
                    $this->baoSuccess('审核成功！', U('weidian/index'));
                }
            }
            $this->baoError('请选择要审核的商店');
        }
    }

        public function create() {
            if ($this->isPost()) {
                $obj = D('WeidianDetails');
                $data = $this->createCheck();
                $details = $this->_post('details', 'SecurityEditorHtml');
                $lao_details = $this->_post('lao_details', 'SecurityEditorHtml');
                $eng_details = $this->_post('eng_details', 'SecurityEditorHtml');
                if ($words = D('Sensitive')->checkWords($details)) {
                    $this->baoError('商家中文介绍含有敏感词：' . $words);
                }
                if ($lao_words = D('Sensitive')->checkWords($lao_details)) {
                    $this->baoError('商家老文介绍含有敏感词：' . $lao_words);
                }
                if ($eng_words = D('Sensitive')->checkWords($eng_details)) {
                    $this->baoError('商家英文介绍含有敏感词：' . $eng_words);
                }
                $robj = $obj->add($data);
                if ($robj) {
                    $this->baoSuccess('操作成功', U('weidian/index'));
                } else {
                    $this->baoError('操作失败');
                }
            } else {
                $this->assign('rebate',D('rebate')->select());
                $this->display();
            }
        
    }

    private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);

        $data['mer_id'] = (int) $data['mer_id'];
        if (empty($data['mer_id'])) {
            $this->baoError('管理者不能为空');
        }
        $weidian = D('WeidianDetails')->find(array('where' => array('mer_id' => $data['mer_id'])));
        if (!empty($weidian)) {
            $this->baoError('该管理者已经拥有商铺了');
        }
        $data['cate_id'] = (int) $data['cate_id'];
        if (empty($data['cate_id'])) {
            $this->baoError('分类不能为空');
        }

        $data['weidian_name'] = htmlspecialchars($data['weidian_name']);
        if (empty($data['weidian_name'])) {
            $this->baoError('商店中文名称不能为空');
        }
        $data['lao_weidian_name'] = htmlspecialchars($data['lao_weidian_name']);
        if (empty($data['lao_weidian_name'])) {
            $this->baoError('商店老文名称不能为空');
        }
        $data['eng_weidian_name'] = htmlspecialchars($data['eng_weidian_name']);
        if (empty($data['eng_weidian_name'])) {
            $this->baoError('商店英文名称不能为空');
        }
        $data['city_id'] = (int) $data['city_id'];
        if (empty($data['city_id'])) {
            $this->baoError('所在城市不能为空');
        }
        $data['area_id'] = (int) $data['area_id'];
        if (empty($data['area_id'])) {
            $this->baoError('所在区域不能为空');
        }
        $data['business_id'] = (int) $data['business_id'];
        if (empty($data['business_id'])) {
            $this->baoError('所在商圈不能为空');
        }
        $data['express_id'] = (int) $data['express_id'];
        if (empty($data['express_id'])) {
            $this->baoError('所选快递商不能为空');
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
        if (empty($data['contact'])) {
            $this->baoError('中文联系人不能为空');
        }
        $data['lao_contact'] = htmlspecialchars($data['lao_contact']);
        if (empty($data['lao_contact'])) {
            $this->baoError('老文联系人不能为空');
        }
        $data['eng_contact'] = htmlspecialchars($data['eng_contact']);
        if (empty($data['eng_contact'])) {
            $this->baoError('英文联系人不能为空');
        }
        $data['orderby'] = (int) $data['orderby'];
        if (empty($data['orderby'])) {
            $this->baoError('固定排名不能为空');
        }
        $data['logo'] = htmlspecialchars($data['logo']);
        if (empty($data['logo'])) {
            $this->baoError('请上传商铺LOGO');
        }
        if (!isImage($data['logo'])) {
            $this->baoError('商铺LOGO格式不正确');
        }
        $data['pic'] = htmlspecialchars($data['pic']);
        if (empty($data['pic'])) {
            $this->baoError('请上传店铺缩略图');
        }
        if (!isImage($data['pic'])) {
            $this->baoError('店铺缩略图格式不正确');
        }
        $data['rate'] = (int) $data['rate'];
        if (empty($data['rate'])) {
            $this->baoError('平台折扣不能为空!');
        } 
        $data['lng'] = htmlspecialchars($data['lng']);
        $data['lat'] = htmlspecialchars($data['lat']);
        if (empty($data['lng'])||empty($data['lat'])) {
            $this->baoError('商家坐标不能为空!');
        } 
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        return $data;
    }


    public function edit($wd_id = 0) {
        if ($id = (int) $wd_id) {
            $obj = D('WeidianDetails');
            if (!$detail = $obj->where(array('id' => $id))->find()) {
                $this->baoError('请选择要编辑的商店');
            }

            if ($this->isPost()) {
                $data = $this->editCheck($id);
                $data['id'] = $id;
                $details = $this->_post('details', 'SecurityEditorHtml');
                $lao_details = $this->_post('lao_details', 'SecurityEditorHtml');
                $eng_details = $this->_post('eng_details', 'SecurityEditorHtml');
                if (empty($data['details']) || $data['details'] == null) {
                    $this->baoError('中文详情不能为空');
                }
                if (empty($data['lao_details']) || $data['lao_details'] == null) {
                    $this->baoError('老文详情不能为空');
                }
                if (empty($data['eng_details']) || $data['eng_details'] == null) {
                    $this->baoError('英文详情不能为空');
                }
                if ($words = D('Sensitive')->checkWords($details)) {
                    $this->baoError('商家中文介绍含有敏感词：' . $words);
                }
                if ($lao_words = D('Sensitive')->checkWords($lao_details)) {
                    $this->baoError('商家老文介绍含有敏感词：' . $lao_words);
                }
                if ($eng_words = D('Sensitive')->checkWords($eng_details)) {
                    $this->baoError('商家英文介绍含有敏感词：' . $eng_words);
                }
                $robj = $obj->save($data);
                if ($robj) {
                    $this->baoSuccess('操作成功', U('weidian/index'));
                } else {
                    $this->baoError('操作失败');
                }
            } else {
                if (strlen($detail['mobile'])===13) {
                    $detail['areacode']=substr($detail['mobile'],0,2);
                    $detail['mobile']=substr($detail['mobile'],2,11);
                }else if (strlen($detail['mobile'])===14) {
                    $detail['areacode']=substr($detail['mobile'],0,3);
                    $detail['mobile']=substr($detail['mobile'],3,11);
                }
                $this->assign('express', D('Express')->fetchAll());
                $this->assign('business', D('Business')->fetchAll());
                $this->assign('rebate',D('rebate')->select());
                $this->assign('merchants',D('Merchants')->find($detail['mer_id']));
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->baoError('请选择要编辑的商家');
        }
    }

    private function editCheck($id) {
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);

        $data['mer_id'] = (int) $data['mer_id'];
        if (empty($data['mer_id'])) {
            $this->baoError('管理者不能为空');
        }
        $shop = D('WeidianDetails')->find(array('where' => array('mer_id' => $data['mer_id'])));
        if (!empty($shop) && $shop['id'] != $id) {
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
        $data['weidian_name'] = htmlspecialchars($data['weidian_name']);
        if (empty($data['weidian_name'])) {
            $this->baoError('商店中文名称不能为空');
        }
        $data['lao_weidian_name'] = htmlspecialchars($data['lao_weidian_name']);
        if (empty($data['lao_weidian_name'])) {
            $this->baoError('商店老文名称不能为空');
        }
        $data['eng_weidian_name'] = htmlspecialchars($data['eng_weidian_name']);
        if (empty($data['eng_weidian_name'])) {
            $this->baoError('商店英文名称不能为空');
        }
        $data['logo'] = htmlspecialchars($data['logo']);
        if (empty($data['logo'])) {
            $this->baoError('请上传商店LOGO');
        }
        if (!isImage($data['logo'])) {
            $this->baoError('商店LOGO格式不正确');
        } 
        $data['pic'] = htmlspecialchars($data['pic']);
        if (empty($data['pic'])) {
            $this->baoError('请上传商店形象照');
        }
        if (!isImage($data['pic'])) {
            $this->baoError('商店形象照格式不正确');
        }
        $data['city_id'] = (int) $data['city_id'];
        if (empty($data['city_id'])) {
            $this->baoError('所在城市不能为空');
        }
        $data['area_id'] = (int) $data['area_id'];
        if (empty($data['area_id'])) {
            $this->baoError('所在区域不能为空');
        }
        $data['business_id'] = (int) $data['business_id'];
        if (empty($data['business_id'])) {
            $this->baoError('所在商圈不能为空');
        }
        $data['express_id'] = (int) $data['express_id'];
        if (empty($data['express_id'])) {
            $this->baoError('所选快递商不能为空');
        }
        $data['addr'] = htmlspecialchars($data['addr']);
        if (empty($data['addr'])) {
            $this->baoError('店铺中文地址不能为空');
        }
        $data['lao_addr'] = htmlspecialchars($data['lao_addr']);
        if (empty($data['lao_addr'])) {
            $this->baoError('店铺老文地址不能为空');
        }
        $data['eng_addr'] = htmlspecialchars($data['eng_addr']);
        if (empty($data['eng_addr'])) {
            $this->baoError('店铺英文地址不能为空');
        }
        $data['tel'] = htmlspecialchars($data['tel']);
        $data['areacode'] = (int) $data['areacode'];
        $data['mobile'] = htmlspecialchars($data['mobile']);
        if (empty($data['tel']) && empty($data['mobile'])) {
            $this->baoError('店铺电话不能为空');
        }
        $data['mobile'] = $data['areacode'].$data['mobile'];
        $data['contact'] = htmlspecialchars($data['contact']);
        if (empty($data['contact'])) {
            $this->baoError('中文联系人不能为空');
        }
        $data['lao_contact'] = htmlspecialchars($data['lao_contact']);
        if (empty($data['lao_contact'])) {
            $this->baoError('老文联系人不能为空');
        }
        $data['eng_contact'] = htmlspecialchars($data['eng_contact']);
        if (empty($data['eng_contact'])) {
            $this->baoError('英文联系人不能为空');
        }
        $data['orderby'] = (int) $data['orderby'];
        if (empty($data['orderby'])) {
            $this->baoError('固定排名不能为空');
        }
        $data['lng'] = htmlspecialchars($data['lng']);
        $data['lat'] = htmlspecialchars($data['lat']);
        if (empty($data['lng'])||empty($data['lat'])) {
            $this->baoError('商家坐标不能为空!');
        } 

        $data['audit'] = intval($data['audit']);

        if ($shop['audit'] != $data['audit']) {
            $data['update_time'] = time();
            $data['update_ip'] = get_client_ip();
        }
        return $data;
    }

    
        public function select(){
        $weidian = D('WeidianDetails');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('audit'=>1);
        if($weidian_name = $this->_param('weidian_name','htmlspecialchars')){
            $map['weidian_name'] = array('LIKE','%'.$weidian_name.'%');
            $this->assign('weidian_name',$weidian_name);
        }
        $count = $weidian->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $pager = $Page->show(); // 分页显示输出
        $list = $weidian->where($map)->order(array('id'=>'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $pager); // 赋值分页输出
        $this->display(); // 输出模板
        
    }
    
    public function delete($wd_id = 0) {
        $obj = D('WeidianDetails');
        if (is_numeric($wd_id) && ($id = (int) $wd_id)) {
            $obj->save(array('id' => $id, 'close' => 1));
            $this->baoSuccess('删除成功！', U('weidian/index'));
        } else {
            $id = $this->_post('id', false);
            if (is_array($wd_id)) {
                foreach ($wd_id as $id) {
                    $obj->save(array('id' => $id, 'close' => 1));
                }
                $this->baoSuccess('删除成功！', U('weidian/index'));
            }
            $this->baoError('请选择要删除的商店');
        }
    }
}
