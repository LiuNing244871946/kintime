<?php



class MallAction extends CommonAction {
    
    public function _initialize() {

        parent::_initialize();

        $goods = D('GoodsCart')->where(array('user_id'=>$this->uid))->select();
        $cartnum = 0;
        foreach ($goods as $key => $value) {
            $cartnum += $value['num'];
        }

        $this->assign('cartnum', $cartnum);
    }

    public function index()
    {
        $Goods = D('Goods');
        //新品上架
        $map2 = array('closed' => 0, 'audit' => 1);
        $orderby = array('create_time'=>'DESC');
        $new = $Goods->where($map2)->order($orderby)->limit('4')->select();
        $this->assign('new', $new);

        //推荐
        $orderby = array('orderby'=>'ASC');
        $tuijian = $Goods->where($map2)->order($orderby)->limit('4')->select();
        $this->assign('tuijian', $tuijian);

        //猜你喜欢
        $orderby = array('views'=>'DESC');
        $like = $Goods->where($map2)->order($orderby)->limit('8')->select();
        $this->assign('like', $like);
        $this->display('index');
    }

    public function items() {

        $keyword = $this->_param('keyword', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        $cate_id = (int) $this->_param('cate_id');
        $order = (int) $this->_param('order');
        $this->assign('cate_id', $cate_id);
        $this->assign('order', $order);
        $this->assign('nextpage', LinkTo('mall/loaddata', array('order' => $order,'cate_id' => $cate_id, 'keyword' => $keyword, 'p' => '0000')));
        $this->mobile_title = '商城商品';
        $this->display();
    }

    public function loaddata() {
        $Goods = D('Goods');
        import('ORG.Util.Page'); // 导入分页类
        $order = (int) $this->_param('order');
        $cate_id = (int) $this->_param('cate_id');
        if ($cate_id) {
            $cates = D('Goodscate')->where(array('parent_id'=>$cate_id))->select();
            $arr = array();
            foreach($cates as $k=>$v) {
             $arr[] = $v['cate_id'];
            }
            $map['cate_id']=array('in',$arr);
        }      
        $map['audit'] = 1;
        $map['closed'] = 0;
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['title|lao_title|eng_title'] = array('LIKE', '%' . $keyword . '%');
        }
        $count = $Goods->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        if ($order == '1') {
            $order_arr = 'create_time desc';
        } elseif ($order == '2') {
            $order_arr = 'sold_num desc';
        } else {
            $order_arr = 'orderby desc';
        }
        $list = $Goods->where($map)->order($order_arr)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function detail($goods_id=0, $vid=0) {

        $goods_id = (int) $goods_id;
        if (empty($goods_id)) {
            $this->error('商品不存在');
        }
        if (!$detail = D('Goods')->find($goods_id)) {
            $this->error('商品不存在');
        }
        if ($detail['closed'] != 0 || $detail['audit'] != 1) {
            $this->error('商品不存在');
        }

        $weidian_id = $detail['weidian_id'];
        $recom = D('Goods')->where(array('weidian_id' => $weidian_id, 'goods_id' => array('neq', $goods_id)))->select();
        $record = D('Usersgoods');
        $insert = $record->getRecord($this->uid, $goods_id);
        $this->assign('recom', $recom);
        $weidian = D('Weidiandetails')->where(array('id' => $weidian_id))->find();
        if($detail['max_buy']<=0){
            $detail['max_buy'] = '99';
        }
        $this->assign('weidian', $weidian);
        $this->mobile_title = '商品详情';
        $goods_vid = 0;
        if($goods_value_list = D('Goodsformat')->where(array('goods_id'=>$goods_id))->select()){
            $gvid = (int)$_GET['vid'];
            $goods_value = $goods_value_list[0];
            $goods_vid = $goods_value['id'];
            $format_list = D('Format')->format_goodsid($goods_id, $detail['shopcate_id']);
            $content_format_values = array(); //以content为key的数组
            foreach($goods_value_list as $v){
                if($v['id'] == $gvid){
                    $goods_value = $v;
                    $goods_vid = $gvid;
                }
                foreach(explode('-', $v['content']) as $vid){
                    $goods_value_ids[$vid] = $vid;
                }
                $content_format_values[$v['content']] = $v;
            }
            $goods_value['values'] = explode('-', $goods_value['content']);
            
            $format_select_value = array();
            
            foreach($format_list as $v){
                foreach($v['values'] as $vv){
                    if(in_array($vv['id'], $goods_value['values'])){
                        $format_select_value[$vv['format_id']] = $vv['id'];
                    }
                }
            }
            
            foreach($format_list as $v){
                $_is_have = false;
                $_values = array();
                $__vids = $format_select_value;
                foreach($v['values'] as $vv){                    
                    if(in_array($vv['id'], $goods_value_ids)){
                        $__vids[$vv['format_id']] = $vv['id'];
                        asort($__vids);
                        if($_val = $content_format_values[implode('-', $__vids)]){
                            $_is_have = true;
                            $vv['vid'] = $_val['id'];
                            $_values[$vv['id']] = $vv; 
                        }
                        if(in_array($vv['id'], $format_select_value)){
                            $vv['checked'] = true;
                        }
                        $_is_have = true;
                        $_values[$vv['id']] = $vv;                        
                    }
                    $v['values'] = $_values;
                }                
                if($_is_have){
                    $_format_list[$v['id']] = $v;
                }
            }
            
            $format_list = $_format_list; 
            
            $detail['value'] = $goods_value;
             
            if($goods_value['photo']){
                $detail['photo'] = $goods_value['photo'];
            }
            $detail['kucun'] = $goods_value['store'];
            $detail['mall_price'] = $goods_value['mall_price'];
            $detail['weight'] = $goods_value['weight'];
        }
        $detail['vid'] = $goods_vid;
        D('Goods')->updateCount($goods_id, 'views');
        D('Weidiandetails')->updateCount($weidian_id, 'view');
        $this->assign('detail', $detail);
        $this->assign('goods_vid', $goods_vid);
        $this->assign('goods_value', $goods_value);
        $this->assign('format_list',$format_list);
        $this->display();
    }



    public function stage($goods_id=0,$vid=0){
        $goods_id = (int) $goods_id;
        $vid = (int)$vid;
        if (empty($goods_id)) {
            $this->error('请选择产品');
        }
        if (!$detail = D('Goods')->find($goods_id)) {
            $this->error('该商品不存在');
        }
        if ($detail['closed'] != 0 || $detail['audit'] != 1) {
            $this->error('该商品不存在');
        }
        if($g_value_list = D('Goodsformat')->where(array('goods_id'=>$goods_id))->select()){
            $goods_value_list = array();
            foreach($g_value_list as $v){
                $goods_value_list[$v['id']] = $v;
            }
            
            if(!$goods_value = $goods_value_list[$vid]){
                 $this->error('你没有选择规格');
            }else if($goods_value_list[$vid]['goods_id'] != $goods_id){
                  $this->error('该商品配置信息不正确');
            }
            $detail['mall_price'] = $goods_value_list[$vid]['mall_price'];
            $detail['photo'] = $goods_value_list[$vid]['photo'];
            $detail['kucun'] = $goods_value_list[$vid]['store'];
            $detail['weight'] = $goods_value_list[$vid]['weight'];
        }
        $sstage = D('Interest')->where(array('goods_id'=>$goods_id,'type'=>1))->order('periods asc')->select();
        foreach($sstage as $s){
            $sstages[$s['id']] = $s;
        }
        foreach($sstages as $s){
            $sstages[$s['id']]['money'] = ceil($detail['mall_price']/$s['periods']*(1+$s['interest']/1000));
        }
        $kstage = D('Interest')->where(array('goods_id'=>$goods_id,'type'=>0))->order('periods asc')->select();
        foreach($kstage as $k){
            $kstages[$k['id']] = $k;
        }
        foreach($kstages as $k){
            $kstages[$k['id']]['money'] = ceil($detail['mall_price']/$k['periods']*(1+$k['interest']/1000));
        }
        $this->assign('sstages', $sstages);
        $this->assign('kstages', $kstages);
        $this->assign('detail', $detail);
        $this->assign('vid', $vid);
        $this->mobile_title = '选择分期';
        $this->display();
    }

    public function cartadd($goods_id=0,$vid=0,$insterest_id=0){
        if (empty($this->uid)) {
            AppJump();
        }
        $goods_id = $this->_post('goods_id', false);
        if (empty($goods_id)) {
            $this->ajaxReturn(array('ret' => 1, 'msg' => '请选择产品'));
        }
        if (!$detail = D('Goods')->find($goods_id)) {
            $this->ajaxReturn(array('ret' => 1, 'msg' => '该商品不存在'));
        }
        if ($detail['closed'] != 0 || $detail['audit'] != 1) {
            $this->ajaxReturn(array('ret' => 1, 'msg' => '该商品不存在'));
        }
        $vid = $this->_post('vid', false);
        $interest_id = $this->_post('interest_id', false);
        if (empty($interest_id)) {
            $this->ajaxReturn(array('ret' => 1, 'msg' => '请选择分期'));
        }
        $interest = D('Interest')->find($interest_id);
        if ($interest['goods_id']!=$goods_id) {
            $this->ajaxReturn(array('ret' => 1, 'msg' => '商品分期信息错误'));
        }
        $cart_goods = D('GoodsCart')->where(array('goods_id'=>$goods_id,'user_id'=>$this->uid,'v_id'=>$vid,'is_stage'=>$interest_id))->find();
        if($g_value_list = D('Goodsformat')->where(array('goods_id'=>$goods_id))->select()){
            $goods_value_list = array();
            foreach($g_value_list as $v){
                $goods_value_list[$v['id']] = $v;
            }
            if(!$goods_value = $goods_value_list[$vid]){
                $this->ajaxReturn(array('ret' => 1, 'msg' => '你没有选择规格'));
            }else if($goods_value_list[$vid]['goods_id'] != $goods_id){
                $this->ajaxReturn(array('ret' => 1, 'msg' => '该商品配置信息不正确'));
            }
            $detail['kucun'] = $goods_value['store'];
        }
        if(empty($cart_goods)){
            
            if($detail['kucun']>0){
                $data['goods_id'] = $goods_id;
                $data['user_id'] = $this->uid;  
                $data['v_id'] = $vid;  
                $data['is_stage'] = $interest_id;  
                $data['weidian_id'] = $detail['weidian_id'];  
                $data['num'] = 1; 
                D('GoodsCart')->add($data);
            
            }else{
                $this->ajaxReturn(array('ret' => 1, 'msg' => '商品库存不足'));
            }
        }else{
            if($detail['kucun']>$cart_goods['num']){
                $data['id'] = $cart_goods['id'];
                $data['num']= $cart_goods['num']+1;
                D('GoodsCart')->save($data);
            }else{
                $this->ajaxReturn(array('ret' => 1, 'msg' => '商品库存不足'));
            }
        }
        $url =  U('mall/detail', array('goods_id' => $goods_id,'vid'=>$vid));
        die(json_encode(array('ret'=>0,'url'=>$url,'msg' => '添加购物车成功')));
    }


    public function cartadd2($goods_id=0,$vid=0,$insterest_id=0){
        if (empty($this->uid)) {
            AppJump();
        }
        $goods_id = (int) $goods_id;
        if (empty($goods_id)) {
            $this->error('请选择产品');
        }
        if (!$detail = D('Goods')->find($goods_id)) {
            $this->error('该商品不存在');
        }
        if ($detail['closed'] != 0 || $detail['audit'] != 1) {
            $this->error('该商品不存在');
        }
        $vid = (int)$vid;
        $interest_id = 0;
        $cart_goods = D('GoodsCart')->where(array('goods_id'=>$goods_id,'user_id'=>$this->uid,'v_id'=>$vid,'is_stage'=>$interest_id))->find();
        if($g_value_list = D('Goodsformat')->where(array('goods_id'=>$goods_id))->select()){
            $goods_value_list = array();
            foreach($g_value_list as $v){
                $goods_value_list[$v['id']] = $v;
            }
            if(!$goods_value = $goods_value_list[$vid]){
                $this->error('你没有选择规格');
            }else if($goods_value_list[$vid]['goods_id'] != $goods_id){
                $this->error('该商品配置信息不正确');
            }
            $detail['kucun'] = $goods_value['store'];
        }
        if(empty($cart_goods)){
            
            if($detail['kucun']>0){
                $data['goods_id'] = $goods_id;
                $data['user_id'] = $this->uid;  
                $data['v_id'] = $vid;  
                $data['is_stage'] = $interest_id;  
                $data['weidian_id'] = $detail['weidian_id'];  
                $data['num'] = 1; 
                D('GoodsCart')->add($data);  
            }else{
                $this->error('商品库存不足');
            }
        }else{
            if($detail['kucun']>$cart_goods['num']){
                $data['id'] = $cart_goods['id'];
                $data['num']= $cart_goods['num']+1;
                D('GoodsCart')->save($data);
            }else{
                $this->error('商品库存不足');
            }
        }
        $this->success('加入购物车成功');
    }
    public function buy($goods_id=0,$vid=0,$insterest_id=0){
        if (empty($this->uid)) {
            AppJump();
        }
        $goods_id = (int) $goods_id;
        if (empty($goods_id)) {
            $this->error('请选择产品');
        }
        if (!$detail = D('Goods')->find($goods_id)) {
            $this->error('该商品不存在');
        }
        if ($detail['closed'] != 0 || $detail['audit'] != 1) {
            $this->error('该商品不存在');
        }
        $vid = (int)$vid;
        $interest_id = 0;
        $cart_goods = D('GoodsCart')->where(array('goods_id'=>$goods_id,'user_id'=>$this->uid,'v_id'=>$vid,'is_stage'=>$interest_id))->find();
        if($g_value_list = D('Goodsformat')->where(array('goods_id'=>$goods_id))->select()){
            $goods_value_list = array();
            foreach($g_value_list as $v){
                $goods_value_list[$v['id']] = $v;
            }
            if(!$goods_value = $goods_value_list[$vid]){
                $this->error('你没有选择规格');
            }else if($goods_value_list[$vid]['goods_id'] != $goods_id){
                $this->error('该商品配置信息不正确');
            }
            $detail['kucun'] = $goods_value['store'];
        }
        if(empty($cart_goods)){
            
            if($detail['kucun']>0){
                $data['goods_id'] = $goods_id;
                $data['user_id'] = $this->uid;  
                $data['v_id'] = $vid;  
                $data['is_stage'] = $interest_id;  
                $data['weidian_id'] = $detail['weidian_id'];  
                $data['num'] = 1; 
                D('GoodsCart')->add($data);  
            }else{
                $this->error('商品库存不足');
            }
        }else{
            if($detail['kucun']>$cart_goods['num']){
                $data['id'] = $cart_goods['id'];
                $data['num']= $cart_goods['num']+1;
                D('GoodsCart')->save($data);
            }else{
                $this->error('商品库存不足');
            }
        }
        header("Location:" . U('mall/cart'));
        die;
    }

    public function cart() {
        $kucun = true;
        $cart_goods = D('GoodsCart')->where(array('user_id'=>$this->uid))->select();
        if (empty($cart_goods)) {
            $this->error("亲还没有选购产品呢!", U('mall/index'));
        }
        $total_num = 0;
        foreach($cart_goods as $k => $v){
            $goods_ids[$v['goods_id']] = $v['goods_id'];
            $value_ids[$v['v_id']] = $v['v_id'];
            $interest_ids[$v['is_stage']] = $v['is_stage'];
        }
        $goods_list = $goods_value_list = $goods_interest_list = array();
        if($goods_ids){
            if($value_ids){
                $values_list = D('Goodsformat')->where(array('id'=>array('IN', implode(',', $value_ids))))->select();
                foreach($values_list as $v){
                    $goods_value_list[$v['id']] = $v;
                }
            }
            if($interest_ids){
                $interests_list = D('Interest')->where(array('id'=>array('IN', implode(',', $interest_ids))))->select();
                foreach($interests_list as $v){
                    $goods_interest_list[$v['id']] = $v;
                }
            }
            if($_goods_list = D('Goods')->where(array('goods_id'=>array('IN', implode(',', $goods_ids))))->select()){
                foreach($_goods_list as $v){
                    if ($v['closed'] != 0 || $v['audit'] != 1) {
                        continue;
                    }                       
                    $goods_list[$v['goods_id']] = $v;
                }
            }
            $new_row = $_cart_goods = $weidians = array();
                
            foreach($cart_goods as $k=>$v){
                $row = $value = array();
                $row = $goods_list[$v['goods_id']];
                $gid = $v['goods_id'];
                $vid = $v['v_id'];
                $interestid = $v['is_stage'];
                if($vid){
                    if($goods_value_list[$vid]['goods_id'] != $gid){
                        continue;
                    }
                    $value = $goods_value_list[$vid];
                    $row['mall_price'] = $value['mall_price'];
                    $total_price += $value['mall_price'] * (int)$v['num'];
                }else{
                    $total_price += $row['mall_price'] * (int)$v['num'];
                }
                if($interestid){
                    if($goods_interest_list[$interestid]['goods_id'] != $gid){
                        continue;
                    }
                    $interest = $goods_interest_list[$interestid];
                    $interest['money'] = ceil($row['mall_price']/$interest['periods']*(1+$interest['interest']/1000));
                }
                $row['num'] = (int)$v['num'];
                $row['vid'] = $vid;
                $total_num += (int)$v['num'];
                if($value){
                    $row['store'] = $row['kucun'] = $value['store'];
                    $row['weight'] = $value['weight'];
                    $row['mall_price'] = $value['mall_price'];
                    $row['total_price'] = $row['mall_price'] * $row['num'];
                }else{
                    $row['store'] = $row['kucun'];
                    $row['total_price'] = $row['mall_price'] * $row['num'];
                }
                if($value['photo']){
                    $row['photo'] = $value['photo'];
                }
                $row['value'] = $value;
                $row['interest'] = $interest;
                var_dump($interestid,$row['interest']);
                $detail = D('Weidiandetails')->where('id = '. $row['weidian_id'])->find();
                $row = array_merge($goods_list[$gid], $row);
                $weidians[$row['weidian_id']] = $detail;
                $new_row[$row['weidian_id']][$k] = $row;
                    // $_cart_goods[$k] = $row;
                    // $json_cart_goods[$row['goods_id']."_".$vid] = $row['kucun'];
                if($row['num'] > $row['kucun']){
                    $kucun = false;
                }
            } 
        }
        if(!$kucun){
            $this->error('商品库存不足');
        }
        $this->assign('total_num',$total_num);
        $this->assign('total_price',$total_price);
        $this->assign('weidians',$weidians);
        $this->assign('cart_goods',$new_row);
            // $this->assign('json_cart_goods',json_encode($json_cart_goods));
        $this->mobile_title = '购物车';
        // var_dump($new_row);
        // $this->display();
    }

    // public function cartadd($goods_id=0, $vid=0) {
    //     $goods_id = (int) $goods_id;
    //     $vid = (int)$vid;
    //     $sku_id = $goods_id.'_'.$vid;   
        
    //     if (empty($goods_id)) {
    //         $this->error('请选择产品');
    //     }
    //     if (!$detail = D('Goods')->find($goods_id)) {
    //         $this->error('该商品不存在');
    //     }
    //     if ($detail['closed'] != 0 || $detail['audit'] != 1) {
    //         $this->error('该商品不存在');
    //     }
    //     $cart_goods = cookie('goods'); //存cookie
    //     $num = (int) $this->_get('num');
    //     if (empty($num) || $num <= 0) {
    //         $num = 1;
    //     }
    //     if ($num > 99) {
    //         $num = 99;
    //     }
    //     if($g_value_list = D('Goodsformat')->where(array('goods_id'=>$goods_id))->select()){
            
    //         $goods_value_list = array();
          
            
    //         foreach($g_value_list as $v){
    //             $goods_value_list[$v['id']] = $v;
    //         }
            
    //         if(!$goods_value = $goods_value_list[$vid]){
    //              $this->error('你没有选择规格');
    //         }else if($goods_value_list[$vid]['goods_id'] != $goods_id){
    //               $this->error('该商品配置信息不正确');
    //         }
    //         $cart_goods[$sku_id] = (int)$cart_goods[$sku_id] + $num;
            
    //         if($cart_goods[$sku_id] > $goods_value['store']){
    //              $this->error('商品库存不足');
    //         }
    //     }else if($cart_goods[$sku_id] > $detail['kucun']){
    //          $this->error('商品库存不足');
    //     }else{
    //         $cart_goods[$sku_id] = (int)$cart_goods[$sku_id] + $num;
    //     }
    //     cookie('goods', $cart_goods);
    //     $this->success('加入购物车成功');

    // }

    // public function buy($goods_id=0, $vid=0) {
    //     $goods_id = (int) $goods_id;
    //     $vid = (int)$vid;
    //     $sku_id = $goods_id.'_'.$vid;
        
    //     if (empty($goods_id)) {
    //         $this->error('请选择产品');
    //     }
    //     if (!$detail = D('Goods')->find($goods_id)) {
    //         $this->error('该商品不存在');
    //     }
    //     if ($detail['closed'] != 0 || $detail['audit'] != 1) {
    //         $this->error('该商品不存在');
    //     }
    //     $cart_goods = cookie('goods'); //存cookie
    //     $num = (int) $this->_get('num');
    //     if (empty($num) || $num <= 0) {
    //         $num = 1;
    //     }
    //     if ($num > 99) {
    //         $num = 99;
    //     }
    //     if($g_value_list = D('Goodsformat')->where(array('goods_id'=>$goods_id))->select()){
            
    //         $goods_value_list = array();
          
            
    //         foreach($g_value_list as $v){
    //             $goods_value_list[$v['id']] = $v;
    //         }
            
    //         if(!$goods_value = $goods_value_list[$vid]){
    //              $this->error('你没有选择规格');
    //         }else if($goods_value_list[$vid]['goods_id'] != $goods_id){
    //               $this->error('该商品配置信息不正确');
    //         }
    //         $cart_goods[$sku_id] = (int)$cart_goods[$sku_id] + $num;
            
    //         if($cart_goods[$sku_id] > $goods_value['store']){
    //              $this->error('商品库存不足');
    //         }
    //     }else if($cart_goods[$sku_id] > $detail['kucun']){
    //          $this->error('商品库存不足');
    //     }else{
    //         $cart_goods[$sku_id] = (int)$cart_goods[$sku_id] + $num;
    //     }
    //     cookie('goods', $cart_goods);
        
    //     header("Location:" . U('mall/cart'));
    //     die;
    // }

    // public function cart() {
    //         $kucun = true;
    //         $cart_goods = $cookie_cart_goods = cookie('goods'); 
    //         if (empty($cart_goods)) {
    //             $this->error("亲还没有选购产品呢!", U('mall/index'));
    //         }
    //         $total_num = 0;
    //         foreach($cart_goods as $k => $v){

    //             list($gid, $vid) = explode('_', $k);
    //             $gid = (int)$gid; $vid = (int)$vid;
    //             $goods_ids[$gid] = $gid;
    //             $value_ids[$vid] = $vid;
    //         }
    //         $goods_list = $goods_value_list = array();
    //         if($goods_ids){
    //             if($value_ids){
    //                 $_values_list = D('Goodsformat')->where(array('id'=>array('IN', implode(',', $value_ids))))->select();
    //                 foreach($_values_list as $v){
    //                     $goods_value_list[$v['id']] = $v;
    //                 }
    //             }
    //             if($_goods_list = D('Goods')->where(array('goods_id'=>array('IN', implode(',', $goods_ids))))->select()){
    //                 foreach($_goods_list as $v){
    //                     if ($v['closed'] != 0 || $v['audit'] != 1) {
    //                         continue;
    //                     }                       
    //                     $goods_list[$v['goods_id']] = $v;
    //                 }
    //             }
    //             $new_row = $_cart_goods = $weidians = array();
                
    //             foreach($cart_goods as $k=>$v){
    //                 list($gid, $vid) = explode('_', $k);
    //                 $gid = (int)$gid; $vid = (int)$vid;
    //                 $row = $value = array();
    //                 $row = $goods_list[$gid];
    //                 if($vid){
    //                     if($goods_value_list[$vid]['goods_id'] != $gid){
    //                         continue;
    //                     }
    //                     $value = $goods_value_list[$vid];
    //                     $total_price += $value['mall_price'] * (int)$v;
    //                 }else{
    //                     $total_price += $row['mall_price'] * (int)$v;
    //                 }
    //                 $row['num'] = (int)$v;
    //                 $row['vid'] = $vid;
    //                 $total_num += (int)$v;
    //                 if($value){
    //                     $row['store'] = $row['kucun'] = $value['store'];
    //                     $row['mall_price'] = $value['mall_price'];
    //                     $row['total_price'] = $row['mall_price'] * $row['num'];
    //                 }else{
    //                     $row['store'] = $row['kucun'];
    //                     $row['total_price'] = $row['mall_price'] * $row['num'];
    //                 }
    //                 if($value['photo']){
    //                     $row['photo'] = $value['photo'];
    //                 }
    //                 $row['value'] = $value;
    //                 $detail = D('Weidiandetails')->where('id = '. $row['weidian_id'])->find();
    //                 $row = array_merge($goods_list[$gid], $row);
    //                 $weidians[$row['weidian_id']] = $detail;
    //                 $new_row[$row['weidian_id']][$k] = $row;
    //                 $_cart_goods[$k] = $row;
    //                 $json_cart_goods[$row['goods_id']."_".$vid] = $row['kucun'];
    //                 if($row['num'] > $row['kucun']){
    //                     $kucun = false;
    //                 }
    //             } 
    //             $cart_goods = $_cart_goods;
    //         }
    //         if(!$kucun){
    //             $this->error('商品库存不足');
    //         }
    //         $this->assign('total_num',$total_num);
    //         $this->assign('total_price',$total_price);
    //         $this->assign('weidians',$weidians);
    //         $this->assign('cart_goods',$new_row);
    //         $this->assign('json_cart_goods',json_encode($json_cart_goods));
    //         $this->mobile_title = '购物车';
    //         $this->display();
    // }

    // public function pangmiao() {
    //     if (IS_AJAX) {
    //         $pang = $_POST['pang'];
    //         $v = (int) $_POST['v'];
    //         $goods = cookie('goods');
    //         $goods[$pang] = $v;
    //         cookie('goods',$goods);
    //         $this->ajaxReturn(array('status' => 'success','msg'=>'修改购物车成功'));
    //     }
    // }

    // public function cartdel2() {

    //     $goods_id = (int) $this->_get('goods_id');
    //     $vid = (int) $this->_get('vid');

    //     $goods = cookie('goods');
    //     $goods_vid = $goods_id . "_" . $vid;
    //     if (isset($goods[$goods_vid])) {

    //         unset($goods[$goods_vid]);

    //         cookie('goods', $goods);
    //     }

    //     header("Location:" . U('mall/cart'));
    // }

    // public function order() {
    //     if (empty($this->uid)) {
    //         AppJump();
    //     }
    //     $num = $this->_post('num', false);
    //     $vids = $goods_ids = array();
    //     $cart_goods = $goods_ids = $value_ids = array();
    //     foreach ($num as $k => $val) {
    //         if(($val = (int) $val) && ($val >0) && ($val < 100)){
    //             list($gid, $vid) = explode('_', $k);
    //             $gid = (int)$gid; $vid = (int)$vid;
    //             $goods_ids[$gid] = $gid;
    //             $value_ids[$vid] = $vid;
    //             $cart_goods[$k] = $val;
    //         }
    //     }
    //     if($value_ids){
    //         if($items = D('Goodsformat')->where(array('id'=>array('IN', implode(',', $value_ids))))->select()){
    //             foreach($items as $v){
    //                 $value_list[$v['id']] = $v;
    //             }
    //         }
    //     }

    //     if($goods_ids){
    //         if($items = D('Goods')->where(array('goods_id'=>array('IN', implode(',', $goods_ids))))->select()){                
    //             foreach($items as $v){
    //                 if ($v['closed'] != 0 || $v['audit'] != 1) {
    //                     continue;
    //                 }
    //                 $goods_list[$v['goods_id']] = $v;
    //             }
    //         }
    //     }
    //     $_cart_goods = $order_goods = array();
    //     foreach($cart_goods as $k=>$v){
    //         list($gid, $vid) = explode('_', $k);
    //         $gid = (int)$gid; $vid = (int)$vid;
    //         $val = $value = array();            
    //         if($val = $goods_list[$gid]){
    //             if($vid){
    //                 if($value_list[$vid]['goods_id'] != $gid){
    //                     continue;
    //                 }
    //                 $value = $value_list[$vid];
    //                 $val['kucun'] = $value['store'];
    //                 $val['mall_price'] = $value['mall_price'];
    //                 $total_price += $value['mall_price'] * (int)$v;
    //             }else{
    //                 $total_price += $val['mall_price'] * (int)$v;
    //             }
    //             $val['num'] = (int)$v;
    //             if($val['kucun'] < $val['num']){
    //                 $this->ajaxReturn(array('ret' => 1, 'msg' => $val['title'].'库存不足'));
    //             }
    //             $val['vid'] = $vid;
    //             $total_num += (int)$v;
    //             $val['value'] = $value;
    //             $val = array_merge($goods_list[$gid], $val);
    //             $_cart_goods[$k] = $val;
    //             $detail = D('Weidiandetails')->where('id = '. $val['weidian_id'])->find();

    //             $you[$detail['weidian_id']]['postage'] += $val['postage'];
    //             $you[$detail['weidian_id']]['shipping'] = $detail['shipping'];
    //             $you[$detail['weidian_id']]['total_price'] += $val['mall_price'] * $val['num'];

    //             $order_goods[$val['weidian_id']]['total_price'] += $val['mall_price'] * $val['num'];
    //             $order_goods[$val['weidian_id']]['goods'][$k] = $val;
    //         }
    //     }
    //     foreach ($you as $key => $value) {
    //         if($value['shipping']==0 || $value['postage'] > $value['total_price']){
    //             $order_goods[$key]['total_price'] += $value['postage'];
    //         }
    //     }
    //     if (empty($_cart_goods)){
    //         $this->ajaxReturn(array('ret' => 1, 'msg' => '订单错误:没有选择要购买的商品'));
    //     }
    //     $tui = cookie('tui');
    //     if (!empty($tui)) {//推广部分
    //         $tui = explode('_', $tui);
    //         $tuiguang = array('uid' => (int) $tui[0],'goods_id' => (int) $tui[1]);
    //     }  
    //     $order_ids = array();
    //     foreach($order_goods as $sid=>$val){
    //         $shop = D('shop')->find($sid);
    //         $a = array('weidian_id'=>$sid, 'user_id'=>$this->uid);
    //         $a['is_shop'] = (int) $shop['is_pei']; //是否由商家自己配送
    //         $a['create_time'] = time();
    //         $a['create_ip'] = get_client_ip();
    //         $a{'total_price'} = $val['total_price']; 
    //         if($order_id = D('Order')->add($a)){ //后期可以考虑加上事务回滚功能
    //             $order_ids[$order_id] = $order_id;
    //             foreach($val['goods'] as $k=>$v){
    //                 $b = array('order_id'=>$order_id, 'weidian_id'=>$sid, 'goods_id'=>$v['goods_id'], 'vid'=>$v['vid']);
    //                 if($tuiguang && $tuiguang['goods_id'] == $v['goods_id']){
    //                     $b['tui_uid'] = $tuiguang['uid'];
    //                 }
    //                 $b['num'] = $v['num'];
    //                 $b['price'] = $v['mall_price'];
    //                 $b['total_price'] = $v['mall_price'] * $v['num'];
    //                 $b['spec_name'] = $v['value']['format_title'];
    //                 $b['title'] = $v['title'];
    //                 if(!$photo = $v['value']['photo']){
    //                     $photo = $v['photo'];
    //                 }
    //                 $b['photo'] = $photo;
    //                 $b['create_time'] = time();
    //                 $b['create_ip'] = get_client_ip();                 
    //                 if(D('Ordergoods')->add($b)){ //扣除库存                        
    //                     if($v['vid'] && $v['rush_ltime'] > $now_time && $v['rush_kucun']>= $v['num']){ //抢购时减去抢购库存
    //                         D('Goods')->updateCount($v['goods_id'], 'rush_kucun', -$v['num']);
    //                     }
    //                     if($v['format']){
    //                         D('Goodsformat')->updateCount($v['vid'], 'store', -$v['num']);
    //                     }
    //                     D('Goods')->updateCount($v['goods_id'], 'kucun', -$v['num']);
    //                 }
    //             }                
    //         }  
    //     }
    //     $goods = cookie('goods');
    //     foreach ($num as $key => $value) {
    //         unset($goods[$key]);
    //     }
    //     cookie('goods',$goods);
    //     if (count($order_ids) > 1) {//如果大于1 那么形成一个 支付记录 来合并付款！如果其他条件可以直接去付款
    //         $need_pay = D('Order')->useIntegral($this->uid,$order_ids);
    //         $logs = array(
    //             'type' => 'goods',
    //             'user_id' => $this->uid,
    //             'order_id' => 0,
    //             'order_ids' => join(',', $order_ids),
    //             'code' => '',
    //             'need_pay' => $need_pay, //使用积分后的支付金额
    //             'create_time' => NOW_TIME,
    //             'create_ip' => get_client_ip(),
    //             'is_paid' => 0
    //         );
    //         $logs['log_id'] = D('Paymentlogs')->add($logs);
    //         $url = U('mall/paycode', array('log_id' => $logs['log_id']));
    //     } else {
    //         $url =  U('mall/pay', array('order_id' => $order_id));
    //     }
    //     die(json_encode(array('ret'=>0,'url'=>$url)));
    // }

    



     

    // public function support($goods_id){
    //     $goods_id = (int) $goods_id;
    //     $Goodscrowd = D('Goodscrowd');
    //     $Goods = D('Goods');
    //     $Goodslist = D('Goodslist');
    //     $Goodstype = D('Goodstype');
    //     if (!$goods = D('Goods')->find($goods_id)) {
    //         $this->error('您访问的产品不存在！');
    //     }
    //     if ($goods['closed'] != 0 || $goods['audit'] != 1) {
    //         $this->error('您访问的产品不存在！');
    //     }
    //     if (!$crowd = $Goodscrowd->find($goods_id)) {
    //         $this->error('您访问的产品不存在！');
    //     }
    //     $detail = array_merge($goods,$crowd);
    //     $shop = D('Shop')->find($detail['weidian_id']);
    //     $this->assign('shop', $shop);
    //     $goodstype = $Goodstype->where(array('goods_id' =>$goods_id))->order(array('price' => 'asc'))->select();
    //     $this->assign('goodstype', $goodstype);
    //     $this->assign('detail', $detail);
    //     $this->display();
    // }
    

    


    // public function ask($goods_id)
    // {
    //     $Goodsask = D('Goodsask');
    //     if (empty($this->uid)) {
    //         $this->ajaxLogin(); //提示异步登录
    //     }
    //     $goods_id = (int) $goods_id;
    //     if (!$detail = D('Goods')->find($goods_id)) {
    //         $this->error('该商品不存在');
    //     }
    //     $data['uid'] = $this->uid;
    //     $data['goods_id'] = $goods_id;
    //     $data['ask_c'] = $this->_post('ask_c', 'htmlspecialchars');
    //     $data['dateline'] = time();

    //     if ($ask_id = $Goodsask->add($data)) {
    //         $this->baoMsg('提交成功');
    //     }
        
    // }

    // public function favorites($goods_id)
    // {
    //     $Goodsfollow = D('Goodsfollow');
    //     $Goodscrowd = D('Goodscrowd');
    //     if (empty($this->uid)) {
    //          AppJump();
    //     }
    //     $goods_id = (int) $goods_id;
    //     if (!$detail = D('Goods')->find($goods_id)) {
    //         $this->error('该商品不存在');
    //     }
    //     $data['type'] = 'follow';
    //     $data['goods_id'] = $goods_id;
    //     $data['uid'] = $this->uid;
    //     $data['dateline'] = time();
    //     $map = array('type'=>'follow','uid'=>$this->uid,'goods_id'=>$goods_id);
    //     if($Goodsfollow->where($map)->find()){
    //          $this->error('您已经收藏过了！');
    //     }else if ($ask_id = $Goodsfollow->add($data)) {
    //         $Goodscrowd->updateCount($goods_id, 'follow_num');
    //          $this->success('恭喜您收藏成功！', U('mall/crowdDetail', array('goods_id' => $goods_id)));
    //     }
    // }
 
    // public function detail_c($goods_id)
    // {
    //     $Goods = D('Goods');
    //     $Goodscrowd = D('Goodscrowd');
    //     $map = array('closed' => 0, 'audit' => 1,'type'=>'crowd', 'city_id' => $this->city_id,'goods_id' => array('NEQ',$goods_id), 'ltime' => array('GT', time()));
       
    //     $list = $Goods->where($map)->order($orderby)->limit('20')->select();
    //     shuffle($list);$num = 0;
    //     foreach($list as $k => $v){
    //         $num++;
    //         if($num<5){
    //             $arr[] = $v['goods_id'];
    //             $arrs[] = $v;
    //         }
    //     }
        
    //     $details['goods_id'] = array('IN',implode(',',$arr));
    //     $crowd = $Goodscrowd->where($details)->select();
    //     $items = $Goodscrowd->merge($arrs,$crowd);
    //     $this->assign('itemss', $items); // 赋值分页输出
    //     $this->display();
    // }

    // public function buycrowd($goods_id)
    // {
    //     $Goodscrowd = D('Goodscrowd');
    //     $Goodstype = D('Goodstype');
    //     if (empty($this->uid)) {
    //         AppJump();
    //     }
    //     $data = $this->_post('data');
    //     $goods_id = (int) $goods_id;
    //     if (!$detail = D('Goods')->find($goods_id)) {
    //         $this->error('该商品不存在');
    //     }
    //     if (!$type = $Goodstype->find($data['type_id'])) {
    //         $this->error('该商品不存在');
    //     }

    //     if(!$data['name']){
    //         $this->error('姓名不能为空');
    //     }
    //     if(!$data['mobile']){
    //         $this->error('手机号码不能为空');
    //     }
    //     if(!$data['addr']){
    //         $this->error('地址不能为空');
    //     }

    //     if ($this->member['money'] < $type['price']) {
    //         $this->error('抱歉，您的余额不足', U('mcenter/money/index'));
    //     }
    //     if (false !== $Goodscrowd->buy($goods_id, $this->uid, $data,$type)) {
    //         $this->success('众筹成功', U('mall/crowdDetail',array('goods_id'=>$goods_id)));
    //     } else {
    //         $this->error('众筹失败', U('mall/crowdDetail',array('goods_id'=>$goods_id)));
    //     }
    // }

    

    

  

    

    


    

    

    // public function cartadd2() {
    //     if (IS_AJAX) {
    //         $weidian_id = (int) $_POST['weidian_id'];
    //         $goods_id = (int) $_POST['goods_id'];
    //         $vid = (int)$_POST['vid'];
    //         $sku_id = $goods_id.'_'.$vid;
    //         if (empty($goods_id)) {
    //             $this->ajaxReturn(array('status' => 'error', 'msg' => '请选择商品'));
    //         }
    //         if (!$detail = D('Goods')->find($goods_id)) {
    //             $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品不存在'));
    //         }
    //         if ($detail['closed'] != 0 || $detail['audit'] != 1) {
    //             $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品不存在'));
    //         }
    //         if ($detail['end_date'] < TODAY) {
    //             $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品已经过期，暂时不能购买'));
    //         }
    //         if (!$detail['kucun'] > 0) {
    //             $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品已售空'));
    //         }

    //         $cart_goods = cookie('goods');
    //         $num = (int) $this->_get('num');
    //         if (empty($num) || $num <= 0) {
    //             $num = 1;
    //         }
    //         if ($num > 99) {
    //             $num = 99;
    //         }
    //         if(!empty($cart_goods)){
    //             if($g_value_list = D('Goodsformat')->where(array('goods_id'=>$goods_id))->select()){
    //                 $goods_value_list = array();
    //                 foreach($g_value_list as $v){
    //                     $goods_value_list[$v['id']] = $v;
    //                 }
    //                 if(!$goods_value = $goods_value_list[$vid]){
    //                     $this->ajaxReturn(array('status' => 'error', 'msg' => '你没有选择规格'));
    //                 }else if($goods_value_list[$vid]['goods_id'] != $goods_id){
    //                     $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品配置信息不正确'));
    //                 }
    //                 if($cart_goods[$sku_id] >= $goods_value['store']){
    //                     $this->ajaxReturn(array('status' => 'error', 'msg' => '商品库存不足'));
    //                 }
    //             }else if($cart_goods[$sku_id] >= $detail['kucun']){
    //                 $this->ajaxReturn(array('status' => 'error', 'msg' => '商品库存不足'));
    //             }
    //             $cart_goods[$sku_id] = (int)$cart_goods[$sku_id] + $num;
    //             foreach ($cart_goods as $k => $v) {
    //                 $nn += $v;
    //             }
    //         }else{
    //             $cart_goods[$sku_id] = $num;
    //             $nn = 1;
    //         }
            

    //         cookie('goods', $cart_goods);
    //         $this->ajaxReturn(array('status' => 'success', 'msg' => '加入购物车成功', 'num' => $nn));
    //     }
    // }
    
    

    

    

    // public function cartdel() {

    //     $goods_id = (int) $this->_get('goods_id');

    //     $goods = cookie('goods');

    //     if (isset($goods[$goods_id])) {

    //         unset($goods[$goods_id]);

    //         cookie('goods', $goods);
    //     }

    //     die('0');
    // }

    

    // public function neworder() {

    //     $goods = $this->_get('goods');

    //     $goods = explode(',', $goods);

    //     if (empty($goods)) {

    //         $this->error('亲购买点吧');
    //     }

    //     $datas = array();

    //     foreach ($goods as $val) {

    //         $good = explode('-', $val);

    //         $good[1] = (int) $good[1];

    //         if (empty($good[0]) || empty($good[1])) {

    //             $this->error('亲购买点吧');
    //         }

    //         if ($good[1] > 99 || $good[1] < 0) {

    //             $this->error('本店不支持批发');
    //         }

    //         $datas[$good[0]] = $good[1];
    //     }

    //     cookie('goods', $datas);

    //     header("Location:" . U('mall/cart'));

    //     die;
    // }

    

    // public function paycode() {

    //     $log_id = (int) $this->_get('log_id');

    //     if (empty($log_id)) {

    //         $this->error('没有有效支付记录！');
    //     }

    //     if (!$detail = D('Paymentlogs')->find($log_id)) {

    //         $this->error('没有有效的支付记录！');
    //     }

    //     if ($detail['is_paid'] != 0 || empty($detail['order_ids']) || !empty($detail['order_id']) || empty($detail['need_pay'])) {

    //         $this->error('没有有效的支付记录！');
    //     }

    //     $order_ids = explode(',', $detail['order_ids']);

    //     $ordergood = D('Ordergoods')->where(array('order_id' => array('IN', $order_ids)))->select();



    //     $goods_id = $weidian_ids = array();



    //     foreach ($ordergood as $k => $val) {

    //         $goods_id[$val['goods_id']] = $val['goods_id'];

    //         $weidian_ids[$val['weidian_id']] = $val['weidian_id'];
    //     }
    //     $weidians = D('Weidiandetails')->where(array('id'=>array('IN',$weidian_ids)))->select();
    //     $goods = D('Goods')->itemsByIds($goods_id);
    //     foreach ($weidians as $key => $value) {
    //         foreach ($goods as $key1 => $value1) {
    //             if($value['weidian_id'] == $value1['weidian_id']){
    //                 $weidians[$key]['goods'][$key1] = $value1;
    //             }
    //         }
    //     }


    //     $this->assign('goods', $goods);

    //     $this->assign('shops', D('Shop')->itemsByIds($weidian_ids));
    //     $this->assign('weidians', $weidians);
    //     $this->assign('ordergoods', $ordergood);

    //     $this->assign('useraddr', D('Useraddr')->where(array('user_id' => $this->uid))->limit()->select());

    //     $this->assign('payment', D('Payment')->getPayments());

    //     $this->assign('logs', $detail);
    //     $this->mobile_title = '订单支付';
        
    //     $this->display(); 
    // }

    // public function pay() {

    //     if (empty($this->uid)) {
    //         AppJump();
    //     }
    //     $this->check_mobile();

    //     $order_id = (int) $this->_get('order_id');

    //     $order = D('Order')->find($order_id);

    //     if (empty($order) || $order['status'] != 0 || $order['user_id'] != $this->uid) {

    //         $this->error('该订单不存在');

    //         die;
    //     }

    //     $ordergood = D('Ordergoods')->where(array('order_id' => $order_id))->select();



    //     $goods_id = $weidian_ids = array();



    //     foreach ($ordergood as $k => $val) {

    //         $goods_id[$val['goods_id']] = $val['goods_id'];

    //         $weidian_ids[$val['weidian_id']] = $val['weidian_id'];
    //     }

    //     $weidians = D('Weidiandetails')->where(array('id'=>array('IN',$weidian_ids)))->select();
    //     $goods = D('Goods')->itemsByIds($goods_id);
    //     foreach ($weidians as $key => $value) {
    //         foreach ($goods as $key1 => $value1) {
    //             if($value['weidian_id'] == $value1['weidian_id']){
    //                 $weidians[$key]['goods'][$key1] = $value1;
    //             }
    //         }
    //     }
    //     $this->assign('goods', D('Goods')->itemsByIds($goods_id));

    //     $this->assign('shops', D('Shop')->itemsByIds($weidian_ids));
    //     $this->assign('weidians', $weidians);
    //     $this->assign('ordergoods', $ordergood);

    //     $this->assign('useraddr', D('Useraddr')->where(array('user_id' => $this->uid))->limit()->select());

    //     $this->assign('order', $order);

    //     $this->assign('payment', D('Payment')->getPayments(true));
    //     $this->mobile_title = '订单支付';
    //     $this->display();
    // }

    // public function paycode2() { //这里是因为原来的是按订单付，这里是合并付款逻辑部分 
    //     $log_id = (int) $this->_get('log_id');
    //     // var_dump($_POST);
    //     if (empty($log_id)) {

    //         $this->error('没有有效支付记录！');
    //     }

    //     if (!$detail = D('Paymentlogs')->find($log_id)) {

    //         $this->error('没有有效的支付记录！');
    //     }

    //     if ($detail['is_paid'] != 0 || empty($detail['order_ids']) || !empty($detail['order_id']) || empty($detail['need_pay'])) {

    //         $this->error('没有有效的支付记录！');
    //     }
    //     $wuliu = $this->_post('wuliu');
    //     $order_ids = explode(',', $detail['order_ids']);

    //     foreach ($order_ids as $key => $value) {
    //         $order = D('order')->where('order_id ='. $value)->find();
    //         $data = array();
    //         foreach ($wuliu as $key1 => $value1) {
    //             if($key1 == $order['weidian_id']){
    //                 $data['order_id'] = $order['order_id'];
    //                 $data['mall_type'] = $value1;
    //                 D('order')->save($data);
    //             }
    //         }
    //     }
        
    //     $addr_id = (int) $this->_post('addr_id');

    //     if (empty($addr_id)) {

    //         $this->error('请选择一个要配送的地址！');
    //     }

    //     D('Order')->where(array('order_id' => array('IN', $order_ids)))->save(array('addr_id' => $addr_id));

    //     if (!$code = $this->_post('code')) {

    //         $this->error('请选择支付方式！');
    //     }

    //     if ($code == 'wait') { //如果是货到付款
    //         D('Order')->save(array(
    //             'is_daofu' => 1,
    //                 ), array('where' => array('order_id' => array('IN', $order_ids))));

    //         D('Ordergoods')->save(array(
    //             'is_daofu' => 1,
    //                 ), array('where' => array(
    //                 'order_id' => array('IN', $order_ids)
    //         )));

    //         D('Payment')->mallSold($order_ids);

    //         D('Payment')->mallPeisong(array($order_ids), 1);

    //         D('Sms')->mallTZshop($order_ids);

    //         $this->success('恭喜您下单成功！', U('mcenter/goods/index'));
    //     } else {

    //         $payment = D('Payment')->checkPayment($code);

    //         if (empty($payment)) {

    //             $this->error('该支付方式不存在');
    //         }

    //         $detail['code'] = $code;

    //         D('Paymentlogs')->save($detail);
            
    //         header("Location:" . U('mall/combine', array('log_id' => $detail['log_id'])));
    //     }
    // }

    // public function combine() {

    //     if (empty($this->uid)) {

    //         AppJump();
    //     }

    //     $log_id = (int) $this->_get('log_id');

    //     if (!$detail = D('Paymentlogs')->find($log_id)) {

    //         $this->error('没有有效的支付记录！');
    //     }

    //     if ($detail['is_paid'] != 0 || empty($detail['order_ids']) || !empty($detail['order_id']) || empty($detail['need_pay'])) {

    //         $this->error('没有有效的支付记录！');
    //     }

    //     $this->assign('button', D('Payment')->getCode($detail));

    //     $this->assign('logs', $detail);
    //     $this->mobile_title = '订单支付';
    //     $this->display();
    // }

    // public function pay2() {
    //     if (empty($this->uid)) {

    //         AppJump();
    //     }
    //     $order_id = (int) $this->_get('order_id');

    //     $order = D('Order')->find($order_id);

    //     if (empty($order) || $order['status'] != 0 || $order['user_id'] != $this->uid) {

    //         $this->error('该订单不存在');
    //     }



    //     $addr_id = (int) $this->_post('addr_id');



    //     if (empty($addr_id)) {

    //         $this->error('请选择一个要配送的地址！');
    //     }

    //     D('Order')->save(array('addr_id' => $addr_id, 'order_id' => $order_id));

    //     if (!$code = $this->_post('code')) {

    //         $this->error('请选择支付方式！');
    //     }



    //     $ua = D('UserAddr');

    //     $uaddr = $ua->where(array('addr_id' => $order['addr_id']))->find();



    //     if ($code == 'wait') { //如果是货到付款
    //         D('Order')->save(array(
    //             'order_id' => $order_id,
    //             'is_daofu' => 1,
    //         ));

    //         D('Ordergoods')->save(array(
    //             'is_daofu' => 1,
    //                 ), array('where' => array(
    //                 'order_id' => $order_id
    //         )));



    //         D('Payment')->mallSold($order_id);

    //         D('Payment')->mallPeisong(array($order_id), 1);

    //         $goods_ids = D('Ordergoods')->where("order_id={$order_id}")->getField('goods_id', true);

    //         $goods_ids = implode(',', $goods_ids);

    //         $map = array('goods_id' => array('in', $goods_ids));

    //         $goods_name = D('Goods')->where($map)->getField('title', true);

    //         $goods_name = implode(',', $goods_name);

    //         //====================微信支付通知===========================

    //         include_once "Kintime/Lib/Net/Wxmesg.class.php";
    //         /* 微信订单通知消息-开始 */
    //         $notice_data = array(
    //             'url' => 'http://' . $_SERVER['HTTP_HOST'] . '/mcenter/goods/index/aready/' . $order_id . '.html',
    //             'first' => '亲,您的订单创建成功!',
    //             'remark' => '详情请登录-http://' . $_SERVER['HTTP_HOST'],
    //             'amount' => round($order['total_price'] / 100, 2) . '元',
    //             'order' => $order_id,
    //             'info' => $goods_name
    //         );
    //         $notice_data = Wxmesg::notice($notice_data);
    //         Wxmesg::net($this->uid, 'OPENTM206930158', $notice_data);
    //         /* 微信订单通知消息-结束 */
    //         //====================微信支付通知===========================
    //         $this->success('恭喜您下单成功！', U('mcenter/goods/index'));
    //     } else {

    //         $payment = D('Payment')->checkPayment($code);

    //         if (empty($payment)) {

    //             $this->error('该支付方式不存在');
    //         }

    //         $logs = D('Paymentlogs')->getLogsByOrderId('goods', $order_id);
    //         $need_pay = D('Order')->useIntegral($this->uid, array($order_id));
    //         if (empty($logs)) {

    //             $logs = array(
    //                 'type' => 'goods',
    //                 'user_id' => $this->uid,
    //                 'order_id' => $order_id,
    //                 'code' => $code,
    //                 'need_pay' => $need_pay,
    //                 'create_time' => NOW_TIME,
    //                 'create_ip' => get_client_ip(),
    //                 'is_paid' => 0
    //             );

    //             $logs['log_id'] = D('Paymentlogs')->add($logs);
    //         } else {

    //             $logs['need_pay'] = $need_pay;

    //             $logs['code'] = $code;

    //             D('Paymentlogs')->save($logs);
    //         }

    //         $goods_ids = D('Ordergoods')->where("order_id={$order_id}")->getField('goods_id', true);
    //         $wuliu = $this->_post('wuliu');
    //         foreach ($wuliu as $key1 => $value1) {
    //             $data = array();
    //             if($key1 == $order['weidian_id']){
    //                 $data['order_id'] = $order['order_id'];
    //                 $data['mall_type'] = $value1;
    //                 D('order')->save($data);
    //             }
    //         }
            
    //         $goods_ids = implode(',', $goods_ids);
    //         $map = array('goods_id' => array('in', $goods_ids));

    //         $goods_name = D('Goods')->where($map)->getField('title', true);

    //         $goods_name = implode(',', $goods_name);

    //         //====================微信支付通知===========================
    //         include_once "Kintime/Lib/Net/Wxmesg.class.php";
    //         /* 微信订单通知消息-开始 */
    //         $notice_data = array(
    //             'url' => 'http://' . $_SERVER['HTTP_HOST'] . '/mcenter/goods/index/aready/' . $order_id . '.html',
    //             'first' => '亲,您的订单创建成功!',
    //             'remark' => '详情请登录-http://' . $_SERVER['HTTP_HOST'],
    //             'amount' => round($order['total_price'] / 100, 2) . '元',
    //             'order' => $order_id,
    //             'info' => $goods_name
    //         );
    //         $notice_data = Wxmesg::notice($notice_data);
    //         Wxmesg::net($this->uid, 'OPENTM206930158', $notice_data);
    //         /* 微信订单通知消息-结束 */
    //         //====================微信支付通知==============================
            
    //             header("Location:" . U('payment/payment', array('log_id' => $logs['log_id'])));
            
    //     }
    // }

}
