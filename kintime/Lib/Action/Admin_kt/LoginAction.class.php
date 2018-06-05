<?php

class LoginAction extends CommonAction{
    
    public function index(){
        $this->display();
    }
    
    public function loging(){
        $yzm = $this->_post('yzm');
        if(strtolower($yzm) != strtolower(session('verify'))){
            session('verify',null);
            $this->baoError('验证码不正确!',2000,true);
        }
        $username = $this->_post('username','trim');
        $password = $this->_post('password','trim,md5');
        $adminObj = D('Admin');
        $admin = $adminObj->getAdminByUsername($username);
        if($admin['role_id'] == 3){
            $this->baoError('管理员类型错误!');
        }
        if(empty($admin) || $admin['password'] != $password){
            session('verify',null);
            $this->baoError('用户名或密码不正确!',2000,true);
        }
        if($admin['closed'] == 1){
           session('verify',null);
           $this->baoError('该账户已经被禁用!',2000,true); 
        }
        $admin['last_time'] = NOW_TIME;
        $admin['last_ip']  = get_client_ip();
        $adminObj->where("admin_id=%d",$admin['admin_id'])->save(array('last_time'=>$admin['last_time'],'last_ip'=>$admin['last_ip']));
        
        session('admin',$admin);
        $this->baoSuccess('登录成功！',U('index/index'));
    }
    
    public function logout(){
        session('admin',null);
        $this->success('退出成功',U('login/index'));
    }
    
    public function verify(){
        import('ORG.Util.Image');
        ob_end_clean();
        Image::buildImageVerify(5,2,'png',60,30);
    }
    
}
