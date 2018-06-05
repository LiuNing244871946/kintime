<?php



class FormatModel extends CommonModel {

    protected $pk = 'id';
    protected $tableName = 'format';
    
    public function format_goodsid($goods_id=0, $cate_id=0){
        if(!$cate_id = (int)$cate_id){
            if($goods = D('Goods')->find($goods_id)){
                $cate_id = $goods['shopcate_id'];
            }
        }
        if(!$cate_id){
            return false;
        }
        $shopcate = D('Goodsshopcate')->find($cate_id);
        $format_list = array();
        if($items = D('Format')->where(array('id'=>array('IN',$shopcate['format'])))->select()){           
            $ids = array();
            foreach($items as $k=>$v){
                $ids[$v['id']] = $v['id'];
                $format_list[$v['id']] = $v;
            }
        }
        if($values = D('FormatValue')->where(array('format_id'=>array('IN',implode(',', $ids))))->select()){
            foreach($values as $k=>$v){
                $format_list[$v['format_id']]['values'][$v['id']] = $v;
            }
        }        
        return $format_list;
    }
    
    public function format_content($format_id = 0){
        $detail = D('Goodsformat')->find($format_id);
        $goods_id = $detail['goods_id'];
        $formats = D('Format')->format_goodsid($goods_id);
        $detail[contents] = explode('-',$detail[content]);

        foreach($formats as $k=>$v){
            $content_detail[$k][title] = $v[name];
            $content_detail[$k][content] = $v[content][$detail[contents][$k]];
        }
        return $content_detail;
    }

    public function update_contents($format_id)
    {
        $contents = array();
        $values = D('FormatValue')->where(array('format_id'=>$format_id))->select();
        foreach($values as $v){
            $contents[] = $v['name'];
        }
        return $this->save(array('id'=>$format_id, 'content'=>implode(',', $contents)));
    }

}