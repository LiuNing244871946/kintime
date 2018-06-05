<?php



class GoodstypeModel extends CommonModel{
    protected $pk   = 'type_id';
    protected $tableName =  'goods_type';
    
    
    public function _format($data){
        $data['all_price'] = round($data['price']/100,2); 
        $data['have_price'] = round($data['yunfei']/100,2); 
        return $data;
    }
}