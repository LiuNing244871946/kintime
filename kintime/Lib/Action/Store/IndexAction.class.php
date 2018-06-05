<?php



class IndexAction extends CommonAction {

    public function index() {
        $this->assign('homepage','商户中心首页');
        $this->display();
    }

    public function main() {
        $counts = array();
        $bg_time = strtotime(TODAY);
        $counts['totay_order'] = (int) D('Order')->where(array(
                    'shop_id' => $this->shop_id,
                    'type' => 'goods',
                    'create_time' => array(
                        array('ELT', NOW_TIME),
                        array('EGT', $bg_time),
                    ), 'status' => array(
                        'EGT', 0
                    ),
                ))->count();
        $counts['order'] = (int) D('Order')->where(array(
                    'shop_id' => $this->shop_id,
                    'type' => 'goods',
                    'status' => array(
                        'EGT', 0
                    ),
                ))->count();

        $counts['today_yuyue'] = (int) D('Shopyuyue')->where(array(
                    'shop_id' => $this->shop_id,
                    'create_time' => array(
                        array('ELT', NOW_TIME),
                        array('EGT', $bg_time),
            )))->count();
        $counts['yuyue'] = (int) D('Shopyuyue')->where(array(
                    'shop_id' => $this->shop_id,
                    'create_time' => array(
                        array('ELT', NOW_TIME),
                        array('EGT', $bg_time),
            )))->count();


        $counts['today_coupon'] = (int) D('Coupondownload')->where(array(
                    'shop_id' => $this->shop_id,
                    'create_time' => array(
                        array('ELT', NOW_TIME),
                        array('EGT', $bg_time),
            )))->count();
        $counts['coupon'] = (int) D('Coupondownload')->where(array(
                    'shop_id' => $this->shop_id,
                ))->count();
        $counts['dianping'] = (int) D('Shopdianping')->where(array(
                    'shop_id' => $this->shop_id,
                ))->count();

        $counts['orderby'] = (int) D('Shop')->where(array(
                    'ranking' => array(
                        'EGT', $this->shop['ranking']
                    )
                ))->count();

        $this->assign('counts', $counts);
        $this->display();
    }

    public function dingwei() {
        $lat = $this->_get('lat', 'htmlspecialchars');
        $lng = $this->_get('lng', 'htmlspecialchars');
        cookie('lat', $lat);
        cookie('lng', $lng);
        die(NOW_TIME);
    }
}
