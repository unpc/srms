<?php

class Index_Controller extends Base_Controller {

	public static function get_envmon_columns($form)
	{
		return
		[
			// '@' => null,
			 'name' => [
				 'title' => I18N::T('envmon', '名称'),
				 'filter' => [
					 'form' => V('envmon:node/filters/name', ['form' => $form]),
					 'value' => $form['name'] ? H($form['name']) : null,
				 ],
				 'nowrap' => true,
				 'align' => 'top',
				 'extra_class' => 'flexible',
			 ],
			 'address' => [
				 'title' => I18N::T('envmon', '地址'),
				 'filter' => [
					 'form' => V('envmon:node/filters/address', ['form' => $form]),
					 'value' => $form['address'] ? H($form['address']) : null,
				 ],
				 'invisible' => true,
				 'align' => 'left top',
			 ],
			 'incharge' => [
				 'title' => I18N::T('envmon', '负责人'),
				 'filter' => [
					 'form' => V('envmon:node/filters/incharge', ['form' => $form]),
					 'value' => $form['incharge'] ? H($form['incharge']) : null,
				 ],
				 'nowrap' => true,
				 'align' => 'left top',
			 ],
			 'rest' => [
				 'align' => 'right top',
				 'nowrap' => true,
			 ],
			];
	}
	function index($tab = 'normal') {
		$primary_tabs = $this->layout->body->primary_tabs;
		$primary_tabs->select('list');
		 
        $secondary_tabs = Widget::factory('tabs');

        $secondary_tabs
            ->add_tab('normal', [
                'url' => URI::url('!envmon/index.normal'),
                'title' => I18N::T('envmon', '正常监控'),
            ])
            ->set('class', 'secondary_tabs');

        $secondary_tabs
            ->add_tab('abnormal', [
                'url' => URI::url('!envmon/index.abnormal'),
                'title' => I18N::T('envmon', '报警中'),
            ])
            ->set('class', 'secondary_tabs');
        
		$secondary_tabs->select($tab);

		$selector = "env_node";

        $normal = Env_Node_Model::ALARM_NORMAL;

		if ($tab == 'normal') {
			$selector .= "[alarm=$normal]";
		} else {
			$selector .= "[alarm!=$normal]";
		}

        $form = Lab::form();

        $selector = Event::trigger('envmon.envmon.extra_selector', $selector, $form) ?: $selector;

        if ($form['name']) {
			$name = Q::quote($form['name']);
			$selector .= "[name*=$name]";
		}
		
		if ($form['address']) {
			$address = Q::quote(trim($form['address']));
			$selector .= "[location*=$address|location2*=$address]";
		}
		
		if ($form['incharge']) {
			$incharge = Q::quote(trim($form['incharge']));
			$selector = "user[name*=$incharge|name_abbr*=$incharge]<incharge ". $selector;
		}

        $user = L('ME');

        //不具有该权限, 只显示自己负责的
		if (!$user->access('查看环境监控模块')) $selector = "{$user}<incharge ". $selector;

        $selector .= ':sort(name ASC)';

		$nodes = Q($selector);

        $start = (int) $form['st'];
        $per_page = 10;
		$pagination = Lab::pagination($nodes, $start, $per_page);
		$columns=static::get_envmon_columns($form);
		if (L('ME')->is_allowed_to('添加', 'env_node')){
			$panel_buttons[] = [
                'text'   => I18N::T('setting', '添加监控对象'),
				'tip'   => I18N::T('setting', '添加管理组'),
				'extra' => 'q-object="add_node" q-event="click" q-src="' .H(URI::url('!envmon/node')) . '" class="button button_add "',
			];
		}
		$search_box=V('application:search_box', ['panel_buttons' => $panel_buttons , 'top_input_arr' => ['name','address', 'incharge'], 'columns' => $columns]);
		$primary_tabs->content = V('envmon:list', [
			'form' => $form,
			'columns'=>$columns,
			'nodes' => $nodes,
			'pagination' => $pagination,
			'search_box'=>$search_box,
			'secondary_tabs' => $secondary_tabs,
		]);
	}
}

class Index_AJAX_Controller extends AJAX_Controller {

	function index_common_unit_click() {
		Output::$AJAX['common_unit'] = (string) V('envmon:sensor/common_unit');
	}
}
