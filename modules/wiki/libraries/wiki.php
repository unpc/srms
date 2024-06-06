<?php

//我们采用PHP Markdown作为Wiki的文本格式基础
Core::load(THIRD_BASE, 'markdown', 'wiki');
Core::load(THIRD_BASE, 'geshi', '*');

final class Wiki {
	
	private $base_dir;
	private $media_base_dir;
	private $pages;
	
	private $path;
	private $base;


	function __construct($opt = [], $path=NULL) {
		
		$opt = (array) $opt;
		
		$this->base_dir = self::base_dir($opt['base']);
		$this->media_base_dir = self::media_base_dir($opt['base']);
		$this->pages = $opt['pages'];
		
		$this->path = $this->fix_path($path);
		$this->base = preg_replace('/:?[^:]+$/', '', $this->path);
	}
	
	static function media_base_dir($base='') {
		return LAB_PATH . PRIVATE_BASE . Config::get('wiki.media_base_dir', 'wiki_media/') . $base;
	}
	
	static function base_dir($base='') {
		return LAB_PATH . PRIVATE_BASE . Config::get('wiki.base_dir', 'wiki/') . $base;
	}
	
	static function fix_path($path) {
		//将$path变成合法路径
		if (preg_match_all('/\s*([^:]+)\s*:?/', $path, $parts)) {
			$units = $parts[1];
		} else {
			$units = [trim($path)];
		}
		$path = NULL;
		foreach ($units as $unit) {
			$unit = preg_replace('/[^a-z0-9\pL]+/iu', '_', $unit);
			$unit = mb_convert_case($unit, MB_CASE_LOWER);
			if($path) $path .= ':';
			$path .= $unit;
		}
		if(!$path) $path = 'index';
		return $path;
	}
	
	function absolute_path($path) {
		
		//对有相对路径的进行转换
		if (FALSE !== mb_strpos($path, '.')) {
		
			$base = $this->base;
		
			if (preg_match_all('/\s*([^:]+)\s*:?/', $path, $parts)) {
				$units = $parts[1];
				
				//将.或者..替换成绝对路径
				$path = NULL;
				while($unit = array_shift($units)){
					if($unit == '.') { //当前目录
						if(!$path) $path = trim($base, ':');
						continue;
					} 
					elseif($unit == '..') { //上级目录
						if(!$path) $path = trim($base, ':');
						$path = preg_replace('/(:)?[^:]*$/', '', $path);
					}
					else {
						if($path) $path .= ':';
						$path .= $unit;
					}
				}
				
			} else {
				$base = trim($base, ':');
				if($base) $path = $base.':'.$path;
			}
		}
		
		return $path;
	}

	function exists($path=NULL) {
		if($path === NULL) $path = $this->path;
		else $path = $this->absolute_path($path);
		return file_exists($this->file($path));
	}
	
	private function file($path) {
		return $this->base_dir.
				strtr(self::fix_path($path), ':', '/').
				'.txt';
	}
	
	private function content($path=NULL) {
		if($path === NULL) $path = $this->path;
		else $path = $this->absolute_path($path);

		return @file_get_contents($this->file($path));
	}

	function __get($name) {
		switch($name) {
		case 'content':
			return $this->content = $this->content();
		case 'path':
			return $this->path;
		default:
			return NULL;
		}
	}

	function save() {
		$file = $this->file($this->path);
		if($this->content) {
			File::check_path($file);
			@file_put_contents($file, $this->content);
			return TRUE;
		} else {
			File::delete($file, TRUE);
			return FALSE;
		}
	}

	function delete() {
		$this->content = '';
		$this->save();
	}
	
	function render($mode=NULL) {
		$parser = new Wiki_Parser($this, $mode);
		return $parser->transform($this->content);
	}
	
	function title($path=NULL) {
		// ns1:ns2:name;
		if($path === NULL) {
			$content = $this->content;
		}
		else {
			$path = $this->absolute_path($path);
			$content = $this->content($path);
		}
		if(preg_match('/(={1,6})\s*(.+?)\s*\1/imu', $content, $parts)){
			$title = $parts[2];
		}
		elseif(preg_match('/\s*([^:]+)\s*$/', $path, $parts)) {
			$title = $parts[1];
		} else {
			$title = $path;
		}
		return $title;
	}
	
