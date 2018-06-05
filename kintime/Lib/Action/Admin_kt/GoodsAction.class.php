<?php

class GoodsAction extends CommonAction {

    private $create_fields = array('weidian_id','title','eng_title','lao_title','cate_id','shopcate_id','photo','mall_price','is_stage','sstage_id','sinterest','kstage_id','kinterest','kucun','sold_num','views','attr','eng_attr','lao_attr','max_buy','orderby','instructions','eng_instructions','lao_instructions','details','eng_details','lao_details','weight');
    private $edit_fields = array('weidian_id','title','eng_title','lao_title','cate_id','shopcate_id','photo','mall_price','is_stage','sstage_id','sinterest','kstage_id','kinterest','kucun','sold_num','views','attr','eng_attr','lao_attr','max_buy','orderby','instructions','eng_instructions','lao_instructions','details','eng_details','lao_details','weight');

    public function index() {
        $Goods = D('Goods');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['title|eng_title|lao_title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if ($cate_id = (int) $this->_param('cate_id')) {
            $map['cate_id'] = array('IN', D('Goodscate')->getChildren($cate_id));
            $this->assign('cate_id', $cate_id);
        }
        if ($weidian_id = (int) $this->_param('weidian_id')) {
            $map['weidian_id'] = $weidian_id;
            $shop = D('WeidianDetails')->find($weidian_id);
            $this->assign('weidian_name', $shop['weidian_name']);
            $this->assign('weidian_id', $weidian_id);
        }
        if ($audit = (int) $this->_param('audit')) {
            $map['audit'] = ($audit === 1 ? 1 : 0);
            $this->assign('audit', $audit);
        }
        $count = $Goods->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 25); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $Goods->where($map)->order(array('goods_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $k => $val) {
            if ($val['weidian_id']) {
                $weidian_ids[$val['weidian_id']] = $val['weidian_id'];
            }
        }
        if ($weidian_ids) {
            $weidian = D('WeidianDetails')->where(array('id'=>array('IN',$weidian_ids)))->select();
            foreach ($weidian as $key => $value) {
                $details[$value['id']] = $value;
            }
            $this->assign('details', $details);
        }
        $this->assign('cates', D('Goodscate')->fetchAll());
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function create() {
        $stage0 =  D('Stage')->select();
        foreach ($stage0 as $key => $value) {
            $stage[$value['id']]=$value;
        }
        if ($this->isPost()) {
            $data = $this->createCheck();
            $obj = D('Goods');
            if ($goods_id = $obj->add($data)) {
                if($data['is_stage']){
                    foreach ($data['sstage_id'] as $key => $value) {
                        D('Interest')->add(array('goods_id'=>$goods_id,'periods'=>$stage[$value]['periods'],'interest'=>$data['sinterest'][$key],'type'=>1));
                    }
                }
                foreach ($data['kstage_id'] as $key => $value) {
                    D('Interest')->add(array('goods_id'=>$goods_id,'periods'=>$stage[$value]['periods'],'interest'=>$data['kinterest'][$key],'type'=>0));
                }
                $this->baoSuccess('添加成功', U('goods/index'));
            }
            $this->baoError('操作失败！');
        } else {
            $this->assign('cates', D('Goodscate')->fetchAll());
            $this->assign('stage', $stage);
            $this->display();
        }
    }

    private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        
        $data['weidian_id'] = (int) $data['weidian_id'];
        if (empty($data['weidian_id'])) {
            $this->baoError('商家不能为空');
        }
        $shop = D('WeidianDetails')->find($data['weidian_id']);
        if (empty($shop)) {
            $this->baoError('请选择正确的商家');
        }
        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->baoError('产品中文名称不能为空');
        }
        $data['eng_title'] = htmlspecialchars($data['eng_title']);
        if (empty($data['eng_title'])) {
            $this->baoError('产品英文名称不能为空');
        }
        $data['lao_title'] = htmlspecialchars($data['lao_title']);
        if (empty($data['lao_title'])) {
            $this->baoError('产品老文名称不能为空');
        }
        $data['cate_id'] = (int) $data['cate_id'];
        if (empty($data['cate_id'])) {
            $this->baoError('请选择分类');
        }
        $data['shopcate_id'] = (int) $data['shopcate_id'];
        if (empty($data['shopcate_id'])) {
            $this->baoError('请选择店铺商品分类');
        }
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->baoError('请上传产品图');
        }
        if (!isImage($data['photo'])) {
            $this->baoError('产品图格式不正确');
        }
        $data['mall_price'] = (int) ($data['mall_price']);
        if (empty($data['mall_price'])) {
            $this->baoError('商品价格不能为空');
        } 
        $data['weight'] = (float) ($data['weight']);
        if (empty($data['weight'])) {
            $this->baoError('商品重量不能为空');
        } 
        $data['is_stage'] = (int) ($data['is_stage']);
        if (empty($data['is_stage'])) {
            $this->baoError('请选择是否支持分期');
        }
        if(count($data['sstage_id'])!=count(array_unique($data['sstage_id']))){
            $this->baoError('商家分期不能重复');
        }
        if(count($data['kstage_id'])!=count(array_unique($data['kstage_id']))){
            $this->baoError('平台分期不能重复');
        }
        if($data['is_stage']){
            foreach ($data['sinterest'] as $key => $value) {
                if(empty($value)){
                    $this->baoError('商家分期利息不能为空');
                }
            }
        }
        foreach ($data['kinterest'] as $key => $value) {
            if(empty($value)){
                $this->baoError('平台分期利息不能为空');
            }
        }
        $data['kucun'] = (int) $data['kucun'];
        if (empty($data['kucun'])) {
            $this->baoError('商品库存不能为空');
        } 
        $data['sold_num'] = (int) $data['sold_num'];
        $data['views'] = (int) $data['views'];
        $data['attr'] = htmlspecialchars($data['attr']);
        $data['eng_attr'] = htmlspecialchars($data['eng_attr']);
        $data['lao_attr'] = htmlspecialchars($data['lao_attr']);
        $data['max_buy'] = (int) $data['max_buy'];
        if (empty($data['max_buy'])) {
            $this->baoError('商品单次最大购买数不能为空');
        } 
        $data['orderby'] = (int) $data['orderby'];
        $data['instructions'] = SecurityEditorHtml($data['instructions']);
        if (empty($data['instructions'])) {
            $this->baoError('中文购买须知不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['instructions'])) {
            $this->baoError('中文购买须知含有敏感词：' . $words);
        }
        $data['eng_instructions'] = SecurityEditorHtml($data['eng_instructions']);
        if (empty($data['eng_instructions'])) {
            $this->baoError('英文购买须知不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['eng_instructions'])) {
            $this->baoError('英文购买须知含有敏感词：' . $words);
        }
        $data['lao_instructions'] = SecurityEditorHtml($data['lao_instructions']);
        if (empty($data['lao_instructions'])) {
            $this->baoError('老文购买须知不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['lao_instructions'])) {
            $this->baoError('老文购买须知含有敏感词：' . $words);
        }
        $data['details'] = SecurityEditorHtml($data['details']);
        if (empty($data['details'])) {
            $this->baoError('商品中文详情不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['details'])) {
            $this->baoError('商品中文详情含有敏感词：' . $words);
        }
        $data['eng_details'] = SecurityEditorHtml($data['eng_details']);
        if (empty($data['eng_details'])) {
            $this->baoError('商品英文详情不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['eng_details'])) {
            $this->baoError('商品英文详情含有敏感词：' . $words);
        }
        $data['lao_details'] = SecurityEditorHtml($data['lao_details']);
        if (empty($data['lao_details'])) {
            $this->baoError('商品老文详情不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['lao_details'])) {
            $this->baoError('商品老文详情含有敏感词：' . $words);
        }
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        return $data;
    }

    public function edit($goods_id = 0) {
        $stage0 =  D('Stage')->select();
        foreach ($stage0 as $key => $value) {
            $stage[$value['id']]=$value;
        }
        if ($goods_id = (int) $goods_id) {
            $obj = D('Goods');
            if (!$detail = $obj->find($goods_id)) {
                $this->baoError('请选择要编辑的商品');
            }
            if ($this->isPost()) {
                $data = $this->editCheck();
                $data['goods_id'] = $goods_id;
                
                if (false !== $obj->save($data)) {
                    if($data['is_stage']){
                        foreach ($data['sstage_id'] as $key => $value) {
                            
                            $res = D('Interest')->where(array('goods_id'=>$goods_id,'type'=>1,'periods'=>$stage[$value]['periods']))->find();
                            if(!empty($res)){
                                
                                D('Interest')->save(array('id'=>$res['id'],'goods_id'=>$goods_id,'periods'=>$stage[$value]['periods'],'interest'=>$data['sinterest'][$key],'type'=>1));
                            }else{
                                D('Interest')->add(array('goods_id'=>$goods_id,'periods'=>$stage[$value]['periods'],'interest'=>$data['sinterest'][$key],'type'=>1));
                            }
                            
                        }
                    }
                    foreach ($data['kstage_id'] as $key => $value) {
                        $res = D('Interest')->where(array('goods_id'=>$goods_id,'type'=>0,'periods'=>$stage[$value]['periods']))->select();
                        if(!empty($res)){
                            D('Interest')->save(array('id'=>$res['id'],'goods_id'=>$goods_id,'periods'=>$stage[$value]['periods'],'interest'=>$data['kinterest'][$key],'type'=>0));
                        }else{
                            D('Interest')->add(array('goods_id'=>$goods_id,'periods'=>$stage[$value]['periods'],'interest'=>$data['kinterest'][$key],'type'=>0));
                        }
                        
                    }
                    
                    $this->baoSuccess('操作成功', U('goods/index'));
                }
                $this->baoError('操作失败');
            } else {
                $sinterests = D('Interest')->where(array('goods_id'=>$goods_id,'type'=>1))->select();
                $kinterests = D('Interest')->where(array('goods_id'=>$goods_id,'type'=>0))->select();
                $this->assign('sinterests',$sinterests);
                $this->assign('kinterests',$kinterests);
                $this->assign('stage',$stage);
                $this->assign('detail',$detail);
                $this->assign('cates', D('Goodscate')->fetchAll());
                $this->assign('shopcates', D('Goodsshopcate')->where('weidian_id='.$detail['weidian_id'])->select());
                $this->assign('weidian', D('WeidianDetails')->find($detail['weidian_id']));
                $this->display();
            }
        } else {
            $this->baoError('请选择要编辑的商品');
        }
    }

    private function editCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['weidian_id'] = (int) $data['weidian_id'];
        if (empty($data['weidian_id'])) {
            $this->baoError('商家不能为空');
        }
        $shop = D('WeidianDetails')->find($data['weidian_id']);
        if (empty($shop)) {
            $this->baoError('请选择正确的商家');
        }
        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->baoError('产品中文名称不能为空');
        }
        $data['eng_title'] = htmlspecialchars($data['eng_title']);
        if (empty($data['eng_title'])) {
            $this->baoError('产品英文名称不能为空');
        }
        $data['lao_title'] = htmlspecialchars($data['lao_title']);
        if (empty($data['lao_title'])) {
            $this->baoError('产品老文名称不能为空');
        }
        $data['cate_id'] = (int) $data['cate_id'];
        if (empty($data['cate_id'])) {
            $this->baoError('请选择分类');
        }
        $data['shopcate_id'] = (int) $data['shopcate_id'];
        if (empty($data['shopcate_id'])) {
            $this->baoError('请选择店铺商品分类');
        }
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->baoError('请上传产品图');
        }
        if (!isImage($data['photo'])) {
            $this->baoError('产品图格式不正确');
        }
        $data['mall_price'] = (int) ($data['mall_price']);
        if (empty($data['mall_price'])) {
            $this->baoError('商品价格不能为空');
        } 
        $data['weight'] = (float) ($data['weight']);
        if (empty($data['weight'])) {
            $this->baoError('商品重量不能为空');
        } 
        $data['is_stage'] = (int) ($data['is_stage']);
        if (empty($data['is_stage'])) {
            $this->baoError('请选择是否支持分期');
        }
        if(count($data['sstage_id'])!=count(array_unique($data['sstage_id']))){
            $this->baoError('商家分期不能重复');
        }
        if(count($data['kstage_id'])!=count(array_unique($data['kstage_id']))){
            $this->baoError('平台分期不能重复');
        }
        if($data['is_stage']){
            foreach ($data['sinterest'] as $key => $value) {
                if(empty($value)){
                    $this->baoError('商家分期利息不能为空');
                }
            }
        }
        foreach ($data['kinterest'] as $key => $value) {
            if(empty($value)){
                $this->baoError('平台分期利息不能为空');
            }
        }
        $data['kucun'] = (int) $data['kucun'];
        if (empty($data['kucun'])) {
            $this->baoError('商品库存不能为空');
        } 
        $data['sold_num'] = (int) $data['sold_num'];
        $data['views'] = (int) $data['views'];
        $data['attr'] = htmlspecialchars($data['attr']);
        $data['eng_attr'] = htmlspecialchars($data['eng_attr']);
        $data['lao_attr'] = htmlspecialchars($data['lao_attr']);
        $data['max_buy'] = (int) $data['max_buy'];
        if (empty($data['max_buy'])) {
            $this->baoError('商品单次最大购买数不能为空');
        } 
        $data['orderby'] = (int) $data['orderby'];
        $data['instructions'] = SecurityEditorHtml($data['instructions']);
        if (empty($data['instructions'])) {
            $this->baoError('中文购买须知不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['instructions'])) {
            $this->baoError('中文购买须知含有敏感词：' . $words);
        }
        $data['eng_instructions'] = SecurityEditorHtml($data['eng_instructions']);
        if (empty($data['eng_instructions'])) {
            $this->baoError('英文购买须知不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['eng_instructions'])) {
            $this->baoError('英文购买须知含有敏感词：' . $words);
        }
        $data['lao_instructions'] = SecurityEditorHtml($data['lao_instructions']);
        if (empty($data['lao_instructions'])) {
            $this->baoError('老文购买须知不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['lao_instructions'])) {
            $this->baoError('老文购买须知含有敏感词：' . $words);
        }
        $data['details'] = SecurityEditorHtml($data['details']);
        if (empty($data['details'])) {
            $this->baoError('商品中文详情不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['details'])) {
            $this->baoError('商品中文详情含有敏感词：' . $words);
        }
        $data['eng_details'] = SecurityEditorHtml($data['eng_details']);
        if (empty($data['eng_details'])) {
            $this->baoError('商品英文详情不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['eng_details'])) {
            $this->baoError('商品英文详情含有敏感词：' . $words);
        }
        $data['lao_details'] = SecurityEditorHtml($data['lao_details']);
        if (empty($data['lao_details'])) {
            $this->baoError('商品老文详情不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['lao_details'])) {
            $this->baoError('商品老文详情含有敏感词：' . $words);
        }
        return $data;
    }

    public function delete($goods_id = 0) {
        if (is_numeric($goods_id) && ($goods_id = (int) $goods_id)) {
            $obj = D('Goods');
            $obj->save(array('goods_id' => $goods_id, 'closed' => 1));
            $this->baoSuccess('删除成功！', U('goods/index'));
        } else {
            $goods_id = $this->_post('goods_id', false);
            if (is_array($goods_id)) {
                $obj = D('Goods');
                foreach ($goods_id as $id) {
                    $obj->save(array('goods_id' => $id, 'closed' => 1));
                }
                $this->baoSuccess('删除成功！', U('goods/index'));
            }
            $this->baoError('请选择要删除的商品');
        }
    }

    public function audit($goods_id = 0) {
        if (is_numeric($goods_id) && ($goods_id = (int) $goods_id)) {
            $obj = D('Goods');
            $obj->save(array('goods_id' => $goods_id, 'audit' => 1));
            $this->baoSuccess('审核成功！', U('goods/index'));
        } else {
            $goods_id = $this->_post('goods_id', false);
            if (is_array($goods_id)) {
                $obj = D('Goods');
                foreach ($goods_id as $id) {
                    $obj->save(array('goods_id' => $id, 'audit' => 1));
                }
                $this->baoSuccess('审核成功！'.$error.'条失败', U('goods/index'));
            }
            $this->baoError('请选择要审核的商品');
        }
    }
    public function shopcate(){
        $weidian_id = $this->_post('weidian_id');
        $weidian_id = (int) $weidian_id;
        $cates = D('Goodsshopcate')->where('weidian_id='.$weidian_id)->select();
        foreach($cates as $k=>$val){
            $shopcates[$val['cate_id']] = $val;
        }
        echo json_encode($shopcates);
    }
}
