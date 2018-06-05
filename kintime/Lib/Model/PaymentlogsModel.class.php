<?php



class PaymentlogsModel extends CommonModel{
    protected $pk   = 'log_id';
    protected $tableName =  'payment_logs';
    
    public function getLogsByOrderId($type,$order_id){
        $order_id = (int)$order_id;
        $type = addslashes($type);
        return $this->find(array('where'=>array('type'=>$type, 'order_id'=>$order_id)));
    }
    
}