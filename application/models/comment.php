<?php

class Comment_Model extends ORM_Model {

	static function comment_ACL($e, $user, $perm, $comment, $options) {
		if (!$comment->id) return;

		if ($user->access('管理所有内容')) {
			$e->return_value = TRUE;
			return FALSE;
		}

		if ($user->id == $comment->author->id) {
			$e->return_value = TRUE;
			return FALSE;
		}
	}

    public function object_url() {

        $params = (array) json_decode($this->url_params, TRUE);
        //如果params['o']为null，则使用默认的op
        if ($params['o'] === NULL) {
            unset($params['o']);
        }

        $link = call_user_func_array([$this->object, 'url'], $params);
        return strtr('[%name](%link)', [
            '%name'=>$this->object->name,
            '%link'=> $link
        ]);
    }

    public function save($overwrite=FALSE) {
        $ret = parent::save();

        if ($ret) {
            //对于不存在url_params，重新设定url_params
            if (!$this->url_params) {
                $this->url_params = json_encode(['a'=>NULL, 'q'=>NULL, 'f'=> $this->id]);
                parent::save();
            }

            $users = Comments_Widget::get_users($this->content);
            if (is_object($users) && $users->length()) {
                foreach($users as $user) {
                    $user->connect($this);
                    Notification::send('at_user', $user, [
                        '%user' => L('ME')->name,
                        '%at_user'=>$user->name,
                        '%link'=> $this->object_url(),
                        '%content'=> $this->content,
                        '%object'=> $this->object->name,
                    ]);
                }
            }
        }
        return $ret;
    }
}
