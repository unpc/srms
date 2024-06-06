<?php

class Message_Model extends Presentable_Model
{

    const TYPE_PERSONAL = 1;
    const TYPE_SYSTEM   = 2;

    protected $object_page = [
        'view'   => '!messages/message/index.%id[.%arguments]',
        'delete' => '!messages/message/delete.%id[.%arguments]',
        'reply'  => '!messages/message/reply.%id[.%arguments]',
        'add'    => '!messages/index/add[.%arguments]',
    ];

    public function &links($mode = 'index')
    {
        $links = new ArrayIterator;
        switch ($mode) {
            case 'view':
                if ($this->sender->id) {
                    $links['reply'] = [
                        'url'   => $this->url('', '', '', 'reply'),
                        'text' => I18N::T('messages', '回复'),
                        'extra' => 'class="button button_add"',
                    ];
                }

                $links['delete'] = [
                    'url'   => $this->url('', '', '', 'delete'),
                    'text' => I18N::T('messages', '删除'),
                    'tip'   => I18N::T('messages', '删除'),
                    'extra' => 'class="button_delete" style="color: #F5222D;" confirm="' . I18N::T('messages', '你确定要删除吗？删除后不可恢复!') . '"',
                ];
                break;
            case 'index':
            default:
                if ($this->sender->id) {
                    $links['reply'] = [
                        'url'   => $this->url('', '', '', 'reply'),
                        'text' => I18N::T('messages', '回复'),
                        'extra' => 'class="blue"',
                    ];
                }

                $links['delete'] = [
                    'url'   => $this->url('', '', '', 'delete'),
                    'text' => I18N::T('messages', '删除'),
                    'tip'  => I18N::T('messages', '删除'),
                    'extra' => 'class="blue" confirm="' . I18N::T('messages', '你确定要删除吗？删除后不可恢复!') . '"',
                ];
        }
        return (array) $links;
    }

}
