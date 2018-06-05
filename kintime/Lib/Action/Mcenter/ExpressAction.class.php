<?php



class ExpressAction extends CommonAction {

    public function index() {
        $status = (int) $this->_param('status');
        $this->assign('status', $status);
        $this->mobile_title = '我的快递';
        $this->display();
    }

    public function loaddata() {
        $express = D('Express');
        import('ORG.Util.Page');
        $map = array('user_id' => $this->uid, 'closed' => 0);

        $status = (int) $this->_param('status');

        if ($status == 2) {
            $map['status'] = 2;
        } elseif ($status == 1) {
            $map['status'] = array(0, 1, -1);
        } else {
            $status == null;
        }
        $count = $express->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $express->where($map)->order('express_id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function detail($express_id){
            $express_id = (int) $express_id;
            if(empty($express_id) || !$detail = D('Express')->find($express_id)){
                $this->error('该快递不存在');
            }
            if($detail['user_id'] != $this->uid){
                $this->error('请不要操作他人的快递');
            }
            $this->assign('detail',$detail);
            $this->mobile_title = '快递详情';
            $this->display();
        }

    
    public function create() {
        if ($this->isPost()) {
            $data = $this->createCheck();
            if ($express_id = D('Express')->add($data)) {
                $this->baoSuccess('发布成功', U('express/index'));
            }
            $this->baoError('发布失败');
        } else {
            $this->mobile_title = '发快递';
            $this->display();
        }
    }

    public function createCheck() {
        $data = $this->_post('data', false);
        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->baoError('标题不能为空');
        }
        $data['from_name'] = htmlspecialchars($data['from_name']);
        if (empty($data['from_name'])) {
            $this->baoError('寄件人姓名不能为空');
        }
        $data['from_addr'] = htmlspecialchars($data['from_addr']);
        if (empty($data['from_addr'])) {
            $this->baoError('寄件人地址不能为空');
        }
        $data['from_mobile'] = htmlspecialchars($data['from_mobile']);
        if (empty($data['from_mobile'])) {
            $this->baoError('寄件人手机不能为空');
        }
        if (!isMobile($data['from_mobile'])) {
            $this->baoError('寄件人手机格式不正确');
        }
        $data['to_name'] = htmlspecialchars($data['to_name']);
        if (empty($data['to_name'])) {
            $this->baoError('收件人姓名不能为空');
        }
        $data['to_addr'] = htmlspecialchars($data['to_addr']);
        if (empty($data['to_addr'])) {
            $this->baoError('收件人地址不能为空');
        }
        $data['to_mobile'] = htmlspecialchars($data['to_mobile']);
        if (empty($data['to_mobile'])) {
            $this->baoError('收件人手机不能为空');
        }
        if (!isMobile($data['to_mobile'])) {
            $this->baoError('收件人手机格式不正确');
        }
        $data['city_id'] = $this->city_id;
        $data['area_id'] = $data['area_id'];
        $data['business_id'] = $data['business_id'];
        $data['user_id'] = $this->uid;
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        return $data;
    }

    public function edit($express_id) {
        if ($express_id = (int) $express_id) {
            $obj = D('Express');
            if (!$detail = $obj->find($express_id)) {
                $this->baoError('请选择要编辑的快递');
            }
            if ($detail['status'] != 0) {
                $this->baoError('该快递状态不允许被编辑');
            }
            if ($detail['closed'] == 1) {
                $this->baoError('该快递已被删除');
            }
            if ($this->isPost()) {
                $data = $this->editCheck();
                $data['express_id'] = $express_id;
                if (false !== $obj->save($data)) {
                    $this->baoSuccess('操作成功', U('express/index'));
                }
                $this->baoError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->mobile_title = '修改快递';
                $this->display();
            }
        } else {
            $this->baoError('请选择要编辑的快递信息');
        }
    }

    public function editCheck() {
        $data = $this->_post('data', false);
        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->baoError('标题不能为空');
        }
        $data['from_name'] = htmlspecialchars($data['from_name']);
        if (empty($data['from_name'])) {
            $this->baoError('寄件人姓名不能为空');
        }
        $data['from_addr'] = htmlspecialchars($data['from_addr']);
        if (empty($data['from_addr'])) {
            $this->baoError('寄件人地址不能为空');
        }
        $data['from_mobile'] = htmlspecialchars($data['from_mobile']);
        if (empty($data['from_mobile'])) {
            $this->baoError('寄件人手机不能为空');
        }
        if (!isMobile($data['from_mobile'])) {
            $this->baoError('寄件人手机格式不正确');
        }
        $data['to_name'] = htmlspecialchars($data['to_name']);
        if (empty($data['to_name'])) {
            $this->baoError('收件人姓名不能为空');
        }
        $data['to_addr'] = htmlspecialchars($data['to_addr']);
        if (empty($data['to_addr'])) {
            $this->baoError('收件人地址不能为空');
        }
        $data['to_mobile'] = htmlspecialchars($data['to_mobile']);
        if (empty($data['to_mobile'])) {
            $this->baoError('收件人手机不能为空');
        }
        if (!isMobile($data['to_mobile'])) {
            $this->baoError('收件人手机格式不正确');
        }
        $data['city_id'] = $this->city_id;
        $data['area_id'] = $data['area_id'];
        $data['business_id'] = $data['business_id'];
        return $data;
    }

    public function delete($express_id) {
        if (is_numeric($express_id) && ($express_id = (int) $express_id)) {
            $obj = D('Express');
            if (!$detail = $obj->find($express_id)) {
                $this->baoError('快递不存在');
            }
            if ($detail['closed'] == 1 || ($detail['status'] != 0 && $detail['status'] != 2)) {
                $this->baoError('该快递状态不允许被删除');
            }
            $obj->save(array('express_id' => $express_id, 'closed' => 1));
            $this->baoSuccess('删除成功！', U('express/index'));
        }
    }

}
