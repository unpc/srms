<?php

class Comments_Widget extends Widget {

	function __construct($vars) {
		parent::__construct('application:comments', $vars);
	}

	function on_form_submit() {
		$form = Input::form();
		if ($form['submit']) {
			$content = trim($form['content']);
			if (!$content) return;

			if (!self::get_users($content, 'check')) {
				if(!JS::confirm(T('您@了错误的用户，确认提交吗?'))) return;
			}
			
			$object = O($form['oname'], $form['oid']);
			
			$me = L('ME');
			if ($object->id && $me->is_allowed_to('发表评论', $object)) {
				
				$comment = O('comment');
				$comment->object = $object;
				$comment->content = $form['content'];
				$comment->author = $me;

                $comment->url_params = $form['url_params'];

				$comment->save();

                //该处没必要使用特定Config去设定字符长度
                if ($comment->id && strlen($form['url_params']) > 200) {
                    Log::add(strtr('[application] 评论[%comment_id] 设定url_params超出字符设定，应为%url_params', [
                        '%comment_id'=> $comment->id,
                        '%url_params'=> $form['url_params']
                    ]), 'comment');
                }

				$list_id = '#'.$form['list_id'];
				Output::$AJAX[$list_id] = [
					'data'=>(string)V('application:widgets/comments/list', [
							'list_id'=>$form['list_id'],
							'object'=>$object
						])
					];

                $span = '#'. $form['form_id']. ' span.max_length';
                Output::$AJAX[$span] = [
                    'data'=> (string) V('application:widgets/comments/max_length', [
                        'max_length'=> $form['max_length']
                    ]),
                ];

				JS::run(JS::smart()->jQuery('[q-widget=comments] [name=content]')->val(''));
			}
			else {
				JS::alert(HT('您无权发表评论!'));
			}
		}

	}

	function on_delete_click() {
		$form = Input::form();

		$comment = O('comment', $form['id']);
		if (!$comment->id) return;

		$me = L('ME');
		if ($me->is_allowed_to('删除', $comment)) {

			if (JS::confirm(HT('您确定要删除该条评论吗?'))) {
				$comment->delete();
				$comment_id = '#'.$form['comment_id'];
				$object = O($form['oname'], $form['oid']);
				if (!$object->id) {
					Output::$AJAX[$comment_id] = ['data'=>'', 'mode'=>'replace'];
				}
				else {
					$list_id = '#' . $form['list_id'];
					$view = (string)V('application:widgets/comments/list', ['object'=>$object, 'list_id'=>$form['list_id']]);
					Output::$AJAX[$list_id] = ['data'=>$view];
				}
			}
		}
		else {
			$message_id = '#'.$form['message_id'];
			JS::alert(HT('删除失败!'));
		}
	}	
	
	function on_more_click() {
		$form = Input::form();
		$object = O($form['oname'], $form['oid']);
		$start = (int)$form['start'];
		if ($object->id) {
			$more_id = '#'.$form['more_id'];

			$view = '';
			$comments = Q("comment[object=$object]:sort(ctime D)")->limit($start, 5);
			$list_id = $form['list_id'];
			foreach ($comments as $comment) {
				$view .= (string) V('application:widgets/comments/item', [
					'comment'=>$comment,
					'object'=>$object,
					'list_id'=>$list_id
				]);
			}

			if ($comments->total_count() > $start + 5) {
				$view .= (string) V('application:widgets/comments/more', [
					'object'=>$object,
					'start'=>$start + 5,
					'list_id'=>$list_id
				]);
			}

			Output::$AJAX[$more_id] = ['data'=>$view, 'mode'=>'replace'];
		}
	}



	//匹配＠用户 @{xxx|11}
	static $at_reg = '/\@\{(.+)\}/U';

	//匹配用户名和id
	static $user_reg = '/(.+)(\|(\d+))?$/U';

	static function change_name($content, $show_html = FALSE) {
		preg_match_all(self::$at_reg, $content, $match);

		foreach ($match[0] as $key => $match_user) {

			 $user = self::get_users($match_user, 'change');

			 if ($user){
                if ($show_html) {
                    $user_link = URI::anchor($user->url(),'@'.H($user->name),'class="blue"');
                }
                else {
                    $user_link = '@'. H($user->name);
                }
			 	$content = str_replace($match_user, $user_link, $content);
			 }
		}

		return $content;
	}

	static function get_users($content, $type = null){
	
		//如果没有匹配到@则直接返回true
		if(strpos($content, '@') === false && $type == 'check'){
			return TRUE;
		}

		preg_match_all(self::$at_reg, $content, $match);

		//如果有@但是没有匹配到名字，可能只有@或者是格式不对（类似@xxx），直接返回true
		if(!$match[0] && $type == 'check'){
			return TRUE;
		}

		foreach ($match[1] as $key => $match_users) {
		 	preg_match_all(self::$user_reg, $match_users, $match_user);

		 	$user_name = $match_user[1][0];
			
		 	if ($user_name) {
		 		$selector = "user[name=\"$user_name\"]";

		 		$user_id = $match_user[3][0];
		 		if ($user_id) {
		 			$selector .= "[id={$user_id}]";
		 		}
		 		$result = Q($selector);

		 		//避免重名的情况
				if($result->length() == 1) {
					$user = $result->current();

					if($type == 'change')return $user;

		 			$user_ids[] = $user->id;
		 		}

		 		//如果有不正确的用户则返回false
		 		if($type == 'check' && !$result->length()){
		 			return false;
		 		}
		 	}
		}

		if (count($user_ids)){
			$uids = implode(',', (array) $user_ids);
			$users = Q("user[id={$uids}]");
			return $users;
		}

	}

	static function decode_at_user($source, $show_html, $markup) {

		return preg_replace_callback('/\@\{(.+?)(?:\|(\d+))?\}/', function($match) use ($markup, $show_html) {
			if ($match[2]) {
				$user = O('user', $match[2]);
			}
			
			if (!$user || !$user->id) {
				$user = O('user', ['name'=>$match[1]]);
			}

			if ($show_html) {
				$string = URI::anchor($user->url(), '@'.H($user->name), 'class="blue"');
			}
			else {
				$string = '@'.$user->name;
			}

			return $markup->map_string($string);

		}, $source);

	}

}
