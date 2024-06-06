<?php 

class Form extends _Form {

	protected function validate_is_mobile($key, $error, $params) {
		//该表达式只匹配国内大陆座机号码和手机号码，以后根据需求再予以添加
		if (!(preg_match('/^(1[3|5][0-9]|18[6-9])\d{8}$/', $this[$key]) || preg_match('/^0([1|2]\d-?\d{8}|\d{3}-?\d{7})$/', $this[$key]))) {
			$this->no_error = FALSE;
			$this->errors[$key][] = $error ? : T('错误的电话号码');
		}
	}

    protected function validate_is_token($key, $error, $params) {
        if (Config::get('auth.enable_cn_token')) {
            //允许中文
            $pattern = '/^([A-z0-9]|[\x7f-\xff])([A-z0-9_.\-@]|[\x7f-\xff])+(\|\w+)?$/';
        }
        else {
            $pattern = '/^[A-z0-9][A-z0-9_.\-@]+(\|\w+)?$/';
        }
        $p = preg_match($pattern, $this[$key]) && !Event::trigger('user.extra.validate_is_token', $this[$key]);
        if(!$p){
            $this->no_error = FALSE;
            $this->errors[$key][]= $error ?: T('不合法');
        }
    }

    static function radio($name, $value=NULL, $selected=NULL, $label=NULL, $extra=NULL, $extra_label=NULL) {

        if (preg_match('/\bid\s*=\s*(["\'])(.+?)\1/', $extra, $parts)) {
            $rel_id = $parts[2];
        }
        else {
            $rel_id = 'radio'.uniqid();
        }

        if ($extra != '') $extra = ' '.$extra;
        $extra .= $value === NULL ? '':' value="'.H($value).'"';
        $extra .= ($selected == $value) ? ' checked="true"':'';

        $form = '<div class="pretty p-default p-curve">'.
            '<input id="'.$rel_id.'" name="'.$name.'" type="radio"'.$extra.'/>';



        if($label) {
            if ($extra_label != '') $extra_label = ' '.$extra_label;
            $form .='<div class="state p-success-o"><label for="'.$rel_id.'"'.$extra_label.'>'.$label.'</label></div>';
        }
        else {
            $form .='<div class="state p-success-o"><label></label></div>';
        }
        $form .= '</div>';
        return $form;
    }
}
