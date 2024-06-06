<?php

class Message_Controller extends Base_Controller
{

    public function index($id = 0)
    {

        $message = O('message', $id);
        if (!$message->id) {
            URI::redirect('error/404');
        }

        if ($message->receiver->id != L('ME')->id) {
            URI::redirect('error/401');
        }

        if (!$message->is_read) {
            $message->is_read = true;
            $message->save();
        }

        $content =
        V('message/view')
            ->set('message', $message);

        $this->layout->body->primary_tabs
            ->add_tab('view', [
                'url'   => $message->url(),
                'title' => I18N::T('messages', '查看消息'),
            ])
            ->set('content', $content)
            ->select('view');

    }

    public function reply($id = 0)
    {
        $message = O('message', $id);
        if (!$message->id || $message->receiver->id != L('ME')->id) {
            URI::redirect('error/404');
        }

        $form = Form::filter(Input::form());

        if ($form['submit']) {
            try {
                $form
                    ->validate('title', 'not_empty', I18N::T('messages', '消息标题不能为空!'))
                    ->validate('body', 'not_empty', I18N::T('messages', '消息内容不能为空!'));
                if ($form->no_error) {
                    $receiver = O('user', $form['receiver']);

                    if (!$receiver->id) {
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('messages', '消息收件人不能为空!'));
                    }

                    $sender            = L('ME');
                    $message           = O('message');
                    $message->title    = $form['title'];
                    $message->body     = $form['body'];
                    $message->receiver = $receiver;
                    $message->sender   = $sender;
                    $message->save();

                    Log::add(strtr('[messages] %user_name[%user_id] 回复了 %receiver_name 的消息', [
                        '%user_name'     => L('ME')->name,
                        '%user_id'       => L('ME')->id,
                        '%receiver_name' => $message->receiver->name,
                    ]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('messages', '消息发送成功!'));
                    URI::redirect('!messages');
                }
            } catch (Error_Exception $e) {
            }
        }

        $content = V('message/reply', ['message' => $message, 'form' => $form]);

        $this->layout->body->primary_tabs
            ->add_tab('reply', [
                'url'   => $message->url('', '', '', 'reply'),
                'title' => I18N::T('messages', '回复消息'),
            ])
            ->set('content', $content)
            ->select('reply');
    }

    public function delete($id = 0)
    {
        $message = O('message', $id);
        if (!$message->id) {
            URI::redirect('error/404');
        }

        $user = L('ME');
        if ($message->receiver->id != $user->id) {
            URI::redirect('error/401');
        }

        if ($message->delete()) {

            /*添加记录*/
            Log::add(strtr('[messages] %user_name[%user_id] 删除了消息 %message_title[%message_id]', [
                '%user_name'     => $user->name,
                '%user_id'       => $user->id,
                '%message_title' => $message->title,
                '%message_id'    => $message->id,
            ]), 'journal');

            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('messages', '消息删除成功!'));
        } else {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('messages', '消息删除失败!'));
        }
        URI::redirect('!messages/index');
    }

    public function delete_read()
    {
        $me   = L('ME');
        $msgs = Q("message[is_read][receiver={$me}]");

        if ($msgs->total_count()) {
            $msgs->delete_all();
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('messages', '已读消息删除成功!'));
        } else {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('messages', '当前没有已读消息!'));
        }

        URI::redirect('!messages/index');
    }
	
    const BATCH_DELETE      = 1;
    const BATCH_MARK_READ   = 2;
    const BATCH_MARK_UNREAD = 3;

    public function batch_action()
    {
        $me   = L('ME');
        $form = Form::filter(Input::form());
        if (is_array($form['select'])) {
            if ($form['delete']) {
                $op = self::BATCH_DELETE;
            } elseif ($form['mark_read']) {
                $op = self::BATCH_MARK_READ;
            } elseif ($form['mark_unread']) {
                $op = self::BATCH_MARK_UNREAD;
            }
            foreach ($form['select'] as $id) {
                $message = O('message', $id);
                if ($message->id && $message->receiver->id == $me->id) {
                    switch ($op) {
                        case self::BATCH_DELETE:
                            $message->delete();

                            /*添加记录*/
                            Log::add(strtr('[messages] %user_name[%user_id] 删除了消息 %message_title[%message_id]', [
                                '%user_name'     => $me->name,
                                '%user_id'       => $me->id,
                                '%message_title' => $message->title,
                                '%message_id'    => $message->id,
                            ]), 'journal');
                            break;
                        case self::BATCH_MARK_READ:
                            $message->is_read = (int) true;
                            $message->save();

                            /*添加记录*/
                            Log::add(strtr('[messages] %user_name[%user_id] 对消息 %message_title[%message_id]标记已读', [
                                '%user_name'     => $me->name,
                                '%user_id'       => $me->id,
                                '%message_title' => $message->title,
                                '%message_id'    => $message->id,
                            ]), 'journal');

                            break;
                        case self::BATCH_MARK_UNREAD:
                            $message->is_read = (int) false;
                            $message->save();
                            /*添加记录*/
                            Log::add(strtr('[messages] %user_name[%user_id] 对消息 %message_title[%message_id]标记未读', [
                                '%user_name'     => $me->name,
                                '%user_id'       => $me->id,
                                '%message_title' => $message->title,
                                '%message_id'    => $message->id,
                            ]), 'journal');
                            break;
                    }
                }
            }

            switch ($op) {
                case self::BATCH_DELETE:
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('messages', '您选中的消息已删除成功!'));
                    break;
                case self::BATCH_MARK_READ:
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('messages', '您选中的消息已标记为已读!'));
                    break;
                case self::BATCH_MARK_UNREAD:
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('messages', '您选中的消息已标记为未读!'));
                    break;
            }

        }

        URI::redirect('!messages/index');
    }

}
