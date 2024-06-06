<?php

class Index_Controller extends Base_Controller {

	function index(){

		$form = Lab::form();
		
		$start = (int) $form['st'];
		$per_page = 15;
		$start = $start - ($start % $per_page);
		
		$query = $form['query'];
		
		if($query) {
			$query = Q::quote($query);
			
			$selector = "message[title*={$query}]";
		}
		else {
			$selector = 'message';
		}
		
        if(isset($form['is_read']) && $form['is_read']){
            $is_read = $form['is_read'] == 1 ? 0 : 1;
            $selector .= "[is_read={$is_read}]";
        }

		$me = L('ME');
		$selector .= "[receiver={$me}]:sort(is_read A, ctime D)";

		$messages = Q($selector);
		
		if($start > 0) {
			$last = floor($messages->total_count() / $per_page) * $per_page;
			if ($last == $messages->total_count()) $last = max(0, $last - $per_page);
			if ($start > $last) {
				$start = $last;
			}
			$messages = $messages->limit($start, $per_page);
		} else {
			$messages = $messages->limit($per_page);
		}
		
		$pagination = Widget::factory('pagination');
		$pagination->set([
			'start' => $start,
			'per_page' => $per_page,
			'total' => $messages->total_count(),
		]);
		
		$content = V('index',['messages'=>$messages,'pagination'=>$pagination, 'form'=>$form]);

		//if (Browser::name() != 'ie') {
			$tab = $this->layout->body->primary_tabs->get_tab('index');
			$me = L('ME');
			$tab['number'] = Q("message[receiver=$me][is_read=0]")->total_count();
			$this->layout->body->primary_tabs->set_tab('index', $tab);
		//}

		$this->layout->body->primary_tabs
			->select('index')
			->set('content', $content);
			
	}

