<?php



class SignAction extends CommonAction {

    public function signed() {
		
        if(!empty($this->uid)){
            $integral = $this->_CONFIG['integral']['sign'];
            $data = D('Usersign')->getSign($this->uid,$integral);
           
            $this->assign('sign',D('Usersign')->getSign($this->uid,$integral));
		}else{
			AppJump();
		}
        $this->mobile_title = '签到';
        $this->display();
    }
    public function signing(){
        if(empty($this->uid)){
            AppJump();            
        }
        $integal = D('Usersign')->sign($this->uid,$this->_CONFIG['integral']['sign'],$this->_CONFIG['integral']['firstsign']);
        if($integal !== false){
            $this->success('恭喜您签到成功！系统赠送了您'.$integal.'积分',U('sign/signed'));
        }else{
            $this->error('很抱歉您已经签到过了！',U('sign/signed'));
        }
    }
    
    
    
}

