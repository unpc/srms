<?php

class Service_Model extends Presentable_Model
{

    protected $object_page = [
        'view' => '!technical_service/service/index.%id[.%arguments]',
        'edit' => '!technical_service/service/edit.%id[.%arguments]',
        'delete' => '!technical_service/service/delete.%id[.%arguments]',
    ];

    function & links($mode = 'index')
    {
        if (!$this->id) return [];

        $links = new ArrayIterator;
        $me = L('ME');

        switch ($mode) {
            case 'index':
                if ($me->is_allowed_to('修改', $this)) {
                    $links['edit'] = [
                        'url' => $this->url(NULL, NULL, NULL, 'edit'),
                        'text' => I18N::T('technical_service', '修改'),
                        'tip' => I18N::T('technical_service', '修改'),
                        'extra' => 'class="blue"',
                    ];
                }
                break;
            case 'view':
                if ($me->is_allowed_to('修改', $this)) {
                    $links['edit'] = [
                        'url' => $this->url(NULL, NULL, NULL, 'edit'),
                        'text' => I18N::T('technical_service', '修改'),
                        'tip' => I18N::T('technical_service', '修改'),
                        'extra' =>'class="button button_edit fa-lg"',
                    ];
                }
                if ($me->is_allowed_to('预约服务', $this)) {
                    $links['apply'] = [
                        'url' => $this->url(NULL, NULL, NULL, 'apply'),
                        'text' => I18N::T('technical_service', '在线预约'),
                        'tip' => I18N::T('technical_service', '在线预约'),
                        'extra' => 'class="button button_add fa-lg" q-object="add" q-event="click"  q-static="' . H(['service_id' => $this->id,'form_token' => uniqid()]) . '" q-src="' . URI::url('!technical_service/apply') . '"',
                    ];
                }
                break;
        }

        Event::trigger('service.links', $this, $links, $mode);

        return (array)$links;
    }

    function save($overwrite = FALSE)
    {
        if (!$this->name) return false;
        $this->ctime = $this->ctime ?: time();
        $result = parent::save($overwrite);
        return $result;
    }


}
