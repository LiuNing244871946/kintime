<?php
class DeliveryAction extends CommonAction {


    public function index() {
	
            $d = D('Delivery'); // 实例化User对象
            import('ORG.Util.Page');// 导入分页类
            $count = $d->count();// 查询满足要求的总记录数
            $Page  = new Page($count,25);// 实例化分页类 传入总记录数和每页显示的记录数
            $show  = $Page->show();// 分页显示输出
			$citys = D('City')->fetchall();
			$this->assign('citys',$citys);
            // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
            $list = $d->order('add_time')->limit($Page->firstRow.','.$Page->listRows)->select();
            $this->assign('list',$list);//二开==,125062100@qq.com
            $this->assign('page',$show);// 赋值分页输出
            $this->display(); // 输出1-2-0-5-8-5-0-2-2模板
    }
    
    public function create(){

        $citys = D('City')->select();
		$this->assign('citys',$citys);
        $this->display();
        
    }
	public function edit(){
		$id = I('id','','intval,trim');
		if(!$id){
			 $this->baoError('没有选择！');
		}
		$d = D('Delivery'); // 实例化User对象
		$detail = $d->find($id);
        $citys = D('City')->select();
		$this->assign('citys',$citys);
		$this->assign('detail',$detail);
        $this->display();
        
    }
    
    public function check(){
        
        $username = I('username','','trim,htmlspecialchars');
        $password = I('password');
        $rpw = I('rpw');
        $name = I('name','','trim,htmlspecialchars');
        $mobile = I('mobile','','trim');
		$city_id = I('city_id','','trim');
        
        if(!$username){
            $this->baoError('帐号没有填写！');
        }
        if(!$password || strlen($password)<6){
            $this->baoError('密码错误或小于6位！');
        }
        if(!$rpw || strlen($rpw)<6){
            $this->baoError('确认密码错误或小于6位！');
        }
        
        if($password != $rpw){
            $this->baoError('两次密码不一致！');
        }
        
        if(!$name){
            $this->baoError('姓名没有填写！');
        }
        
        if(!$mobile || strlen($mobile) != 11){
            $this->baoError('手机号填写错误！');
        }
        
        $dv = D('Delivery');
        
        $fu = $dv -> where('username ="'.$username.'"') -> find();
        p($dv -> getLastSql());
        if($fu){
            $this->baoError('重复的帐号！');
        }
        
        $fm = $dv -> where('mobile ='.$mobile) -> find();
        if($fm){
            $this->baoError('重复的手机号！');
        }
        
        $result = array(
            'username'=>$username,
            'password'=>md5($password),
            'name'=>$name,
            'mobile'=>$mobile,
            'add_time'=>time(),
			'city_id'=>$city_id
        );
        
        
        $r = $dv -> add($result);
        if($r){
            $this->baoSuccess('添加成功', U('delivery/index'));
        }else{
            $this->baoError('添加失败！');
        }
        
        
    }
    
    
     public function check1(){
        
        $username = I('username','','trim,htmlspecialchars');
        $name = I('name','','trim,htmlspecialchars');
        $mobile = I('mobile','','trim');
		$city_id = I('city_id','','trim');
		$id = I('id','','trim');
        
        if(!$username){
            $this->baoError('帐号没有填写！');
        }
		if(!$id){
            $this->baoError('参数错误！');
        }
        
        
        if(!$name){
            $this->baoError('姓名没有填写！');
        }
        
        if(!$mobile || strlen($mobile) != 11){
            $this->baoError('手机号填写错误！');
        }
        
        $dv = D('Delivery');
        
        $result = array(
			'id'=>$id,
            'username'=>$username,
            'name'=>$name,
            'mobile'=>$mobile,
			'city_id'=>$city_id
        );
        
        
        $r = $dv -> save($result);
        if($r){
            $this->baoSuccess('修改成功', U('delivery/index'));
        }else{
            $this->baoError('修改失败！');
        }
        
        
    }
    public function del(){
        
        $id = I('id','','intval,trim');
        if(!$id){
            $this->baoError('没有选择！');
        }else{
            $dv = D('Delivery');
            $dec = $dv -> where('id ='.$id) -> delete();
            if($dec){
                $this->success('删除成功！',U('delivery/index'));
            }else{
                $this->error('删除失败！');
            }
        }
        
    }
    
    
    public function lists(){
        
        $id = I('id','','intval,trim');
        if(!$id){
            $this->baoError('没有选择！');
        }else{
                $this->assign('delivery',D('Delivery')->where('id ='.$id)->find());
                $dvo = D('DeliveryOrder');
                import('ORG.Util.Page');// 导入分页类
                $count      = $dvo->where('delivery_id ='.$id)->count();// 查询满足要求的总记录数
                $Page       = new Page($count,25);// 实例化分页类 传入总记录数和每页显示的记录数
                $show       = $Page->show();// 分页显示输出
                // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
                $list = $dvo->where('delivery_id ='.$id)->order('order_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
                $this->assign('list',$list);//二开==,125062100@qq.com
                $this->assign('page',$show);// 赋值分页输出
                $this->display(); // 输出1-2-0-5-8-5-0-2-2模板
        }
        
    }
            
    public function change(){
        $id = I('id','','intval,trim');
        if(!$id){
             $this->baoError('没有选择！');
        }
        $d = D('Delivery'); // 实例化User对象
        if(!$detail = $d->find($id)){
            $this->baoError('没有找到该骑手！');
        }
        if($detail['closed'] == 0){
            $dec = $d->save(array('id' =>$id, 'closed' => 1));
        }else{
            $dec = $d->save(array('id' =>$id, 'closed' => 0));
        }

        if($dec){
            $this->success('修改成功！',U('delivery/index'));
        }else{
            $this->error('修改失败！');
        }
    }          
}
