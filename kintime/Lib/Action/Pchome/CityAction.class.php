<?php



class CityAction extends CommonAction{
    public function index(){
        $citylists = array();
        foreach($this->citys as $val){
            if($val['is_open'] == 1){
                $a = strtoupper($val['first_letter']);
                $citylists[$a][] = $val;
            }
        }
        ksort($citylists);
        $this->assign('citylists',$citylists);
        $this->display();
    }

}