	function media_url($path, $size = NULL) {
		if (preg_match('/[^\\\]\/|^\//', $path)) {  # 查看是否是外部路径
			if(!$size) return $path;
		} 
		else {
			$path = File::relative_path($this->media_base_dir, ROOT_PATH).strtr($path, ':', '/');
		}
		return Wiki_Preview::url($path, $size);
	}
	
	function url($path=NULL, $op='view') {
		if (preg_match('/(?:[^\\\]|^)\//', $path)) {  # 查看是否是外部路径
			return URI::url($path);
		}
		if($path === NULL) $path = $this->path;
		else $path = $this->absolute_path($path);

		$path = strtr(self::fix_path($path), ':', '.');

		return URI::url(strtr($this->pages[$op], ['%wiki'=>$path]));
	}
	
	function anchor($path, $title=NULL) {

		//提取fragment
		list($path, $fragment) = explode('#', $path, 2);
		if(!$path) $path = $this->path;

		if (!preg_match('/(?:[^\\\]|^)\//', $path)) { # 查看是否是外部路径
			if($this->exists($path)) {
				$class='wiki_link';
			} else {
				$class='wiki_link_missing';
			}
		} 
		
		if(!$title) {
			$title = $this->title($path);
		}
				
		return '<a href="'.
			$this->url($path).
			($fragment?'#'.$fragment:'').
			'" class="'.$class.'">'.
			$title.
			'</a>';
	}
	
	function index($path=NULL) {
		
		preg_match('/(^|:)([^:]+)$/', $path, $parts);
		$node->id = $parts[2];

		$path = preg_replace('/[^:]$/', '$0:', $path);

		$node->anchor = $this->anchor($path);
		$base = $this->base_dir.strtr($path, ':', '/');
		if (is_dir($base)) {
			$dh = opendir($base);
			if ($dh) {
				while($n=readdir($dh)){
					if ($n[0]=='.') continue;
					if(is_file($base.$n)) {
						$n = preg_replace('/.txt$/', '', $n);
						if (is_dir($base.$n)) continue;
					}
					$node->children[] = $this->index($path.$n);
				}
				closedir($dh);
			}
		}
		
		return $node;
		
	}
	
	function breadcrumb($direction=NULL) {
		$path = $this->path;
		while($path) {
			$anchors[] = $this->anchor($path);
			$path = preg_replace('/:?[^:]+$/', '', $path);
		}

		if($this->path != 'index') $anchors[] = $this->anchor('index');
		
		if($direction=='r') {
			return implode(' &#171; ', $anchors).' •';
		}

		return '• '.implode(' &#187; ', array_reverse($anchors));

	}
	
}

class Wiki_Parser extends Markdown_Parser {

	private $wiki;
	private $mode;
	
	private $section;

	function __construct(Wiki $wiki, $mode = NULL) {
		
		$this->wiki = $wiki;
		$this->mode = $mode;
		
		$this->section = (object) [
			'level'=>0,
		];
		
		$this->document_gamut += [
			// "doWikiTOC" => 200,
			"doCodeBlocks" => 10,
		];
		
		$this->block_gamut += [
			"doWikiTables" => 5,	//最高优先级 从而使表格内可以嵌套任何标记
			"doWikiLevels" => 200,
		];
		
		$this->span_gamut += [
			"doWikiLinks"    => 50,
			"doItalic" => 50,
			"doUnderline" => 50,
			"doFixWidth" => 50,
		];
		
		parent::__construct();
	}
	
	function source ($source) {
		return ' source="'.$this->encodeAttribute($source).'"';
	}

	function doWikiTOC($text) {
		$toc = $this->_doWikiTOC_callback($this->section->sections);		
		return '<div class="wiki_toc"><div class="content"><h4>'.T('内容目录').'</h4>'.$toc.'</div></div>'.$text;
	}
	function _doWikiTOC_callback($sections){
		if(!$sections) return '';
		$text = '<ul>';
		foreach ($sections as $section) {
			if($section->title) {
				$text .= 
					'<li>'.
						$this->wiki->anchor('#'.$this->encodeAttribute($section->title), $section->title).
						($section->level<2 ? $this->_doWikiTOC_callback($section->sections) : '') .
					'</li>';
			}		
		}
		$text .= '</ul>';
		return $text;
	}
	
