<?php



class PassportAction extends CommonAction {

    private $create_fields = array('account', 'password', 'nickname','invite1');

    public function register() {
        $invite_id=(int)I("get.invite_id");
        cookie('invite_id',$invite_id);
        if ($this->isPost()) {
            $account = htmlspecialchars($_POST['areacode'].$_POST['account']);
            if (isMobile($account)) {
                if (!$scode = trim($_POST['scode'])) {
                    $this->baoMsg('请输入短信验证码！');
                }
                $scode2 = session('scode');
                if (empty($scode2)) {
                    $this->baoMsg('请获取短信验证码！');
                }
                if ($scode != $scode2) {
                    $this->baoMsg('请输入正确的短信验证码！');
                }
            }
            $data = $this->createCheck();
            $data['invite_id'] = (int)cookie('invite_id');
            $password2 = $this->_post('password2');
            if ($password2 !== $data['password']) {

                $this->baoMsg('两次密码不一致');
            }
            //开始其他的判断了
            if (true == D('Passport')->register($data)) {
                $this->baoMsg('恭喜您，注册成功！', U('index/index'));
            }
            $this->baoError(D('Passport')->getError());
        } else {
            $this->assign('invite_id', $invite_id);
            $this->display();
        }
    }

    public function sendsms() {
        $areacode = $_POST['areacode'];
        if (!$mobile = htmlspecialchars($_POST['mobile'])) {
            die('请输入正确的手机号码');
        }

        if (!isMobile($areacode.$mobile)) {
            die('请输入正确的手机号码');
        }

        if ($user = D('Users')->getUserByAccount($areacode.$mobile)) {
            die('手机号码已经存在！');
        }

        $randstring = rand_string(6, 1);
        session('scode', $randstring);
        $data = array($randstring);
        D('Sms')->sendSms(1, $mobile, $areacode , $data);
        die('1');
    }

    public function bind() {
        $this->display();
    }

    public function index() {

        $this->redirect('login');
    }


    public function login() {
        if ($this->uid) {
            header("Location:" . U('mcenter/index/index'));
        }

        if ($this->isPost()) {
            $areacode = $this->_post('areacode');

            $account = $this->_post('account');
            $account = $areacode.$account;
            if (empty($account)) {
                $this->baoMsg('请输入用户名!');
            }

            $password = $this->_post('password');
            if (empty($password)) {
                $this->baoMsg('请输入登录密码!');
            }
            $backurl = $this->_post('backurl', 'htmlspecialchars');
            if (empty($backurl)) {
                $backurl = U('mcenter/index/index');
            }

            if (IS_WEIXIN) {
                $back_url = cookie('wx_back_url');
                if(strstr($back_url,'passport')){
                    $back_url = '';
                }
                $backurl = $back_url ? $back_url : $backurl;
            }
            if (true == D('Passport')->login($account, $password)) {
                $this->baoMsg('恭喜您登录成功！', $backurl);
            } else {
                $this->baoMsg(D('Passport')->getError());
            }
        } else {
            if (!empty($_SERVER['HTTP_REFERER']) && strstr($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) && !strstr($_SERVER['HTTP_REFERER'], 'passport')) {
                $backurl = $_SERVER['HTTP_REFERER'];
            } else {
                $backurl = U('mcenter/index/index');
            }
            $this->assign('backurl', $backurl);

            $this->display();
        }
    }

