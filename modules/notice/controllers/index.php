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

        if ($form['name']) {
            $name = H($form['name']);
            $selector .= "[name*={$name}]";
        }

        $materials = Q($selector);

		$pagination = Lab::pagination($materials, (int)$form['st'], 15, NULL, '', URI::url('!notice/play'));

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
        
        if ($form['name']) {
            $name = H($form['name']);
            $selector .= "[name*={$name}]";
        }

        $broadcasts = Q($selector);

		$pagination = Lab::pagination($broadcasts, (int)$form['st'], 15, NULL,'', URI::url('!notice/play.list'));
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

    function index_add_meterial_submit() 
    {
        $form = Input::form();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $material = O('material');
            $material->name = H($form['name']);
            $material->type = (int)$form['type'];
            $material->description = $form['description'];
            $material->ctime = Date::time();
            $material->user = L('ME');
            if ($material->save()) {
                switch ($material->type) {
                    case 1:
                        $file = Input::file('image');
                        if ($file['tmp_name']) {
                            $name = $file['name'];
                            File::check_path($material->source_path('foobar'));
                            move_uploaded_file($file['tmp_name'], $material->source_path($name));
                            $material->size = $file['size'];
                            $material->save();
                        }
                    case 2:
                        $file = Input::file('video');
                        if ($file['tmp_name']) {
                            $name = $file['name'];
                            File::check_path($material->source_path('foobar'));
                            move_uploaded_file($file['tmp_name'], $material->source_path($name));
                            $material->size = $file['size'];
                            $material->save();
                        }
                        break;
                    default:
                        break;
                }
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('notice', '素材添加成功!'));
                JS::redirect();
            }
            else {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('notice', '素材添加失败!'));
            }
        }
        JS::dialog((string)V('material/add',[
        ]), [
            'title' => I18N::T('notice', '添加公共素材')
        ]);
	}

    function index_add_broadcast_click() 
    {
		JS::dialog((string)V('broadcast/add',[
		]), [
            'title' => I18N::T('notice', '添加播单')
        ]);
	}

    function index_add_broadcast_submit() 
    {
        $form = Input::form();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $broadcast = O('broadcast');
            $broadcast->name = H($form['name']);
            $broadcast->type = (int)$form['type'];
            $broadcast->description = $form['description'];
            $broadcast->ctime = Date::time();
            $broadcast->user = L('ME');
            $broadcast->connect = (int)$form['connect'];
            if ($broadcast->save()) {
                $materials = (array)json_decode($form["materials_{$broadcast->type}"], TRUE);
                foreach ($materials as $id => $name) {
                    $material = O('material', $id);
                    $broadcast->connect($material);
                }

                $meetings = (array)json_decode($form['meetings'], TRUE);
                foreach ($meetings as $id => $name) {
                    $meeting = O('meeting', $id);
                    $broadcast->connect($meeting);
                }

                $tag_room = O('tag_room', (int)$form['meeting_group']);
                if ($tag_room->id) {
                    foreach (Q("{$tag_room} meeting") as $meeting) {
                        $broadcast->connect($meeting);
                    }
                }

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('notice', '播单添加成功!'));
                JS::redirect('!notice/play.list');
            }
            else {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('notice', '播单添加失败!'));
            }
        }
        JS::dialog((string)V('broadcast/add',[
        ]), [
            'title' => I18N::T('notice', '添加播单')
        ]);
	}
}