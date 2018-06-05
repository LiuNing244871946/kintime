<?php



class HotelAction extends CommonAction {

    protected $types = array();

    public function _initialize() {
        parent::_initialize();
        $this->types = D('Hotelbrand')->fetchAll();
        $this->assign('types', $this->types);
        $this->cates = D('Hotel')->getHotelCate();
        $this->assign('cates', $this->cates);
        $this->stars = D('Hotel')->getHotelStar();
        $this->assign('stars', $this->stars);
        $this->assign('roomtype',D('Hotelroom')->getRoomType());
        $this->assign('lao_roomtype',D('Hotelroom')->laoGetRoomType());
        $this->assign('eng_roomtype',D('Hotelroom')->engGetRoomType());
    }

    public function index() {
        $linkArr = array();
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        $linkArr['keyword'] = $keyword;
        
        $cate_id = (int) $this->_param('cate_id');
        $this->assign('cate_id', $cate_id);
        $linkArr['cate_id'] = $cate_id;
        
        $area_id = (int) $this->_param('area_id');
        $this->assign('area_id', $area_id);
        $linkArr['area_id'] = $area_id;
        
        $business_id = (int) $this->_param('business_id');
        $this->assign('business_id', $business_id);
        $linkArr['business_id'] = $business_id;
        
        $order = $this->_param('order', 'htmlspecialchars');
        $this->assign('order', $order);
        $linkArr['order'] = $order;

        $this->assign('nextpage', LinkTo('hotel/loaddata',$linkArr,array('t' => NOW_TIME,'p' => '0000')));
        $this->assign('linkArr',$linkArr);
        $this->mobile_title = '酒店列表';
        $this->display(); // 输出模板
    }
    