	/* BUG #834::送样预约机主对送样者发送消息时，应预填消息标题
	   解决：给add方法添加表示仪器id的参数$eq，默认为NULL。该参数由!messages/index/send方法传递。
	   添加判断当$eq非空时，将$form['title']赋值为$eq所对应的name。。(kai.wu@2011.7.25)
	 */
	function add($to=0, $eq=NULL) {

        if (!Event::trigger('cannot.add.message')) {
            $form = Form::filter(Input::form());
            $to   = O('user', $to);
            $me   = L('ME');

            if (!$me->id || !$me->is_active() || !Config::get('messages.add_message.switch_on', true)) {
                URI::redirect('error/401');
            }

            if ($form['submit']) {
                try {

                    /* NO.BUG#239(xiaopei.li@2010.12.13) */
                    /* BUG #836::在添加消息页面先选择一个收件人名称后再删除并且发送的消息有标题和内容时就算收件人一项为空也可点击发送并提示消息发送成功 但收件人并不能收到消息。
                    原因： 只有一个收件人时，先添加再删除的操作将会把$form['receivers']的值改为string(2)"{}"而不是NULL，这样的话validate就能够正常通过而不会报错。
                    解决： 用preg_match将"{}"筛选掉，并将$form['receivers']置为NULL。(kai.wu@2011.7.25) */

                    $form
                        ->validate('title', 'not_empty', I18N::T('messages', '消息标题不能为空!'))
                        ->validate('body', 'not_empty', I18N::T('messages', '消息内容不能为空!'));

                    $form['body'] = Output::safe_html($form['body']);

                    $receivers_type = $form['receivers_type'];
                    if ($receivers_type == 'all') {
                        if ($form->no_error) {
                            Message::send($form, 'all');
                        }
                    } elseif ($receivers_type == 'user') {
                        $receiver_users = (array) @json_decode($form['receiver_users'], true);

                        if (!count($receiver_users)) {
                            $form->set_error('receiver_users', I18N::T('messages', '收件人不能为空!'));
                        }

                        if ($form->no_error) {
                            if (count($receiver_users) == 1) {
                                foreach ($receiver_users as $key => $value) {
                                    Message::send($form, 'user', $key);
                                }
                            } else {
                                $batch_id = Message::start_batch();
                                foreach ($receiver_users as $key => $value) {
                                    Message::send($form, 'user', $key, $batch_id);
                                }
                                Message::finish_batch($batch_id);
                            }
                        }
                    } elseif ($receivers_type == 'group') {
                        $receiver_groups = (array) @json_decode($form['receiver_group'], true);
                        if (!count($receiver_groups)) {
                            $form->set_error('receiver_group', I18N::T('messages', '收件组织机构不能为空!'));
                        }
                        if ($form->no_error) {
                            if (count($receiver_groups) == 1) {
                                foreach ($receiver_groups as $key => $value) {
                                    Message::send($form, 'group', $key);
                                }
                            } else {
                                $batch_id = Message::start_batch();
                                foreach ($receiver_groups as $key => $value) {
                                    Message::send($form, 'group', $key, $batch_id);
                                }
                                Message::finish_batch($batch_id);
                            }
                        }
                    } elseif ($receivers_type == 'role') {
                        $roles = (array) @json_decode($form['receiver_role'], true);
                        if (!count($roles)) {
                            $form->set_error('receiver_role', I18N::T('messages', '收件角色不能为空!'));
                        }

                        if ($form->no_error) {
                            if (count($roles) == 1) {
                                foreach ($roles as $key => $value) {
                                    Message::send($form, 'role', $key);
                                }
                            } else {
                                $batch_id = Message::start_batch();
                                foreach ($roles as $key => $value) {
                                    Message::send($form, 'role', $key, $batch_id);
                                }
                                Message::finish_batch($batch_id);
                            }
                        }
                    } elseif ($receivers_type == 'lab' && Module::is_installed('labs')) {
                        $labs = (array) @json_decode($form['receiver_labs'], true);

                        if (!count($labs)) {
                            $form->set_error('receiver_labs', I18N::T('messages', '收件实验室不能为空!'));
                        }
                        if ($form->no_error) {
                            if (count($labs) == 1) {
                                foreach ($labs as $key => $value) {
                                    Message::send($form, 'lab', $key);
                                }
                            } else {
                                $batch_id = Message::start_batch();
                                foreach ($labs as $key => $value) {
                                    Message::send($form, 'lab', $key, $batch_id);
                                }
                                Message::finish_batch($batch_id);
                            }
                        }
                    }

                    if (!$form->no_error) {
                        throw new Error_Exception();
                    }

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('messages', '消息发送成功!'));
                    URI::redirect('!messages');
                } catch (Error_Exception $e) {
                }
            }

            if (!$form['submit'] && $eq) {
                $title = Event::trigger('message.title.get', $eq);
                if ($title) {
                    $form['title'] = H($title);
                }

            }

            $tmp_receivers = isset($form['receivers']) ? json_decode($form['receivers'], true) : null;
            if (empty($tmp_receivers) && $to->id > 0) {
                $form['receivers'] = json_encode([
                    $to->id => $to->name,
                ]);
            }
            $this->layout->body->primary_tabs->select('add');
            $this->layout->body->primary_tabs->content = V('add', ['form' => $form, 'to' => $to]);
        } else {
            return false;
        }
    }
}

class Index_AJAX_Controller extends AJAX_Controller
{
    public function index_delete_selected_click()
    {
        $me           = L('ME');
        $selected_ids = Input::form('selected_ids');
        if (JS::confirm(I18N::T('messages', '您确定删除选中的消息吗?'))) {
            foreach ($selected_ids as $id) {
                $message = O('message', $id);
                if ($message->id && $message->receiver->id == $me->id) {
                    $message->delete();
                    Log::add(strtr('[messages] %user_name[%user_id] 删除了消息 %message_title[%message_id]', [
                        '%user_name'     => $me->name,
                        '%user_id'       => $me->id,
                        '%message_title' => $message->title,
                        '%message_id'    => $message->id,
                    ]), 'journal');
                }
            }
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('messages', '消息删除成功!'));
            JS::redirect('!messages');
        }
    }
}
