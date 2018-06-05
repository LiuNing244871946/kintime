<?php



class IndexAction extends CommonAction {

    public function index() {
        $this->mobile_title = '首页';
        $nav = D('Function')->where('is_index = 1')->order('orderby asc')->select();
        $this->assign('nav',$nav);
        $this->display();
    }

    public function dingwei() {
        $lat = $this->_get('lat', 'htmlspecialchars');
        $lng = $this->_get('lng', 'htmlspecialchars');
        cookie('lat', $lat);
        cookie('lng', $lng);
        echo NOW_TIME;
    }
    public function search() {
        $keys = D('Keyword')->fetchAll();
        $keytype = D('Keyword')->getKeyType();
        $this->assign('keys',$keys);
        $this->mobile_title = '搜索';
        $this->display();
    }
	
	public function more() {
        $this->mobile_title = '更多'; 
        $this->display();
    }

    public function language() {
        $lan = I('lan','','trim,htmlspecialchars');
        if (empty($this->uid)) {
            $this->ajaxReturn(array('status' => 'success', 'msg' => '切换语言成功！','lan'=>$lan)); 
        }else{

            $data = array();
            $data['user_id'] = $this->uid;
            switch ($lan) {
                case 'zh':
                    $data['lang'] = 1;
                    break;
                case 'lao':
                    $data['lang'] = 2;
                    break;
                case 'en':
                    $data['lang'] = 3;
                    break;
                default:
                    $data['lang'] = 1;
                    break;
            }
            
                    
            $user = D('Users');
            $save = $user -> save($data);
            if($save){
                $this->ajaxReturn(array('status' => 'success', 'msg' =>'切换语言成功！','lan'=>$lan)); 
            }else{
                $this->ajaxReturn(array('status' => 'error', 'msg' => '切换语言失败！')); 
            }
                    
        }
    }

}
