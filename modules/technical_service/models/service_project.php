<?php

class Service_Project_Model extends Presentable_Model
{

    protected $object_page = [
        'view' => '!technical_service/project/index.%id[.%arguments]',
        'edit' => '!technical_service/project/edit.%id[.%arguments]',
        'delete' => '!technical_service/project/delete.%id[.%arguments]',
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
                        'extra' => 'class="blue"  q-object="edit_project" q-event="click"  q-static="' . H(['project_id' => $this->id]) . '" q-src="' . URI::url('!technical_service/project') . '"',
                    ];
                }
                break;
        }

        Event::trigger('service_project.links', $this, $links, $mode);

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
