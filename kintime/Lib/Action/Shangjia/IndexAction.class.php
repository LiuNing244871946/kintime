<?php



class IndexAction extends CommonAction {

    public function index() {
        $this->display();
    }

    public function main() {
        $counts = array();
        $bg_time = strtotime(TODAY);
        $counts['totay_order'] = (int) D('AllOrder')->where(array(
                    'mer_id' => $this->mer_id,
                    'time' => array(
                        array('ELT', NOW_TIME),
                        array('EGT', $bg_time),
                    )
                ))->count();
/*        echo D('AllOrder')->getLastSql();
        // echo $counts['totay_order'];
        die();*/
        $counts['order'] = (int) D('AllOrder')->where(array(
                    'mer_id' => $this->mer_id,
                    'status' => array(
                        'EGT', 0
                    ),
                ))->count();

        /*$counts['today_yuyue'] = (int) D('Shopyuyue')->where(array(
                    'mer_id' => $this->mer_id,
                    'create_time' => array(
                        array('ELT', NOW_TIME),
                        array('EGT', $bg_time),
            )))->count();
        $counts['yuyue'] = (int) D('Shopyuyue')->where(array(
                    'mer_id' => $this->mer_id,
                    'create_time' => array(
                        array('ELT', NOW_TIME),
                        array('EGT', $bg_time),
            )))->count();*/


        $counts['today_coupon'] = (int) D('Coupondownload')->where(array(
                    'mer_id' => $this->mer_id,
                    'create_time' => array(
                        array('ELT', NOW_TIME),
                        array('EGT', $bg_time),
            )))->count();
        $counts['coupon'] = (int) D('Coupondownload')->where(array(
                    'mer_id' => $this->mer_id,
                ))->count();
        $counts['dianping'] = (int) D('Shopdianping')->where(array(
                    'mer_id' => $this->mer_id,
                ))->count();

        $counts['orderby'] = (int) D('Shop')->where(array(
                    'ranking' => array(
                        'EGT', $this->shop['ranking']
                    )
                ))->count();
        
        $this->assign('counts', $counts);
        
        /* 统计抢购 */
        $bg_date = date('Y-m-d', NOW_TIME - 86400 * 6);
        $end_date = TODAY;
        $bg_datee = date('Y-m-d', NOW_TIME - 86400 * 5);
        $end_datee = date('Y-m-d', NOW_TIME + 86400);
        $bg_time = strtotime($bg_datee)-1;
        $end_time = strtotime($end_datee)-1;
        $this->assign('bg_date', $bg_date);
        $this->assign('end_date', $end_date);
        $this->assign('money', D('Tuanorder')->money($bg_time, $end_time, $this->mer_id));
        $this->assign('ordermoney', D('Order')->money($bg_time, $end_time, $this->mer_id));
        // $this->assign('shopmoney', D('Shopmoney')->money($bg_time, $end_time, $this->shop_id));
        $this->display();
    }

}
