<?php



class GoodsshopcateAction extends CommonAction {

    private $create_fields = array( 'cate_name', 'orderby', 'weidian_id');
    private $edit_fields = array( 'cate_name', 'orderby', 'weidian_id');

    public function _initialize() {
        parent::_initialize();
        $this->weidian = D('WeidianDetails')->where(array('mer_id' => $this->mer_id))->find();
        $this->weidian_id = $this->weidian['id'];
        $this->autocates = D('Goodsshopcate')->where(array('weiian_id' => $this->weiian_id))->select();
    }

    public function index() {
        $this->check_weidian();
        $this->check_format();
        $this->assign('autocates', $this->autocates);
        $this->display();
    }
    
    private function check_weidian(){
        
        $wd_res = $this->weidian;
        if(!$wd_res){
            $this->error('请先完善微店资料！',U('goods/weidian'));
        }elseif($wd_res['audit'] == 0){
            $this->error('您的微店正在审核中，请耐心等待！',U('index/index'));
        }elseif($wd_res['audit'] == 2){
            $this->error('您的微店未通过审核！',U('index/index'));
        }
        
    }
    
    private function check_format(){
        $f = D('Format');
        $res = $f -> where('weidian_id ='.$this->weidian_id) -> find();
        if(!$res){
            $this->error('您必须得先建立商品规格！',U('goodsshopformat/create'));
        }
    }
    
    
    
    public function create() {
        if ($this->isPost()) {
            $data = $this->createCheck();
            $obj = D('Goodsshopcate');
            $format = $_POST['format'];
            $data['format'] = implode(',',$format);
            $data['weidian_id'] = $this->weidian_id;
            if ($obj->add($data)) {
                $this->baoSuccess('添加成功', U('goodsshopcate/index'));
            }
            $this->baoError('操作失败！');
        } else {
            $formats = D('Format')->where(array('weidian_id'=>$this->weidian_id))->select();
            $this->assign('formats',$formats);
            $this->display();
        }
    }

    private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['cate_name'] = htmlspecialchars($data['cate_name']);
        if (empty($data['cate_name'])) {
            $this->baoError('分类名称不能为空');
        }
        $detail = D('Goodsshopcate')->where(array('weidian_id'=>$this->weidian_id,'cate_name'=>$data['cate_name']))->select();
        if(!empty($detail)){
            $this->baoError('分类名称已存在');
        }
        $data['orderby'] = (int) $data['orderby'];
        if (empty($data['orderby'])) {
           $data['orderby'] = 100;
        }
        return $data;
    }
    
    public function edit($cate_id=0) {
        if ($cate_id = (int) $cate_id) {
            $obj = D('Goodsshopcate');
            if (!$detail = $obj->find($cate_id)) {
                $this->baoError('请选择要编辑的商家分类');
            }
            if($detail['weidian_id'] != $this->weidian_id){
                $this->baoError('不可以修改别人的内容');
            }
            
            if ($this->isPost()) {
                $data = $this->editCheck();
                $format = $_POST['format'];
                $data['format'] = implode(',',$format);
                $data['cate_id'] = $cate_id;
                $data['weidian_id'] = $this->weidian_id;
                if (false !== $obj->save($data)) {
                    $this->baoSuccess('操作成功', U('goodsshopcate/index'));
                }
                $this->baoError('操作失败');
            } else {
                $formats = D('Format')->where(array('weidian_id'=>$this->weidian_id))->select();
                $this->assign('formats',$formats);
                $detail['format'] = explode(',', $detail['format']);
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->baoError('请选择要编辑的商家分类');
        }
    }

    private function editCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['cate_name'] = htmlspecialchars($data['cate_name']);
        if (empty($data['cate_name'])) {
            $this->baoError('分类名称不能为空');
        }
        $detail = D('Goodsshopcate')->where(array('weidian_id'=>$this->weidian_id,'cate_name'=>$data['cate_name']))->select();
        if(!empty($detail) && ($detail['cate_id'] != $data['cate_id'])){
            $this->baoError('分类名称已存在');
        }
        $data['orderby'] = (int) $data['orderby'];
        if (empty($data['orderby'])) {
           $data['orderby'] = 100;
        }
        return $data;
    }
    
    public function delete($cate_id=0){
        if (is_numeric($cate_id) && ($cate_id = (int) $cate_id)) {
            $obj = D('Goodsshopcate');
            if (!$detail = $obj->find($cate_id)) {
                $this->baoError('改分类不存在');
            }
            if($detail['weidian_id'] != $this->weidian_id){
                $this->baoError('改分类不存在');
            }
            
            $obj->delete($cate_id);
            $this->success('删除成功！', U('goodsshopcate/index'));
        }
    }
    
    
    

}
