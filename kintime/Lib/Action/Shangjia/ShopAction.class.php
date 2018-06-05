<?php



class ShopAction extends CommonAction {

    public function index() {
        $this->display();
    }

    public function logo() {
        if ($this->isPost()) {
            $logo = $this->_post('logo', 'htmlspecialchars');
            if (empty($logo)) {
                $this->baoError('请上传商铺LOGO');
            }
            if (!isImage($logo)) {
                $this->baoError('商铺LOGO格式不正确');
            }
            $data = array('logo' => $logo);
            if (D('Shop')->where('mer_id='.$this->mer_id)->save($data)) {
                $this->baoSuccess('上传LOGO成功！', U('shop/logo'));
            }
            $this->baoError('更新LOGO失败');
        } else {
            $this->assign('shop', D('Shop')->where('mer_id='.$this->mer_id)->find());
            $this->display();
        }
    }

    public function image() {
        if ($this->isPost()) {
            $photo = $this->_post('photo', 'htmlspecialchars');
            if (empty($photo)) {
                $this->baoError('请上传商铺形象照');
            }
            if (!isImage($photo)) {
                $this->baoError('商铺形象照格式不正确');
            }
            $data = array('photo' => $photo);
            if (false !== D('Shop')->where('mer_id='.$this->mer_id)->save($data)) {
                $this->baoSuccess('上传形象照成功！', U('shop/image'));
            }
            $this->baoError('更新形象照失败');
        } else {
            $this->assign('shop', D('Shop')->where('mer_id='.$this->mer_id)->find());
            $this->display();
        }
    }
    
    public function about() {
        $shop = D('Shop')->where('mer_id='.$this->mer_id)->find();
        if ($this->isPost()) {
            $data = $this->checkFields($this->_post('data', false), array('shop_name','lao_shop_name','eng_shop_name','city_id','area_id','business_id','addr','lao_addr','eng_addr', 'cate_id','contact','lao_contact','eng_contact', 'tel','areacode','mobile','tags','lao_tags','eng_tags', 'business_time','is_ele','is_dianzicaidan','price','rate','lng', 'lat'));

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
            $data['city_id'] = (int) $data['city_id'];
            $data['area_id'] = (int) $data['area_id'];
            if (empty($data['area_id'])) {
                $this->baoError('所在区域不能为空');
            } 
            $data['business_id'] = (int) $data['business_id'];
            if (empty($data['business_id'])) {
                $this->baoError('所在商圈不能为空');
            } 
            $data['cate_id'] = (int) $data['cate_id'];
            if (empty($data['cate_id'])) {
                $this->baoError('分类不能为空');
            } 
            $data['addr'] = htmlspecialchars($data['addr']);
            if (empty($data['addr'])) {
                $this->baoError('店铺中文地址不能为空');
            }$data['lao_addr'] = htmlspecialchars($data['lao_addr']);
            if (empty($data['lao_addr'])) {
                $this->baoError('店铺老文地址不能为空');
            }$data['eng_addr'] = htmlspecialchars($data['eng_addr']);
            if (empty($data['eng_addr'])) {
                $this->baoError('店铺英文地址不能为空');
            }
            $data['contact'] = htmlspecialchars($data['contact']);
            $data['lao_contact'] = htmlspecialchars($data['lao_contact']);
            $data['eng_contact'] = htmlspecialchars($data['eng_contact']);
            $data['tel'] = htmlspecialchars($data['tel']);
            $data['areacode'] = (int) $data['areacode'];
            $data['mobile'] = htmlspecialchars($data['mobile']);
            if (empty($data['tel']) && empty($data['mobile'])) {
                $this->baoError('店铺电话不能为空');
            }
            $data['mobile'] = $data['areacode'].$data['mobile'];
            $data['tags'] = str_replace(',', '，', htmlspecialchars($data['tags']));
            $data['lao_tags'] = str_replace(',', '，', htmlspecialchars($data['lao_tags']));
            $data['eng_tags'] = str_replace(',', '，', htmlspecialchars($data['eng_tags']));
            $data['business_time'] = htmlspecialchars($data['business_time']);
            $data['is_ele'] = (int) $data['is_ele'];
            $data['is_dianzicaidan'] = (int) $data['is_dianzicaidan'];
            $data['price'] = (int) $data['price'];
            $data['lng'] = htmlspecialchars($data['lng']);
            $data['lat'] = htmlspecialchars($data['lat']);
            $data['rate'] = (int) $data['rate'];
            if (empty($data['rate'])) {
                $this->baoError('平台折扣不能为空!');
            } 
            $details = $this->_post('details', 'SecurityEditorHtml');
            if ($words = D('Sensitive')->checkWords($details)) {
                $this->baoError('商家中文介绍含有敏感词：' . $words);
            }
            $lao_details = $this->_post('lao_details', 'SecurityEditorHtml');
            if ($words = D('Sensitive')->checkWords($lao_details)) {
                $this->baoError('商家老文介绍含有敏感词：' . $words);
            }
            $eng_details = $this->_post('eng_details', 'SecurityEditorHtml');
            if ($words = D('Sensitive')->checkWords($eng_details)) {
                $this->baoError('商家英文介绍含有敏感词：' . $words);
            }
            $ex = array(
                'details'        => $details,
                'lao_details'    => $lao_details,
                'eng_details'    => $eng_details,
                'business_time'  => $data['business_time'],
                'price' => $data['price']
            );
            unset($data['business_time'],$data['price']);
            if($shop){

                $is_dianzicaidan = 0;
                if($data['is_dianzicaidan'] == 0){
                    $is_dianzicaidan = 1;
                }


                D('Shopding')->save(array('shop_id'=>$shop['shop_id'],'closed'=>$is_dianzicaidan));

                D('Ele')->save(array('shop_id'=>$shop['shop_id'],'is_open'=>$data['is_ele']));
                if (false !== D('Shop')->where('mer_id='.$this->mer_id)->save($data)) {
                    D('Shopdetails')->upDetails($shop['shop_id'],$ex);
                    $this->baoSuccess('操作成功', U('shop/about'));
                }
            }else{
                $data['mer_id']=$this->mer_id;
                $add = D('Shop')->add($data);
                if (false !== $add) {
                    D('Shopdetails')->upDetails($add['shop_id'],$ex);
                    $this->baoSuccess('操作成功', U('shop/about'));
                }
            }
            $this->baoError('操作失败');
        } else {
            $this->assign('cates', D('Tuancate')->fetchAll());
            $this->assign('business', D('Business')->fetchAll());
            $this->assign('rebate',D('rebate')->select());
            if($shop){
                if (strlen($shop['mobile'])===13) {
                    $shop['areacode']=substr($shop['mobile'],0,2);
                    $shop['mobile']=substr($shop['mobile'],2,11);
                }else if (strlen($shop['mobile'])===14) {
                    $shop['areacode']=substr($shop['mobile'],0,3);
                    $shop['mobile']=substr($shop['mobile'],3,11);
                }
                $this->assign('shop',$shop);
                $this->assign('ex', D('Shopdetails')->find($shop['shop_id']));
            }
            $this->display();
        }
    }
    

}
