<?php



class  SarticleAction extends CommonAction{
    
    public function index($cate_id = 0){
        $cates =  D('Articlecate')->fetchAll();
        if ($cate_id = (int) $cate_id) {
            $map['cate_id'] = $cate_id;
        }
 /*       
//取子分类
        $str =array();
        foreach($cates as $var){
            if($var['parent_id'] == 0 ){
                foreach($cates as $var2){
                    if($var2['parent_id'] == $var['cate_id']){
                        $str[] = $var2['cate_id'];
                        foreach($cates as $var3){
                            if($var3['parent_id'] == $var2['cate_id']){   
                               $str[] = $var3['cate_id'];
                            } 
                        }
                    }  
                }              
            }           
        }
        //print_r($str);die;
*/
        $obj = D('Article');
        import('ORG.Util.Page'); // 导入分页类
		$city_id = $this->city_id;
		$map['city_id'] =array('in',array(0,$city_id));
        $count = $obj->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($count, 20); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $orderby = array('orderby' => 'asc', 'create_time' => 'asc');
        $list = $obj->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list); // 赋值数据集
        $this->assign('cates', $cates);
		//print_r($cates);exit;
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }
    
    
    public function detail($article_id = 0){
        if ($article_id = (int) $article_id) {
            $obj = D('Article');
            if (!$detail = $obj->find($article_id)) {
                $this->error('没有该新闻');
            }
            $obj->updateCount($article_id, 'views');
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