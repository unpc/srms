<?php

class Output extends _Output {
	
    //该函数进行xss的注入脚本过滤操作
	static function safe_html($html) {
		$del = [
		   "/<script.*>(.*)<\/script>/siU",
		   '/on(click|dblclick|mousedown|mouseup|mouseover|mousemove|mouseout|keypress|keydown|keyup)="[^"]*"/i',
		   '/on(abort|beforeunload|error|load|move|resize|scroll|stop|unload)="[^"]*"/i',
		   '/on(blur|change|focus|reset|submit)="[^"]*"/i',
		   '/on(bounce|finish|start)="[^"]*"/i',
		   '/on(beforecopy|beforecut|beforeeditfocus|beforepaste|beforeupdate|contextmenu|cut)="[^"]*"/i',
		   '/on(drag|dragdrop|dragend|dragenter|dragleave|dragover|dragstart|drop|losecapture|paste|select|selectstart)="[^"]*"/i',
		   '/on(afterupdate|cellchange|dataavailable|datasetchanged|datasetcomplete|errorupdate|rowenter|rowexit|rowsdelete|rowsinserted)="[^"]*"/i',
		   	'/on(afterprint|beforeprint|filterchange|help|propertychange|readystatechange)="[^"]*"/i',
            '/javascript\:.*(\;|")/',
		];
		$replace = ['$1','','','','','','','','',''];

        return preg_replace($del, $replace, $html);
	}

	static function & T($format, $args=NULL, $options=NULL) {
		if (Config::get('debug.i18n_ipe')) {
			if ($args) foreach($args as &$v) {
				$v = preg_replace('/\{\[.+?\]\}/', '', $v);
			}
			$format = preg_replace('/\{\[.+?\]\}/', '', $format);
		}
		return parent::T($format, $args, $options);
	}

	static function HTML_brief($html, $length=NULL) {
		$html = strip_tags($html);
		if ($length > 0 && mb_strlen($html) > $length) {
			$html = mb_substr($html, 0, $length) . '...';
		}

		return $html;
	}

	static function H($str, $convert_return = FALSE) {

		$str = parent::H($str);

		if ($convert_return) {
			$in = [
				'`((?:https?|ftp)://\S+)`si',
				'`((?<!//)(www\.\S+))`si',
				'`\r\n|\n`si',
			];  

			$out = [
				'<a href="$1" class="blue prevent_default" target="_blank">$1</a>',
				'<a href="http://$1" class="blue prevent_default" target="_blank">$1</a>',
				'<br/>',
			];

			$str = preg_replace($in, $out, $str);
		}

		return $str;
	}

}
