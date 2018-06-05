<?php



class ListsAction extends CommonAction {

    public function index() {
        if(!cookie('DL')){
		header("Location: " . U('login/index'));
		}else{
			$cid = $this->reid();
			$dv = D('DeliveryOrder');
            $dvuser = D('Delivery');
            $rdv = $dvuser->where('id =' . $cid)->find();
			//条件开始
			$map = array(
                'city_id' => $rdv['city_id']
            );
			
            $ss = i( "ss", 0, "intval,trim" );
            $this->assign( "ss", $ss );
            if ( $ss == 2 ) {
                $map['status'] = 2;
                $map['delivery_id'] = $cid;
            }
            else if ( $ss == 3 ){
                $map['status'] = 3;
                $map['delivery_id'] = $cid;
            }
            else{
                $map['status'] = 1;
                $map['delivery_id'] = 0;
            }
			//条件结束
			//计算那个距离开始
			$lat = addslashes( cookie( "lat" ) );
            $lng = addslashes( cookie( "lng" ) );
            if ( empty( $lat ) || empty( $lng ) )
            {
                $city_row = D('City')->find($rdv['city_id']);
                $lat = $city_row['lat'];
                $lng = $city_row['lng'];
            }
            $orderby = " (ABS(lng - '".$lng."') +  ABS(lat - '{$lat}') ) asc ";
            $rdv = $dv->where( $map )->order( $orderby )->select( );//赋值过程
            foreach ( $rdv as $k => $val )
            {
                $rdv[$k]['d'] = getdistance( $lat, $lng, $val['lat'], $val['lng'] );
                $rdv[$k]['ud'] = getdistance( $lat, $lng, $val['user_lat'], $val['user_lng'] );
            }
            
			//计算那个距离结束
            $this->assign('rdv',$rdv);
		}
        // var_dump($map);
		$this->display();      
    }
    
  
	
    public function handle(){
        
        if(IS_AJAX){
            
            $id = I('order_id',0,'trim,intval');
            $dvo = D('DeliveryOrder');
            
            if(!cookie('DL')){
                $this->ajaxReturn(array('status'=>'error','message'=>'您还没有登录或登录超时!'));
            }else{
                $f = $dvo -> where('order_id ='.$id) -> find();
                if(!$f){
                    $this->ajaxReturn(array('status'=>'error','message'=>'错误!'));
                }else{
                    $cid = $this->reid(); //获取配送员ID
                    $data = array(
                        'delivery_id' => $cid,
                        'status' => 2,
						'update_time' => time()
                    );
					
					
                    $up = $dvo -> where('order_id ='.$id) -> setField($data);
                    if($up){
                        
                        if($f['type'] == 0){//商城
                            $old = D('Order');
                        }elseif($f['type'] == 1){//外卖
                            $old = D('EleOrder');
                        }elseif($f['type'] == 2){//商城异地
                            $old = D('Order');
                            $mail_data = array(
                                'delivery_id' => $cid,
                                'status' => 1,
                                'update_time' => time()
                            );
                            D('MailOrder') -> where('mail_order_id ='.$f['type_order_id']) -> setField($mail_data);
                        }

                        $old -> where('order_id ='.$f['type_order_id']) -> setField('status',4);
						// D('Ordergoods') -> where('order_id ='.$f['type_order_id']) -> setField('status',1);
					
                        $this->ajaxReturn(array('status'=>'success','message'=>'恭喜您！接单成功！请尽快进行配送！'));
                    }else{
                        $this->ajaxReturn(array('status'=>'error','message'=>'接单失败！错误！'));
                    }
                }
            }
            
        }
        
        
    }
    

    
    public function set_ok(){
        if(IS_AJAX){
            $id = I('order_id',0,'trim,intval');
            $dvo = D('DeliveryOrder');
            if(!cookie('DL')){
                $this->ajaxReturn(array('status'=>'error','message'=>'您还没有登录或登录超时!'));
            }else{
                $f = $dvo -> where('order_id ='.$id) -> find();
                if(!$f){
                    $this->ajaxReturn(array('status'=>'error','message'=>'订单不存在'));
                }else{
                    $cid = $this->reid(); //获取配送员ID
                    /*if($cid == 5){
                       $this->ajaxReturn(array('status'=>'error','message'=>'演示站不提供数据操作!'));
                    }*/
                    if($f['delivery_id'] != $cid){
                        $this->ajaxReturn(array('status'=>'error','message'=>'订单信息错误'));
                    }else{
                        if($f['status'] == 2){
                            $up = $dvo -> where('order_id ='.$id)-> setField('status',8);
                            if(!$up){
                                $this->ajaxReturn(array('status'=>'error','message'=>'操作失败!'));
                            }else{
                                if($f['type'] == 0){
                                    D('Order') -> where('order_id ='.$f['type_order_id']) -> setField('status',3);//商城暂时不更新，进入用户确认
                                }elseif($f['type'] == 1){
                                    D('EleOrder') -> where('order_id ='.$f['type_order_id']) -> setField('status',8);//更新外卖,暂时给一步骤确认
                                }
                                $this->ajaxReturn(array('status'=>'success','message'=>'操作成功!','orderid'=>$cid));
                            }
                        }
                        
                       
                    }
                }
            }
        }
    }
	
