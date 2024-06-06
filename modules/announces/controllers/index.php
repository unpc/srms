<?php 

class Index_Controller extends Base_Controller {

	function index(){

		$form = Input::form();

		$selector = 'announce';

		if($form['query']) {
			$title = Q::quote($form['query']);
			$selector .= "[title*={$title}]";
		}

		$me = L('ME');
		$now = time();
		$selector .= " user_announce[receiver={$me}]:sort(is_read A,ctime D)";
		$user_announces = Q($selector);


        $start = (int) $form['st'];
        $per_page = 15;
        $start = $start - ($start % $per_page);

        $pagination = Lab::pagination($user_announces, $start, $per_page);

		$tab = $this->layout->body->primary_tabs->get_tab('index');
        $tab['number'] = Q("user_announce[receiver={$me}][!is_read]")->total_count();
		$this->layout->body->primary_tabs->set_tab('index',$tab);

        $this->layout->body
            ->primary_tabs
            ->select('index')
            ->set('content', V('index', ['user_announces'=> $user_announces, 'form'=> $form, 'pagination'=> $pagination]));
	}

	function all(){

		$form = Input::Form();
		$me = L('ME');
		if (!$me->is_allowed_to('查看所有', 'announce')) URI::redirect('!announce');

		$selector = 'announce';

		if ($form['query']) {
			$title = Q::quote($form['query']);
			$selector .= "[title *= {$title}]";
		}

        $selector = (string) Event::trigger('announce.extra.selector', $form, $selector) ?: $selector;

        $selector .= ':sort(ctime D)';

		$announces = Q($selector);

        $start = (int) $form['st'];
        $per_page = 15;
        $start = $start - ($start % $per_page);

        $pagination = Lab::pagination($announces, $start, $per_page);

		$this->layout->body->primary_tabs->select('all')->set('content', V('all', [
            'announces'=> $announces, 
            'form'=> $form,
            'pagination'=> $pagination
        ]));
	}

	function add() {
		$form = Form::filter(Input::form());
		$me = L('ME');
		if(!$me->is_allowed_to('添加','announce')){
			return;
		}
		if($form['submit']){
			$form->validate('title', 'not_empty', I18N::T('announces', '公告题目不能为空!'));
			$form->validate('content', 'not_empty', I18N::T('announces', '公告内容不能为空!'));

            $receivers_type = $form['receivers_type'];

            switch ($receivers_type) {
                case 'user':
                    $receiver_users = (array)@json_decode($form['receiver_users'],TRUE);

                    if (!count($receiver_users)) {
                        $form->set_error('receiver_users', I18N::T('announces', '个别用户不能为空!'));
                    }
                    break;
                case 'role':
                    $roles = (array)@json_decode($form['receiver_role'],TRUE);

                    if (!count($roles)) {
                        $form->set_error('receiver_role', I18N::T('announces', '角色不能为空!'));
                    }

                    break;
                case 'group':
                    $receiver_groups = (array)@json_decode($form['receiver_group'], TRUE);

                    if (!count($receiver_groups)) {
                        $form->set_error('receiver_group', I18N::T('announces', '组织机构不能为空!'));
                    }

                    break;
            }

            if (Module::is_installed('nfs')) {
                if (Event::trigger('nfs.submit_require_file_has_uploaded', 'announce', 0) === false) {
                    $form->set_error('file', I18N::T('announces', '请等待附件上传完成!'));
                }
            }

            if ($form->no_error) {

                $need_approval = Event::trigger('announces.need.approval', $me);
                switch ($receivers_type) {
                    case 'all':

                        $announce = Announce::send($form, 'all');
                        if (!$need_approval)
                            Announce::extract_users($announce, 'all');

                        break;
                    case 'user':

                        $announce = Announce::send($form, 'user');
                        if (!$need_approval){
                            $receiver = json_decode($announce->receiver, true);
                            foreach ($receiver['scope'] as $id => $value) {
                                Announce::extract_users($announce, 'user', $id);
                            }
                        }

                        break;
                    case 'role':

                        $announce = Announce::send($form, 'role');
                        if (!$need_approval){
                            $receiver = json_decode($announce->receiver, true);

                            foreach ($receiver['scope'] as $id => $value) {
                                Announce::extract_users($announce, 'role', $id);
                            }
                        }

                        break;
                    case 'group':

                        $announce = Announce::send($form, 'group');
                        if (!$need_approval){
                            $receiver = json_decode($announce->receiver,true);
                            foreach ($receiver['scope'] as $id => $value) {
                                Announce::extract_users($announce,'group',$id);
                            }
                        }

                        break;
                }

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('announces', '公告发布成功!'));
                if ($me->is_allowed_to('查看所有', 'announce')) URI::redirect('!announces/all');
                else if (!$need_approval) URI::redirect('!announces/index');
                else URI::redirect('!announces/extra/approval');
            }
		}