    private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['account'] = $_POST['areacode'].htmlspecialchars($_POST['account']);
        if (!isMobile($data['account'])) {
            session('verify', null);
            $this->baoMsg('只允许手机号注册');
        }
        $data['password'] = htmlspecialchars($data['password']); //整合UC的时候需要
        if (empty($data['password']) || strlen($data['password']) < 6) {
            session('verify', null);
            $this->baoMsg('请输入正确的密码!密码长度必须要在6个字符以上');
        }
        $data['nickname'] =$data['account'];
        $data['ext0'] = $data['account']; //兼容UCENTER
        $data['mobile'] =$data['account'];
        $data['reg_ip'] = get_client_ip();
        $data['reg_time'] = NOW_TIME;
        return $data;
    }

    public function wblogin() {
        $login_url = 'https://api.weibo.com/oauth2/authorize?client_id=' . $this->_CONFIG['connect']['wb_app_id'] . '&response_type=code&redirect_uri=' . urlencode(__HOST__ . U('passport/wbcallback'));
        header("Location:$login_url");
        die;
    }

    public function qqlogin() {
        $state = md5(uniqid(rand(), TRUE));

        session('state', $state);
        $login_url = "https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id="
                . $this->_CONFIG['connect']['qq_app_id'] . "&redirect_uri=" . urlencode(__HOST__ . U('passport/qqcallback'))
                . "&state=" . $state
                . "&scope=";
        header("Location:$login_url");
        die;
    }

    public function wxlogin() {
        $state = md5(uniqid(rand(), TRUE));
        cookie('wx_back_url', $_SERVER['HTTP_REFERER']);
        session('state', $state);
        $login_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $this->_CONFIG['weixin']['appid']
                . '&redirect_uri=' . urlencode(__HOST__ . U('passport/wxcallback')) . '&response_type=code&scope=snsapi_base&state=' . $state . '#wechat_redirect';
        header("Location:$login_url");
        die;
    }

    public function wbcallback() {

        import("@/Net.Curl");
        $curl = new Curl();

        $params = array(
            'grant_type' => 'authorization_code',
            'code' => $_REQUEST["code"],
            'client_id' => $this->_CONFIG['connect']['wb_app_id'],
            'client_secret' => $this->_CONFIG['connect']['wb_app_key'],
            'redirect_uri' => __HOST__ . U('passport/qqcallback')
        );
        $url = 'https://api.weibo.com/oauth2/access_token';
        $response = $curl->post($url, http_build_query($params));
        $params = json_decode($response, true);
        if (isset($params['error'])) {
            echo "<h3>error:</h3>" . $params['error'];
            echo "<h3>msg  :</h3>" . $params['error_code'];
            exit;
        }
        $url = 'https://api.weibo.com/2/account/get_uid.json?source=' . $this->_CONFIG['connect']['wb_app_key'] . '&access_token=' . $params['access_token'];
        $result = $curl->get($url);
        $user = json_decode($result, true);
        if (isset($user['error'])) {
            echo "<h3>error:</h3>" . $user['error'];
            echo "<h3>msg  :</h3>" . $user['error_code'];
            exit;
        }
        $data = array(
            'type' => 'weibo',
            'open_id' => $user['uid'],
            'token' => $params['access_token']
        );
        $this->thirdlogin($data);
    }

    public function wxcallback() {
            import("@/Net.Curl");
            $curl = new Curl();
            if (empty($_REQUEST["code"])) {
                $this->error('授权后才能登陆！', U('passport/login'));
            }
            $token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $this->_CONFIG['weixin']["appid"] .
                    '&secret=' . trim($this->_CONFIG['weixin']["appsecret"]) .
                    '&code=' . $_REQUEST["code"] . '&grant_type=authorization_code';
            $str = $curl->get($token_url);
            $params = json_decode($str, true);
          // print_r($params);die;
            if (!empty($params['errcode'])) {
                echo "<h3>error:</h3>" . $params['errcode'];
                echo "<h3>msg  :</h3>" . $params['errmsg'];
                exit;
            }
            if (empty($params['unionid']) && empty($params['openid'])) {
                $this->error('登录失败', U('passport/login'));
            }
            $data = array(
                'type' => 'weixin',
                'wx_unionid' => $params['unionid'],
                'open_id' => $params['openid'],
                'token' => $params['refresh_token'],
            );
             if ($this->_CONFIG['connect']['debug']) { 
                $cache = cache(array('type'=>'File','expire'=>7100));
                if(!$token = $cache->get('mobile_weixin')){
                    $token_url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->_CONFIG['weixin']["appid"].'&secret='. trim($this->_CONFIG['weixin']["appsecret"]) ;
                    $str = $curl->get($token_url);
                    $params = json_decode($str, true);
                    $token = $params['access_token'];
                    $cache->set('mobile_weixin',$token);
                }
                //echo $token;die;
                $token_url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$token.'&openid='.$data['open_id'].'&lang=zh_CN';
                $str = $curl->get($token_url);
                $params = json_decode($str, true);
                $data['nickname'] = $params['nickname'];
                if(!empty($params['headimgurl'])){
                    $filepatch = '/weixin/'.substr(md5($data['open_id']),0,3).'/';
                    $dir = BASE_PATH.'/attachs/'.$filepatch;
                    if(!is_dir($dir)){
                        mkdir($dir,0755,true);
                    }
                    if(!file_exists($dir.$data['open_id'].'.png')){
                        $face = file_get_contents($params['headimgurl']);
                        file_put_contents($dir.$data['open_id'].'.png', $face);
                        $data['face'] =$filepatch.$data['open_id'].'.png';
                    }
                }
                
             }
           // print_r($data);die;
            $this->thirdlogin($data);
    }

    public function qqcallback() {
        import("@/Net.Curl");
        $curl = new Curl();
            $token_url = "https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&"
                    . "client_id=" . $this->_CONFIG['connect']["qq_app_id"] . "&redirect_uri=" . urlencode(__HOST__ . U('passport/qqcallback'))
                    . "&client_secret=" . $this->_CONFIG['connect']["qq_app_key"] . "&code=" . $_REQUEST["code"];
            $response = $curl->get($token_url);

            if (strpos($response, "callback") !== false) {
                $lpos = strpos($response, "(");
                $rpos = strrpos($response, ")");
                $response = substr($response, $lpos + 1, $rpos - $lpos - 1);
                $msg = json_decode($response);
                echo "<h3>error:</h3>" . $msg->error;
                echo "<h3>msg  :</h3>" . $msg->error_description;
                exit;
            }
            $params = array();
            parse_str($response, $params);
            if (empty($params))
                die;
            $graph_url = "https://graph.qq.com/oauth2.0/me?access_token="
                    . $params['access_token'];

            $str = $curl->get($graph_url);
            if (strpos($str, "callback") !== false) {
                $lpos = strpos($str, "(");
                $rpos = strrpos($str, ")");
                $str = substr($str, $lpos + 1, $rpos - $lpos - 1);
            }

            $user = json_decode($str, true);
            if (isset($user['error'])) {
                echo "<h3>error:</h3>" . $user['error'];
                echo "<h3>msg  :</h3>" . $user['error_description'];
                exit;
            }
            if (empty($user['openid']))
                die;
            $data = array(
                'type' => 'qq',
                'open_id' => $user['openid'],
                'token' => $params['access_token']
            );
            $this->thirdlogin($data);
        
    }

    private function thirdlogin($data) {
        if ($this->_CONFIG['connect']['debug']) { //调试状态下 可以直接就登录 不是调试状态就要走绑定用户名的流程
            // $data['type'] = 'wx'; //DEBUG状态是直接登录
            if($data['wx_unionid']){
                $connect = D('Connect')->getConnectByUnionid($data['type'], $data['wx_unionid']);
            }
            if(empty($contect)){
                $connect = D('Connect')->getConnectByOpenid($data['type'], $data['open_id']);
            }            
            
            if (empty($connect)) {
                $connect = $data;
                $connect['connect_id'] = D('Connect')->add(array( 'type' => $data['type'],
                'open_id' => $data['open_id'],
                'token' => $data['token']));
            } else if($data['token'] != $connect['token']) {
                D('Connect')->save(array('connect_id' => $connect['connect_id'], 'token' => $data['token']));
            }
            $time = time();
            if (empty($connect['uid'])) {
                $account =  $data['open_id'];
                $user = array(
                    'account' => $account,
                    'password' => rand(100000, 999999),
                    'nickname' => empty($data['nickname']) ? $data['type'] . $connect['connect_id'] : $data['nickname'] ,
                    'face' => empty($data['face']) ?  '': $data['face'],
                    'ext0' => $account,
                    'reg_time' => $time,
                    'create_ip' => get_client_ip(),
                );
                if (!D('Passport')->register($user))
                    $this->error('创建帐号失败');

                $token = D('Passport')->getToken();
                $connect['uid'] = $token['uid'];
                D('Connect')->save(array('connect_id' => $connect['connect_id'], 'uid' => $connect['uid']));
            }

            setUid($connect['uid']);
            if (IS_WEIXIN) {
                cookie('weixin_access', $connect['connect_id']);
                $back_url = cookie('wx_back_url');
                $back_url = $back_url ? $back_url : U('index/index');
                header("Location:" . $back_url);
            }
            header("Location:" . U('index/index'));
            die;
        } else {
            if($data['wx_unionid']){
                $connect = D('Connect')->getConnectByUnionid($data['type'], $data['wx_unionid']);
            }
            if(empty($contect)){
                $connect = D('Connect')->getConnectByOpenid($data['type'], $data['open_id']);
            }   
            if (empty($connect)) {
                $connect = $data;
                $connect['connect_id'] = D('Connect')->add($data);
            } else if($data['token'] != $contect['token']) {
                D('Connect')->save(array('connect_id' => $connect['connect_id'], 'token' => $data['token']));
            }
            if (empty($connect['uid'])) {
                if ($this->uid) {
                    D('Connect')->save(array('connect_id' => $connect['connect_id'], 'uid' => $this->uid));
                    if (IS_WEIXIN) { //防止退出后还需要登录
                        cookie('weixin_access', $connect['connect_id']);
                    }
                    $this->success('绑定第三方登录成功', U('mcenter/information/index'));
                } else {
                    session('connect', $connect['connect_id']);
                    if (IS_WEIXIN) { //防止退出后还需要登录
                        cookie('weixin_access', $connect['connect_id']);
                    }
                    header("Location: " . U('passport/bind'));
                }
            } else {

                if (IS_WEIXIN) { //防止退出后还需要登录

                    cookie('weixin_access', $connect['connect_id']);

                }

                    header("Location: " . U('passport/bind'));

                setUid($connect['uid']);
                if (IS_WEIXIN) {
                    cookie('weixin_access', $connect['connect_id']);
                    $back_url = cookie('wx_back_url');
                    $back_url = $back_url ? $back_url : U('index/index');
                    header("Location:" . $back_url);
                }
                header("Location:" . U('index/index'));
            }
            die;
        }
    }
    

    public function logout() {

        D('Passport')->logout();
        $this->success('退出登录成功！', U('index/index'));
    }

    //两种找回密码的方式 1个是通过邮件 //填写了2个就改密码相对来说是不太合理，但是毕竟逻辑和操作相对简单一些！
    public function forget() {
        $way = (int) $this->_param('way');
        $this->assign('way', $way);
        $this->display();
    }

    public function newpwd() {

        $way = (int) $this->_param('way');
        $password = rand_string(6, 1);

        /*if ($way == 1) {
            $yzm = $this->_post('yzm');
            if (strtolower($yzm) != strtolower(session('verify'))) {
                session('verify', null);
                $this->baoError('验证码不正确!');
            }
             $email = $this->_post('email');
             if (empty($email) || $email != $user['email']) {
                 $this->baoError('邮件不正确');
             }
             D('Passport')->uppwd($user['account'], '', $password);
             D('Email')->sendMail('email_newpwd', $email, '重置密码', array('newpwd' => $password));
        } elseif ($way == 2) {*/
            $mobile = htmlspecialchars($_POST['mobile']);
            $areacode = $_POST['areacode'];

            if (!$scode = trim($_POST['scode'])) {
                $this->baoError('请输入短信验证码！');
            }
            $scode2 = session('scode');
            if (empty($scode2)) {
                $this->baoError('请获取短信验证码！');
            }
            if ($scode != $scode2) {
                $this->baoError('请输入正确的短信验证码！');
            }
            $mi['password'] = md5($password);
            if (!D('users')->where('account='.$areacode.$mobile)->save($mi)) {
                $this->baoError('密码重置失败');
            }
//          D('Passport')->uppwd($user['account'], '', $password);
            $data = array($password);
            D('Sms')->sendSms(2, $mobile, $areacode , $data);
        // }
        $this->baoSuccess('重置密码成功！', U('passport/suc', array('way' => $way)));
    }

    public function findsms() {
        $areacode = $_POST['areacode'];
        if (!$mobile = htmlspecialchars($_POST['mobile'])) {
            die('请输入正确的手机号码');
        }
        if (!isMobile($areacode.$mobile)) {
            die('请输入正确的手机号码');
        }
        
        if ($user = D('Users')->getUserByAccount($areacode.$mobile)) {
            if (empty($user['mobile'])) {
                die('你还未绑定手机号，请选择其他方式！');
            } else {
                if ($user['mobile'] != $areacode.$mobile) {
                    die('请填写您的绑定手机号！');
                }
            }
        }
        
            $randstring = rand_string(6, 1);
            session('scode', $randstring);
            $data=array($randstring);
        //die(session('scode'));
        D('Sms')->sendSms(1, $mobile, $areacode , $data);
        die('1');
    }

    public function suc() {
        $way = (int) $this->_get('way');
        switch ($way) {
            case 1:
                $this->success('密码重置成功！请登录邮箱查看！', U('passport/login'));
                break;
            default:
                $this->success('密码重置成功！请查看验证手机！', U('passport/login'));
                break;
        }
    }

}
