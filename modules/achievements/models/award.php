<?php

class Award_Model extends Presentable_Model
{

    protected $object_page = [
        'view' => '!achievements/awards/award/index.%id',
        'edit' => '!achievements/awards/award/edit.%id[.%arguments]',
        'delete' => '!achievements/awards/award/delete.%id',
        'add' => '!achievements/awards/award/add[.%arguments]',
    ];

    public function &links($mode = 'index')
    {
        $links = new ArrayIterator;
        $me = L('ME');
        switch ($mode) {
            case 'view':
                if ($me->is_allowed_to('修改', $this)) {
                    $links['edit'] = [
                        'url' => $this->url(null, null, null, 'edit'),
                        'text' => '修改',
                        'tip' => I18N::T('achievements', '编辑'),
                        'extra' => 'class="button button_edit"',
                    ];
                }
                if ($me->is_allowed_to('删除', $this)) {
                    $links['delete'] = [
                        'url' => $this->url(null, null, null, 'delete'),
                        'text' => '删除',
                        'tip' => I18N::T('achievements', '删除'),
                        'extra' => 'class="button icon-trash" confirm="' . I18N::T('achievements', '你确定要删除吗? 删除后不可恢复!') . '" style="border: 1px solid #F5222D;color: #F5222D;" ',
                    ];
                }
                break;
            case 'index':
            default:
                if ($me->is_allowed_to('修改', $this)) {
                    $links['edit'] = [
                        'url' => $this->url(null, null, null, 'edit'),
                        'text' => I18N::T('achievements', '修改'),
                        'tip' => I18N::T('achievements', '编辑'),
                        'extra' => 'class="blue"',
                    ];
                }
                if ($me->is_allowed_to('查看', $this)) {
                    $links['view'] = [
                        'url' => $this->url(null, null, null),
                        'tip' => I18N::T('achievements', '查看'),
                        'text' => I18N::T('achievements', '查看'),
                        'extra' => 'class="blue"',
                    ];
                }
                if ($me->is_allowed_to('删除', $this)) {
                    $links['delete'] = [
                        'url' => $this->url(null, null, null, 'delete'),
                        'tip' => I18N::T('achievements', '删除'),
                        'text' => I18N::T('achievements', '删除'),
                        'extra' => 'class="red" confirm="' . I18N::T('achievements', '你确定要删除吗? 删除后不可恢复!') . '"',
                    ];
                }
        }
        return (array)$links;
    }

    public function save($overwrite = false)
    {
        if (!$this->lab->id) {
            $this->lab = Lab_Model::default_lab();
        }

        // 由于 timestamp 不记录时区, 所以为防止不同时区年月信息不准:
        // 1. 如果 date 在当月 1 日, 就放到当月 2 日;
        // 2. 如果 date 在当月最后一日, 就放到当月倒数第二日;
        if (date('j', $this->date) == 1) {
            $this->date += 86400;
        } else if (date('j', $this->date + 86400) == 1) {
            $this->date -= 86400;
        }

        return parent::save($overwrite);
    }
}
