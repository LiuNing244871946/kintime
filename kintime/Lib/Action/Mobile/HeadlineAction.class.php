<?php



class HeadlineAction extends CommonAction {

    public function index(){
        //商家类
        $adv = D('Ad');
        $shop_adv = $adv -> where(array('site_id'=>53)) -> select();
        $shop_news = D('ShopNews') ->order('rand()')-> limit(10)->select();
        //平台类
        $site_adv = $adv -> where(array('site_id'=>51)) -> select();
        $site_news = D('Article') ->order('rand()')-> limit(10) -> select();
        
        $this->assign('shop_adv',$shop_adv);
        $this->assign('shop_news',$shop_news);
        $this->assign('site_news',$site_news);
        $this->assign('site_adv',$site_adv);
		$this->display();
	}

	public function news_detail(){
        $news_id = I('news_id',0,'intval,trim');
        if(!$news_id){
            $this->error('错误的新闻!');
        }elseif(!$detail = D('ShopNews')->where(array('news_id'=>$news_id))->find()){
            $this->error('错误的新闻!');
        }else{
            if($shop = D('Shop')->where(array('shop_id'=>$detail['shop_id']))->find()){
                $detail['shop'] = $shop;
            }
            $other_news = D('ShopNews')->where(array('news_id'=>array('neq',$detail['news_id'])))->limit(5)->select();
            $this->assign('other_news',$other_news);
            $this->assign('detail',$detail);
            $this->display();
        }
	}
    
    public function article_detail(){
        $article_id = I('article_id',0,'intval,trim');
        if(!$article_id){
            $this->error('错误的新闻!');
        }elseif(!$detail = D('Article')->where(array('article_id'=>$article_id))->find()){
            $this->error('错误的新闻!');
        }else{
            $this->assign('detail',$detail);
            $this->display();
        }
	}

}
