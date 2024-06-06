<?php

class Tag_Selector_Widget extends Widget {

	function __construct($vars) {
		parent::__construct('application:tag_selector', $vars);
	}

	function on_tag_search() {
		$form = Input::form();
		$tag = O($form['tag_name'], Q::quote($form['tag_id']));
		$root = O($form['tag_name'], Q::quote($form['root_id']));
		$search_name = $form['search_name'];


		$root = $root->id ? $root : O($form['tag_name'], $tag->root->id);
		$uniqid = $form['uniqid'];
		$real_root = $root->root->id ? $root->root : $root;

		if ($real_root->id == $tag->id) {
			$selector = "{$real_root->name()}[root=$real_root][name*=$search_name]:sort(weight)";
		} else {
			$list = $this->get_group_tag_list($tag);
			$list[] = $tag->id;
			$list = array_unique($list);

			$tag_ids = implode(',',$list);
			$selector = "{$real_root->name()}[root=$real_root][name*=$search_name][id=$tag_ids]:sort(weight)";
		}
		
		$children = Q($selector);
		$items = [];
		foreach ($children as $t) {
            if ($form['status'] != 'null' && $form['status'] != '' && !Q("({$t}) equipment:[status={$form['status']}]")->total_count()) {
                continue;
            }
			$items[$t->id] = [
				'html' => (string) V('application:widgets/tag_selector/item', ['tag'=>$t, 'i18n'=> $form['i18n']]),
				'ccount' => 0,
				'weight' => (int)$t->weight
			];
		}

		if (count($children) > 0) {
			//判断是对标签隐藏还是展示
			Output::$AJAX['items'] = $items;
		}
	}

	private function get_group_tag_list($tag)
    {

        $tag_list = [];
       
        if (!$tag->children()->total_count()) {
            return $tag_list;
        }else {
            foreach ($tag->children() as $child) {
				$tag_list[] = $child->id;
                $tag_list = array_merge(self::get_group_tag_list($child),$tag_list);
            }
        }

        return $tag_list;
    }
	function on_tag_mouseover() {
		$form = Input::form();
		$tag = O($form['tag_name'], Q::quote($form['tag_id']));
		$root = O($form['tag_name'], Q::quote($form['root_id']));
		$root = $root->id ? $root : O($form['tag_name'], $tag->root->id);
		$uniqid = $form['uniqid'];
		$real_root = $root->root->id ? $root->root : $root;
		Event::trigger('tag_selector_widget.on_tag_mouseover', $real_root, $tag, $form);
		$children = Q("{$real_root->name()}[root=$real_root][parent=$tag]:sort(weight)");
		$items = [];
		foreach ($children as $t) {
            if ($form['status'] != 'null' && $form['status'] != '' && !Q("({$t}) equipment:[status={$form['status']}]")->total_count()) {
                continue;
            }
			$items[$t->id] = [
				'html' => (string) V('application:widgets/tag_selector/item', ['tag'=>$t, 'i18n'=> $form['i18n']]),
				'ccount' => Q("{$real_root->name()}[root=$real_root][parent=$t]")->total_count(),
				'weight' => (int)$t->weight
			];
		}

		if (count($children) > 0) {
			//判断是对标签隐藏还是展示
			Output::$AJAX['items'] = $items;
		}
	}

	function on_tag_change() {
		$form = Input::form();
		if (!preg_match('/^tag_selector_[0-9A-Za-z]+$/', $form['uniqid'])) return;
		$tag = O($form['tag_name'], Q::quote($form['tag_id']));
		$root = O($form['tag_name'], Q::quote($form['root_id']));
		Output::$AJAX['.'.H($form['uniqid'])]
			= (string) V('application:widgets/tag_selector/container',[
					'tag' => $tag->id ? $tag : $root,
					'root' => $root,
					'root_name' => H($form['root_name']),
					'name' => H($form['name']),
					'uniqid' => H($form['uniqid']),
                    'i18n' => $form['i18n'],
				]);
	}

	function on_tag_click() {
		$this->on_tag_change();
	}


}
