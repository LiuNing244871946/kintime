<?php



class MsgModel extends CommonModel{
    protected $pk   = 'msg_id';
    protected $tableName =  'msg';
    
    protected $types = array(
        'gift'      => '红包礼物',
        'movie'     => '电影资讯',
        'message'   => '个人消息',
        'coupon'    => '抢购优惠',
    );
    
    public function getType(){
        return $this->types;
    }
    
    protected function getTitle(){
        return array(
            'tuan' => '抢购',
            'goods' => '购物',
            'eleorder' => '外卖',
            'cloud' => '云购',
            'ding' => '订座',
            'breaks' => '优惠买单',
        );
    }

    

    public function sendmsg($user_id,$type,$id=null){
        $title = $this->getTitle();
        $user_id = (int) $user_id;
        $data = array(
            'user_id' => $user_id,
            'type' => 'message',
            'title' => '您有新的'.$title[$type].'订单消息',
            'intro' =>'您有新的'.$title[$type].'订单消息，订单编号为'.$id,
            'link_url' => 'http://' . $_SERVER['HTTP_HOST'].'/mcenter/'.$type.'/index.html',
            'create_time' => NOW_TIME,
            'create_ip' => get_client_ip(),
        );
        if($msg_id = D('Msg')->add($data)){
            return TRUE;
        }else{
            return false;
        }
    }
    
    
    
}