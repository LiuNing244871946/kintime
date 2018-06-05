<?php



class  PhotoAction extends CommonAction{
    
    public function index(){ //不用分页了！
        $Shoppic = D('Shoppic');
        $map = array('mer_id' =>  $this->mer_id);
        $list = $Shoppic->where($map)->order(array('orderby'=>'desc'))->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('sig',md5($this->mer_id.C('AUTH_KEY')));
        $this->display(); // 输出模板
    }
    
    public function update(){
        $title   = $this->_post('title',false);
        $lao_title   = $this->_post('lao_title',false);
        $eng_title   = $this->_post('eng_title',false);
        $orderby = $this->_post('orderby',false);
        $Shoppic = D('Shoppic');
        $map = array('mer_id' =>  $this->mer_id);
        
        if($photo_list = $Shoppic->where($map)->order(array('orderby'=>'desc'))->select()){
            foreach($photo_list as $k=>$val){
                $data = array(
                    'pic_id' => (int)$val['pic_id'],
                    'title'  => htmlspecialchars($title[$val['pic_id']]),
                    'lao_title'  => htmlspecialchars($lao_title[$val['pic_id']]),
                    'eng_title'  => htmlspecialchars($eng_title[$val['pic_id']]),
                    'orderby' => $orderby[$val['pic_id']]
                );
                $Shoppic->save($data);
            }            
        }
        $this->baoSuccess('更新成功！',U('photo/index'));
    }
    
    public function delete(){
        $pic_id = (int)$this->_get('pic_id');
        $obj = D('Shoppic');
        $detail = $obj->find($pic_id);
        if(!empty($detail) && $detail['mer_id'] == $this->mer_id){
            $obj->delete($pic_id);
            $this->baoSuccess('删除成功！',U('photo/index'));
        }
        $this->baoError('你懂的');
    }
    
    
    public function banner(){ 
        $Shopbanner = D('Shopbanner');
        $map = array('mer_id' =>  $this->mer_id,'is_mobile'=>1);
        $list = $Shopbanner->where($map)->order(array('orderby'=>'desc'))->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('sig',md5($this->mer_id.C('AUTH_KEY')));
        $this->display(); // 输出模板
    }
    public function banner1(){ 
        $Shopbanner = D('Shopbanner');
        $map = array('mer_id' =>  $this->mer_id ,'is_mobile'=>0);
        $list = $Shopbanner->where($map)->order(array('orderby'=>'desc'))->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('sig',md5($this->mer_id.C('AUTH_KEY')));
        $this->display(); // 输出模板
    }
    
    public function updatebanner(){
        $title   = $this->_post('title',false);
        $orderby = $this->_post('orderby',false);
        $obj = D('Shopbanner');
        foreach($orderby as $k=>$val){
            $data = array();
            $val = (int)$val;
            $detail = $obj->find($k);
            if(!empty($detail) && $detail['mer_id'] == $this->mer_id){
                $data = array(
                    'banner_id' => (int)$k,
                    'title'  => htmlspecialchars($title[$k]),
                    'orderby' => $val,
                );
                $obj->save($data);
            }
        }
        $this->baoSuccess('更新成功！',U('photo/banner'));
    }
    
    public function deletebanner(){
        $banner_id = (int)$this->_get('banner_id');
        $obj = D('Shopbanner');
        $detail = $obj->find($banner_id);
        if(!empty($detail) && $detail['mer_id'] == $this->mer_id){
            $obj->delete($banner_id);
            $this->baoSuccess('删除成功！',U('photo/banner'));
        }
        $this->baoError('你懂的');
    }
    
    
    public function updatebanner1(){
        $title   = $this->_post('title',false);
        $orderby = $this->_post('orderby',false);
        $obj = D('Shopbanner');
        foreach($orderby as $k=>$val){
            $data = array();
            $val = (int)$val;
            $detail = $obj->find($k);
            if(!empty($detail) && $detail['mer_id'] == $this->mer_id){
                $data = array(
                    'banner_id' => (int)$k,
                    'title'  => htmlspecialchars($title[$k]),
                    'orderby' => $val,
                );
                $obj->save($data);
            }
        }
        $this->baoSuccess('更新成功！',U('photo/banner1'));
    }
    
    public function deletebanner1(){
        $banner_id = (int)$this->_get('banner_id');
        $obj = D('Shopbanner');
        $detail = $obj->find($banner_id);
        if(!empty($detail) && $detail['mer_id'] == $this->mer_id){
            $obj->delete($banner_id);
            $this->baoSuccess('删除成功！',U('photo/banner1'));
        }
        $this->baoError('你懂的');
    }
    
    
}