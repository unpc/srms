<?php

class I18N_IPE_Widget extends Widget {

	function __construct($vars) {
		parent::__construct('i18n_ipe', $vars);
	}

	private function _modify_i18n($i18n_path, $orig, $trans) {
		$lang = [];
		if (file_exists($i18n_path)) include $i18n_path;
		$lang[$orig] = $trans;

		$dict = [];
		foreach($lang as $k => $v) {
			if ($v !== NULL) {
				$dict[$k] = sprintf('$lang[\'%s\'] = \'%s\';',  addcslashes($k, '\''), addcslashes($v,'\''));
			}
			else {
				$dict[$k] = sprintf('$lang[\'%s\'] = NULL;',  addcslashes($k, '\''));
			}
		}

		File::check_path($i18n_path, 0775);
		file_put_contents($i18n_path, "<?php\n".implode("\n", $dict)."\n");

		JS::refresh();
	}

	function on_form_submit() {
		
		$form = Input::form();

		// 只有开启翻译开关的才进行翻译
		if (!Config::get('debug.i18n_ipe')) return;

		$domain = $form['domain'];
		switch ($domain) {
		case 'application':
			$path = APP_PATH;
			break;
		case 'system':
			$path = SYS_PATH;
			break;
		default:
			$path = Core::module_path($domain);
		}
		
		$locale = Config::get('system.locale');

		$i18n_path = $path . I18N_BASE . $locale . EXT;

		$this->_modify_i18n($i18n_path, $form['orig'], $form['trans']);

	}

}
