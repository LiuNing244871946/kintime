<?php
class LifeAction extends CommonAction {
    
    public function index() {        
        $this->mobile_title = '我的信息';
        $Life = D('Life');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('user_id' => $this->uid); //分类信息是关联到UID 的 
        $count = $Life->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $Life->where($map)->order(array('last_time' => 'desc'))->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('areas', D('Area')->fetchAll());
        $this->assign('business', D('Business')->fetchAll());
        $this->assign('cates', D('Lifecate')->fetchAll());
        $this->assign('channelmeans', D('Lifecate')->getChannelMeans());
        $this->display();
    }
    
    public function del(){
         $life_id = I('life_id','','intval,trim');
        
        if(!$life_id){
            $this->error('没有选择！');
        }else{
            
            $l = D('Life');
            $r = $l->where('life_id ='.$life_id." and  user_id=".$this->uid)->delete();
            if($r){
                $this->success('删除成功！',U('mcenter/life/index'));
            }else{
                $this->error('删除失败！');
            }
        }
    }
    
}