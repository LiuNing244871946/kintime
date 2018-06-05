<?php



class IndexAction extends CommonAction {

    
    public function index() {
        
        $this->display();
    }
    
        public function dingwei(){
        $lat = $this->_get('lat', 'htmlspecialchars');
        $lng = $this->_get('lng', 'htmlspecialchars');
        cookie('lat',$lat);
        cookie('lng',$lng);
        die(NOW_TIME);
    }
}

   