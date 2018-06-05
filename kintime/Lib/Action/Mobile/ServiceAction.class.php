<?php



class ServiceAction extends CommonAction {

    public function index(){
		$this->display();
	}
	
    
    public function detail($service_id){
        $service_id = (int) $service_id;
        if(!$service_id){
            $this->error('数据不存在');
        }
        if(!$detail = D('Service')->find($service_id)){
            $this->error('数据不存在');
        }
        $this->assign('detail',$detail);
        $this->display();
    }
}