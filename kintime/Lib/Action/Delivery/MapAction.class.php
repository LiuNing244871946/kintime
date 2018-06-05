<?php



class MapAction extends CommonAction {

	public function index($lat,$lng) {
		if (!cookie('DL')) {
            header("Location: " . U('login/index'));
        }else{
        	if(($lat = (float) $lat)&&($lng = (float) $lng) ){
        		$d_lat = addslashes( cookie( "lat" ) );
	            $d_lng = addslashes( cookie( "lng" ) );
	            if ( empty( $d_lat ) || empty( $d_lng ) )
	            {
	                $d_lat = $this->city['lat'];
	                $d_lng = $this->city['lng'];
	            }
	            $detail['d_lat'] = $d_lat;
	            $detail['d_lng'] = $d_lng;
	            $detail['lat'] = $lat;
	            $detail['lng'] = $lng;

	            $this->assign('detail', $detail);
	            $this->mobile_title = '地图导航';
        		$this->display(); // 输出模板
        	}else{
        		$this->baoError('地址信息错误');
        	}
        	
        }
		
	}

  
}