<?php

class Platform_Model extends Presentable_Model {

    protected $object_page = [
        'view' =>'!servant/platform/index.%id[.%arguments]',
        'edit' => '!servant/platform/edit.%id[.%arguments]',
    ];

    function & links($mode = 'index') {
        if (!$this->id) return [];

        $links = new ArrayIterator;
        $me = L('ME');

        switch ($mode) {
            case 'view':
                if ($me->is_allowed_to('修改', $this)) {
                    $links['edit'] = [
                        'url' => $this->url(NULL, NULL, NULL, 'edit'),
                        'text' => I18N::T('servant', '修改'),
                        'extra' => 'class="button button_edit"',
                    ];
                }
                break;
            case 'index':
            default:
                if ($me->is_allowed_to('修改', $this)) {
                    $links['edit'] = [
                        'url' => $this->url(NULL, NULL, NULL, 'edit'),
                        'text' => I18N::T('servant', '修改'),
                        'extra' => 'class="blue"',
                    ];
                }
                if ($me->is_allowed_to('删除', $this)) {
                    $links['delete'] = [
                        'url' => '#',
                        'text' => I18N::T('servant', '删除'),
                        'extra'=>'class="blue" q-event="click" q-object="pf_delete" q-static="' . H(['id' => $this->id]) . '" q-src="' . $this->url(NULL, NULL, NULL, 'edit') . '"',
                    ];
                }
                break;
        }

        return (array)$links;
    }

    function href() {
        return 'http://' . $_SERVER['HTTP_HOST'] . '/' . $this->code. '/';
    }

    function save($overwrite = FALSE) {
        $this->source_site = SITE_ID;
        $this->source_lab = LAB_ID;
        return parent::save($overwrite);
    }
}
