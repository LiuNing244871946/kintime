<?php

class TuanAction extends CommonAction { //按逻辑  instructions  和  details 要分表出去

    private $create_fields = array('shop_id', 'orderby','intro', 'eng_intro','lao_intro','title','eng_title','lao_title', 'photo', 'thumb','tuan_price', 'num', 'sold_num', 'bg_date', 'end_date', 'is_hot', 'is_new', 'is_chose', 'freebook', 'limit');
    private $edit_fields = array('shop_id', 'orderby','intro', 'eng_intro','lao_intro','title','eng_title','lao_title', 'photo', 'thumb','tuan_price', 'num', 'sold_num', 'bg_date', 'end_date', 'is_hot', 'is_new', 'is_chose', 'freebook', 'limit');


    public function index() {
        $Tuan = D('Tuan');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['title|eng_title|lao_title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if ($shop_id = (int) $this->_param('shop_id')) {
            $map['shop_id'] = $shop_id;
            $shop = D('Shop')->find($shop_id);
            $this->assign('shop_name', $shop['shop_name']);
            $this->assign('shop_id', $shop_id);
        }
        if ($audit = (int) $this->_param('audit')) {
            $map['audit'] = ($audit === 1 ? 1 : 0);
            $this->assign('audit', $audit);
        }
        $count = $Tuan->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 25); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $Tuan->where($map)->order(array('tuan_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $k => $val) {
            if ($val['shop_id']) {
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $val['create_ip_area'] = $this->ipToArea($val['create_ip']);
            $val = $Tuan->_format($val);
            $list[$k] = $val;
        }
        if ($shop_ids) {
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function branch($shop_id){
        $shop_id = (int)$shop_id;
        $branch = D('Shopbranch')->where(array('shop_id' => $shop_id, 'closed' => 0, 'audit' => 1))->select();
        $str = "";
        foreach($branch as $k=>$val){
            $str += '<label style="margin-right: 10px;"><span><{$val.name}>：</span><input style="width: 20px; height: 20px;" type="checkbox" name="branch_id[]" value="<{$val.branch_id}>" /></label>';
        }
        $this->ajaxReturn(array('status'=>'0','str'=>$str));
    }

        public function create() {
        if ($this->isPost()) {
            $data = $this->createCheck();
            $obj = D('Tuan');
            $details = $this->_post('details', 'SecurityEditorHtml');
            $lao_details = $this->_post('lao_details', 'SecurityEditorHtml');
            $eng_details = $this->_post('eng_details', 'SecurityEditorHtml');
            if (empty($details)) {
                $this->baoError('优惠中文详情不能为空');
            }
            if (empty($lao_details)) {
                $this->baoError('优惠老文详情不能为空');
            }
            if (empty($eng_details)) {
                $this->baoError('优惠英文详情不能为空');
            }
            if ($words = D('Sensitive')->checkWords($details)) {
                $this->baoError('中文详情内容含有敏感词：' . $words);
            }if ($words = D('Sensitive')->checkWords($lao_details)) {
                $this->baoError('老文详情内容含有敏感词：' . $words);
            }if ($words = D('Sensitive')->checkWords($eng_details)) {
                $this->baoError('英文详情内容含有敏感词：' . $words);
            }
            $instructions = $this->_post('instructions', 'SecurityEditorHtml');
            $lao_instructions = $this->_post('lao_instructions', 'SecurityEditorHtml');
            $eng_instructions = $this->_post('eng_instructions', 'SecurityEditorHtml');
            if (empty($instructions)) {
                $this->baoError('中文购买须知不能为空');
            }
            if (empty($lao_instructions)) {
                $this->baoError('老文购买须知不能为空');
            }
            if (empty($eng_instructions)) {
                $this->baoError('英文购买须知不能为空');
            }
            if ($words = D('Sensitive')->checkWords($instructions)) {
                $this->baoError('中文购买须知含有敏感词：' . $words);
            }if ($words = D('Sensitive')->checkWords($lao_instructions)) {
                $this->baoError('老文购买须知含有敏感词：' . $words);
            }if ($words = D('Sensitive')->checkWords($eng_instructions)) {
                $this->baoError('英文购买须知含有敏感词：' . $words);
            }
            $thumb = $this->_param('thumb', false);
            foreach ($thumb as $k => $val) {
                if (empty($val)) {
                    unset($thumb[$k]);
                }
                if (!isImage($val)) {
                    unset($thumb[$k]);
                }
            }
            $data['thumb'] = serialize($thumb);
            if ($tuan_id = $obj->add($data)) {
                $wei_pic = D('Weixin')->getCode($tuan_id, 2); //抢购类型是2
                $obj->save(array('tuan_id' => $tuan_id, 'wei_pic' => $wei_pic));
                D('Tuandetails')->add(array('tuan_id' => $tuan_id, 'details' => $details, 'instructions' => $instructions,'lao_details' => $lao_details, 'lao_instructions' => $lao_instructions,'eng_details' => $eng_details, 'eng_instructions' => $eng_instructions));
                $this->baoSuccess('添加成功', U('tuan/index'));
            }
            $this->baoError('操作失败！');
        } else {
            $this->display();
        }
    }

    private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['shop_id'] = (int) $data['shop_id'];
        if (empty($data['shop_id'])) {
            $this->baoError('商家不能为空');
        }
        $shop = D('Shop')->find($data['shop_id']);
        if (empty($shop)) {
            $this->baoError('请选择正确的商家');
        }
        
        $data['lng'] = $shop['lng'];
        $data['lat'] = $shop['lat'];
        $data['city_id'] = $shop['city_id'];
        $data['area_id'] = $shop['area_id'];
        $data['business_id'] = $shop['business_id'];

        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->baoError('商品中文名称不能为空');
        }
        $data['lao_title'] = htmlspecialchars($data['lao_title']);
        if (empty($data['lao_title'])) {
            $this->baoError('商品老文名称不能为空');
        }
        $data['eng_title'] = htmlspecialchars($data['eng_title']);
        if (empty($data['eng_title'])) {
            $this->baoError('商品英文名称不能为空');
        }

        $data['intro'] = htmlspecialchars($data['intro']);
        if (empty($data['intro'])) {
            $this->baoError('中文副标题不能为空');
        }
        $data['lao_intro'] = htmlspecialchars($data['lao_intro']);
        if (empty($data['lao_intro'])) {
            $this->baoError('老文副标题不能为空');
        }
        $data['eng_intro'] = htmlspecialchars($data['eng_intro']);
        if (empty($data['eng_intro'])) {
            $this->baoError('英文副标题不能为空');
        }

        $data['limit'] = (int) $data['limit'];
        if (empty($data['limit'])) {
            $this->baoError('每人限购数不能为空');
        }
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->baoError('请上传图片');
        }
        if (!isImage($data['photo'])) {
            $this->baoError('图片格式不正确');
        }
        $data['tuan_price'] = (int) ($data['tuan_price']);
        if (empty($data['tuan_price'])) {
            $this->baoError('抢购价格不能为空');
        }
        $data['num'] = (int) $data['num'];
        if (empty($data['num'])) {
            $this->baoError('库存不能为空');
        } 
        $data['sold_num'] = (int) $data['sold_num'];

        $data['bg_date'] = htmlspecialchars($data['bg_date']);
        if (empty($data['bg_date'])) {
            $this->baoError('开始时间不能为空');
        }
        if (!isTime($data['bg_date'])) {
            $this->baoError('开始时间格式不正确');
        } 
        $data['end_date'] = htmlspecialchars($data['end_date']);
        if (empty($data['end_date'])) {
            $this->baoError('结束时间不能为空');
        }
        if (!isTime($data['end_date'])) {
            $this->baoError('结束时间格式不正确');
        }
        $data['is_hot'] = (int) $data['is_hot'];
        $data['is_new'] = (int) $data['is_new'];
        $data['is_chose'] = (int) $data['is_chose'];
        $data['freebook'] = (int) $data['freebook'];
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        $data['orderby'] = (int) $data['orderby'];
        return $data;
    }

