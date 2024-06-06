<?php

class Presentable_Model extends ORM_Model {

	protected $view;
	protected $object_page = [
		'view'=>'show/%object.%id'
		];
	protected $icon_page = 'icon/%object.%id.%size?_=%mtime';
	protected $icon_size = [16, 32, 36, 48, 64, 128, 'real'];

	private $vars = [];

	/*
	 * url用于实现一些和对象有关的路径
	 * 最后的$op默认永远是view 希望只要涉及到查看对象的都更改$object_page的view键值
	 * 不要使用info, show之类自定义的object_page
	 */
	function url($arguments=NULL, $query=NULL, $fragment=NULL, $op='view'){
		if (is_array($arguments)) $arguments = implode('.', $arguments);

		$url = $this->object_page[$op];
		$url = Event::trigger("{$this->name()}.get_object_page", $this, $url) ? : $url;

		$this->vars['object'] = $this->name();
		$this->vars['arguments'] = $arguments;

		$url = preg_replace_callback('/\[([^\[\]]+)\]/',
			[$this, '_url_ignore'], $url);

		if (preg_match_all('/%([a-z]+)/i', $url, $parts)) {
			foreach($parts[1] as $name) {
				$val = $this->vars[$name];
				if(NULL === $val) $val = $this->$name;
				$url = preg_replace('/%'.preg_quote($name).'/', $val, $url);
			}
		}
		$url = Event::trigger('orm_model.call.url', $this, $url, $query, $fragment, $op) ? : URI::url($url, $query, $fragment);
		
		return $url;
	}

	private function _url_ignore($matches) {
		$text = $matches[1];

		if (preg_match_all('/%([a-z]+)/i', $text, $parts)) {
			foreach($parts[1] as $name) {
				$val = $this->vars[$name];
				if(NULL === $val) $val = $this->$name;
				if(!$val && !is_numeric($val)) return ''; //返回清空值
				$text = preg_replace('/%'.preg_quote($name).'/', $val, $text);
			}
		}
		return $text;
	}

	function icon_url($size=128) {
		$size = $this->normalize_icon_size($size);
		$icon_file = $this->icon_file($size);
        return Config::get('system.base_url').Cache::cache_file($icon_file).'?_='.$this->mtime;
	}

	function icon($size=128, $extra=NULL){
		if ($extra) $extra = ' '.$extra;
		$icon_class = 'icon icon_'.$this->name();
		if (preg_match('/\bclass=\"(.+?)\"/', $extra)) {
			$extra = preg_replace('/\bclass=\"(.+?)\"/', 'class="$1 '.$icon_class.'"', $extra);
		}
		else {
			$extra .= ' class="'.$icon_class.'"';
		}

		if ($size != 128) {
			$size = $size ?: '32';
			$extra .= ' width="'.$size.'px" height="'.$size.'px"';
		}

		return '<img'.$extra.' src="'.$this->icon_url($size).'" />';
	}

	function icon_file($size=128, array $fields=['id']) {
		foreach($fields as $field){
			$file = Core::file_exists(PRIVATE_BASE.'icons/'.$this->name().'/'.$size.'/'.$this->$field.'.png', '*');
			if($file) break;
		}

        if(!$file) $file = Core::file_exists(PRIVATE_BASE.'icons/'.$this->name().'/'.$size.'.png', '*');

        if (!$file) $file = Core::file_exists(PRIVATE_BASE.'icons/'.$size.'.png', '*');

		return $file;
	}

	function normalize_icon_size($size) {
		if(!in_array($size, $this->icon_size)){
			//如果不合适 选一个最接近的
			$nsize = 0;
			$csize = 16;
			foreach ($this->icon_size as $sz) {
				if ($size == $sz) {
					$nsize = $sz;
					break;
				}
				elseif (abs($size - $sz) < abs($size - $csize)){
					$csize = $sz;
				}
			}

			$size = $nsize ?: $csize;
		}

		return $size;
	}

