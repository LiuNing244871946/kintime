<?php



class InformationAction extends CommonAction {

	public function index() {
		$u = D('Users');
		$ud = D('UserAddr');
                $bc = D('Connect');
		$map = array('user_id' => $this->uid);
		$res = $u-> where($map) -> find();
		$addr_count = $ud -> where($map) -> count();
                $rbc = $bc -> where('uid ='.($this->uid)) -> select();
                $bind = array();
                foreach($rbc as $val){
                    $bind[$val['type']] = $val;
                }
                //print_r($bind);
		$this->assign('res',$res);
		$this->assign('addr_count',$addr_count);
                $this->assign('bind',$bind);
                
                $userexs = D('Usersex')->find($this->uid);
                if(!empty($userexs['sex'])&& !empty($userexs['star_id'])&& !empty($userexs['job']) && !empty($userexs['born_year'])&& !empty($userexs['born_month'])&& !empty($userexs['born_day'])){
                    $cp = 1;
                }else{
                    $cp = 1;
                }
                $this->assign('cp',$cp);
                $this->mobile_title = '账户信息';
		$this->display(); // 输出模板
	}
        
        public function upload_face(){
                
            if(!$this->uid){
                $this->ajaxReturn(array('status'=>'error','message'=>'您没有登录或登录超时！'));
            }else{
                $avatar = I('avatar','','trim,htmlspecialchars');
                if(!$avatar){
                    $this->ajaxReturn(array('status'=>'error','message'=>'没有上传头像！'));
                }else{
                    $u = D('Users');
                    $up = $u -> where('user_id ='.($this->uid))-> setField('face',$avatar);
                    if($up){
                        $this->ajaxReturn(array('status'=>'success','message'=>'修改成功！'));
                    }else{
                       $this->ajaxReturn(array('status'=>'error','message'=>'修改失败！')); 
                    }
                }
            }
            
        }
  
}