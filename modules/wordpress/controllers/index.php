<?php

class Index_Controller extends Base_Controller {

	function index($tab='bind') {
		$content = V("wordpress:index");
		$secondary_tabs = Widget::factory('tabs');

		Event::bind('public.wordpress.content', [$this, '_index_bind'], 0, 'bind');
		// Event::bind('public.wordpress.content', array($this, '_index_pages'), 0, 'pages');
		Event::bind('public.wordpress.content', [$this, '_index_push'], 0, 'push');
		Event::bind('public.wordpress.content', [$this, '_index_admin'], 0, 'admin');

		$secondary_tabs
			->add_tab('bind', [
						  'url' => URI::url('!wordpress/index.bind'),
						  'title' => I18N::T('wordpress', '绑定')
						  ])
			/*
			->add_tab('pages', array(
						  'url' => URI::url('!wordpress/index.pages'),
						  'title' => I18N::T('wordpress', '列表')
						  ))
			*/
			->add_tab('push', [
						  'url' => URI::url('!wordpress/index.push'),
						  'title' => I18N::T('wordpress', '推送')
						  ])
			->add_tab('admin', [
						  'url' => URI::url('!wordpress/index.admin'),
						  'title' => I18N::T('wordpress', '进入 WordPress 后台编辑')
						  ])
			->tab_event('public.wordpress.tab')
			->content_event('public.wordpress.content')
			->set('class', 'secondary_tabs')
			->select($tab);

		$content->secondary_tabs = $secondary_tabs;

		$this->layout->body->primary_tabs
			->select('public')
			->set('content', $content);
	}

	function _index_admin($e, $tabs) {
		$admin_url = Config::get('wordpress.wp_base') . '/wp-admin/';

		URI::redirect($admin_url);
	}

	function _index_bind($e, $tabs) {

		$me = L('ME');
		$is_bind = $me->wordpress_username && $me->wordpress_password;

		$form = Form::filter(Input::form());

		if ($form['bind']) {
			$form->validate('username', 'not_empty', I18N::T('wordpress', '管理员帐号不能为空!'))
				->validate('password', 'not_empty', I18N::T('wordpress', '管理员密码不能为空!'));

			$username = $form['username'];
			$password = $form['password'];

			if ($form->no_error) {

				$client = new Wordpress(NULL, $username, $password);

				$blogs = $client->get_users_blogs();

				if ($blogs) {
					$me->blog_id = $blogs[0]['blogid']; // TODO blog_id 为实验室级别的更妥当
					$me->wordpress_username = $username;
					$me->wordpress_password = $password;

					if ($me->save()) {
						$is_bind = TRUE;

                        Log::add(strtr('[wordpress] %user_name[%user_id]绑定wordpress', [
                            '%user_name'=> $me->name,
                            '%user_id'=> $me->id
                        ]), 'journal');

						Lab::message(Lab::MESSAGE_NORMAL,I18N::T('wordpress','绑定成功!'));
					}
				}
				else {
					Lab::message(Lab::MESSAGE_ERROR,I18N::T('wordpress','绑定失败!'));
				}
			}
		}
		if ($form['unbind']) {
			$me->blog_id = 0;
			$me->wordpress_username = NULL;
			$me->wordpress_password = NULL;
			if ($me->save()) {
				$is_bind = FALSE;
                Log::add(strtr('[wordpress] %user_name[%user_id]解除绑定wordpress', [
                    '%user_name'=> $me->name,
                    '%user_id'=> $me->id
                ]), 'journal');

				Lab::message(Lab::MESSAGE_NORMAL,I18N::T('wordpress','成功解除绑定!'));
			}
		}

		$tabs->content = V('wordpress:bind', ['form'=>$form, 'is_bind'=>$is_bind]);
	}

	function _index_pages($e, $tabs) {
		$me = L('ME');
		$client = new Wordpress($me->blog_id, $me->wordpress_username, $me->wordpress_password);

		// $client->hello();

		$categories = $client->get_categories();
		// TODO 应该是 catagory + post (xiaopei.li@2011.10.26)
		// $siteurl = $client->call_wp_method('get_option', array('siteurl'));
		$siteurl = Config::get('wordpress.wp_base');
		$tabs->content = V('wordpress:pages', ['categories' => $categories, 'siteurl' => $siteurl]);
	}


	function _index_push($e, $tabs) {

		$me = L('ME');
		$form = Input::form();

        if ($form['submit']) {
            if ($me->blog_id && $me->wordpress_username && $me->wordpress_password) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('wordpress', '推送进行中, 请稍后刷新 wordpress 页面查看推送结果!'));

                //SITE_ID=xx LAB_ID=xxx php cli.php wordpress push blogid username password  json
                //json 和当前提交 form 一致
                putenv('Q_ROOT_PATH='. ROOT_PATH);
                putenv('SITE_ID='. SITE_ID);
                putenv('LAB_ID='. LAB_ID);

                $cmd = 'php '. ROOT_PATH.'cli/cli.php wordpress push '. $this->_push_opts($me, $form).' &';

                $ph = popen($cmd, 'r');

                if ($ph) pclose($ph);
            }
            else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('wordpress', '异常请求!'));
            }
        }
        $tabs->content = V('wordpress:sync');
    }

    //返回 push 使用的参数
    private function _push_opts($me, $form) {
        $blogid = $me->blog_id;
        $username = $me->wordpress_username;
        $password = $me->wordpress_password;

        $new_form = [];
        $new_form['sync'] = $form['sync'];
        $new_form['sp_type'] = $form['sp_type'];

        $opts = [
            $blogid,
            $username,
            $password,
            json_encode($new_form),
        ];

        return join(' ', array_map(function($v) {
            return '\''. $v. '\'';
        }, $opts));
    }
}
