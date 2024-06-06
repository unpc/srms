<?php

class GIS_Building_Model extends Presentable_Model
{

    protected $object_page = [
        'view'   => '!gismon/building/index.%id[.%arguments]',
        'map'    => '!gismon/map/index.%id[.%arguments]',
        'edit'   => '!gismon/building/edit.%id[.%arguments]',
        'delete' => '!gismon/building/delete.%id[.%arguments]',
    ];

    public function &links($mode = 'index')
    {
        $links = new ArrayIterator;
        $me    = L('ME');
        switch ($mode) {
            case 'view':
                if (L('ME')->is_allowed_to('修改', $this)) {
                    $links['edit'] = [
                        'url'   => '!gismon/building/edit.' . $this->id,
                        'text'  => '修改',
                        'tip'  => I18N::T('gismon', '修改'),
                        'extra' => 'class="button button_edit"',
                    ];

                }
                break;
            case 'index':
            default:
                if (L('ME')->is_allowed_to('修改', $this)) {
                    $links['edit'] = [
                        'url'   => '!gismon/building/edit.' . $this->id,
                        'text' => I18N::T('gismon', '修改'),
                        'tip'  => I18N::T('gismon', '修改'),
                        'extra' => 'class="blue"',
                    ];

                }
        }
        Event::trigger('gismon.buildings.extra.links', $this, $links, $mode);
        return (array) $links;
    }

}
