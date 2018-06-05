<?php



class CityAction extends CommonAction{
    
    public function index(){
        $citylists = array();
        $citys = D('City')->where(array('is_open'=>1))->select();

        foreach($citys as $val){
            $a = strtoupper($val['first_letter']);
            $citylists[$a][] = $val;
        }

        ksort($citylists);
        $this->assign('citys',$citys);
        $this->assign('citylists',$citylists);
        $this->mobile_title = '城市切换';
        $this->display();
    }
    
    public function change($city_id){
        if(empty($city_id)){
            $this->error('没有正确的城市');
        }
        if(isset($this->citys[$city_id])){            
            cookie('city_id',$city_id,86400*30);
            header("Location:".U('index/index'));die;
        }
        $this->error('没有正确的城市');
    }
    
    
}