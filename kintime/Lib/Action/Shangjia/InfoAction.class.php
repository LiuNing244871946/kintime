<?php



class InfoAction extends CommonAction {

    public function ranking() {
        if ($this->member['gold'] < 5) {
            $this->baoError('账户金块余额不足！');
        }
        if (D('Users')->updateCount($this->uid, 'gold', -5)) {
            D('Usergoldlogs')->add(array(
                'user_id' => $this->uid,
                'gold' => -5,
                'intro' => '刷新排名',
                'create_time' => NOW_TIME,
                'create_ip' => get_client_ip()
            ));
            D('Shop')->save(array('shop_id' => $this->shop_id, 'ranking' => NOW_TIME));
            $this->baoSuccess('刷新排名成功！', U('index/main'));
        }
        $this->baoError('操作失败');
    }

    public function password() {
        if ($this->isPost()) {
            $oldpwd = $this->_post('oldpwd', 'htmlspecialchars');
            if (empty($oldpwd)) {
                $this->baoError('旧密码不能为空！');
            }
            $newpwd = $this->_post('newpwd', 'htmlspecialchars');
            if (empty($newpwd)) {
                $this->baoError('请输入新密码');
            }
            $pwd2 = $this->_post('pwd2', 'htmlspecialchars');
            if (empty($pwd2) || $newpwd != $pwd2) {
                $this->baoError('两次密码输入不一致！');
            }
            if ($this->member['password'] != md5($oldpwd)) {
                $this->baoError('原密码不正确');
            }
            $user = D('Users')->getUserByAccount($this->member['account']);
            if (flase !== D('Users')->save(array('user_id' => $user['user_id'], 'password' => md5($newpwd)))) {
                session('uid', null);
                $this->baoSuccess('更改密码成功！', U('login/index'));
            }
            $this->baoError('修改密码失败！');
        } else {
            $this->display();
        }
    }

        public function about() {
        if ($this->isPost()) {
            $data = $this->checkFields($this->_post('data', false), array('mer_name','logo','mobile','areacode'));
            $data['mer_name'] = htmlspecialchars($data['mer_name']);
            if (empty($data['mer_name'])) {
                $this->baoError('商家昵称不能为空');
            }
            $data['logo'] = htmlspecialchars($data['logo']);
            if (empty($data['logo'])) {
                $this->baoError('请上传商铺LOGO');
            }
            if (!isImage($data['logo'])) {
                $this->baoError('商家LOGO格式不正确');
            }
            $data['mobile'] = htmlspecialchars($data['mobile']);
            if (empty($data['mobile'])) {
                $this->baoError('手机号码不能为空');
            }
            $data['mobile'] = $data['areacode'].$data['mobile']; 
            $data['mer_id'] = $this->mer_id;
            if (false !== D('Merchants')->save($data)) {
                $this->baoSuccess('操作成功', U('info/about'));
            }
            $this->baoError('操作失败');
        } else {
            $this->display();
        }
    }

}
