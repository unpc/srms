<?php 

class Resume_Model extends Presentable_Model {
	
	protected $object_page = [
		'view'=>'!resume/resume/index.%id[.%arguments]',
		'edit'=>'!resume/resume/edit.%id[.%arguments]',
		'delete'=>'!resume/resume/delete.%id',
		'delete_file' => '!resume/resume/delete_file.%id[.%arguments]',
		'download_file' => '!resume/resume/download_file.%id[.%arguments]'
		];
	
	function & links($mode='edit') {
		$links = new ArrayIterator;
		switch ($mode) {
		case 'file':
			$links['download_file'] = [
				'url' => $this->url(NULL, NULL, NULL, 'download_file'),
				'text'  => I18N::T('resume', '下载'),
				'extra' => ' class="blue"',
				];
			$links['delete_file'] = [
				'url' => $this->url(NULL, NULL, NULL, 'delete_file'),
				'text'  => I18N::T('resume', '删除'),
				'extra'=> 'class="blue" confirm="'.I18N::T('resume', '您确认删除该文件吗？').'"' ,
				];
			break;
		case 'view':
			if( L('ME')->is_allowed_to('修改', $this) ){	
				$links['edit'] = [
					'url' => $this->url(NULL, NULL, NULL, 'edit'),
					'text'  => I18N::T('resume', '修改'),
					'extra'=> ' class="blue"' ,
				];
			}
			break;
		case 'edit':
		default:
			$new_links	=	Event::trigger('resume.models.link.index',$this,$links);
			if( L('ME')->is_allowed_to('修改', $this) ){	
				$links['edit'] = [
					'url' => $this->url(NULL, NULL, NULL, 'edit'),
					'text'  => I18N::T('resume', '修改'),
					'extra'=> ' class="button button_edit"' ,
				];
			}
			break;
		}
		return (array) $links;
	}
	
	function get_path($name) {
		$root = LAB_PATH . 'private/resume/' . $this->id . '/';
		if(!file_exists($root)) {
			File::check_path($root.'foo.bar');
			if (is_dir($root)) {
				@mkdir($root, 0755);
			}
		}
		$full_path = $root . $name;
		return $full_path;
	}

	function delete_dir() {
		$result = TRUE;
		$dir = LAB_PATH . 'private/resume/' . $this->id . '/';
		if (is_dir($dir)) {
			$dir_handle = opendir($dir);
			while(count(scandir($dir)) != 2) {
				$file = readdir($dir_handle);
				if (is_file($dir . $file)) {
					unlink($dir . $file);
				}
			}
			$result = rmdir($dir);
			closedir($dir_handle);
		}
		return $result;
	}
}
