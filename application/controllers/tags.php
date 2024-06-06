<?php

class Tags_AJAX_Controller extends AJAX_Controller {

	function index_tag_edit_click() {
		$form = Form::filter(Input::form());
        $title = I18N::T('application', '添加'.$form['title']);
		if ($form['id']) {
			$tag = O($form['tag_name'], Q::quote($form['id']));
		}		
		JS::dialog(V('application:admin/tags/tag_edit', [
			'tag'=>$tag,
			'tag_name'=>$form['tag_name'],
			'parent'=>H($form['parent']),
			'uniqid'=>H($form['uniqid']),
			'parent_uniqid'=>H($form['parent_uniqid']),
			'is_root'=>H($form['is_root']),
			'collapsed'=>H($form['collapsed']),
            'title' => $title
			]),
            ['title' => $title]
		);
	}
	
	function index_tag_edit_submit() {
		$me = L('ME');
		$form = Form::filter(Input::form());
		$name = trim($form['name']);
		$code = trim($form['code']);
        $tag = O($form['tag_name'], $form['id']);
		$form->validate('name', 'not_empty', I18N::T('application', '标签名称不能为空!'));
        $exist = Q("tag[code={$code}]")->current();
		if ($code && $exist->id && $exist->id != $tag->id) {
			$form->set_error('code', I18N::T('application', '已存在编码相同的其他组织机构!'));
		}
        
        if ($form->no_error) {
			try {

                //要求输入0可以正常进行保存
                if (!$name && $name != 0) {
                    throw new Error_Exception;
                }

				$tag->name = $name;
				$tag->code = $code;
				if ($tag->id) {
					$new = FALSE;
				} else {
					$new = TRUE;
					$parent = O($tag->name(), $form['parent']);
					$tag->parent = $parent;
                    $last_tag = Q("{$tag->name()}[parent={$parent}]:sort(weight D):limit(1)")->current();
					$tag->weight = $last_tag->weight + 1;	
				}

				$tag->update_root()->save();
				
				if (!$tag->id) throw new Error_Exception;
				
				$item_rel = '#'.H($form['uniqid']);

				if ($new) {
					if ($parent->id) {
						$tags = Q("{$tag->name()}[parent=$parent]:sort(weight)");
					}
					else {
						$tags = Q("{$tag->name()}[!root]:sort(weight)");
					}
					
					if ($form['is_root']) {
						Output::$AJAX["$item_rel > .tag_container"] = (string) V('application:admin/tags/tag_list', ['tags'=> $tags, 'parent_uniqid'=>H($form['uniqid'])]);
					}
					else {
						Output::$AJAX["$item_rel"] = [
							'data' => (string) V('application:admin/tags/tag_item', ['tag'=>$parent, 'tags'=>$tags, 'parent_uniqid'=> H($form['parent_uniqid'])]),
							'mode' => 'replace',
						];
					}

					Log::add(strtr('[application] %user_name[%user_id]成功添加标签%tag_name[%tag_id]', [
						'%user_name' => $me->name,
						'%user_id' => $me->id,
						'%tag_name' => $tag->name,
						'%tag_id' => $tag->id,
					]), 'admin');
				}
				else {
					Output::$AJAX["$item_rel > .tag_title"] = [
						'data'=>(string) V('application:admin/tags/tag_title', ['tag'=>$tag, 'uniqid'=>H($form['uniqid']), 'parent_uniqid'=>H($form['parent_uniqid']), 'collapsed'=>H($form['collapsed'])]),
						'mode'=>'replace',
					];

					Log::add(strtr('[application] %user_name[%user_id]成功修改标签%tag_name[%tag_id]', [
						'%user_name' => $me->name,
						'%user_id' => $me->id,
						'%tag_name' => $tag->name,
						'%tag_id' => $tag->id,
					]), 'admin');
				}

			}
			catch (Error_Exception $e) {
			}

			JS::close_dialog();
		}
		else {
			JS::dialog(V('application:admin/tags/tag_edit', [
				'tag'=>$tag,
				'parent'=>H($form['parent']),
				'uniqid'=>H($form['uniqid']),
				'parent_uniqid'=>H($form['parent_uniqid']),
				'is_root'=>H($form['is_root']),
				'collapsed'=>H($form['collapsed']),
				'form'=>$form
				]),
                ['title' => $form['title']]
			);
		}
 	}
	