		Event::trigger('announce.before.add');

		$this->layout->body
		->primary_tabs
			->select('add')
			->content = V('add',['form'=>$form,'announce'=>$announce]);
		
	}

	const BATCH_DELETE = 1;
	const BATCH_MARK_READ = 2;
	const BATCH_MARK_UNREAD = 3;

	function batch_action() {
		$me = L('ME');
		$form = Form::filter(Input::form());
		if (is_array($form['select'])) {
			if ($form['delete']) {
				$op = self::BATCH_DELETE;
			}
			elseif ($form['mark_read']) {
				$op = self::BATCH_MARK_READ;
			}
			elseif ($form['mark_unread']) {
				$op = self::BATCH_MARK_UNREAD;
			}
			foreach ($form['select'] as $id) {
				$user_announce = O('user_announce', $id);
				if ($user_announce->id) {
					
					switch($op) {
					case self::BATCH_DELETE:
						$user_announce->delete();
						break;
					case self::BATCH_MARK_READ:
						
						$user_announce->is_read = 1;
						$user_announce->save();
						break;
					case self::BATCH_MARK_UNREAD:
						
						$user_announce->is_read = 0;
						$user_announce->save();
						break;
					}					
				}
			}

			switch($op) {
			case self::BATCH_DELETE:
				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('announces', '您选中的公告已删除成功!'));
				break;
			case self::BATCH_MARK_READ:
				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('announces', '您选中的公告已标记为已读!'));
				break;
			case self::BATCH_MARK_UNREAD:
				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('announces', '您选中的公告已标记为未读!'));
				break;
			}					
			
		}
		
		URI::redirect('!announces/index');	
	}
}

class Index_AJAX_Controller extends AJAX_Controller {
	function index_delete_selected_click() {
		
		$me = L('ME');
		$selected_ids = Input::form('selected_ids');
		if (JS::confirm(I18N::T('announces','您确定删除选中的公告吗?,删除后不可恢复!'))) {
			if(!$me->is_allowed_to('删除', 'announce')){
				return;
			}

            $ids = join(',', $selected_ids);

            Q("announce[id={$ids}]")->delete_all();

            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('announces', '公告删除成功!'));
            JS::redirect('!announces/all');
		}
	}

	function index_delete_announce_click($a_id=0) {
		$form = Input::form();
		$id = $form['a_id'];
		$me = L('ME');
		if (JS::confirm(I18N::T('announces','您确定要删除吗? 删除后不可恢复!'))) {

			$announce = O('announce',$id);
			if(!$me->is_allowed_to('删除', $announce)) return;
			if(!$announce->id) return;
			if($announce->delete()){
				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('announces', '公告删除成功!'));
            	JS::redirect('!announces/all');
			}
		}
	}

	function index_announce_view() {
		$now = time();
		$me = L('ME');
		$must_read_announce = Q("announce[dtstart<={$now}][dtend>={$now}][must_read]<announce user_announce[receiver={$me}][is_read=0]:limit(1)")->current();

        //需要必读，但是管理员可忽略
        if ($must_read_announce->announce->id) {
            JS::dialog(V('announces:view', ['announce'=>$must_read_announce->announce, 'user_announce'=>$must_read_announce]), ['title' => '系统公告', 'no_close'=>TRUE]);
        }
	}

	function index_view_and_close_announce_submit() {
		$form = Input::form();
		
		if($form['has_read'] != 'on') return FALSE;

		$user_announce = O('user_announce', $form['ua_id']);
		if(!$user_announce->id) return FALSE;
		$user_announce->is_read = TRUE;
		$user_announce->save();

		$now = time();
		$me = L('ME');
		$must_read_announce = Q("announce[dtstart<={$now}][dtend>={$now}][must_read]<announce user_announce[receiver={$me}][is_read=0]:sort(ctime A):limit(1)")->current();

		if($must_read_announce->announce->id && !$me->is_allowed_to('管理', 'announce')){
			JS::dialog(V('announces:view', ['announce'=>$must_read_announce->announce, 'user_announce'=>$must_read_announce]), ['title' => '系统公告', 'no_close'=>TRUE]);
		}
		else{
            //跳转回之前的页面
            JS::redirect($_SESSION['HTTP_REFERER']);
		}
	}

	
}
