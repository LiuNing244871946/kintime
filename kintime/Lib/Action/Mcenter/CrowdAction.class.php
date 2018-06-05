<?php



class CrowdAction extends CommonAction {

	public function index() {
        $this->assign('nextpage', LinkTo('crowd/loaddata', array('user_id'=>$this->uid, 't' => NOW_TIME, 'p' => '0000')));
        $this->mobile_title = '我的众筹';
        $this->display(); // 输出模板
    }

    public function loaddata() {
        $Goods = D('Goods');
		$Goodslist = D('Goodslist');
        import('ORG.Util.Page'); // 导入分页类
        $map = array('user_id' => $this->uid);
        $detail = D('Goods')->find($goods_id);
        $count = $Goods->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出

        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
		$orderby = array('dateline'=>'desc');
        $list = $Goodslist->where($map)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach($list as $k => $v){
			$goods_ids[$v['goods_id']] = $v['goods_id'];
			$type_ids[$v['type_id']] = $v['type_id'];
		}
		if (!empty($type_ids)) {
			$this->assign('type', D('Goodstype')->itemsByIds($type_ids));
		}
		if (!empty($goods_ids)) {
			$this->assign('goodss', $Goods->itemsByIds($goods_ids));
		}
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }
}