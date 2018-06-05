<?php



class  NewsAction extends CommonAction{
    
    public function index(){
        $News = D('Shopnews');
        import('ORG.Util.Page'); // 导入分页类
        $map =array('audit'=>'1');
        $count = $News->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($count, 20); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $orderby = array('orderby' => 'asc', 'create_time' => 'asc');
        $list = $News->where($map)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }
    
    
    public function detail($news_id = 0){
        if ($news_id = (int) $news_id) {
            $obj = D('Shopnews');
            if (!$detail = $obj->find($news_id)) {
                $this->error('没有该新闻');
            }
            $obj->updateCount($news_id, 'views');
            $shop = D('Shop')->select();

            foreach($shop as $k=>$v){
                $shops[$v['shop_id']]=$v;
            }
            
            $this->assign('shops', $shops);
            $this->assign('detail', $detail);
            $this->assign('cate_id',$detail['cate_id']);
        
            $this->seodatas['title'] = $detail['title'];
            $this->seodatas['keywords'] = $detail['keywords'];
            $this->seodatas['desc'] = $detail['desc'];
            
            $this->display();
        } else {
            $this->error('没有该新闻');
        }
    }
    
    
    
    
}