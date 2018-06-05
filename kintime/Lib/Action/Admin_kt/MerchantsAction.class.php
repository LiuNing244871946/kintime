<?php

class MerchantsAction extends CommonAction {

    private $create_fields = array('user_id', 'mer_name','logo','areacode','mobile','audit','create_time','create_ip');
    private $edit_fields = array('user_id', 'mer_name','logo','areacode','mobile','audit','create_time','create_ip');

    public function index() {
        $merchants = D('Merchants');
        import('ORG.Util.Page'); // 导入分页类
        $map = array( 'audit' => 1);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['mer_name|mobile'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }

        $count = $merchants->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 25); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $merchants->order(array('mer_id' => 'asc'))->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $ids = array();
        foreach ($list as $k => $val) {
            if ($val['user_id']) {
                $ids[$val['user_id']] = $val['user_id'];
            }
        }
        $this->assign('users', D('Users')->itemsByIds($ids));
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function apply() {
        $merchants = D('Merchants');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('closed' => 0, 'audit' => 0);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['mer_name|mobile'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }

        $count = $merchants->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 25); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $merchants->order(array('mer_id' => 'asc'))->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $ids = array();
        foreach ($list as $k => $val) {

            if ($val['user_id']) {
                $ids[$val['user_id']] = $val['user_id'];
            }
        }
        $this->assign('users', D('Users')->itemsByIds($ids));
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function create() {
        if ($this->isPost()) {
            $data2=$data = $this->createCheck();
            $obj = D('Merchants');
            if ($obj->add($data)) {
                $this->baoSuccess('添加成功', U('merchants/apply'));
            }
            $this->baoError('操作失败！');
        } else {
            $this->display();
        }
    }

    public function select() {
        $merchants = D('Merchants');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('audit' => 1);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['mer_name|mobile'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }

        $count = $merchants->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $merchants->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $ids = array();
        foreach ($list as $k => $val) {

            if ($val['user_id']) {
                $ids[$val['user_id']] = $val['user_id'];
            }
        }
        $this->assign('users', D('Users')->itemsByIds($ids));
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['user_id'] = (int) $data['user_id'];
        if (empty($data['user_id'])) {
            $this->baoError('管理者不能为空');
        }
        $merchants = D('Merchants')->find(array('where' => array('user_id' => $data['user_id'])));
        if (!empty($merchants)) {
            $this->baoError('该用户已申请开通商家');
        }
        
        $data['mer_name'] = htmlspecialchars($data['mer_name']);
        if (empty($data['mer_name'])) {
            $this->baoError('商家名称不能为空');
        }
        $data['logo'] = htmlspecialchars($data['logo']);
        if (empty($data['logo'])) {
            $this->baoError('请上传商家头像');
        }
        if (!isImage($data['logo'])) {
            $this->baoError('商家上传头像格式不正确');
        } 
        $data['areacode'] = (int) $data['areacode'];
        $data['mobile'] = htmlspecialchars($data['mobile']);
        if (empty($data['mobile'])) {
            $this->baoError('商家电话不能为空');
        }
        $data['mobile'] = $data['areacode'].$data['mobile'];
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        return $data;
    }

    public function edit($mer_id = 0) {

        if ($mer_id = (int) $mer_id) {
            $obj = D('Merchants');
            if (!$detail = $obj->find($mer_id)) {
                $this->baoError('请选择要编辑的商家');
            }
            if ($this->isPost()) {
                $data = $this->editCheck($mer_id);
                $data['mer_id'] = $mer_id;
                
                if ($obj->save($data)) {
                    $this->baoSuccess('操作成功', U('merchants/index'));
                }
                $this->baoError('操作失败');
            } else {
                if (strlen($detail['mobile'])===13) {
                    $detail['areacode']=substr($detail['mobile'],0,2);
                    $detail['mobile']=substr($detail['mobile'],2,11);
                }else if (strlen($detail['mobile'])===14) {
                    $detail['areacode']=substr($detail['mobile'],0,3);
                    $detail['mobile']=substr($detail['mobile'],3,11);
                }
                $this->assign('user', D('Users')->find($detail['user_id']));
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->baoError('请选择要编辑的商家');
        }
    }

    private function editCheck($mer_id) {
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['user_id'] = (int) $data['user_id'];
        if (empty($data['user_id'])) {
            $this->baoError('管理者不能为空');
        }
        $merchants = D('Merchants')->find(array('where' => array('user_id' => $data['user_id'])));
        if (!empty($merchants) && $merchants['mer_id'] != $mer_id) {
            $this->baoError('该管理者已经拥有商铺了');
        }
        
        $data['mer_name'] = htmlspecialchars($data['mer_name']);
        if (empty($data['mer_name'])) {
            $this->baoError('商家名称不能为空');
        }
        $data['logo'] = htmlspecialchars($data['logo']);
        if (empty($data['logo'])) {
            $this->baoError('请上传商家头像');
        }
        if (!isImage($data['logo'])) {
            $this->baoError('商家头像格式不正确');
        }
        $data['areacode'] = (int) $data['areacode'];
        $data['mobile'] = htmlspecialchars($data['mobile']);
        if (empty($data['mobile'])) {
            $this->baoError('商家电话不能为空');
        }

        $data['mobile'] = $data['areacode'].$data['mobile'];
        return $data;
    }

    public function audit($mer_id = 0) {
        if (is_numeric($mer_id) && ($mer_id = (int) $mer_id)) {
            $obj = D('Merchants');
            $obj->save(array('mer_id' => $mer_id, 'audit' => 1));
            $this->baoSuccess('审核成功！', U('merchants/apply'));
        } else {
            $mer_id = $this->_post('mer_id', false);
            if (is_array($mer_id)) {
                $obj = D('Merchants');
                foreach ($mer_id as $id) {
                    $obj->save(array('mer_id' => $id, 'audit' => 1));
                }
                $this->baoSuccess('审核成功！', U('merchants/apply'));
            }
            $this->baoError('请选择要审核的商家');
        }
    }

    public function login($mer_id) {
        $obj = D('Merchants');
        if (!$detail = $obj->find($mer_id)) {
            $this->error('请选择要编辑的商家');
        }
        if (empty($detail['user_id'])) {
            $this->error('该用户没有绑定管理者');
        }
        setUid($detail['user_id']);
        header("Location:" . U('shangjia/index/index'));
        die;
    }
  

}
