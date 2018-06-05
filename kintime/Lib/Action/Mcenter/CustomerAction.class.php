<?php



class CustomerAction extends CommonAction {

    public function index() {
    	$this->mobile_title = '客服中心';
        $this->display(); // 输出模板
    }

}