	function doWikiLevels($text) {
		return $this->_doWikiLevels_callback($this->section->sections, $text);		
	}
	function _doWikiLevels_callback($sections, $text){
		if(!$sections) return $text;
		$i = 0;
		while ($sections[$i]) {
			if (preg_match(
				'{(^.*)('.preg_quote($sections[$i]->hash).')(.*)('
				. ($sections[$i+1]?preg_quote($sections[$i+1]->hash):'')
				.'.*)$}s', 
				$text, $matches)) {
				$text = $matches[1].
					'<div class="level level'.$sections[$i]->level.'">'.
					$matches[2].
					$this->_doWikiLevels_callback($sections[$i]->sections, $matches[3]).
					'</div>'.
					$matches[4];
			}
			$i++;
		}
		return $text;
	}

	function doWikiLinks($text) {
		// [[ns1:ns2:link|name]]
		$text = preg_replace_callback(
			'/\[\[([^|]+)(?:\|(.*))?\]\]/', 
			[$this, '_doWikiLinks_callback'], $text);

		return $text;
	}
	function _doWikiLinks_callback($matches) {
		$path = $matches[1];
		$title = $this->runSpanGamut($matches[2]);
		
		return $this->hashPart($this->wiki->anchor($path, $title));
	}

	function doItalic($text) {
		// [[ns1:ns2:link|name]]
		$text = preg_replace_callback(
			'|//(.+?)//|', 
			[$this, '_doItalic_callback'], $text);

		return $text;
	}
	function _doItalic_callback($matches) {
		$text = $this->runSpanGamut($matches[1]);		
		return $this->hashPart('<em>'.$text.'</em>');
	}

	function doFixWidth($text) {
		// [[ns1:ns2:link|name]]
		$text = preg_replace_callback(
			'|\'\'(.+?)\'\'|', 
			[$this, '_doFixWidth_callback'], $text);

		return $text;
	}
	function _doFixWidth_callback($matches) {
		$text = $this->runSpanGamut($matches[1]);		
		return $this->hashPart('<code>'.$text.'</code>');
	}

	function doUnderline($text) {
		// [[ns1:ns2:link|name]]
		$text = preg_replace_callback(
			'|__(.+?)__|', 
			[$this, '_doUnderline_callback'], $text);

		return $text;
	}
	function _doUnderline_callback($matches) {
		$text = $this->runSpanGamut($matches[1]);		
		return $this->hashPart('<span style="text-decoration:underline">'.$text.'</span>');
	}

	//override doHeaders
	function doHeaders($text) {

		$text = preg_replace_callback('{
				^\s*(\={1,6})	# $1 = string of #\'s
				\s*(.*?)\s*
				\1\s*$			# optional closing #\'s (not counted)
			}xm',
			[$this, '_doHeaders_callback_atx'], $text);

