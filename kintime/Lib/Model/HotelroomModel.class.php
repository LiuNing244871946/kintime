<?php



class HotelroomModel extends CommonModel{
    protected $pk   = 'room_id';
    protected $tableName =  'hotel_room';
    
    
    public function getRoomType(){
        return array(
            1 => '双床房',
            2 => '单人房',
            3 => '大床房',
            4 => '无烟房',
        );
    }

    public function laoGetRoomType(){
        return array(
            1 => '老双床房',
            2 => '老单人房',
            3 => '老大床房',
            4 => '老无烟房',
        );
    }

    public function engGetRoomType(){
        return array(
            1 => '英双床房',
            2 => '英单人房',
            3 => '英大床房',
            4 => '英无烟房',
        );
    }
     
}