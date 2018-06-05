<?php



class MemberAction extends CommonAction {

	private function addressCheck() {
		$data = $this->checkFields($this->_post('data', false), array('addr_id', 'area_id', 'business_id', 'name', 'mobile', 'addr'));
		$data['name'] = htmlspecialchars($data['name']);
		if (empty($data['name'])) {
			$this->error('收货人不能为空');
		}
		$data['user_id'] = (int) $this->uid;
		$data['area_id'] = (int) $data['area_id'];
		$data['business_id'] = (int) $data['business_id'];
		if (empty($data['area_id'])) {
			$this->error('地区不能为空');
		}
		if (empty($data['business_id'])) {
			$this->error('商圈不能为空');
		}
		$data['mobile'] = htmlspecialchars($data['mobile']);
		if (empty($data['mobile'])) {
			$this->error('手机号码不能为空');
		}
		if (!isMobile($data['mobile'])) {
			$this->error('手机号码格式不正确');
		}
		$data['addr'] = htmlspecialchars($data['addr']);
		if (empty($data['addr'])) {
			$this->error('具体地址不能为空');
		}
		return $data;
	}

	public function addressadd() {
                if(empty($this->uid)){
                    AppJump();
                }
		if ($this->isPost()) {
			$data = $this->addressCheck();
			$obj = D('Useraddr');
			$data['is_default'] = 0;
			if ($obj->add($data)) {
				$backurl = $this->_post('backurl', 'htmlspecialchars');
				$this->success('新增收货地址成功', $backurl);
			}
			$this->error('操作失败！');
		} else {
			if (!empty($_SERVER['HTTP_REFERER']) && strstr($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST'])) {
				$backurl = $_SERVER['HTTP_REFERER'];
			} else {
				$backurl = U('mcenter/index/index');
			}
			$this->assign('backurl', $backurl);

			$this->assign('areas', D('Area')->fetchAll());
			$this->assign('business', D('Business')->fetchAll());
			$this->display();
		}
	}

}
