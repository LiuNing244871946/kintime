<?php



class TribeAction extends CommonAction {

   public function index() {
        $tribecollect = D('Tribecollect');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('user_id' => $this->uid);
        $count = $tribecollect->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 15); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $tribecollect->where($map)->order(array('tribe_id'=>'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        
        $tribe_ids = array();
        foreach($list as $k=>$val){
            $tribe_ids[$val['tribe_id']] = $val['tribe_id'];
        }
        $this->assign('tribes',D('Tribe')->itemsByIds($tribe_ids));
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出 
		$this->display();
    }

    public function collect() {
        $tribe_id = (int) $this->_get('tribe_id');
        if (!$detail = D('Tribe')->find($tribe_id)) {
            $this->baoError('没有该部落');
        }
        if ($detail['closed']) {
            $this->baoError('该部落已经被删除');
        }
        if (D('Tribecollect')->check($tribe_id, $this->uid)) {
            if(D('Tribecollect')->where(array('tribe_id'=>$tribe_id,'user_id'=>$this->uid))->delete()){
                D('Tribe')->updateCount($tribe_id,'fans',-1);
                $this->baoSuccess('取消关注成功！',U('tribe/index'));
            }
            $this->baoError('取消失败！');
        }else{
            $data = array(
                'tribe_id' => $tribe_id,
                'user_id' => $this->uid,
            );
            if (D('Tribecollect')->add($data)) {
                D('Tribe')->updateCount($tribe_id,'fans');
                $this->baoSuccess('恭喜您关注成功！',U('tribe/index'));
            }
            $this->baoError('关注失败！');
        }
    }
    
}
