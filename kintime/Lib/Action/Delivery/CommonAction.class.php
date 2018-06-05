<?php



class CommonAction extends Action {
     protected $citys = array();
     protected $city  = array();

    protected function _initialize() {
        define('__HOST__', 'http://' . $_SERVER['HTTP_HOST']);
        
        $this->_CONFIG = D('Setting')->fetchAll();
        $this->citys = D('City')->fetchAll();
        $this->assign('citys', $this->citys);
        
       
        
        $is_app = (boolean)is_app();
        define('IS_APP', $is_app);
        if (!IS_APP) {
            $is_weixin = (boolean)is_weixin();
        } else {
           // echo $_SERVER['HTTP_USER_AGENT'];die;
            $is_android =  is_android();
            define('IS_ANDROID', $is_android);
            $is_weixin = false;
            $this->assign('is_android',$is_android);
        }
        $this->assign('ctl', strtolower(MODULE_NAME)); //主要方便调用
        $this->assign('act', ACTION_NAME);
        $this->assign('nowtime', NOW_TIME); 
        $this->assign('is_app',IS_APP);
        $this->user_info = $this->check_login(); 
        if ($this->user_info) {
                $this->assign('user_info', $this->user_info);
        }elseif(strtolower(MODULE_NAME) != 'login'){
           header("Location:".U('login/index'));
        }
    }

    public function check_login(){   //检测登录状态
            $id = $this->reid();
            $rs = D('Delivery');
            $result = $rs->find($id);

            if (!$result) {
                //cookid(0);
                return false;
            }
            return $result;
    }
    
    

    protected function cookid($uid){

        import("ORG/Crypt/Base64");

        $uid = 'Delivery_'.$uid.'_'.NOW_TIME;
     
        $uid = Base64::encrypt($uid, C('AUTH_KEY'));

        cookie('DL',$uid,86400*365); //存一年
        return true;
    }
    
    protected function reid(){
        import("ORG/Crypt/Base64");
        $token = cookie('DL');
        $token = Base64::decrypt($token, C('AUTH_KEY'));
        $token = explode('_', $token);
        if($token[0]!= 'Delivery') return 0;
        return (int)$token[1];
    }
   

    protected function baoMsg($message, $jumpUrl = '', $time = 3000, $callback = '', $parent = true) {
        $parents = $parent ? 'parent.' : '';
        $str = '<script>';
        $str .=$parents . 'bmsg("' . $message . '","' . $jumpUrl . '","' . $time . '","' . $callback . '");';
        $str.='</script>';
        exit($str);
    }

    protected function baoOpen($message, $close = true, $style) {
        $str = '<script>';
        $str .='parent.bopen("' . $message . '","' . $close . '","' . $style . '");';
        $str.='</script>';
        exit($str);
    }

    protected function baoSuccess($message, $jumpUrl = '', $time = 3000, $parent = true) {
        $this->baoMsg($message, $jumpUrl, $time, '', $parent);
    }

    protected function baoErrorJump($message, $jumpUrl = '', $time = 3000) {
        $this->baoMsg($message, $jumpUrl, $time);
    }

    protected function baoError($message, $time = 3000, $yzm = false, $parent = true) {

        $parent = $parent ? 'parent.' : '';
        $str = '<script>';
        if ($yzm) {
            $str .= $parent . 'bmsg("' . $message . '","",' . $time . ',"yzmCode()");';
        } else {
            $str .= $parent . 'bmsg("' . $message . '","",' . $time . ');';
        }
        $str.='</script>';
        exit($str);
    }
	
	private function seo() {
        $seo = D('Seo')->fetchAll();
        $this->assign('seo_title', $this->_CONFIG['site']['title']);
        $this->assign('seo_keywords', $this->_CONFIG['site']['keyword']);
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
        
        define('NOW_PATH',BASE_PATH.'/themes/'.$theme.'Delivery/');
       
        // 获取当前主题的模版路径
        define('THEME_PATH', BASE_PATH . '/themes/default/Delivery/');
        define('APP_TMPL_PATH', __ROOT__ . '/themes/default/Delivery/');

        // 分析模板文件规则
        if ('' == $template) {
            // 如果模板文件名为空 按照默认规则定位
            $template = strtolower(MODULE_NAME) . $depr . strtolower(ACTION_NAME);
        } elseif (false === strpos($template, '/')) {
            $template = strtolower(MODULE_NAME) . $depr . strtolower($template);
        }
        $file = NOW_PATH . $template . C('TMPL_TEMPLATE_SUFFIX');
        if(file_exists($file)) return $file;
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

}