    public function edit($tuan_id = 0) {
        if ($tuan_id = (int) $tuan_id) {
            $obj = D('Tuan');
            if (!$detail = $obj->find($tuan_id)) {
                $this->baoError('请选择要编辑的优惠商品');
            }
            $tuan_details = D('Tuandetails')->getDetail($tuan_id);

            if ($this->isPost()) {
                $data = $this->editCheck();
                $details = $this->_post('details', 'SecurityEditorHtml');
                $lao_details = $this->_post('lao_details', 'SecurityEditorHtml');
                $eng_details = $this->_post('eng_details', 'SecurityEditorHtml');
                if (empty($details)) {
                    $this->baoError('优惠中文详情不能为空');
                }
                if (empty($lao_details)) {
                    $this->baoError('优惠老文详情不能为空');
                }
                if (empty($eng_details)) {
                    $this->baoError('优惠英文详情不能为空');
                }
                if ($words = D('Sensitive')->checkWords($details)) {
                    $this->baoError('中文详细内容含有敏感词：' . $words);
                }if ($words = D('Sensitive')->checkWords($lao_details)) {
                    $this->baoError('老文详细内容含有敏感词：' . $words);
                }if ($words = D('Sensitive')->checkWords($eng_details)) {
                    $this->baoError('英文详细内容含有敏感词：' . $words);
                }
                $instructions = $this->_post('instructions', 'SecurityEditorHtml');
                $lao_instructions = $this->_post('lao_instructions', 'SecurityEditorHtml');
                $eng_instructions = $this->_post('eng_instructions', 'SecurityEditorHtml');
                if (empty($instructions)) {
                    $this->baoError('中文购买须知不能为空');
                }
                if (empty($lao_instructions)) {
                    $this->baoError('老文购买须知不能为空');
                }
                if (empty($eng_instructions)) {
                    $this->baoError('英文购买须知不能为空');
                }
                if ($words = D('Sensitive')->checkWords($instructions)) {
                    $this->baoError('中文购买须知含有敏感词：' . $words);
                }if ($words = D('Sensitive')->checkWords($lao_instructions)) {
                    $this->baoError('老文购买须知含有敏感词：' . $words);
                }if ($words = D('Sensitive')->checkWords($eng_instructions)) {
                    $this->baoError('英文购买须知含有敏感词：' . $words);
                }
                $thumb = $this->_param('thumb', false);
                foreach ($thumb as $k => $val) {
                    if (empty($val)) {
                        unset($thumb[$k]);
                    }
                    if (!isImage($val)) {
                        unset($thumb[$k]);
                    }
                }
                $data['thumb'] = serialize($thumb);
                $data['tuan_id'] = $tuan_id;
                if (!empty($detail['wei_pic'])) {
                    if (true !== strpos($detail['wei_pic'], "https://mp.weixin.qq.com/")) {
                        $wei_pic = D('Weixin')->getCode($tuan_id, 2);
                        $data['wei_pic'] = $wei_pic;
                    }
                } else {
                    $wei_pic = D('Weixin')->getCode($tuan_id, 2);
                    $data['wei_pic'] = $wei_pic;
                }
                $ex = array(
                    'tuan_id' => $tuan_id,
                    'details' => $details,
                    'instructions' => $instructions,
                    'lao_details' => $lao_details,
                    'lao_instructions' => $lao_instructions,
                    'eng_details' => $eng_details,
                    'eng_instructions' => $eng_instructions,
                );
                if (false !== $obj->save($data)) {
                    D('Tuandetails')->save($ex);
                    $this->baoSuccess('操作成功', U('tuan/index'));
                }
                $this->baoError('操作失败');
            } else {
                $this->assign('detail', $obj->_format($detail));
                $thumb = unserialize($detail['thumb']);
                $this->assign('thumb', $thumb);
                $this->assign('shop', D('Shop')->find($detail['shop_id']));
                $this->assign('tuan_details', $tuan_details);
                $this->display();
            }
        } else {
            $this->baoError('请选择要编辑的优惠商品');
        }
    }