	function index_tag_view_click(){
		$form = Form::filter(Input::form());
		
		$tag = O($form['tag_name'], Q::quote($form['tag']));

		$item_rel = '#'.$form['uniqid'];
		$view = V('application:admin/tags/tag_item', ['tag'=>$tag, 'uniqid'=>H($form['uniqid']), 'parent_uniqid'=>H($form['uniqid']), 'title'=>$form['title']]);
		
		if (!$form['collapse'] && $tag->id) {
			$view->tags = Q("{$tag->name()}[parent=$tag]:sort(weight)");
		}

		Output::$AJAX["$item_rel"] = [
			'data' => (string) $view,
			'mode' => 'html',
		]; 

	}
	
	function index_tag_delete_click() {
		if (JS::confirm(T('您确定要删除该标签及其下面所有的子标签吗?删除后不可恢复!'))) {
			//遍历删除并将页面重置
			$me = L('ME');
			$form = Form::filter(Input::form());
			$tag = O($form['tag_name'], Q::quote($form['id']));
			if ($tag->id) {
				$parent = $tag->parent;
				$item_rel = '#'.$form['uniqid'];
				$id = $tag->id;
				$name = $tag->name;
	
				if (!$tag->delete()) {
					$messages = Lab::$messages[Lab::MESSAGE_ERROR];
					if (count($messages) > 0) {
						JS::alert(implode("\n", $messages));
					}
					return;
				}
								
				Output::$AJAX["$item_rel"] = [
					'data'=>'',
					'mode'=>'replace',			
				];
				
				if($parent->id && Q("tag[parent=$parent]")->length() == 0) {
					$parent_item_rel = '#'.$form['parent_uniqid'];
					$js = "jQuery('$parent_item_rel > .tag_title .toggle_button').click()";
					JS::run($js);
				}

				Log::add(strtr('[application] %user_name[%user_id]成功删除标签%tag_name[%tag_id]及其下面所有的子标签', [
					'%user_name' => $me->name,
					'%user_id' => $me->id,
					'%tag_name' => $name,
					'%tag_id' => $id,
				]), 'admin');
			}			
		}
	}
	
	function index_tag_sortable_change() {
		$me = L('ME');
		$form = Form::filter(Input::form());
		$p_tag = O($form['tag_name'],$form['prev_id']);
		$tag = O($form['tag_name'],$form['current_id']);
		$uniqid = $form['uniqid'];
		$parent = $tag->parent ?: $tag->root;
		if($p_tag->id && ($p_tag->parent->id == $parent->id)) {
			if($p_tag->weight < $tag->weight) {
				$new_weight = $p_tag->weight+1;
			}
			else{
				$new_weight = $p_tag->weight;		
			}
		}else{
			$new_weight = 0;
		}
		$old_weight = $tag->weight;
		if($old_weight < $new_weight) {
			$tags = Q("{$tag->name()}[parent={$parent}][weight>$old_weight][weight<=$new_weight]:sort(weight D)");
			$tmp = $new_weight;
			foreach($tags as $t) {
				if($tmp > $t->weight) break;
				$t->weight = $t->weight-1;
				$t->save();
				$tmp--;				
			}
		}
		else{
			$tags = Q("{$tag->name()}[parent={$parent}][weight>=$new_weight][weight<$old_weight]:sort(weight)");
			$tmp = $new_weight;
			foreach($tags as $t) {
				if($tmp < $t->weight) break;
				$t->weight = $t->weight+1;
				$t->save();
				$tmp++;
			}
		}
		$tag->weight = $new_weight;
		$tag->save();
		$tags = Q("{$tag->name()}[parent={$parent}]:sort(weight)");

		Log::add(strtr('[application] %user_name[%user_id]成功修改标签%tag_name[%tag_id]排序', [
			'%user_name' => $me->name,
			'%user_id' => $me->id,
			'%tag_name' => $tag->name,
			'%tag_id' => $tag->id,
		]), 'admin');

		if($parent->id == $tag->root->id) {
			Output::$AJAX["#$uniqid > .tag_root_container"] = [
				'data' => (string)V('application:admin/tags/tag_list', [
					'tags'=>$tags, 
					'parent_uniqid'=>$uniqid,
				]),
			];
		}
		else{
			Output::$AJAX["#$uniqid"] = [
				'data'=>(string)V('application:admin/tags/tag_item', [
					'tags'=>$tags,
					'tag'=>$parent, 
					'parent_uniqid'=> H($form['parent_uniqid'])
				]),
				'mode'=>'replace',
			];
		}
	}	
	