    public function loaddata() {
        $hotel = D('Hotel');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('audit' => 1, 'closed' => 0, 'city_id' => $this->city_id);
        $linkArr = array();
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['hotel_name'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
            $linkArr['keywrod'] = $keyword;
        }
        $cate_id = (int) $this->_param('cate_id');
        if($cate_id){
            $map['cate_id'] = $cate_id;
            $linkArr['cate_id'] = $cate_id;
        }
        $this->assign('cate_id', $cate_id);
        $area_id = (int) $this->_param('area_id');
        if ($area_id) {
            $map['area_id'] = $area_id;
            $linkArr['area_id'] = $area_id;
        }
        $this->assign('area_id', $area_id);
        $business_id = (int) $this->_param('business_id');
        if ($business_id) {
            $map['business_id'] = $business_id;
            $linkArr['business_id'] = $business_id;
        }
        $this->assign('business_id', $business_id);
        $order = $this->_param('order', 'htmlspecialchars');
        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if (empty($lat) || empty($lng)) {
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        $orderby = '';
        switch ($order) {
            case 'd':
                $orderby = " (ABS(lng - '{$lng}') +  ABS(lat - '{$lat}') ) asc ";
                break;
            case 'p':
                $orderby = array('price' => 'asc');
                break;
            case 's':
                $orderby = array('sold_num' => 'desc');
                break;
            default:
                $orderby = array('sold_num' => 'desc', 'hotel_id' => 'desc');
                break;
        }
        $this->assign('order', $order);
        $lists = $hotel->where($map)->order($orderby)->select();
        $shops = array();
        foreach ($lists as $k => $val) {
            if($shops[$val['mer_id']]){
                unset($lists[$k]);
            }else{
                $shop = D('merchants')->find($val['mer_id']);
                if($shop['audit'] == 0 && $shop['closed'] == 1){
                    $shops[$val['mer_id']] = $val['mer_id'];
                    unset($lists[$k]);
                }
            }
            $lists[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);
        }
        $count = count($lists);
        $Page = new Page($count, 20); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出  
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = array_slice($lists, $Page->firstRow, $Page->listRows);

        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function gps($hotel_id) {
        $hotel_id = (int) $hotel_id;
        if (empty($hotel_id)) {
            $this->error('该商家不存在');
        }
        $hotel = D('Hotel')->find($hotel_id);
        $this->assign('hotel_id', $hotel_id);
        $this->assign('hotel', $hotel);
        $this->mobile_title = '路线导航';
        $this->display();
    }

    public function favorites() {
        if (empty($this->uid)) {
            AppJump();
        }
        $hotel_id = (int) $this->_get('hotel_id');
        if (!$detail = D('hotel')->find($hotel_id)) {
            $this->error('没有该酒店');
        }
        if (D('Shopfavorites')->where(array('shop_id'=>$hotel_id,'user_id'=>$this->uid,'type'=>2))->select()) {
            $this->error('您已经关注过了！');
        }
        $data = array(
            'shop_id' => $hotel_id,
            'user_id' => $this->uid,
            'create_time' => NOW_TIME,
            'create_ip' => get_client_ip(),
            'type'=>2
        );
        if (D('Shopfavorites')->add($data)) {
            D('Hotel')->updateCount($hotel_id,'fans_num');
            $this->success('恭喜您关注成功！', U('hotel/detail', array('hotel_id' => $hotel_id)));
        }
        $this->error('关注失败！');
    }
    
    public function detail($hotel_id){
        $obj = D('Hotel');
        if(empty(cookie('into_time'))) cookie('into_time',date("Y-m-d",time()));
        if(!$hotel_id = (int)$hotel_id){
            $this->error('该酒店不存在');
        }elseif(!$detail = $obj->find($hotel_id)){
            $this->error('该酒店不存在');
        }elseif($detail['closed'] == 1||$detail['audit'] == 0){
            $this->error('该酒店已删除或未通过审核');
        }else{
            $lat = addslashes(cookie('lat'));
            $lng = addslashes(cookie('lng'));
            if (empty($lat) || empty($lng)) {
                $lat = $this->city['lat'];
                $lng = $this->city['lng'];
            }
            $detail['d'] = getDistance($lat, $lng, $detail['lat'], $detail['lng']);
            $pics = D('Hotelpics')->where(array('hotel_id'=>$hotel_id))->select();
            $pics[] = array('photo'=>$detail['photo']);
            $into_time = htmlspecialchars($_COOKIE['into_time']);
            $out_time = htmlspecialchars($_COOKIE['out_time']);
            //房间
            $room_list = D('Hotelroom')->where(array('hotel_id'=>$hotel_id))->select();
            $hotelnum = D('HotelNum');
            $come_time = strtotime(cookie('into_time'))+1;
            $leave_time = strtotime(cookie('out_time'));
            foreach ($room_list as $key => $value) {
                $hotel_count = 0;
                $room_id = $value['room_id'];
                $hotel_count = $hotelnum->where('room_id='.$room_id.' AND (('.$come_time.'>come_time AND '.$come_time.' <leave_time) OR ('.$leave_time.'>come_time AND '.$leave_time.' <= leave_time)) AND state=1')->count();
                $room_list[$key]['sku'] = $value['sku']-$hotel_count;
            }
            $detail['zhu'] = 0;
            if (D('Shopfavorites')->where(array('shop_id'=>$hotel_id,'user_id'=>$this->uid,'type'=>2))->select()) {
                $detail['zhu'] = 1;
            }
            D('Hotel')->updateCount($hotel_id, 'view');
            if ($this->uid) {
                D('Userslook')->look($this->uid, $hotel_id,2);
            }
            $room_count = D('Hotelroom')->where(array('hotel_id'=>$hotel_id))->count();
            $this->assign('room_list',$room_list);
            $this->assign('room_count',$room_count);
            $this->assign('into_time',$into_time); 
            $this->assign('out_time',$out_time);
            $this->assign('detail',$detail);
            $this->assign('pics',$pics);

            $this->display();
        }
    }

    public function roomnum(){
        $hotel_id = (int) $_POST['hotel_id'];
        $room_list = D('Hotelroom')->where(array('hotel_id'=>$hotel_id))->select();
        $hotelnum = D('HotelNum');
        $come_time = strtotime(cookie('into_time'))+1;
        $leave_time = strtotime(cookie('out_time'));
        foreach ($room_list as $key => $value) {
            $hotel_count = 0;
            $room_id = $value['room_id'];
            $hotel_count = $hotelnum->where('room_id='.$room_id.' AND (('.$come_time.'>come_time AND '.$come_time.' <leave_time) OR ('.$leave_time.'>come_time AND '.$leave_time.' <= leave_time)) AND state=1')->count();
            $room[] = array($room_id,$value['sku']-$hotel_count); 
        }
        echo json_encode($room);
    }

    public function info($hotel_id){
        $obj = D('Hotel');
        if(!$hotel_id = (int)$hotel_id){
            $this->error('该酒店不存在');
        }elseif(!$detail = $obj->find($hotel_id)){
            $this->error('该酒店不存在');
        }elseif($detail['closed'] == 1||$detail['audit'] == 0){
            $this->error('该酒店已删除或未通过审核');
        }else{
            $this->assign('detail',$detail);
            $this->display();
        }
    }
    
    public function order($room_id){
        $obj = D('Hotelroom');
        if(!$room_id = (int)$room_id){
            $this->error('房间不存在');
        }elseif(!$detail = $obj->find($room_id)){
            $this->error('房间不存在');
        }elseif($detail['sku'] == 0){
            $this->error('房间已经预订完了');
        }else{
            $hotel = D('Hotel')->find($detail['hotel_id']);
            $into_time = htmlspecialchars($_COOKIE['into_time']);
            $out_time = htmlspecialchars($_COOKIE['out_time']); 
            // 该时段下的房间库存
            $hotelnum = D('HotelNum');
            $come_time = strtotime(cookie('into_time'))+1;
            $leave_time = strtotime(cookie('out_time'));
            $hotel_count = $hotelnum->where('room_id='.$room_id.' AND (('.$come_time.'>come_time AND '.$come_time.' <leave_time) OR ('.$leave_time.'>come_time AND '.$leave_time.' <= leave_time)) AND state=1')->count();
            $detail['sku'] = $detail['sku']-$hotel_count; 
            $this->assign('hotel',$hotel);
            $this->assign('detail',$detail);
            $this->assign('into_time',$into_time); 
            $this->assign('out_time',$out_time);
            $this->display();
        }
    }

    
    public function orderCreate(){
        if (empty($this->uid)) {
            $this->ajaxReturn(array('status' => 'login'));
        }
        $room_id = (int) $_POST['room_id'];
        $detail = D('Hotelroom')->find($room_id);
        if (empty($detail)) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '该房间不存在'));
        }
        if (IS_AJAX) {
            $num = (int) $_POST['num'];
            if (empty($num)) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '房间数不能为空'));
            }
            // 该时段下的房间库存
            $hotelnum = D('HotelNum');
            $come_time = strtotime(cookie('into_time'))+1;
            $leave_time = strtotime(cookie('out_time'));
            $hotel_count = $hotelnum->where('room_id='.$room_id.' AND (('.$come_time.'>come_time AND '.$come_time.' <leave_time) OR ('.$leave_time.'>come_time AND '.$leave_time.' <= leave_time)) AND state=1')->count();
            $detail['sku'] = $detail['sku']-$hotel_count; 

            if ($num > $detail['sku'] ) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '对不起，房间太火爆已经被抢光了'));
            }
            $data = array(
                'room_id' => $room_id,
                'num' => $num,
                'price'=> $detail['price'],
                'user_id' => $this->uid,
                'hotel_id'=>$detail['hotel_id'],
                'create_time'=>NOW_TIME,
                'create_ip'=>  get_client_ip(),
            );
            $data['online_pay'] = 1;
            $data['stime'] = htmlspecialchars($_POST['stime']);
            $data['ltime'] = htmlspecialchars($_POST['ltime']);
            if(!$data['stime'] || !$data['ltime']){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '选择时间不能为空'));
            }
            if($data['stime'] > $data['ltime']){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '选择时间不正确'));
            }
            $data['name'] = htmlspecialchars($_POST['realname']);
            if(!$data['name']){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '入住人姓名不能为空'));
            }
            $mobile = htmlspecialchars($_POST['mobile']);
            $data['mobile'] = htmlspecialchars($_POST['areacode']).$mobile;
            if(!$mobile){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '入住人手机号不能为空'));
            }
            $data['note'] = htmlspecialchars($_POST['note']);
            $data['last_time'] = htmlspecialchars($_POST['last_time']);
            $night_num = $this->diffBetweenTwoDays($data['stime'],$data['ltime']);
            $data['amount'] = $night_num*$num*$detail['price'];
            if($order_id = D('Hotelorder')->add($data)){
                D('Hotel')->updateCount($detail['hotel_id'],'sold_num');
                $data_num = array(
                    'room_id'=>$room_id,
                    'come_time'=>strtotime(cookie('into_time')),
                    'leave_time'=>strtotime(cookie('out_time')),
                    'state'=>1,
                    'order_id'=>$order_id
                );
                D('HotelNum')->add($data_num);
                cookie('into_time', null);
                cookie('out_time', null);
                $this->ajaxReturn(array('status' => 'success', 'msg' => '恭喜下单成功','order_id'=>$order_id));
            }else{
                $this->ajaxReturn(array('status' => 'error', 'msg' => '下单失败'));
            }
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

    public function pay(){
        if (empty($this->uid)) {
            AppJump();
        }
        $order_id = (int) $this->_get('order_id');
        $order = D('Hotelorder')->find($order_id);
        if (empty($order) || $order['order_status'] != 0 || $order['user_id'] != $this->uid) {
            $this->error('该订单不存在');
            die;
        }
        $room = D('Hotelroom')->find($order['room_id']);
        if (empty($room) || $room['hotel_id'] != $order['hotel_id'] ) {
            $this->error('该房间不存在');
            die;
        }
        $this->assign('payment', D('Payment')->getPayments());
        $this->assign('room', $room);
        $this->assign('order', $order);
        $this->display();
    }
    
    
    public function pay2(){
        if (empty($this->uid)) {
             AppJump();
        }
        $order_id = (int) $this->_get('order_id');
        $order = D('Hotelorder')->find($order_id);
        if (empty($order) || $order['order_status'] != 0 || $order['user_id'] != $this->uid) {
            $this->error('该订单不存在');
            die;
        }
        if (!$code = $this->_post('code')) {
            $this->error('请选择支付方式！');
        }
        
        $payment = D('Payment')->checkPayment($code);
        if (empty($payment)) {
            $this->error('该支付方式不存在');
        }
        $room = D('Hotelroom')->find($order['room_id']);
        if (empty($room) || $room['hotel_id'] != $order['hotel_id'] ) {
            $this->error('该房间不存在');
        }
        $logs = D('Paymentlogs')->getLogsByOrderId('hotel', $order_id);
        if (empty($logs)) {
            $logs = array(
                'type' => 'hotel',
                'user_id' => $this->uid,
                'order_id' => $order_id,
                'code' => $code,
                'need_pay' => $order['amount'],
                'create_time' => NOW_TIME,
                'create_ip' => get_client_ip(),
                'is_paid' => 0
            );
            $logs['log_id'] = D('Paymentlogs')->add($logs);
        } else {
            $logs['need_pay'] = $order['amount'];
            $logs['code'] = $code;
            D('Paymentlogs')->save($logs);
        }

            
            $this->success('选择支付方式成功！下面请进行支付！', U('payment/payment',array('log_id' => $logs['log_id'])));
    }

 //点评
    public function dianping() {
        $hotel_id = (int) $this->_get('hotel_id');
        if (!$detail = D('Hotel')->find($hotel_id)) {
            $this->error('没有该商家');
            die;
        }
        if ($detail['closed']) {
            $this->error('该商家已经被删除');
            die;
        }
        $this->assign('detail', $detail);
        $this->mobile_title = '商家点评';
        $this->display();
    }

    public function dianpingloading() {
        $hotel_id = (int) $this->_get('hotel_id');
        if (!$detail = D('Hotel')->find($hotel_id)) {
            die('0');
        }
        if ($detail['closed']) {
            die('0');
        }
        $Tuandianping = D('HotelComment');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0, 'hotel_id' => $hotel_id);
        $count = $Tuandianping->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 5); // 实例化分页类 传入总记录数和每页显示的记录数

        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            $this->assign('totalnum', $count);
            $this->display();
            die();
        }

        $show = $Page->show(); // 分页显示输出
        $list = $Tuandianping->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $dianping_ids = array();
        foreach ($list as $k => $val) {
            $list[$k] = $val;
            $user_ids[$val['user_id']] = $val['user_id'];
            $dianping_ids[$val['comment_id']] = $val['comment_id'];
            $list[$k]['show_date'] = date("Y-m-d",$val['create_time']);
        }
        if (!empty($user_ids)) {
            $this->assign('users', D('Users')->itemsByIds($user_ids));
        }
        if (!empty($dianping_ids)) {
            $pics = D('HotelCommentPics')->where(array('comment_id' => array('IN', $dianping_ids)))->select();
            $this->assign('pics', $pics);
        }
        $this->assign('totalnum', $count);
        $this->assign('list', $list); // 赋值数据集
        $this->assign('detail', $detail);
        $this->display();
    }

}
