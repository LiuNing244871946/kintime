<?php



class AddrsAction extends CommonAction {

	public function index() {
		$u = D('Users');
		$ud = D('UserAddr');
                $addr = $ud -> where('user_id='.$this->uid) -> select();
                $this->assign('addr',$addr);
                $this->mobile_title = '我的地址';
		$this->display(); // 输出模板
	}


	public function create() {
		if (empty($this->uid)) {
            $this->baoMsg('您还没有登录或登录超时！'); 
        }else{
        	$this->mobile_title = '添加收货地址';
        	$this->display(); // 输出模板
        }
		
	}


	public function edit($addr_id = 0) {
		if (empty($this->uid)) {
            $this->baoMsg('您还没有登录或登录超时！'); 
        }else{
        	if($addr_id = (int) $addr_id){
        		$obj = D('user_addr');
        		if (!$detail = $obj->find($addr_id)) {
	                $this->baoError('请选择要编辑的地址');
	            }
	            if (strlen($detail['mobile'])===13) {
	                $detail['areacode']=substr($detail['mobile'],0,2);
	                $detail['mobile']=substr($detail['mobile'],2,11);
	            }else if (strlen($detail['mobile'])===14) {
	                $detail['areacode']=substr($detail['mobile'],0,3);
	                $detail['mobile']=substr($detail['mobile'],3,11);
	            }
	            $this->assign('detail', $detail);
	            $this->mobile_title = '修改收货地址';
        		$this->display(); // 输出模板
        	}else{
        		$this->baoError('请选择要编辑的地址');
        	}
        	
        }
		
	}

  
}