<?php



class IndexAction extends CommonAction {

    public function index() {

        if (!cookie('DL')) {
            header("Location: " . U('login/index'));
        } else {
            $cid = $this->reid();
            $dv = D('Delivery');
            $rdv = $dv->where('id =' . $cid)->find();
            if (!$rdv) {
                header("Location: " . U('login/logout'));
            } else {
                $this->assign('rdv', $rdv);
            }

            //未配送订单
            $do = D('DeliveryOrder');

            $today = strtotime(date('Y-m-d'));

            //今日配送
            $today_p = $do->where('update_time >=' . $today . ' and delivery_id =' . $cid . ' and city_id ='.$rdv['city_id'])->count();
            $this->assign('today_p', $today_p);

            //今日完成
            $today_ok = $do->where('update_time >=' . $today . ' and delivery_id =' . $cid . ' and status =3 and city_id ='.$rdv['city_id'])->count();
            $this->assign('today_ok', $today_ok);

            //总计完成
            $all_ok = $do->where('delivery_id =' . $cid . ' and status =3 and city_id ='.$rdv['city_id'])->count();
            $this->assign('all_ok', $all_ok);

            //抢新单
            $new = $do->where('status = 1 and delivery_id =0 and city_id ='.$rdv['city_id'])->count();
            // $new = $do->where('status = 1 and delivery_id =0 and city_id ='.$rdv['city_id'])->select();
            $this->assign('new', $new);

            //配送中
            $ing = $do->where('status = 2 and delivery_id =' . $cid .' and city_id ='.$rdv['city_id'])->count();
            // $ing = $do->where('status = 2 and delivery_id =' . $cid .' and city_id ='.$rdv['city_id'])->select();
            $this->assign('ing', $ing);

            //已完成
            $ed = $do->where('status = 3 and delivery_id =' . $cid .' and city_id ='.$rdv['city_id'])->count();
            $this->assign('ed', $ed);
            // var_dump($rdv);
            $this->display();
        }
    }

    public function express() {

        if (!cookie('DL')) {
            header("Location: " . U('login/index'));
        } else {
            $cid = $this->reid();
            $dv = D('Delivery');
            $rdv = $dv->where('id =' . $cid)->find();
            if (!$rdv) {
                header("Location: " . U('login/logout'));
            } else {
                $this->assign('rdv', $rdv);
            }

            //未配送订单
            $express = D('Express');
            $today = strtotime(date('Y-m-d'));
            //今日配送
            $today_p = $express->where('update_time >=' . $today . ' and cid =' . $cid . ' and status =1 and city_id ='.$rdv['city_id'])->count();
            $this->assign('today_p', $today_p);

            //今日完成
            $today_ok = $express->where('update_time >=' . $today . ' and cid =' . $cid . ' and status =2 and city_id ='.$rdv['city_id'])->count();
            $this->assign('today_ok', $today_ok);

            //总计完成
            $all_ok = $express->where('cid =' . $cid . ' and status =2 and city_id ='.$rdv['city_id'])->count();
            $this->assign('all_ok', $all_ok);

            //抢新单
            $new = $express->where('status = 0 and cid =0 and city_id ='.$rdv['city_id'])->count();
            $this->assign('new', $new);

            //配送中
            $ing = $express->where('status = 1 and cid =' . $cid .' and city_id ='.$rdv['city_id'])->count();
            $this->assign('ing', $ing);

            //已完成
            $ed = $express->where('status = 2 and cid =' . $cid .' and city_id ='.$rdv['city_id'])->count();
            $this->assign('ed', $ed);

            $this->display();
        }
    }

    public function dingwei() {
        $lat = $this->_get('lat', 'htmlspecialchars');
        $lng = $this->_get('lng', 'htmlspecialchars');
        cookie('lat', $lat);
        cookie('lng', $lng);
        echo NOW_TIME;
    }

}