	function index_tag_move_change(){
		$me = L('ME');
		$form = Form::filter(Input::form());
		$rec_tag = O($form['tag_name'],Q::quote($form['rec_id']));
		$tag = O($form['tag_name'],Q::quote($form['current_id']));
		
		#if (tag.group_limit >= 1)
		
		$root = $rec_tag->root;
		$max_levels = $GLOBALS['preload']['tag.group_limit'];
		if ($max_levels && $root->id) {
			//求target标签层数（第几层）
			$rec_levels = $root->current_levels($rec_tag);
			//target标签，要移动的标签都要作限制
			if ($rec_levels >= $max_levels) {
				Output::$AJAX[] = ['error'=>true];
				return false;
			}
		}
		#endif
		
		$item = '#'.$form['uniqid'];
		$parent_uniqid = '#'.$form['parent_uniqid'];
        $view =  V('application:admin/tags/tag_item', ['tag'=>$rec_tag, 'parent_uniqid'=> H($form['parent_uniqid'])]);
		if(!$tag->has_descendant($rec_tag) && ($tag->parent->id != $rec_tag->id || !$form['is_refresh'])) {
			$tag->parent = $rec_tag;
			$tags = Q("{$tag->name()}[parent=$rec_tag]:sort(weight)");
			$t = $tags->current();
			if($t->weight == 0) {
				foreach($tags as $v) {
					$v->weight = $v->weight+1;
					$v->save();
				}
			}
			$tag->weight = 0;
			$tag->save();
		}
		else {
			Output::$AJAX[] = ['error'=>true];
			return false;
		}
		$tags = Q("{$tag->name()}[parent={$rec_tag}]:sort(weight)");
		$collapse = $form['collapse'];
		if (!$collapse) {
			$view->tags = $tags;
		}

		if ($form['is_refresh']) {
			if ($rec_tag->root->id == 0) {
				Output::$AJAX["$parent_uniqid > .tag_root_container"] = [
					'data'=>(string)V('application:admin/tags/tag_list', [
						'tags'=>$tags, 
						'parent_uniqid'=>H($form['parent_uniqid']),
					]),
				];
			}
			else {
				Output::$AJAX["$item"] = [
					'data'=>(string)$view,
					'mode'=>'replace',
				];
			}
		}
		Output::$AJAX[] = ['error' => false];

		Log::add(strtr('[application] %user_name[%user_id]成功拖拽标签%tag_name[%tag_id]', [
			'%user_name' => $me->name,
			'%user_id' => $me->id,
			'%tag_name' => $tag->name,
			'%tag_id' => $tag->id,
		]), 'admin');

		return true;
	}

    function index_tag_export_click()
    {
        $form = Form::filter(Input::form());
        $tag_name = $form['tag_name']?:'tag_group';
        $tag_name = strpos($tag_name,'tag_') === 0 ?substr($tag_name, 4):$tag_name;
        $root = Tag_Model::root($tag_name);
        $tags = self::get_group_tag_list($root, $root);

        $file_name = time();
        $excel = new Excel($file_name);
        if (count(Config::get('tag.export_group_headers', []))) {
            $excel->write(Config::get('tag.export_group_headers', []));
        }
        foreach ($tags as $tag) {
            $excel->write($tag);
        }
        $excel->save();

        $path = Config::get('system.excel_path');
        if (file_exists($path.'/'.$file_name.'.xlsx')) {
            JS::redirect(URI::url('index/download.' . $file_name . '.xlsx'));
        }
    }

    private function get_group_tag_list($root, $tag, $temp=[])
    {
        static $tag_list = [];
        if ($root->id != $tag->id) {
            $temp[] = $tag->code ? $tag->name."({$tag->code})" : $tag->name;
        }
        if (!$tag->children()->total_count()
            || (Config::get('tag.export_group_max_level', 0)
            && Config::get('tag.export_group_max_level', 0) <= $root->current_levels($tag))) {
            $tag_list[] = $temp;
        }else {
            foreach ($tag->children() as $child) {
                self::get_group_tag_list($root, $child, $temp);
            }
        }

        return $tag_list;
    }
}