    private function editCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['shop_id'] = (int) $data['shop_id'];
        if (empty($data['shop_id'])) {
            $this->baoError('商家不能为空');
        }
        $shop = D('Shop')->find($data['shop_id']);
        if (empty($shop)) {
            $this->baoError('请选择正确的商家');
        }
        $data['lng'] = $shop['lng'];
        $data['lat'] = $shop['lat'];
        $data['city_id'] = $shop['city_id'];
        $data['area_id'] = $shop['area_id'];
        $data['business_id'] = $shop['business_id'];

        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->baoError('商品中文名称不能为空');
        }
        $data['lao_title'] = htmlspecialchars($data['lao_title']);
        if (empty($data['lao_title'])) {
            $this->baoError('商品老文名称不能为空');
        }
        $data['eng_title'] = htmlspecialchars($data['eng_title']);
        if (empty($data['eng_title'])) {
            $this->baoError('商品英文名称不能为空');
        }

        $data['intro'] = htmlspecialchars($data['intro']);
        if (empty($data['intro'])) {
            $this->baoError('中文副标题不能为空');
        }
        $data['lao_intro'] = htmlspecialchars($data['lao_intro']);
        if (empty($data['lao_intro'])) {
            $this->baoError('老文副标题不能为空');
        }
        $data['eng_intro'] = htmlspecialchars($data['eng_intro']);
        if (empty($data['eng_intro'])) {
            $this->baoError('英文副标题不能为空');
        }

        $data['limit'] = (int) $data['limit'];
        if (empty($data['limit'])) {
            $this->baoError('每人限购数不能为空');
        }
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->baoError('请上传图片');
        }
        if (!isImage($data['photo'])) {
            $this->baoError('图片格式不正确');
        }
        $data['tuan_price'] = (int) ($data['tuan_price']);
        if (empty($data['tuan_price'])) {
            $this->baoError('抢购价格不能为空');
        }
        $data['num'] = (int) $data['num'];
        if (empty($data['num'])) {
            $this->baoError('库存不能为空');
        } 
        $data['sold_num'] = (int) $data['sold_num'];

        $data['bg_date'] = htmlspecialchars($data['bg_date']);
        if (empty($data['bg_date'])) {
            $this->baoError('开始时间不能为空');
        }
        if (!isTime($data['bg_date'])) {
            $this->baoError('开始时间格式不正确');
        } 
        $data['end_date'] = htmlspecialchars($data['end_date']);
        if (empty($data['end_date'])) {
            $this->baoError('结束时间不能为空');
        }
        if (!isTime($data['end_date'])) {
            $this->baoError('结束时间格式不正确');
        }
        $data['is_hot'] = (int) $data['is_hot'];
        $data['is_new'] = (int) $data['is_new'];
        $data['is_chose'] = (int) $data['is_chose'];
        $data['freebook'] = (int) $data['freebook'];
        $data['orderby'] = (int) $data['orderby'];
        return $data;
    }

    public function delete($tuan_id = 0) {
        if (is_numeric($tuan_id) && ($tuan_id = (int) $tuan_id)) {
            $obj = D('Tuan');
            $obj->save(array('tuan_id' => $tuan_id, 'closed' => 1));
            $this->baoSuccess('删除成功！', U('tuan/index'));
        } else {
            $tuan_id = $this->_post('tuan_id', false);
            if (is_array($tuan_id)) {
                $obj = D('Tuan');
                foreach ($tuan_id as $id) {
                    $obj->save(array('tuan_id' => $id, 'closed' => 1));
                }
                $this->baoSuccess('删除成功！', U('tuan/index'));
            }
            $this->baoError('请选择要删除的抢购');
        }
    }

    public function audit($tuan_id = 0) {
        if (is_numeric($tuan_id) && ($tuan_id = (int) $tuan_id)) {
            $obj = D('Tuan');
            $obj->save(array('tuan_id' => $tuan_id, 'audit' => 1));
            $this->baoSuccess('审核成功！', U('tuan/index'));
        } else {
            $tuan_id = $this->_post('tuan_id', false);
            if (is_array($tuan_id)) {
                $obj = D('Tuan');
                foreach ($tuan_id as $id) {
                    $obj->save(array('tuan_id' => $id, 'audit' => 1));
                }
                $this->baoSuccess('审核成功！', U('tuan/index'));
            }
            $this->baoError('请选择要审核的抢购');
        }
    }


        public function select(){
        $tuan = D('Tuan');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('audit'=>1);
        if($keyword = $this->_param('keyword','htmlspecialchars')){
            $map['title|intro'] = array('LIKE','%'.$keyword.'%');
            $this->assign('keyword',$keyword);
        }
        $count = $tuan->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $pager = $Page->show(); // 分页显示输出
        $list = $tuan->where($map)->order(array('tuan_id'=>'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $pager); // 赋值分页输出
        $this->display(); // 输出模板
        
    }
    
}
