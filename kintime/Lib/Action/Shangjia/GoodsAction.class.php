<?php



class GoodsAction extends CommonAction {

    private $create_fields = array('title','lao_title','eng_title','photo','cate_id','shopcate_id','mall_price','is_stage','sstage_id','sinterest','kucun','attr','lao_attr','eng_attr','max_buy','instructions','lao_instructions','eng_instructions','details','lao_details','eng_details','weight');
    private $edit_fields = array('title','lao_title','eng_title','photo','cate_id','shopcate_id','mall_price','is_stage','sstage_id','sinterest','kucun','attr','lao_attr','eng_attr','max_buy','instructions','lao_instructions','eng_instructions','details','lao_details','eng_details','weight');

    public function _initialize() {
        parent::_initialize();
        $this->weidian = D('WeidianDetails')->where(array('mer_id' => $this->mer_id))->find();
        $this->weidian_id = $this->weidian['id'];
        $this->autocates = D('Goodsshopcate')->where(array('weiian_id' => $this->weiian_id))->select();
        $this->assign('autocates', $this->autocates);
    }

    private function check_weidian() {
        $weidian = $this->weidian;
        if (!$weidian) {
            $this->error('请先完善微店资料！', U('goods/weidian'));
        } elseif ($weidian['audit'] == 0) {
            $this->error('您的微店正在审核中，请耐心等待！', U('index/index'));
        } elseif ($weidian['audit'] == 2) {
            $this->error('您的微店未通过审核！', U('index/index'));
        }
    }
    
    private function check_format(){
        $f = D('Format');
        $res = $f -> where('weidian_id ='.($this->weidian_id)) -> find();
        if(!$res){
            $this->error('您必须得先建立商品规格！',U('goodsshopformat/create'));
        }
    }

    public function index() {


        $this->check_weidian();

        $Goods = D('Goods');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0, 'weidian_id' => $this->weidian_id);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['title|lao_title|eng_title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $Goods->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 25); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $Goods->where($map)->order(array('goods_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function get_select() {

        if (IS_AJAX) {

            $pid = I('pid', 0, 'intval,trim');
            $gc = D('GoodsCate');
            $list = $gc->where('parent_id =' . $pid)->select();

            if ($pid == 0) {
                $this->ajaxReturn(array('status' => 'success', 'list' => ''));
            }

            if ($list) {
                $l = '';
                foreach ($list as $k => $v) {
                    $l = $l . '<option value=' . $v['cate_id'] . ' style="color:#333333;">' . $v['cate_name'] . '</option>';
                }

                $this->ajaxReturn(array('status' => 'success', 'list' => $l));
            }
        }
    }

    public function weidian() {

        $wd = D('WeidianDetails');
        $weidian = $this->weidian;
        if ($this->isPost()) {
            $data = $this->checkFields($this->_post('data', false), array('cate_id','weidian_name','lao_weidian_name','eng_weidian_name','logo','pic','tel','areacode','mobile','contact','lao_contact','eng_contact','addr','lao_addr','eng_addr','lng','lat','details','lao_details','eng_details','rate','city_id','area_id','business_id','express_id'));
            $data['mer_id'] = $this->mer_id;
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
            $data['lng'] = htmlspecialchars($data['lng']);
            $data['lat'] = htmlspecialchars($data['lat']);
            if (empty($data['lng'])||empty($data['lat'])) {
                $this->baoError('商家坐标不能为空!');
            }
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
            if (empty($weidian)) {
                $data['create_time'] = NOW_TIME;
                $data['create_ip'] = get_client_ip();
                $add = $wd->add($data);
                if (!$add) {
                    $this->baoError('设置失败');
                } else {
                    $this->baoSuccess('设置成功', U('goods/weidian'));
                }
            }else{
                $data['update_time'] = NOW_TIME;
                $data['update_ip'] = get_client_ip();
                $up = $wd->where('mer_id =' . ($this->mer_id))->save($data);
                if (!$up) {
                    $this->baoError('修改失败');
                } else {
                    $this->baoSuccess('修改成功', U('goods/weidian'));
                }
            }
        } else {
            //冗余信息
            if (strlen($weidian['mobile'])===13) {
                $weidian['areacode']=substr($weidian['mobile'],0,2);
                $weidian['mobile']=substr($weidian['mobile'],2,11);
            }else if (strlen($weidian['mobile'])===14) {
                $weidian['areacode']=substr($weidian['mobile'],0,3);
                $weidian['mobile']=substr($weidian['mobile'],3,11);
            }
            $this->assign('express', D('Express')->fetchAll());
            $this->assign('business', D('Business')->fetchAll());
			$cates = D("WeidianCate") ->select();
            $this->assign('cates', $cates);
            $this->assign('rebate',D('rebate')->select());
            $this->assign('weidian', $weidian);
            $this->display();
        }
    }

    public function create() {
        $this->check_weidian();
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
        $data['weidian_id'] = $this->weidian_id;
        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->baoError('商品中文名称不能为空');
        }
        $data['eng_title'] = htmlspecialchars($data['eng_title']);
        if (empty($data['eng_title'])) {
            $this->baoError('商品英文名称不能为空');
        }
        $data['lao_title'] = htmlspecialchars($data['lao_title']);
        if (empty($data['lao_title'])) {
            $this->baoError('商品老文名称不能为空');
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
        $data['weight'] = (int) ($data['weight']);
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
        if($data['is_stage']){
            foreach ($data['sinterest'] as $key => $value) {
                if(empty($value)){
                    $this->baoError('商家分期利息不能为空');
                }
            }
        }
        $data['kucun'] = (int) $data['kucun'];
        if (empty($data['kucun'])) {
            $this->baoError('商品库存不能为空');
        }
        $data['attr'] = htmlspecialchars($data['attr']);
        $data['eng_attr'] = htmlspecialchars($data['eng_attr']);
        $data['lao_attr'] = htmlspecialchars($data['lao_attr']);
        $data['max_buy'] = (int) $data['max_buy'];
        if (empty($data['max_buy'])) {
            $this->baoError('商品单次最大购买数不能为空');
        } 
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
        $data['sold_num'] = 0;
        $data['view'] = 0;
        return $data;
    }

    public function edit($goods_id = 0) {
        $this->check_weidian();
        $stage0 =  D('Stage')->select();
        foreach ($stage0 as $key => $value) {
            $stage[$value['id']]=$value;
        }
        if ($goods_id = (int) $goods_id) {
            $obj = D('Goods');
            if (!$detail = $obj->find($goods_id)) {
                $this->error('请选择要编辑的商品');
            }
            if ($detail['weidian_id'] != $this->weidian_id) {
                $this->error('请不要试图越权操作其他人的内容');
            }
            if ($this->isPost()) {
                $data = $this->editCheck();
                $data['goods_id'] = $goods_id;
                $data['audit'] = 0;
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
                    $this->baoSuccess('操作成功', U('goods/index'));
                }
                $this->baoError('操作失败');
            } else {
                $this->assign('detail',$detail);
                $this->assign('cates', D('Goodscate')->fetchAll());
                $this->assign('stage', $stage);
                $sinterests = D('Interest')->where(array('goods_id'=>$goods_id,'type'=>1))->select();
                $this->assign('sinterests',$sinterests);
                $this->display();
            }
        } else {
            $this->baoError('请选择要编辑的商品');
        }
    }

