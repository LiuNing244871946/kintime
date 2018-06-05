 <?php
class CommonAction extends Action
{
	protected $uid = 0;
	protected $member = array();
	protected $_CONFIG = array();
	protected $mer_id = 0;
	protected $merchants = array();
	protected $shopcates = array();
	protected function _initialize()
	{
		$this->uid = getUid();

		if (!empty($this->uid)) {
			$this->member = D('Users')->find($this->uid);
		}
		if ((strtolower(MODULE_NAME) != 'login') && (strtolower(MODULE_NAME) != 'public')) {
			if (empty($this->uid)) {
				header('Location: ' . U('login/index'));
				exit();
			}
			$this->merchants = D('Merchants')->find(array('where' => array('user_id' => $this->uid,'audit' => 1)));
			if (empty($this->merchants)) {
				$this->error('该用户没有开通商户', U('login/index'));
			}
			$this->mer_id = $this->merchants['mer_id'];
			if (strlen($this->merchants['mobile'])===13) {
                $this->merchants['areacode']=substr($this->merchants['mobile'],0,2);
                $this->merchants['mobile']=substr($this->merchants['mobile'],2,11);
            }else if (strlen($this->merchants['mobile'])===14) {
                $this->merchants['areacode']=substr($this->merchants['mobile'],0,3);
                $this->merchants['mobile']=substr($this->merchants['mobile'],3,11);
            }
			$this->assign('MER', $this->merchants);
		}
		$this->_CONFIG = D('Setting')->fetchAll();
		define('__HOST__', 'http://' . $_SERVER['HTTP_HOST']);
		$this->assign('CONFIG', $this->_CONFIG);
		$this->assign('MEMBER', $this->member);
		$this->shopcates = D('Shopcate')->fetchAll();
		$this->assign('shopcates', $this->shopcates);
		$this->assign('ctl', strtolower(MODULE_NAME));
		$this->assign('act', ACTION_NAME);
		$this->assign('today', TODAY);
		$this->assign('nowtime', NOW_TIME);
	}
	public function display($templateFile = '', $charset = '', $contentType = '', $content = '', $prefix = '')
	{
		parent::display($this->parseTemplate($templateFile), $charset, $contentType, $content = '', $prefix = '');
	}
	private function parseTemplate($template = '')
	{
		$depr = C('TMPL_FILE_DEPR');
		$template = str_replace(':', $depr, $template);
		$theme = $this->getTemplateTheme();
		define('NOW_PATH', BASE_PATH . '/themes/' . $theme . 'Shangjia/');
		define('THEME_PATH', BASE_PATH . '/themes/default/Shangjia/');
		define('APP_TMPL_PATH', __ROOT__ . '/themes/default/Shangjia/');

		if ('' == $template) {
			$template = strtolower(MODULE_NAME) . $depr . strtolower(ACTION_NAME);
		}
		else if (false === strpos($template, '/')) {
			$template = strtolower(MODULE_NAME) . $depr . strtolower($template);
		}
		$file = NOW_PATH . $template . C('TMPL_TEMPLATE_SUFFIX');

		if (file_exists($file)) {
			return $file;
		}
		return THEME_PATH . $template . C('TMPL_TEMPLATE_SUFFIX');
	}
	private function getTemplateTheme()
	{
		define('THEME_NAME', 'default');

		if ($this->theme) {
			$theme = $this->theme;
		}
		else {
			$default = D('Template')->getDefaultTheme();
			$themes = D('Template')->fetchAll();

			if (C('TMPL_DETECT_THEME')) {
				$t = C('VAR_TEMPLATE');

				if (isset($_GET[$t])) {
					$theme = $_GET[$t];
					cookie('think_template', $theme, 864000);
				}
				else if (cookie('think_template')) {
					$theme = cookie('think_template');
				}
				if (!isset($themes[$theme])) {
					$theme = $default;
				}
			}
			else {
				$theme = $default;
			}
		}
		return $theme ? $theme . '/' : '';
	}
	protected function baoMsg($message, $jumpUrl = '', $time = 3000, $callback = '', $parent = true)
	{
		$parents = ($parent ? 'parent.' : '');
		$str = '<script>';
		$str .= $parents . 'bmsg("' . $message . '","' . $jumpUrl . '","' . $time . '","' . $callback . '");';
		$str .= '</script>';
		exit($str);
	}
	protected function baoOpen($message, $close = true, $style)
	{
		$str = '<script>';
		$str .= 'parent.bopen("' . $message . '","' . $close . '","' . $style . '");';
		$str .= '</script>';
		exit($str);
	}
	protected function baoSuccess($message, $jumpUrl = '', $time = 3000, $parent = true)
	{
		$this->baoMsg($message, $jumpUrl, $time, '', $parent);
	}
	protected function baoErrorJump($message, $jumpUrl = '', $time = 3000)
	{
		$this->baoMsg($message, $jumpUrl, $time);
	}
	protected function baoError($message, $time = 3000, $yzm = false, $parent = true)
	{
		$parent = ($parent ? 'parent.' : '');
		$str = '<script>';

		if ($yzm) {
			$str .= $parent . 'bmsg("' . $message . '","",' . $time . ',"yzmCode()");';
		}
		else {
			$str .= $parent . 'bmsg("' . $message . '","",' . $time . ');';
		}
		$str .= '</script>';
		exit($str);
	}
	protected function checkFields($data = array(), $fields = array())
	{
		foreach ($data as $k => $val ) {
			if (!in_array($k, $fields)) {
				unset($data[$k]);
			}
		}
		return $data;
	}
	protected function ipToArea($_ip)
	{
		return IpToArea($_ip);
	}
	protected function wechat_client($mer_id)
	{
		static $client;

		if ($client === NULL) {
			if (!$client = D('Weixin')->admin_wechat_client($mer_id)) {
				exit('网站公众号设置错误');
			}
		}
		return $client;
	}
	protected function weixin_jssdk($appid, $secret)
	{
		static $jssdk;

		if ($jssdk === NULL) {
			include_once BASE_PATH . '/Kintime/Lib/Action/Weixin/jssdk.php';
			$jssdk = new WeixinJSSDK($appid, $secret);
		}
		return $jssdk;
	}
}
