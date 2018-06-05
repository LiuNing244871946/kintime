<?php



class HotelAction extends CommonAction {
    
    public function _initialize() {
        parent::_initialize();
        $this->cates = D('Hotel')->getHotelCate();
        $this->assign('cates', $this->cates);
        $this->stars = D('Hotel')->getHotelStar();
        $this->assign('stars', $this->stars);
        $this->assign('roomtype',D('Hotelroom')->getRoomType());
    }

    
    private function check_hotel(){
        
        $hotel = D('Hotel');
        $res =  $hotel->where(array('mer_id'=>$this->mer_id))->find();
        if(!$res){
            $this->error('请先完善酒店资料！',U('hotel/set_hotel'));
        }elseif($res['audit'] == 0){
            $this->error('您的酒店申请正在审核中，请耐心等待！',U('hotel/set_hotel'));
        }elseif($res['audit'] == 2){
            $this->error('您的酒店申请未通过审核！',U('hotel/set_hotel'));
        }else{
            return $res['hotel_id'];
        }
        
    }
    
    public function index(){
        $hotel_id = $this->check_hotel();
        $hotelorder = D('Hotelorder');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('hotel_id' => $hotel_id);
        $map['closed'] = 0;
        if (($bg_date = $this->_param('bg_date', 'htmlspecialchars') ) && ($end_date = $this->_param('end_date', 'htmlspecialchars'))) {
            $bg_time = strtotime($bg_date);
            $end_time = strtotime($end_date);
            $map['create_time'] = array(array('ELT', $end_time), array('EGT', $bg_time));
            $this->assign('bg_date', $bg_date);
            $this->assign('end_date', $end_date);
        } else {
            if ($bg_date = $this->_param('bg_date', 'htmlspecialchars')) {
                $bg_time = strtotime($bg_date);
                $this->assign('bg_date', $bg_date);
                $map['create_time'] = array('EGT', $bg_time);
            }
            if ($end_date = $this->_param('end_date', 'htmlspecialchars')) {
                $end_time = strtotime($end_date);
                $this->assign('end_date', $end_date);
                $map['create_time'] = array('ELT', $end_time);
            }
        }
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['order_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        
        $count = $hotelorder->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $hotelorder->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $room_ids = array();
        foreach($list as $k=>$val){
            $room_ids[$val['room_id']] = $val['room_id'];
        }
        $this->assign('rooms',D('Hotelroom')->itemsByIds($room_ids));
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }
    
    
    public function set_hotel(){
        $obj = D('Hotel');
        $hotel = $obj->where(array('mer_id'=>$this->mer_id))->find();
        if ($this->isPost()) { 
           $data = $this->createCheck();
           $thumb = $this->_param('thumb', false);
            foreach ($thumb as $k => $val) {
                if (empty($val)) {
                    unset($thumb[$k]);
                }
                if (!isImage($val)) {
                    unset($thumb[$k]);
                }
            }
            if (empty($hotel)) {
                $data['create_time'] = NOW_TIME;
                $data['create_ip'] = get_client_ip();
                if($hotel_id = $obj->add($data)){
                    foreach($thumb as $k=>$val){
                        D('Hotelpics')->add(array('hotel_id'=>$hotel_id,'photo'=>$val));
                    }
                     $this->baoSuccess('设置成功', U('hotel/index'));
                }else{
                    $this->baoError('设置失败');
                }
            }else{
                $data['update_time'] = NOW_TIME;
                $data['update_ip'] = get_client_ip();
                $data['audit'] = 0;
                $data['hotel_id'] = $hotel['hotel_id'];
                if(false !== $obj->save($data)){
                    D('Hotelpics')->where(array('hotel_id'=>$hotel['hotel_id']))->delete();
                    foreach($thumb as $k=>$val){
                        D('Hotelpics')->add(array('hotel_id'=>$hotel['hotel_id'],'photo'=>$val));
                    }
                    $this->baoSuccess('修改成功', U('hotel/index'));
                }else{
                    $this->baoError('修改失败');
                }
            }
        } else {
            if (strlen($hotel['mobile'])===13) {
                $hotel['areacode']=substr($hotel['mobile'],0,2);
                $hotel['mobile']=substr($hotel['mobile'],2,11);
            }else if (strlen($hotel['mobile'])===14) {
                $hotel['areacode']=substr($hotel['mobile'],0,3);
                $hotel['mobile']=substr($hotel['mobile'],3,11);
            }
            $this->assign('hotel',$hotel);
            $thumb = D('Hotelpics')->where(array('hotel_id'=>$hotel['hotel_id']))->select();
            $this->assign('thumb', $thumb);
            $this->assign('rebate',D('rebate')->select());
            $this->assign('types',D('Hotelbrand')->fetchAll());
            $this->display();
        }
    }
    
    private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), array('hotel_name', 'lao_hotel_name','eng_hotel_name','addr','lao_addr','eng_addr', 'city_id', 'area_id','business_id','cate_id', 'type','price','star', 'tel', 'details', 'lao_details','eng_details','photo', 'lng', 'lat','is_wifi','is_kt','is_nq','is_tv','is_xyj','is_ly','is_bx','is_base','is_tooth','is_rsh','in_time','out_time','rate','mobile','areacode'));
        $data['hotel_name'] = htmlspecialchars($data['hotel_name']);
        $data['lao_hotel_name'] = htmlspecialchars($data['lao_hotel_name']);
        $data['eng_hotel_name'] = htmlspecialchars($data['eng_hotel_name']);
        if (empty($data['hotel_name'])) {
            $this->baoError('酒店中文名称不能为空');
        }
        if (empty($data['lao_hotel_name'])) {
            $this->baoError('酒店老文名称不能为空');
        }
        if (empty($data['eng_hotel_name'])) {
            $this->baoError('酒店英文名称不能为空');
        }
        $data['addr'] = htmlspecialchars($data['addr']);
        $data['lao_addr'] = htmlspecialchars($data['lao_addr']);
        $data['eng_addr'] = htmlspecialchars($data['eng_addr']);
        if (empty($data['addr'])) {
            $this->baoError('酒店中文地址不能为空');
        }
         if (empty($data['lao_addr'])) {
            $this->baoError('酒店老文地址不能为空');
        }
         if (empty($data['eng_addr'])) {
            $this->baoError('酒店英文地址不能为空');
        }
        $data['city_id'] = (int) $data['city_id'];
        $data['area_id'] = (int) $data['area_id'];
        $data['business_id'] = (int) $data['business_id'];
        if (empty($data['city_id'])) {
            $this->baoError('所在城市不能为空');
        } 
        if (empty($data['area_id'])) {
            $this->baoError('所在区域不能为空');
        } 
        if (empty($data['business_id'])) {
            $this->baoError('所在商圈不能为空');
        } 
        $data['cate_id'] = (int)$data['cate_id'];
        if (empty($data['cate_id'])) {
            $this->baoError('酒店级别没有选择');
        }
        $data['rate'] = (int)$data['rate'];
        if (empty($data['rate'])) {
            $this->baoError('平台折扣不能为空!');
        } 
        $data['price'] = (int)$data['price'];
        if (empty($data['price'])) {
            $this->baoError('酒店起价不能为空');
        }
        $data['tel'] = htmlspecialchars($data['tel']);
        if (empty($data['tel'])) {
            $this->baoError('酒店联系电话不能为空');
        }
        $data['areacode'] = (int) $data['areacode'];
        $data['mobile'] = htmlspecialchars($data['mobile']);
        if (empty($data['mobile'])) {
            $this->baoError('手机号不能为空');
        }
        $data['mobile'] = $data['areacode'].$data['mobile'];
        $data['type'] = (int)$data['type'];
        if (empty($data['type'])) {
            $this->baoError('酒店品牌不能为空');
        }
        $data['star'] = (int)$data['star'];
        if (empty($data['star'])) {
            $this->baoError('酒店星级不能为空');
        }
        $data['details'] = SecurityEditorHtml($data['details']);
        $data['lao_details'] = SecurityEditorHtml($data['lao_details']);
        $data['eng_details'] = SecurityEditorHtml($data['eng_details']);
        if (empty($data['details'])) {
            $this->baoError('酒店中文详情不能为空');
        }
        if (empty($data['lao_details'])) {
            $this->baoError('酒店老文详情不能为空');
        }
        if (empty($data['eng_details'])) {
            $this->baoError('酒店英文详情不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['details'])) {
            $this->baoError('酒店中文详情含有敏感词：' . $words);
        }
        if ($words = D('Sensitive')->checkWords($data['lao_details'])) {
            $this->baoError('酒店老文详情含有敏感词：' . $words);
        }
        if ($words = D('Sensitive')->checkWords($data['eng_details'])) {
            $this->baoError('酒店英文详情含有敏感词：' . $words);
        }
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->baoError('请上传缩略图');
        }
        if (!isImage($data['photo'])) {
            $this->baoError('缩略图格式不正确');
        } 
        $data['is_wifi'] = (int)$data['is_wifi'];
        $data['is_kt'] = (int)$data['is_kt'];
        $data['is_nq'] = (int)$data['is_nq'];
        $data['is_tv'] = (int)$data['is_tv'];
        $data['is_xyj'] = (int)$data['is_xyj'];
        $data['is_ly'] = (int)$data['is_ly'];
        $data['is_bx'] = (int)$data['is_bx'];
        $data['is_base'] = (int)$data['is_base'];
        $data['is_tooth'] = (int)$data['is_tooth'];
        $data['is_rsh'] = (int)$data['is_rsh'];
        $data['lng'] = htmlspecialchars($data['lng']);
        $data['lat'] = htmlspecialchars($data['lat']);
        if (empty($data['lng']) || empty($data['lat'])) {
            $this->baoError('酒店坐标没有选择');
        }
        $data['mer_id'] = $this->mer_id;
        $data['in_time'] = htmlspecialchars($data['in_time']);
        $data['out_time'] = htmlspecialchars($data['out_time']);
        if (empty($data['in_time']) || empty($data['out_time'])) {
            $this->baoError('酒店入住时间和离店时间没有设置');
        }
        
        return $data;
    }
    
    
    public function room(){ //房间列表
        $hotel_id = $this->check_hotel();
        $room = D('Hotelroom');
        import('ORG.Util.Page'); // 导入分页类    
        $map = array('hotel_id' => $hotel_id,'closed' => 0);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['title|lao_title|eng_title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $room->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 25); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $room->where($map)->order(array('room_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
        // var_dump($list);
    }

     public function del($room_id = 0) {
       
            $obj = D('Hotelroom');
            $obj->save(array('room_id' => $room_id, 'closed' => 1));
            $this->baoSuccess('关闭成功！', U('hotel/index'));       
    }

    public function setroom(){ //添加房间
        $this->check_hotel();
        if ($this->isPost()) {
            $data = $this->roomCreateCheck();
            $obj = D('Hotelroom');
            if ($room_id = $obj->add($data)) {
                $this->baoSuccess('添加成功', U('hotel/room'));
            }
            $this->baoError('操作失败！');
        } else {
            $this->display();
        }
    }
    
    
    private function roomCreateCheck() {
        $data = $this->checkFields($this->_post('data', false), array('title','lao_title','eng_title', 'price','type', 'photo','hotel_id','is_zc', 'is_kd','is_cancel','sku'));
        $data['title'] = htmlspecialchars($data['title']);
        $data['lao_title'] = htmlspecialchars($data['lao_title']);
        $data['eng_title'] = htmlspecialchars($data['eng_title']);
        if (empty($data['title'])) {
            $this->baoError('房间中文名称不能为空');
        }
        if (empty($data['lao_title'])) {
            $this->baoError('房间老文名称不能为空');
        }
        if (empty($data['eng_title'])) {
            $this->baoError('房间英文名称不能为空');
        }
        $data['price'] = (int)$data['price'];
        if (empty($data['price'])) {
            $this->baoError('房间价格不能为空');
        }
        $data['type'] = (int)$data['type'];
        if (empty($data['type'])) {
            $this->baoError('房间类型不能为空');
        }
        $hotel = D('Hotel')->where(array('mer_id'=>$this->mer_id))->find();
        $data['hotel_id'] = $hotel['hotel_id'];
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->baoError('请上传房间图片');
        }
        if (!isImage($data['photo'])) {
            $this->baoError('房间图片格式不正确');
        } 
        $data['sku'] = (int) $data['sku'];
        $data['is_zc'] = (int)$data['is_zc'];
        $data['is_kd'] = (int)$data['is_kd'];
        $data['is_cancel'] = (int)$data['is_cancel'];
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        return $data;
    }
    
    public function editroom($room_id=null){
        $hotel_id = $this->check_hotel();
        if ($room_id = (int) $room_id) {
            $obj = D('Hotelroom');
            if (!$detail = $obj->find($room_id)) {
                $this->baoError('请选择要编辑的房间');
            }
            if ($detail['hotel_id'] != $hotel_id) {
                $this->baoError('请不要操作别人的房间');
            }
            if ($this->isPost()) {
                $data = $this->roomEditCheck();
                $data['room_id'] = $room_id;
                if (false !== $obj->save($data)) {
                    $this->baoSuccess('操作成功', U('hotel/room'));
                }
                $this->baoError('操作失败');
            } else {
                $this->assign('detail',$detail);
                $this->display();
            }
        } else {
            $this->baoError('请选择要编辑的房间');
        }
    }

    

    private function roomEditCheck() {
        $data = $this->checkFields($this->_post('data', false), array('title','lao_title','eng_title', 'price','type', 'photo','is_zc', 'is_kd','is_cancel','sku'));
        $data['title'] = htmlspecialchars($data['title']);
        $data['lao_title'] = htmlspecialchars($data['lao_title']);
        $data['eng_title'] = htmlspecialchars($data['eng_title']);
        if (empty($data['title'])) {
            $this->baoError('房间中文名称不能为空');
        }
        if (empty($data['lao_title'])) {
            $this->baoError('房间老文名称不能为空');
        }
        if (empty($data['eng_title'])) {
            $this->baoError('房间英文名称不能为空');
        }
        $data['price'] = (int)$data['price'];
        if (empty($data['price'])) {
            $this->baoError('房间价格不能为空');
        }
        $data['type'] = (int)$data['type'];
        if (empty($data['type'])) {
            $this->baoError('房间类型不能为空');
        }
        $hotel = D('Hotel')->where(array('mer_id'=>$this->mer_id))->find();
        $data['hotel_id'] = $hotel['hotel_id'];
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->baoError('请上传房间图片');
        }
        if (!isImage($data['photo'])) {
            $this->baoError('房间图片格式不正确');
        } 
        $data['sku'] = (int) $data['sku'];
        $data['is_zc'] = (int)$data['is_zc'];
        $data['is_kd'] = (int)$data['is_kd'];
        $data['is_cancel'] = (int)$data['is_cancel'];
        return $data;
    }
   
    
    public function cancel($order_id){
        $hotel_id = $this->check_hotel();
        if($order_id = (int) $order_id){
            if(!$order = D('Hotelorder')->find($order_id)){
                $this->baoError('订单不存在');
            }elseif($order['hotel_id'] != $hotel_id){
                $this->baoError('非法操作订单');
            }elseif($order['order_status'] == -1){
                $this->baoError('该订单已取消');
            }else{
                if(false !== D('Hotelorder')->cancel($order_id)){
                    $this->baoSuccess('订单取消成功',U('hotel/index'));
                }else{
                    $this->baoError('订单取消失败');
                }
            }
        }else{
            $this->baoError('请选择要取消的订单');
        }
    }
    
    
    public function complete($order_id){
        $hotel_id = $this->check_hotel();
        if($order_id = (int) $order_id){
            if(!$order = D('Hotelorder')->find($order_id)){
                $this->baoError('订单不存在');
            }elseif($order['hotel_id'] != $hotel_id){
                $this->baoError('非法操作订单');
            }elseif($order['online_pay'] == 1&&$order['order_status'] != 1){
                $this->baoError('该订单无法完成');
            }else{
                if(false !== D('Hotelorder')->complete($order_id)){
                    
                    $this->baoSuccess('订单操作成功',U('hotel/index'));
                }else{
                    $this->baoError('订单操作失败');
                }
            }
        }else{
            $this->baoError('请选择要完成的订单');
        }
    }
    
    
    public function delete($order_id){
        $hotel_id = $this->check_hotel();
        if($order_id = (int) $order_id){
            if(!$order = D('Hotelorder')->find($order_id)){
                $this->baoError('订单不存在');
            }elseif($order['hotel_id'] != $hotel_id){
                $this->baoError('非法操作订单');
            }elseif($order['order_status'] != -1){
                $this->baoError('订单状态不正确');
            }else{
                if(false !== D('Hotelorder')->save(array('order_id'=>$order_id,'closed'=>1))){
                    $this->baoSuccess('订单删除成功',U('hotel/index'));
                }else{
                    $this->baoError('订单删除失败');
                }
            }
        }else{
            $this->baoError('请选择要删除的订单');
        }
    }
    
    public function detail($order_id=null){
        $hotel_id = $this->check_hotel();
        if(!$order_id = (int)$order_id){
            $this->error('订单不存在');
        }elseif(!$detail = D('Hotelorder')->find($order_id)){
             $this->error('订单不存在');
        }elseif($detail['closed'] == 1){
             $this->error('订单已删除');
        }elseif($detail['hotel_id'] != $hotel_id){
             $this->error('非法的订单操作');
        }else{
            $detail['night_num'] = $this->diffBetweenTwoDays($detail['stime'],$detail['ltime']); 
            $detail['room'] = D('Hotelroom')->find($detail['room_id']); 
            $detail['hotel'] = D('Hotel')->find($detail['hotel_id']);
            $this->assign('detail',$detail);
            $this->display();
        }
    }
    
        function diffBetweenTwoDays ($day1, $day2){
          $second1 = strtotime($day1);
          $second2 = strtotime($day2);

          if ($second1 < $second2) {
            $tmp = $second2;
            $second2 = $second1;
            $second1 = $tmp;
          }
          return ($second1 - $second2) / 86400;
    }

  
}