	  //快递众包开始
	public function express( ){
        if ( !cookie( "DL" ) ){
            header( "Location: ".u( "login/index" ) );
        }
        else{
            $cid = $this->reid( );
            $express = d( "Express" );
            $map = array(
                "city_id" => $this->city_id
            );
            $ss = i( "ss", 0, "intval,trim" );
            $this->assign( "ss", $ss );
            if ( $ss == 1 ){
                $map['status'] = 1;
                $map['cid'] = $cid;
            }
            else if ( $ss == 1){
                $map['status'] = 2;
                $map['cid'] = $cid;
            }
            else{
                $map['status'] = 0;
                $map['cid'] = 0;
            }
            $lat = addslashes( cookie( "lat" ) );
            $lng = addslashes( cookie( "lng" ) );
            if ( empty( $lat ) || empty( $lng ) ){
                $lat = $this->city['lat'];
                $lng = $this->city['lng'];
            }
            $orderby = " (ABS(lng - '".$lng."') +  ABS(lat - '{$lat}') ) asc ";
            $rdv = $express->where( $map )->order( $orderby )->select( );
            foreach ( $rdv as $k => $val ){
                $rdv[$k]['d'] = getdistance( $lat, $lng, $val['lat'], $val['lng'] );
            }
			
            $this->assign( "rdv", $rdv );
		
        }
        $this->display( );
    }
	
	//快递众包结束
	
	
	
   //强快递开始
	public function qiang( ){
        if ( IS_AJAX ){
            $express_id = i( "express_id", 0, "trim,intval" );
            $express = d( "Express" );
            if ( !cookie( "DL" ) ){
                $this->ajaxReturn( array( "status" => "error", "message" => "您还没有登录或登录超时!" ) );
            }
            else{
                $detail = $express->find( $express_id );
                if ( !$detail ){
                    $this->ajaxReturn( array( "status" => "error", "message" => "快递不存在!" ) );
                }
                if ( $detail['status'] != 0 || $detail['closed'] != 0 ){
                    $this->ajaxReturn( array( "status" => "error", "message" => "该快递状态不支持抢单!" ) );
                }
                $cid = $this->reid( );
                $data = array(
                    "express_id" => $express_id,
                    "cid" => $cid,
                    "status" => 1,
                    "update_time" => NOW_TIME
                );
                if ( FALSE !== $express->save( $data ) ){
                    $this->ajaxReturn( array( "status" => "success", "message" => "恭喜您！接单成功！请尽快进行配送！" ) );
                }
                else{
                    $this->ajaxReturn( array( "status" => "error", "message" => "接单失败！错误！" ) );
                }
            }
        }
    }

	//快递确认
	public function express_ok( ){
        if ( IS_AJAX ){
            $express_id = i( "express_id", 0, "trim,intval" );
            $express = d( "Express" );
            if ( !cookie( "DL" ) ){
                $this->ajaxReturn( array( "status" => "error", "message" => "您还没有登录或登录超时!" ) );
            }
            else
            {
                $detail = $express->find( $express_id );
                if ( !$detail ){
                    $this->ajaxReturn( array( "status" => "error", "message" => "快递不存在!" ) );
                }
                if ( $detail['status'] != 1 || $detail['closed'] != 0 ){
                    $this->ajaxReturn( array( "status" => "error", "message" => "该快递状态不能完成!" ) );
                }
                $cid = $this->reid( );
                if ( $detail['cid'] != $cid ){
                    $this->ajaxReturn( array( "status" => "error", "message" => "不能操作别人的快递!" ) );
                }
                if ( FALSE !== $express->save( array(
                    "express_id" => $express_id,
                    "status" => 2
                ) ) ){
                    $this->ajaxReturn( array( "status" => "success", "message" => "恭喜您完成订单" ) );
                }
                else{
                    $this->ajaxReturn( array( "status" => "error", "message" => "操作失败！" ) );
                }
            }
        }
    }

	//快递确认结束


}