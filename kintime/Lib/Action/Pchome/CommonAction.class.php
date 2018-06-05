 <?php
class CommonAction extends Action
{
	protected $uid = 0;
	protected $member = array();
	protected $_CONFIG = array();
	protected $seodatas = array();
	protected $shopcates = array();
	protected $citys = array();
	protected $areas = array();
	protected $bizs = array();
	protected $template_setting = array();
	protected $city_id = 0;
	protected $city = array();
	protected function _initialize()
	{
		define('__HOST__', 'http://' . $_SERVER['HTTP_HOST']);
		$this->_CONFIG = D('Setting')->fetchAll();
		$this->citys = D('City')->fetchAll();
		$this->assign('citys', $this->citys);
		$this->city_id = cookie('city_id');

		if (empty($this->city_id)) {
			import('ORG/Net/IpLocation');
			$IpLocation = new IpLocation('UTFWry.dat');
			$result = $IpLocation->getlocation($_SERVER['REMOTE_ADDR']);

			foreach ($this->citys as $val ) {
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
		}
		else {
			$city = $this->citys[$this->city_id];
		}
		$this->city = $city;
		$this->uid = getUid();

		if (!empty($this->uid)) {
			$this->member = D('Users')->find($this->uid);

			if (empty($this->member)) {
				setUid(0);
				$this->uid = 0;
			}
		}
		$invite = (int) $this->_get('invite');

		if (!empty($invite)) {
			cookie('invite_id', $invite);
		}
		searchWordFrom();
		$this->shopcates = D('Shopcate')->fetchAll();
		$this->assign('shopcates', $this->shopcates);
		$this->Tuancates = D('Tuancate')->fetchAll();
		$this->assign('tuancates', $this->Tuancates);
		$this->areas = D('Area')->fetchAll();
		$areass = array();

		foreach ($this->areas as $key => $v ) {
			if ($v['city_id'] == $this->city_id) {
				$areass[$v['area_id']] = $this->areas[$key];
			}
		}
		$this->assign('areas', $areass);
		$this->bizs = D('Business')->fetchAll();
		$this->assign('bizs', $this->bizs);
		$limit_area = array();

		foreach ($this->areas as $k => $val ) {
			if ($val['city_id'] == $this->city_id) {
				$limit_area[$val['area_id']] = $val['area_id'];
			}
		}
		$this->assign('limit_area', $limit_area);
		$this->assign('ctl', strtolower(MODULE_NAME));
		$this->assign('act', ACTION_NAME);
		$this->assign('nowtime', NOW_TIME);
		$current_url = strtolower(MODULE_NAME) . '/' . ACTION_NAME;
		$this->assign('current_url', $current_url);
		$this->assign('func', D('Pcfunction')->fetchAll());
		$this->assign('city_name', $city['name']);
		$this->assign('city_id', $this->city_id);
		$this->assign('city', $city);
		$this->getTemplateTheme();
		$this->template_setting = D('Templatesetting')->detail($this->theme);
		$this->assign('CONFIG', $this->_CONFIG);
		$this->assign('MEMBER', $this->member);
		$this->assign('today', TODAY);
		$city_ids = array('0', $this->city_id);
		$city_ids = join(',', $city_ids);
		$this->assign('city_ids', $city_ids);
	}
	private function seo()
	{
		$seo = D('Seo')->fetchAll();
		$this->seodatas['sitename'] = $this->_CONFIG['site']['sitename'];
		$this->seodatas['tel'] = $this->_CONFIG['site']['tel'];
		$key = strtolower(MODULE_NAME . '_' . ACTION_NAME);

		if (isset($seo[$key])) {
			$this->assign('seo_title', $this->tmplToStr($seo[$key]['seo_title'], $this->seodatas));
			$this->assign('seo_keywords', $this->tmplToStr($seo[$key]['seo_keywords'], $this->seodatas));
			$this->assign('seo_description', $this->tmplToStr($seo[$key]['seo_desc'], $this->seodatas));
		}
		else {
			$this->assign('seo_title', $this->_CONFIG['site']['title']);
			$this->assign('seo_keywords', $this->_CONFIG['site']['keyword']);
			$this->assign('seo_description', $this->_CONFIG['site']['description']);
		}
	}
	private function tmplToStr($str, $datas)
	{
		return tmplToStr($str, $datas);
	}
	public function display($templateFile = '', $charset = '', $contentType = '', $content = '', $prefix = '')
	{
		$this->seo();
		parent::display($this->parseTemplate($templateFile), $charset, $contentType, $content = '', $prefix = '');
	}
	private function parseTemplate($template = '')
	{
		$depr = C('TMPL_FILE_DEPR');
		$template = str_replace(':', $depr, $template);
		$theme = $this->getTemplateTheme();
		define('NOW_PATH', BASE_PATH . '/themes/' . $theme . 'Pchome/');
		define('THEME_PATH', BASE_PATH . '/themes/default/Pchome/');
		define('APP_TMPL_PATH', __ROOT__ . '/themes/default/Pchome/');

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
				else if (!empty($this->city['theme'])) {
					$theme = $this->city['theme'];
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
	protected function baoJump($jumpUrl)
	{
		$str = '<script>';
		$str .= 'parent.jumpUrl("' . $jumpUrl . '");';
		$str .= '</script>';
		exit($str);
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
	protected function baoLoginSuccess()
	{
		$str = '<script>';
		$str .= 'parent.parent.LoginSuccess();';
		$str .= '</script>';
		exit($str);
	}
	protected function ajaxLogin()
	{
		if ($mini = $this->_get('mini')) {
			exit('0');
		}
		$str = '<script>';
		$str .= 'parent.ajaxLogin();';
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
}
