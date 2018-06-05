<?php



class CommonAction extends Action {

    protected $uid = 0;
    protected $lang = 2;
    protected $member = array();
    protected $_CONFIG = array();
    protected $citys = array();
    protected $areas = array();
    protected $bizs = array();
    protected $city = array();
    protected $ex = array();
    protected $mobile_title = '';
 
    protected function _initialize() {
        if($lang = D('Users')->find($this->uid)['lang']){
            $this->lang = $lang;
        }
        define('__HOST__', 'http://' . $_SERVER['HTTP_HOST']);
         define('IN_MOBILE', true);
        $is_app = is_app();
        $is_app = $is_app ? true : false;
        define('IS_APP', $is_app);
        $this->assign('is_app',$is_app);
        if (!IS_APP) {
            $is_weixin = is_weixin();
            $is_weixin = $is_weixin ? true : false;
        } else {
            $is_android =  is_android();
            define('IS_ANDROID', $is_android);
            $is_weixin = false;
            $this->assign('is_android',$is_android);
        }
        define('IS_WEIXIN', $is_weixin);
        $this->uid = (int) getUid();
        if (empty($this->uid)) {
            AppJump();
        }
        $this->member = D('Users')->find($this->uid);
        if (empty($this->member)) {
            setUid(0);
            AppJump();
        }
        $this->ex = D('Usersex')->find($this->uid);
        $this->checkFzmoney();
        $this->_CONFIG = D('Setting')->fetchAll();
        $this->citys = D('City')->fetchAll();
        $this->city_id = cookie('city_id');
        if (empty($this->city_id)) {
            import('ORG/Net/IpLocation');
            $IpLocation = new IpLocation('UTFWry.dat'); // 实例化类 参数表示IP地址库文件
            $result = $IpLocation->getlocation($_SERVER['REMOTE_ADDR']);
            foreach ($this->citys as $val) {
                if (strstr($result['country'], $val['name'])) {
                    $city = $val;
                    $this->city_id = $val['city_id'];
                    break;
                }
            }
            if (empty($city)) {
                $this->city_id = $this->_CONFIG['site']['city_id'];
                $city = $this->citys[$this->_CONFIG['site']['city_id']];
            }
        } else {
            $city = $this->citys[$this->city_id];
        }
        
		/*$guoqi_time = $this->_CONFIG['tuantime']['tuan_time']*60;
		$time = time();
		$jiancha_time = $this->_CONFIG['tuantime']['tuan_time']/10*60;
		if(file_exists(BASE_PATH.'/tuantime.txt')){
			$up_time = filemtime(BASE_PATH.'/tuantime.txt');
			if($time-$up_time>$jiancha_time){
				 $a =  fopen(BASE_PATH.'/tuantime.txt', 'w');
				 D('Tuanorder')->update_count($guoqi_time);
			}
		}else{
			$a =  fopen(BASE_PATH.'/tuantime.txt', 'w');
			D('Tuanorder')->update_count($guoqi_time);
		}*/

        $this->assign('city', $city);
        $this->assign('citys', $this->citys);
        $this->areas = D('Area')->fetchAll();
        $this->assign('areas', $this->areas);
        $this->bizs = D('Business')->fetchAll();
        $this->assign('bizs', $this->bizs);
        $this->assign('lang', $this->lang);
        //这里主要处理推广链接入口！如果是用户推广进来的会增加到COOKIE
        $invite = (int) $this->_get('invite');
        if (!empty($invite)) { //这里改成了获得积分
            cookie('invite_id', $invite);
        }
        //全民推广员结束

        $tui_uid = (int) $this->_get('tui_uid');
        if ($tui_uid) {
            if ($goods_id = (int) $this->_get('goods_id')) { //两个条件都满足的情况下 COOKIE 存一个月
                cookie('tui', $tui_uid . '_' . $goods_id, 30 * 86400); //一个月有效果
            }
        }
        $this->assign('CONFIG', $this->_CONFIG);
        $this->assign('MEMBER', $this->member);
        $this->assign('MEMBER_EX',$this->ex);
        $this->assign('shopcates', D('Shopcate')->fetchAll());
        $this->assign('tuancates', D('Tuancate')->fetchAll());
        $this->assign('goodscates', D('Goodscate')->fetchAll());
        $this->assign('today', TODAY); //兼容模版的其他写法
        $this->assign('nowtime', NOW_TIME);
        $this->assign('ctl', strtolower(MODULE_NAME)); //主要方便调用
        $this->assign('act', ACTION_NAME);
        $this->assign('is_weixin', IS_WEIXIN);
        $this->assign('is_app',IS_APP);
        $this->msg();
    }
    
    //自动将用户的冻结余额到期后自动转到余额
    private function checkFzmoney(){
        if($this->ex['frozen_money'] && $this->ex['is_no_frozen'] != 1 && $this->ex['frozen_date'] < NOW_TIME){
            $this->ex['is_no_frozen'] = 1;
            if(D('Usersex')->save(array('user_id'=>  $this->uid,'is_no_frozen'=>1))){
                D('Users')->addMoney($this->uid,$this->ex['frozen_money'],'冻结金额系统自动解冻了');
            }
        }
        return true;
    }

