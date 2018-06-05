<?php
class  UserslookModel extends CommonModel{
    protected $pk   = 'look_id';
    protected $tableName =  'users_look';
    
    public function look($user_id,$shop_id,$type){
        $user_id = (int)$user_id;
        $shop_id = (int)$shop_id;
        $type = (int)$type;
        $data = $this->find(array("where"=>array('user_id'=>$user_id,'shop_id'=>$shop_id,'type'=>$type)));
        if(empty($data)){
            return $this->add(array(
                'user_id'   => $user_id,
                'shop_id'   => $shop_id,
                'type'   => $type,
                'last_time' => NOW_TIME
            ));
        }else{
            return $this->save(array(
                'look_id'   => $data['look_id'],
                'last_time' => NOW_TIME
            ));
            
        }        
    }
}
