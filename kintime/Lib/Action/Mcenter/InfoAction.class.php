<?php

class InfoAction extends CommonAction {

    public function sendsms() {
        $mobile = $this->_post('mobile');
        if (isMobile($mobile)) {
            session('mobile', $mobile);
            $randstring = session('code');
            if (empty($randstring)) {
                $randstring = rand_string(6, 1);
                session('code', $randstring);
            }
            D('Sms')->sendSms('sms_code', $mobile, array('code' => $randstring));
        }
    }

    public function password() {
        if ($this->isPost()) {
            $oldpwd = $this->_post('oldpwd', 'htmlspecialchars');
            if (empty($oldpwd)) {
                $this->baoMsg('旧密码不能为空！');
            }
            $newpwd = $this->_post('newpwd', 'htmlspecialchars');
            if (empty($newpwd)) {
                $this->baoMsg('请输入新密码');
            }
            $pwd2 = $this->_post('pwd2', 'htmlspecialchars');
            if (empty($pwd2) || $newpwd != $pwd2) {
                $this->baoMsg('两次密码输入不一致！');
            }
            $abc=1;
            if (strlen($newpwd) < 6 || strlen($pwd2) < 6) {
                $this->baoMsg('新密码长度必须要在6个字符以上');
            }
            if ($this->member['password'] != md5($oldpwd)) {
                $this->baoMsg('原密码不正确');
            }
            if (D('Passport')->uppwd($this->member['account'], $oldpwd, $newpwd)) {
                clearUid();
                $this->baoMsg('更改密码成功！', U('mobile/passport/login'));
            }
            $this->baoMsg('修改密码失败！');
        } else {
            $this->mobile_title = '修改密码';
            $this->display();
        }
    }

    public function base() {
        if ($this->isPost()) {
            $data['job'] = $this->_post('job', 'htmlspecialchars');
            $data['sex'] = (int) $this->_post('sex');
            $data['star_id'] = (int) $this->_post('star_id');
            $data['born_year'] = (int) $this->_post('born_year');
            $data['born_month'] = (int) $this->_post('born_month');
            $data['born_day'] = (int) $this->_post('born_day');
            $detail = D('Usersex')->getUserex($this->uid);
            
            $data['user_id'] = $detail['user_id'];
            if (false !== D('Usersex')->save($data)) {
                $this->baoSuccess('基本信息设置成功！', U('mcenter/information/index'));
            }
            $this->baoError('基本信息设置失败');
        } else {
            $usersex = D('Usersex')->find($this->uid);
            $stars = D('Usersex')->getStar();
            $this->assign('stars',$stars);
            $this->assign('usersex',$usersex);
            $this->mobile_title = '基本信息';
            $this->display();
        }
    }
	
	public function nickname() {
        if ($this->isPost()) {
            $nickname = $this->_post('nickname', 'htmlspecialchars');
            if (empty($nickname)) {
                $this->baoError('请填写昵称');
            }
            $data = array('user_id' => $this->uid, 'nickname' => $nickname);
            if (false !== D('Users')->save($data)) {
                $this->baoSuccess('昵称设置成功！', U('mcenter/information/index'));
            }
            $this->baoError('昵称设置失败');
        } else {
            $this->mobile_title = '设置昵称';
            $this->display();
        }
    }
    
}
