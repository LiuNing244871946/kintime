<?php

class ShopfavoritesModel extends CommonModel{
    protected $pk   = 'favorites_id';
    protected $tableName =  'shop_favorites';
    
    public function check($shop_id,$user_id,$type){
        $data = $this->find(array('where'=>array('shop_id'=>(int)$shop_id,'user_id'=>(int)$user_id,'type'=>(int)$type)));
        return $this->_format($data);
    }
    
}