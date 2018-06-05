<?php



class ExtendAction extends CommonAction { //推广页面

    public function index() {
        
        $this->mobile_title = '全民推广';
        $this->display(); // 输出模板   
    }

}
