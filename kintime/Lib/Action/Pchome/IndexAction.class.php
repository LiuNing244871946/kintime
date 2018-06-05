<?php



class IndexAction extends CommonAction {
    
     public function _initialize() {
        parent::_initialize();
        $this->type = D('Keyword')->fetchAll();
        $this->assign('types', $this->type);
    }

    public function index() {
        if (is_mobile()) {
            header("Location:" . U('mobile/index/index'));
            die;
        }

        $hs = D('Houseworksetting');
        $workTypes = D('Housework')->getCfg();
        $hslist = $hs->select();
        $list = array();
        foreach ($workTypes as $k => $val) {
            foreach ($hslist as $kk => $v) {
                if ($k == $v['id']) {
                    $list[$k] = $v;
                }
            }
            $list[$k]['t'] = $val;
        }
		$this->assign('elecate', D('Ele')->getEleCate());
		$this->assign('channelmeans', D('Lifecate')->getChannelMeans());
        $this->assign('list',$list);
        $this->display();
    }
    
    
    public function get_arr(){
        
         if(IS_AJAX){
            
            $cate_id = I('val',0,'intval,trim');
            
            $today = date('Y-m-d');

            $t = D('Tuan');
            $map = array(
                'cate_id'=>$cate_id,
                'city_id'=>$this->city_id,
                'closed'=>0,
                'audit'=>1,
                'bg_date' => array('elt',NOW),
                'end_date'=>array('egt',NOW)
                
            );
            $r = $t->where($map)->limit(8)->select();
            
            if($r){
                $this->ajaxReturn(array('status'=>'success','arr'=>$r));
            }else{
                $this->ajaxReturn(array('status'=>'error'));
            }
            
        }
        
    }
    

    public function test() {
        
        //$data = D('Email')->sendMail('email_newpwd', '1442211217@qq.com', '重置密码', array('newpwd' => 123456));
       // var_dump($data);
        //var_dump(D('Email')->getEorrer());
       
    }

}
