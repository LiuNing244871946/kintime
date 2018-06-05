<?php

class AdsiteAction extends CommonAction {


    public function index() {
        $Adsite = D('Adsite');
        $this->assign('adsite',$Adsite->fetchAll());
        $this->assign('types', $Adsite->getType());
        $this->assign('place', $Adsite->getPlace());
        $this->display(); // 输出模板
    }

}
