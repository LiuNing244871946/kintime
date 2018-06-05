<?php



class PhoneAction extends CommonAction {

    public function index() {
        $phones = D('Convenientphone');
        import('ORG.Util.Page'); // 导入分页类
		
		$community_id = $this->community_id;
		$map['id']=array('in',array(0,$community_id));
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['name'] = array('LIKE', '%' . $keyword . '%');
        }
        $count = $phones->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 16); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $phones->order(array('phone_id' => 'desc'))->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->display();
    }

    public function delete() {
        if (IS_AJAX) {
            $phone_id = (int) $_POST['phone_id'];
            $obj = D('Convenientphone');
            if (empty($phone_id)) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '便民电话不存在'));
            }
            if (!$detail = $obj->find($phone_id)) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '便民电话不存在'));
            }
            if ($detail['community_id'] != $this->community_id) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '不是您小区的'));
            }
            $obj->delete($phone_id);
            $this->ajaxReturn(array('status' => 'success', 'msg' => '删除成功'));
        }
    }

    public function create() {
        if ($this->isPost()) {
            $data = $this->checkCreate();
            $obj = D('Convenientphone');
            if ($phone_id = $obj->add($data)) {
                $this->baoSuccess('添加成功', U('phone/index'));
            }
            $this->baoError('操作失败！');
        } else {
            $this->display();
        }
    }

    public function checkCreate() {
        $data = $this->checkFields($this->_post('data', false), array('name', 'phone', 'expiry_date','orderby'));
        $data['community_id'] = (int) $this->community_id;
        $data['name'] = htmlspecialchars($data['name']);
        $data['phone'] = htmlspecialchars($data['phone']);
        $data['expiry_date'] = htmlspecialchars($data['expiry_date']);
        if (empty($data['name'])) {
            $this->baoError('名称不能为空');
        }
        if (empty($data['phone'])) {
            $this->baoError('电话不能为空');
        }
        if (empty($data['expiry_date'])) {
            $this->baoError('过期时间不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['name'])) {
            $this->baoError('名称含有敏感词：' . $words);
        }
        $data['orderby'] = (int) $data['orderby'];
        return $data;
    }
    
    public function edit($phone_id) {
        if ($phone_id = (int) $phone_id) {
            $obj = D('Convenientphone');
            if(empty($phone_id)){
                $this->baoError('便民电话不存在');
            }
            if(!$detail = $obj->find($phone_id)){
                $this->baoError('便民电话不存在');
            }
            if($detail['community_id'] != $this->community_id){
                 $this->baoError('不能操作其他小区电话');
            }
            if ($this->isPost()) {
                $data = $this->checkEdit();
                if ($phone_id = $obj->add($data)) {
                    $this->baoSuccess('编辑成功', U('phone/index'));
                }
                $this->baoError('操作失败！');
            } else {
                $this->assign('detail',$detail);
                $this->display();
            }
        }else{
             $this->baoError('请选择要编辑的便民电话');
        }
    }

    public function checkEdit() {
        $data = $this->checkFields($this->_post('data', false), array('name', 'phone', 'expiry_date','orderby'));
        $data['name'] = htmlspecialchars($data['name']);
        $data['phone'] = htmlspecialchars($data['phone']);
        $data['expiry_date'] = htmlspecialchars($data['expiry_date']);
        if (empty($data['name'])) {
            $this->baoError('名称不能为空');
        }
        if (empty($data['phone'])) {
            $this->baoError('电话不能为空');
        }
        if (empty($data['expiry_date'])) {
            $this->baoError('过期时间不能为空');
        }
        if ($words = D('Sensitive')->checkWords($data['name'])) {
            $this->baoError('名称含有敏感词：' . $words);
        }
        $data['orderby'] = (int) $data['orderby'];
        return $data;
    }
    

}