    protected function msg() {
        $msgs = D('Msg')->where(array('user_id'=>array('IN', array(0,$this->uid))))->limit(0, 20)->select();
        $this->assign('msgs', $msgs);
        $msg_ids = array();
        foreach ($msgs as $val) {
            $msg_ids[] = $val['msg_id'];
        }
        if (!empty($this->uid)) {
            $reads = D('Msgread')->where(array('user_id' => $this->uid, 'msg_id' => array('IN', $msg_ids)))->select();
            $messagenum = count($msgs) - count($reads);
            $messagenum = $messagenum > 9 ? 9 : $messagenum;
            $readids = array();
            foreach ($reads as $val) {
                $readids[$val['msg_id']] = $val['msg_id'];
            }
            $this->assign('readids', $readids);
            $this->assign('messagenum', $messagenum);
        } else {
            $this->assign('messagenum', 0);
        }
    }

    private function seo() {
       
        $this->assign('mobile_title', $this->mobile_title);
    }

    private function tmplToStr($str, $datas) {
        preg_match_all('/{(.*?)}/', $str, $arr);
        foreach ($arr[1] as $k => $val) {
            $v = isset($datas[$val]) ? $datas[$val] : '';
            $str = str_replace($arr[0][$k], $v, $str);
        }
        return $str;
    }

    public function display($templateFile = '', $charset = '', $contentType = '', $content = '', $prefix = '') {
        $this->seo();
        parent::display($this->parseTemplate($templateFile), $charset, $contentType, $content = '', $prefix = '');
    }

    private function parseTemplate($template = '') {

        $depr = C('TMPL_FILE_DEPR');
        $template = str_replace(':', $depr, $template);
        // 获取当前主题名称
        $theme = $this->getTemplateTheme();
        define('NOW_PATH', BASE_PATH . '/themes/' . $theme . 'Mcenter/');

        // 获取当前主题的模版路径
        define('THEME_PATH', BASE_PATH . '/themes/default/Mcenter/');
        define('APP_TMPL_PATH', __ROOT__ . '/themes/default/Mcenter/');

        // 分析模板文件规则
        if ('' == $template) {
            // 如果模板文件名为空 按照默认规则定位
            $template = strtolower(MODULE_NAME) . $depr . strtolower(ACTION_NAME);
        } elseif (false === strpos($template, '/')) {
            $template = strtolower(MODULE_NAME) . $depr . strtolower($template);
        }
        $file = NOW_PATH . $template . C('TMPL_TEMPLATE_SUFFIX');
        if (file_exists($file))
            return $file;
        return THEME_PATH . $template . C('TMPL_TEMPLATE_SUFFIX');
    }

   private function getTemplateTheme() {
        define('THEME_NAME','default');

        if ($this->theme) { // 指定模板主题
            $theme = $this->theme;
        } else {
            /* 获取模板主题名称 */
            $default = D('Template')->getDefaultTheme();
            $themes = D('Template')->fetchAll();
            if (C('TMPL_DETECT_THEME')) {// 自动侦测模板主题
                $t = C('VAR_TEMPLATE');
                if (isset($_GET[$t])) {
                    $theme = $_GET[$t];
                    cookie('think_template', $theme, 864000);
                } elseif (cookie('think_template')) {
                    $theme = cookie('think_template');
                }
                if (!isset($themes[$theme])) {
                    $theme = $default;
                }
              
            }else{
                $theme = $default;
            }
        }
        return $theme ? $theme . '/' : '';
    }

    protected function baoSuccess($message, $jumpUrl = '', $time = 3000) {
        $str = '<script>';
        $str .='parent.success("' . $message . '",' . $time . ',\'jumpUrl("' . $jumpUrl . '")\');';
        $str.='</script>';
        exit($str);
    }

    protected function baoMsg($message, $jumpUrl = '', $time = 3000) {
        $str = '<script>';
        $str .='parent.bmsg("' . $message . '","' . $jumpUrl . '","' . $time . '");';
        $str.='</script>';
        exit($str);
    }

    protected function baoErrorJump($message, $jumpUrl = '', $time = 3000) {
        $str = '<script>';
        $str .='parent.error("' . $message . '",' . $time . ',\'jumpUrl("' . $jumpUrl . '")\');';
        $str.='</script>';
        exit($str);
    }

    protected function baoError($message, $time = 3000, $yzm = false) {
        $str = '<script>';
        if ($yzm) {
            $str .='parent.error("' . $message . '",' . $time . ',"yzmCode()");';
        } else {
            $str .='parent.error("' . $message . '",' . $time . ');';
        }
        $str.='</script>';
        exit($str);
    }

    protected function baoAlert($message, $url = '') {
        $str = '<script>';
        $str.='parent.alert("' . $message . '");';
        if (!empty($url)) {
            $str.='parent.location.href="' . $url . '";';
        }
        $str.='</script>';
        exit($str);
    }

    protected function baoLoginSuccess() { //异步登录
        $str = '<script>';
        $str .='parent.parent.LoginSuccess();';
        $str.='</script>';
        exit($str);
    }

    protected function ajaxLogin() {
        $str = '<script>';
        $str .='parent.ajaxLogin();';
        $str.='</script>';
        exit($str);
    }

    protected function checkFields($data = array(), $fields = array()) {
        foreach ($data as $k => $val) {
            if (!in_array($k, $fields)) {
                unset($data[$k]);
            }
        }
        return $data;
    }

    protected function ipToArea($_ip) {
        return IpToArea($_ip);
    }

}
