<?php



class LifecateModel extends CommonModel {

    protected $pk = 'cate_id';
    protected $tableName = 'life_cate';
    protected $token = 'life_cate';
    protected $orderby = array(
        'is_hot'    => 'desc',
        'orderby'   => 'asc',
    );
    protected $channel = array(
        'ershou'  => 1,
        'car'     => 2,
        'qiuzhi'  => 3,
        'love'    => 4,
        'house'   => 5,
        'peixun'  => 6,
        'jobs'    => 7,
        'service' => 8,
        'jianzhi' => 9,
        'chongwu' => 10  
    );
    protected $channelMeans = array(
         1 => '二手',
         2 => '车辆',
         3 => '求职',
         4 => '交友',
         5 => '房屋',
         6 => '培训',
         7 => '招聘',
         8 => '服务',
         9 => '兼职',
        10 => '宠物'
    );

    public function getChannel(){
        return $this->channel;
    }

	public function getchild($channel)
	{
		$this->cates = D('Lifecate')->fetchAll();
		foreach ($this->cates as $val) {
			if ($val['channel_id'] == $channel) {
				$cates_ids.= $val['cate_id'].',';
			}
		}
		return substr($cates_ids,0,-1);
	}
    
    public function getChannelMeans(){
        return $this->channelMeans;
    }
}