<?php

class Index_Controller extends Base_Controller 
{
    function index(){
		URI::redirect('!notice/play');
	}

	function play($tab='material')
	{
		Event::bind('notice.index.content', [$this, '_index_material_content'], 0, 'material');
		Event::bind('notice.index.content', [$this, '_index_list_content'], 0, 'list');

		$this->layout->body->primary_tabs
            ->tab_event('notice.index.tab')
			->content_event('notice.index.content')
			->select($tab);
	}

    function _index_material_content($e, $tabs) {
		$me = L('ME');

		$form = Lab::form();

		$selector  = 'material';

        $materials = Q($selector);

		$pagination = Lab::pagination($materials, (int)$form['st'], 15, NULL,'material', URI::url('!notice/play'));
		$tabs->content = V('notice:material/list', [
            'form' => $form,
            'materials' => $materials,
            'pagination' => $pagination,
            'panel_buttons' => []
        ]);
	}

    function _index_list_content($e, $tabs) {
		$me = L('ME');

		$form = Lab::form();

		$selector  = 'broadcast';

        $broadcasts = Q($selector);

		$pagination = Lab::pagination($broadcasts, (int)$form['st'], 15, NULL,'broadcast', URI::url('!notice/play.list'));
		$tabs->content = V('notice:broadcast/list', [
            'form' => $form,
            'broadcasts' => $broadcasts,
            'pagination' => $pagination,
            'panel_buttons' => []
        ]);
	}
}

class Index_AJAX_Controller extends AJAX_Controller 
{
	function index_add_meterial_click() 
    {
		JS::dialog((string)V('material/add',[
		]), [
            'title' => I18N::T('notice', '添加公共素材')
        ]);
	}
}