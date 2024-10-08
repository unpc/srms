<?php

class Meeting_Model extends Presentable_Model
{

    //使用中
    const STATUS_USING = 1;

    //空闲
    const STATUS_AVAILABLE = 2;


    const TYPE_SPACE = 0;
    const TYPE_TECH = 1;
    const TYPE_SPACE_TECH = 2;

    static $TYPES = [
        self::TYPE_SPACE => '空间预约',
        self::TYPE_TECH => '教学预约',
        self::TYPE_SPACE_TECH => '空间/教学预约'
    ];


    protected $object_page = [
        'view'    => '!meeting/meeting/index.%id[.%arguments]',
        'meeting' => '!meeting/meeting/index.%id[.%arguments]',
        'edit'    => '!meeting/index/edit.%id[.%arguments]',
        'delete'  => '!meeting/index/delete.%id[.%arguments]',
        'reserv' => '!meeting/meeting/index.%id[.%arguments]'
    ];

    public function get_root()
    {
        $root = $this->tag_root;
        if (!$root->id) {
            $root->name     = (string) $this;
            $root->readonly = 1;
            $root->save();
            if ($root->id) {
                $this->tag_root = $root;
                $this->save();
            }
        }
        return $root;
    }

    public function &links($mode = 'edit')
    {
        $links = new ArrayIterator;
        switch ($mode) {
            case 'edit':
                if (L('ME')->is_allowed_to('修改', $this)) {
                    $links['edit'] = [
                        'url'   => $this->url(null, null, null, 'edit'),
                        'tip'   => I18N::T('meeting', '修改'),
                        'text'  => I18N::T('meeting', '修改'),
                        'extra' => 'class="blue"',
                    ];
                }
                break;
        }
        return (array) $links;
    }

}