    private function editCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['weidian_id'] = $this->weidian_id;
        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->baoError('商品中文名称不能为空');
        }
        $data['eng_title'] = htmlspecialchars($data['eng_title']);
        if (empty($data['eng_title'])) {
            $this->baoError('商品英文名称不能为空');
        }
        $data['lao_title'] = htmlspecialchars($data['lao_title']);
        if (empty($data['lao_title'])) {
            $this->baoError('商品老文名称不能为空');
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
            $this->baoError('商城价格不能为空');
        } 
        $data['weight'] = (int) ($data['weight']);
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
        if($data['is_stage']){
            foreach ($data['sinterest'] as $key => $value) {
                if(empty($value)){
                    $this->baoError('商家分期利息不能为空');
                }
            }
        }
        $data['kucun'] = (int) $data['kucun'];
        if (empty($data['kucun'])) {
            $this->baoError('商品库存不能为空');
        }
        $data['attr'] = htmlspecialchars($data['attr']);
        $data['eng_attr'] = htmlspecialchars($data['eng_attr']);
        $data['lao_attr'] = htmlspecialchars($data['lao_attr']);
        $data['max_buy'] = (int) $data['max_buy'];
        if (empty($data['max_buy'])) {
            $this->baoError('商品单次最大购买数不能为空');
        } 
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
    
    
    
    
     public function format($goods_id = 0) {
        $this->check_weidian();
        $this->check_format();
        if ($goods_id = (int) $goods_id) {
            $obj = D('Goodsformat');
            if (!$goods = D('Goods')->find($goods_id)) {
                $this->error('该商品不存在');
            }
            if ($goods['weidian_id'] != $this->weidian_id) {
                $this->error('请不要试图越权操作其他人的内容');
            }
            $formats = $obj->where(array('goods_id'=>$goods_id))->select();
            foreach($formats as $k=>$v){
                $formats[$k]['detail'] = D('Format')->format_content($v['id']);
            }
            $this->assign('goods',$goods);
            $this->assign('formats',$formats);
            $this->display();
        } else {
            $this->baoError('请选择商品');
        }
    }
    
    public function format_create($goods_id = 0) {
        $this->check_weidian();
        $obj = D('Goodsformat');
        if(!$goods = D('Goods')->find($goods_id)){
             $this->error('商品信息不存在');
        }
        if ($this->isPost()) {
                $data = $_POST['data'];                
                 //遍历选中的项目把标题整合好写入规格表
                $format_ids = $value_ids = array();
                foreach($data['value'] as $k => $v){
                    if(($k = (int)$k) && ($v = (int)$v)){
                        $format_ids[$k] = $k;
                        $value_ids[$v] = $v;
                    }
                }
                if($format_list = D('Format')->format_goodsid($goods_id)){
                    $fids = $vids = $vnames = array();
                    foreach($format_list as $v){
                        if(in_array($v['id'], $format_ids)){
                            $fids[$v['id']] = $v['id'];
                            foreach($v['values'] as $vv){
                                if(in_array($vv['id'], $value_ids)){
                                    $vids[$vv['id']] = $vv['id'];
                                    $vnames[$vv['id']] = $vv['name'];
                                }
                            }
                        }
                    }
                    $format_ids = $fids;
                    $value_ids = $vids;
                }else{
                   $this->error('规格不存在或已经删除'); 
                }
                $data['format_title'] = implode(',', $vnames);
                asort($value_ids);
                $data['content'] = implode('-', $value_ids);
                $data['goods_id'] = $goods_id;
                $data['mall_price'] = $data['mall_price'];
                $data['weight'] = $data['weight'];
                if(isset($data['photo'])){
                    $data['photo'] = htmlspecialchars($data['photo']);
                }

                $kucuns = $obj->where(array('goods_id'=>$goods_id))->select();
                $g_data['goods_id'] = $goods_id;
                $g_data['kucun'] = 0;
                foreach ($kucuns as $key => $value) {
                    $g_data['kucun'] += $value['store'];
                }
                $g_data['kucun'] += $data['store'];
                if(D('Goods')->save($g_data) === false){
                    $this->baoError('库存更新失败');
                }
                if ($obj->add($data)) {
                    $this->baoSuccess('操作成功', U('goods/format',array('goods_id'=>$goods_id)));
                }                
                $this->baoError('操作失败');
        } else {
            $format = D('Format')->format_goodsid($goods_id);
            $this->assign('goods', $goods);
            $this->assign('formats',$format);
            $this->display();
        }
    }
    
    public function format_detail($id = 0) {
        $this->check_weidian();
        if ($id = (int) $id) {
            $obj = D('Goodsformat');
            if (!$detail = $obj->find($id)) {
                $this->error('该条信息不存在');
            }
            $goods = D('Goods')->find($detail['goods_id']);
            if ($this->isPost()) {
                $data = $_POST['data'];                
                 //遍历选中的项目把标题整合好写入套餐表
                $format_ids = $value_ids = array();
                foreach($data['value'] as $k => $v){
                    if(($k = (int)$k) && ($v = (int)$v)){
                        $format_ids[$k] = $k;
                        $value_ids[$v] = $v;
                    }
                }
                if($format_list = D('Format')->format_goodsid($detail['goods_id'])){
                    $fids = $vids = $vnames = array();
                    foreach($format_list as $v){
                        if(in_array($v['id'], $format_ids)){
                            $fids[$v['id']] = $v['id'];
                            foreach($v['values'] as $vv){
                                if(in_array($vv['id'], $value_ids)){
                                    $vids[$vv['id']] = $vv['id'];
                                    $vnames[$vv['id']] = $vv['name'];
                                }
                            }
                        }
                    }
                    $format_ids = $fids;
                    $value_ids = $vids;
                }else{
                   $this->error('规格不存在或已经删除'); 
                }
                $data['format_title'] = implode(',', $vnames);
                asort($value_ids);
                $data['content'] = implode('-', $value_ids);
                $data['id'] = $id;
                $data['mall_price'] = $data['mall_price'];
                if(isset($data['photo'])){
                    $data['photo'] = htmlspecialchars($data['photo']);
                }
                $kucuns = $obj->where(array('goods_id'=>$detail['goods_id']))->select();
                $g_data['goods_id'] = $detail['goods_id'];
                $g_data['kucun'] = 0;
                foreach ($kucuns as $key => $value) {
                    if($value['id'] != $id){
                        $g_data['kucun'] += $value['store'];
                    }
                }
                $g_data['kucun'] += $data['store'];
                if(D('Goods')->save($g_data) === false){
                    $this->baoError('库存更新失败');
                }
                if (false !== $obj->save($data)) {
                    $this->baoSuccess('操作成功', U('goods/format',array('goods_id'=>$detail['goods_id'])));
                }                
                $this->baoError('操作失败');
            } else {
                $format = D('Format')->format_goodsid($detail['goods_id']);
                $detail['title'] = $goods['title'].'| '.$goods['lao_title'].'| '.$goods['eng_title'];
                $detail['format'] = explode('-',$detail['content']);
                $detail['values'] = explode('-', $detail['content']);
                $this->assign('detail', $detail);
                $this->assign('formats',$format);                
                $this->display();
            }
        } else {
            $this->baoError('请选择要编辑的配置');
        }
    }
    
    public function delete($goods_id = 0) {
        $goods_id = (int) $goods_id;
        $obj = D('goods');
        $obj->save(array('goods_id' => $goods_id, 'closed' => 1));
        $this->baoSuccess('删除成功', U('goods/index'));
    }
}
