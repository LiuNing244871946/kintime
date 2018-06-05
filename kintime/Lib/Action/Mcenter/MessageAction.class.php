<?php



class MessageAction extends CommonAction {

	public function index() {

        $this->mobile_title = '我的消息';
		$this->display();
	}

	public function msgshow($msg_id) {
		$msg_id = (int) $msg_id;
		if (!$detail = D('Msg')->find($msg_id)) {
			$this->error('消息不存在');
		}
		if ($detail['user_id'] != $this->uid && $detail['user_id'] != 0) {
			$this->error('您没有权限查看该消息');
		}
		if (!D('Msgread')->where(array('user_id' => $this->uid, 'msg_id' => $msg_id))->find()) {
			D('Msgread')->add(array(
				'user_id' => $this->uid,
				'msg_id' => $msg_id,
				'create_time' => NOW_TIME,
				'create_ip' => get_client_ip()
			));
		}
		if ($detail['link_url']) {
			header("Location:" . $detail['link_url']);
			die;
		}
        $this->mobile_title = '信息详情';
		$this->assign('detail', $detail);
		$this->display();
	}

  
}