	function show_icon($size=128, array $fields=['id']){
		$size = $this->normalize_icon_size($size);
		$file = $this->icon_file($size, $fields);
		if ($file) {
			Image::show_file($file, 'png');
		}
	}

	private function _save_icon($image, $size, $base = '') {
		$base = $base ?: LAB_PATH.PRIVATE_BASE.'icons/'.$this->name().'/';

		$image->resize($size, $size, FALSE);
		// $image->crop_center($size, $size);
		$path = $base.$size.'/'.$this->id.'.png';
		File::check_path($path);
		$image->save('png', $path);
		Cache::cache_file($path, TRUE);

		return $path;
	}

	function save_icon($image){

		$base = LAB_PATH.PRIVATE_BASE.'icons/'.$this->name().'/';

		$image->background_color('#ffffff');
		$image->resize(128, 128, FALSE);
		$image->crop_center(128, 128);
		$path = $base.'128/'.$this->id.'.png';
		File::check_path($path);

		$image->save('png', $path);

		Cache::cache_file($path, TRUE);

		$icon_size = $this->icon_size;
		rsort($icon_size);	// 反向排序
		foreach ($icon_size as $size) {
			if ($size == 128) continue;
			$this->_save_icon($image, $size, $base);
		}

		$this->touch()->save();
//error_log(print_r($this,1));
		return $this;
	}

	function delete() {
		$return = parent::delete();
		if ($return) {
			$this->_delete_icon();
		}
		return $return;
	}

	private function _delete_icon() {
		$base = LAB_PATH.PRIVATE_BASE.'icons/'.$this->name().'/';
		foreach ($this->icon_size as $size) {
			$path = $base.$size.'/'.$this->id.'.png';
            file_exists($path) and @unlink($path);
			Cache::remove_cache_file($path);
		}
	}

	function delete_icon() {
		$this->_delete_icon();
		$this->touch()->save();
	}

	function render($view=NULL, $return = FALSE, $vars=[]){
		if(!$view) $view = V('objects/'.$this->name());
		$view = ($view instanceof View)? $view : V($view);
		$view->set($vars);
		$view->object = $this;

		if ($return) return (string) $view;

		echo $view;

		return $this;
	}

	function &links($mode=NULL) {
		return [];
	}

	function connect($object, $type = NULL, $approved = false) {
		$ret = parent::connect($object, $type, $approved);
		$name1 = $this->name();
		if (is_array($object)) {
			foreach ($object as $o) {
				if (is_object($o)) {
					$name2 = $o->name();
					if (strcmp($name1, $name2) < 0) {
						list($name1, $name2) = array($name2, $name1);
					}
					Event::trigger("{$name1}_{$name2}.connect", $this, $o, $type);
				}
			}
		} elseif (is_object($object)) {
			$name2 = $object->name();
			if (strcmp($name1, $name2) < 0) {
				list($name1, $name2) = array($name2, $name1);
			}
			Event::trigger("{$name1}_{$name2}.connect", $this, $object, $type);
		}

		return $ret;
	}

    function disconnect($object, $type=null, $approved=null)
    {
        $ret = parent::disconnect($object, $type, $approved);
        $name1 = $this->name();
		if (is_array($object)) {
			foreach ($object as $o) {
				if (is_object($o)) {
					$name2 = $o->name();
					if (strcmp($name1, $name2) < 0) {
						list($name1, $name2) = array($name2, $name1);
					}
					Event::trigger("{$name1}_{$name2}.disconnect", $this, $o, $type);
				}
			}
		} elseif (is_object($object)) {
			$name2 = $object->name();
			if (strcmp($name1, $name2) < 0) {
				list($name1, $name2) = array($name2, $name1);
			}
			Event::trigger("{$name1}_{$name2}.disconnect", $this, $object, $type);
		}
        return $ret;
    }
}
