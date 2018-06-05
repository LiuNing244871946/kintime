<?php



class EleAction extends CommonAction {

    private $create_fields = array('shop_id','is_open', 'package_money', 'since_money', 'sold_num', 'month_num', 'intro', 'lao_intro', 'eng_intro', 'orderby','cate');
    protected $ele;

    public function _initialize() {
        parent::_initialize();
        $this->shop_id = D('Shop')->where('mer_id='.$this->mer_id)->find()['shop_id'];
        if (empty($this->shop_id) && ACTION_NAME != 'apply') {
            $this->error('您还没有入住美食频道', U('shop/about'));
        }
        $this->is_ele = D('Shop')->where('mer_id='.$this->mer_id)->find()['is_ele'];
        if ($this->is_ele==0) {
            $this->error('您还没有开通外卖频道', U('shop/about'));
        }
        $getEleCate = D('Ele')->getEleCate();
        $this->assign('getEleCate', $getEleCate);
        $this->ele = D('Ele')->find($this->shop_id);
        if (empty($this->ele) && ACTION_NAME != 'apply') {
            $this->error('您还没有填写外卖资料', U('ele/apply'));
        }
        if (!empty($this->ele) && $this->ele['audit'] == 0) {
            $this->error("亲，您的资料正在审核中！");
        }
        $this->assign('ele', $this->ele);
    }

    public function index() {
        $this->display();
    }

    public function setting(){
        $detail = D('Shopsetting')->where(array('shop_id'=>$this->shop_id))->find();
        if ($this->isPost()) {
            $data = $this->_param('data',false);
            if(empty($detail)){
                $data['shop_id'] = $this->shop_id;
                if($set_id = D('Shopsetting')->add($data)){
                    $this->baoSuccess('设置成功', U('ele/setting'));
                }
            }else{
                $data['set_id'] = $detail['set_id'];
                if(false !== D('Shopsetting')->save($data)){
                    $this->baoSuccess('设置成功', U('ele/setting'));
                }
            }
        }else{
            $this->assign('detail',$detail);
            $this->display();
        }
    }

    

    
    public function open() {
        $is_open = (int) $_POST['is_open'];
        D('Ele')->save(array(
            'shop_id' => $this->shop_id,
            'is_open' => $is_open
        ));
        $this->baoSuccess('操作成功！', U('ele/index'));
    }

    public function apply() {
        $this->assign("area", D("Area")->fetchAll());
        $this->assign("city", D("City")->fetchAll());
        $ele = D('ele')->where('shop_id='.$this->shop_id)->find();
        $shop = D('Shop')->where('shop_id='.$this->shop_id)->find();
        $this->assign("shop", $shop);
        if ($this->isPost()) {
            $data = $this->applyCheck();
            $obj = D('Ele');
            $cate = $this->_post('cate', false);
            $cate = implode(',', $cate);
            $data['cate'] = $cate;
            $data['audit'] = 0;
            if($ele){
                if ($obj->where('shop_id='.$this->shop_id)->save($data)) {
                    $this->baoSuccess('添加成功', U('ele/index'));
                }
            }else{
                if ($obj->add($data)) {
                    $this->baoSuccess('添加成功', U('ele/index'));
                }
            }
            $this->baoError('操作失败！');
        } else {
            if($ele){
                $this->assign('ele',$ele);
                $cate = explode(',', $ele['cate']);
                $this->assign('cate', $cate);
            }
            $this->display();
        }
    }

    private function applyCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['shop_id'] = $this->shop_id;
        if (empty($data['shop_id'])) {
            $this->baoError('ID不能为空');
        }
        if (!$shop = D('Shop')->find($data['shop_id'])) {
            $this->baoError('商家不存在');
        }
        $data['is_open'] = (int) $data['is_open'];
        $data['package_money'] = (int) ($data['package_money']);
        $data['since_money'] = (int) ($data['since_money']);
        $data['intro'] = SecurityEditorHtml($data['intro']);
        if (empty($data['intro'])) {
            $this->baoError('中文说明不能为空');
        }
        $data['lao_intro'] = SecurityEditorHtml($data['lao_intro']);
        if (empty($data['lao_intro'])) {
            $this->baoError('老文说明不能为空');
        }
        $data['eng_intro'] = SecurityEditorHtml($data['eng_intro']);
        if (empty($data['eng_intro'])) {
            $this->baoError('英文说明不能为空');
        }
        return $data;
    }

}