		return $text;
	}
	function _doHeaders_callback_atx($matches) {
		$level = 7 - strlen($matches[1]);
		$block = '<h'.$level;

		if ($this->mode == 'edit') {
			$block .= $this->source($matches[0]);
		}
		
		$title = $matches[2];
		
		//添加锚点
		$block .=' id="'.$this->encodeAttribute($title).'"';

		$block .= '>'.$this->encodeAttribute($title).'</h'.$level.'>';

		$hash=$this->hashBlock($block);
		
		$s = $this->section;
		
		while ($s) {
			if(!$s->sections) break;
			$c = end($s->sections);
			if($c->level >= $level) break;
			$s = $c;
		}
		
		$s->sections[] = (object) [
			'level' => $level,
			'hash' => $hash,
			'title' => $title, 
		];
		
		return "\n".$hash."\n\n";
	}

	function doCodeBlocks($text) {
		$text = preg_replace_callback('{
				<code\s*([^>]*)\s*>
				([\s\S\n]*?)
				</code>
			}xm',
			[&$this, '_doCodeBlocks_callback'], $text);

		return $text;
	}
	function _doCodeBlocks_callback($matches) {
		$lang = $matches[1];
		$code = $matches[2];
		
		if(class_exists('GeSHi', false) && $lang) {
			$geshi = new GeSHi($code, $lang);
			$output = $geshi->parse_code();
		} else {
			$output = '<pre>'.$code.'</pre>';
		}

		return $this->hashBlock($output) . "\n";
	}

	function doWikiTables($text) {
		/*
		// 顶部表头
		|    id    |    name     |     [test\|description]       | price  |
		|----------|-------------|-------------------------------|--------|
		|        1 | gizmo       | Takes care of the doohickies  |   1.99 |
		|        2 | doodad      | Collects *gizmos*             |  23.80 |
		|       10 | dojigger    | Handles:                      | 102.98 |
		|          :             : * gizmos                      :        :
		|          :             : * doodads                     :        :
		|          :             | * thingamobobs                :        :
		|     1024 | thingamabob | Self‐explanatory, no?         |   0.99 |
		


		// 左侧表头
		
		|!  status  | ok      | women  | tamen   
		|!  status  : ok      | women  | tamen   
		|!  other   | failed  | nimen  | shuimen 
		|!  other   | failed  | nimen  | shuimen 

		*/
		
		$text = preg_replace_callback('{
				(?:^\s*[^\\\][|].+$\n)+  # $0 整个表格内容
			}xm',
			[&$this, '_doWikiTables_callback'], $text);

		return $text;
	}
	function _doWikiTables_callback($matches) {

		preg_match_all('/^\s*[|:](.+)$/m', $matches[0], $parts);

		$rows = $parts[1];
		$col_width = [];
		$max_num_cols = 0;
		$curr_row = 0;
		$trows = [];
		foreach($rows as $row) 
		if($num_cols = preg_match_all('{
			(					# $1: 所有内容
				([!])?			# $2: 是否表头
				(\s*)			# $3: 左空格
				(				# $4: 内容
					(?:\\\[|:]|[^|:])*
				)
				(\s+)			# $5: 右空格
				|
				(-{3,})			# $6: 头部分隔
				|				# 空
				\s*
			)
			([|:])				# $7:右边界
			}x', $row, $parts, PREG_SET_ORDER)) {
			
			// 表头分隔线
			if(!isset($num_head) && preg_match('/-{3,}/', $parts[0][6])){
				$num_head = count($trows);
				continue;
			}

			$max_num_cols = max($max_num_cols, $num_cols);

			$new_tr = [];

			foreach($parts as $i=>$part) {
				list(
					$dummy,
					$full_content,
					$head,
					$left_space,
					$content,
					$right_space,
					$dummy,	//头部分隔
					$border
				) = $part;
			
				if(!$full_content) {
					//colspan + 1
					if(isset($new_tr[$i-1])) {
						$new_tr[$i-1]['colspan'] ++;
						continue;
					}
				}
			
				$new_td = [
					'width' => $cw,
					'head' => $head != '',
					'colspan' => 1,
				];
				
				$sl=mb_strlen($left_space, 'UTF-8');
				$sr=mb_strlen($right_space, 'UTF-8');
				if($sl>1 && $sr>1) {
					$new_td['align']='center';
				} 
				elseif($sr<=1 && $sl>$sr) {
					$new_td['align']='right';
				}
				
				if ($border == ':') {
					$j = $curr_row - 1;
					while($j>=0 && !isset($trows[$j][$i])) $j--;
					if ($j>=0) {
						$trows[$j][$i]['content'] .= "\n\n". ($content ?: '&#160;');
						continue;
					}
				}
				
				$new_td['content'] = $content;
				$new_td['rowspan'] = 1;
				$new_tr[$i] = $new_td;
				
			}
			
			if(count($new_tr)>0) {

				for($i=0; $i<$max_num_cols; $i++) {
					if(isset($new_tr[$i])) continue;
					$j = $curr_row - 1;
					while($j>=0 && !isset($trows[$j][$i])) $j--;
					if ($j>=0) $trows[$j][$i]['rowspan'] += 1;
				}
				
				$trows[$curr_row] = $new_tr;
				$curr_row ++;
				
			}
			
		}

		//生成表格
		$output = '<table>';
		$thead = array_splice($trows, 0, $num_head);
		if ($thead) {
			$output .= '<thead>';
			foreach ($thead as &$tr) {
				$output .= '<tr>';
				foreach($tr as &$td) {
					$output.=$this->_doWikiTables_render_cell($td, TRUE);
				}
				$output .= '</tr>';
			}
			$output .= '</thead>';
		}
		$output .= '<tbody>';
		foreach ($trows as &$tr) {
			$output .= '<tr>';
			foreach($tr as &$td) {
				$output.=$this->_doWikiTables_render_cell($td);
			}
			$output .= '</tr>';
		}
		$output .= '</tbody></table>';


		return $this->hashBlock($output). "\n";
	}
	protected function _doWikiTables_render_cell(&$td, $header=FALSE) {
		$output .= '<td';
		if($header || $td['head']) $class.= ($class? ' ': '').'header';
		if($td['align']) $class.= ($class? ' ': '').$td['align'];
		if($class) $output.=' class="'.$class.'"';
		if($td['colspan']>1) $output.= ' colspan="'.$td['colspan'].'"';
		if($td['rowspan']>1) $output.= ' rowspan="'.$td['rowspan'].'"';
		$content = preg_replace('/[\\\]([|:])/', '$1', $td['content']);
		$output .= '>'.$this->runBlockGamut($content).'</td>';
		return $output;
	}

	function doImages($text) {
		#
		# First, handle reference-style labeled images: {{[id]?size|alt text}}
		#
		$text = preg_replace_callback('{
			  \{\{
			 	\[
					([^|\]]+?)		# id = $1
			  	\]
				\s*
				(?:
					[?]\s*(\d+(?:x\d+)?)  #size = $2
				)?
				\s*
				(?:
					\|\s*([^\}]+?)\s*	# alt text = $3
				)?
			  \}\}
			}xs', 
			[$this, '_doImages_reference_callback'], $text);

		#
		# Next, handle inline images:  {{image url?size|alt text}}
		# Don't forget: encode * and _
		#
		$text = preg_replace_callback('{
			  \{\{
				\s*
				([^?|]+?)	# src url = $1
				\s*
				(?:
					[?]\s*(\d+(?:x\d+)?)  #size = $2
				)?
				\s*
				(?:
					\|
					\s*
					([^\}]+?)		# alt text = $3
					\s*
				)?
			  \}\}
			}xs',
			[$this, '_doImages_inline_callback'], $text);
		return $text;
	}
	function _doImages_reference_callback($matches) {
		$whole_match = $matches[0];
		$link_id     = strtolower($matches[1]);
		$size			= $matches[2];
		$alt_text    = $matches[3];

		if ($link_id == "") {
			$link_id = strtolower($alt_text); # for shortcut links like ![this][].
		}

		$alt_text = $this->encodeAttribute($alt_text);
		if (isset($this->urls[$link_id])) {
			$url = $this->wiki->media_url($this->urls[$link_id], $size);
			$result = "<img src=\"$url\" alt=\"$alt_text\"";
			if (isset($this->titles[$link_id])) {
				$title = $this->titles[$link_id];
				$title = $this->encodeAttribute($title);
				$result .=  " title=\"$title\"";
			} else {
				$result .=  " title=\"$alt_text\"";
			}
			$result .= $this->empty_element_suffix;
			$result = $this->hashPart($result);
		}
		else {
			# If there's no such link ID, leave intact:
			$result = $whole_match;
		}

		return $result;
	}
	function _doImages_inline_callback($matches) {
		$url			= $matches[1];
		$size			= $matches[2];
		$alt_text		= $matches[3];

		$alt_text = $this->encodeAttribute($alt_text);
		$url = $this->wiki->media_url($url, $size);
		$result = "<img src=\"$url\" alt=\"$alt_text\" title=\"$alt_text\"";
		$result .= $this->empty_element_suffix;

		return $this->hashPart($result);
	}

}
