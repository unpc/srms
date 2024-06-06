<?php
class Extra_Model extends Presentable_Model {

    const TYPE_RADIO = 1;
    const TYPE_CHECKBOX = 2;
    const TYPE_TEXT = 3;
    const TYPE_NUMBER = 4;
    const TYPE_TEXTAREA = 5;
    const TYPE_SELECT = 6;
    const TYPE_RANGE = 7;
    const TYPE_STAR = 8; // 评星功能
    const TYPE_DATETIME = 15;

    static $types = [
        self::TYPE_RADIO => 'radio',
        self::TYPE_CHECKBOX => 'checkbox',
        self::TYPE_TEXT => 'text',
        self::TYPE_NUMBER => 'number',
        self::TYPE_TEXTAREA => 'textarea',
        self::TYPE_SELECT => 'select',
        self::TYPE_RANGE => 'range',
        self::TYPE_DATETIME => 'datetime',
    ];

    static $type_names = [
        self::TYPE_RADIO => '单选',
        self::TYPE_CHECKBOX => '多选',
        self::TYPE_TEXT => '单行文本',
        self::TYPE_NUMBER => '数值',
        self::TYPE_TEXTAREA => '多行文本',
        self::TYPE_SELECT => '下拉菜单',
        self::TYPE_RANGE => '数值范围',
        self::TYPE_DATETIME => '日期时间',
    ];

    //all fields
    private $_fields = [];

    //all categories
    private $_categories = [];

    //all categories and fields
    public $_params = [];

    protected $object_page = [
        'view'=> '!extra/extra/index.%id[.%arguments]'
    ];

    public function init() {

        if (!$this->id) return FALSE;

        $type = $this->type;
        $name = $this->object->id ? $this->object->name() : '';

        //$c 为config配置
        $c = Config::get('extra.'.$name.'.'.$type, []);


        //$p 为params配置
        $p = (array) $this->params + $c;
        $ck = array_keys($c);
        $pk = array_keys($p);
        $mergeK = array_unique(array_merge($ck, $pk));

        if (sha1(json_encode($p)) != sha1(json_encode($c))) {

            $diff = array_diff($mergeK, $pk);
            $same = array_intersect($mergeK, $pk);

            //针对diff的category的信息，需要增加到params中
            foreach($diff as $dk) {
                if (is_array($c[$dk])) {
                    unset($c[$dk]['#i18n_module']);
                    uasort($c[$dk], 'self::cmp');
                    $p[$dk] = $c[$dk];
                }
            }

            //针对same的category，merge c和p
            foreach($same as $sk) {
                if (is_array($c[$sk])) {
                    unset($c[$sk]['#i18n_module']);
                    uasort($c[$sk], 'self::cmp');
                    foreach ($c[$sk] as $key => $value) {
                        //当extra配置字段中有adopted_edit时，配置可被输入影响
                        if ($value['adopted_edit']) {
                            $p[$sk][$key] = (array) $c[$sk][$key] + (array) $p[$sk][$key];
                        }
                        else {
                            $p[$sk][$key] = (array) $c[$sk][$key];
                        }
                    }
                }
            }
        }

        foreach($p as $category => $fields) {
            foreach((array)$fields as $uniqid => $field) {
                //去除辅助功能的配置
                if(!is_array($field) || $uniqid[0] == '#') {
                    unset($p[$category][$uniqid]);
                    continue;
                }
                //写入该field属于哪个category
                $field['category'] = $category;
                $f[$uniqid] = $field;
            }
        }

        $this->_categories = array_keys($p);
        $this->_params = $p;
        //save params
        Event::trigger('extra.equipment.common_setting', $this);

        //$this->set('params', $p)->save();
        $this->set('params', $this->_params)->save();

        $this->_fields = $f;
    }

    public function get_categories() {
        return $this->_categories;
    }

    public function get_fields($category = NULL) {
        if ($category) {
            return $this->_params[$category];
        }
        else {
            return $this->_fields;
        }
    }

    // 根据　$field_original_title 判断是编辑还是新增
    public function get_field($category, $field_title, $field_original_title = '')
    {
        $field = [];
        $_uniqid = '';
        $fields = $this->get_fields($category);

        if ($field_original_title) {
            foreach($fields as $uniqid => $item) {
                if ($item['title'] == $field_original_title) {
                    $_uniqid = $uniqid;
                    break;
                }
            }
        }

        foreach($fields as $uniqid => $item) {
            if ($_uniqid && ($_uniqid == $uniqid)) {
                continue;
            }

            if ($item['title'] == $field_title) {
                $field = $item;
                break;
            }
        }

        return $field;
    }

    public function get_field_uniqid($category, $field_title)
    {
        $_uniqid = '';
        $fields = $this->get_fields($category);

        foreach($fields as $uniqid => $item) {
            if ($item['title'] == $field_title) {
                $_uniqid = $uniqid;
                break;
            }
        }

        return $_uniqid;
    }

    public function add_category($name) {

        if (in_array($name, $this->get_categories())) return FALSE; //已存在，添加失败

        $params = $this->params;
        $params[$name] = []; //add

        $this->params = $params;

        return $this->save();
    }

    public function delete_category($name) {

        $config_categories = array_keys(Config::get('extra.'.$this->object->name().'.'.$this->type, []));
        if (in_array($name, $config_categories)) return FALSE; //系统配置无法删除

        $params = (array) $this->params;

        unset($params[$name]);

        Event::trigger('extra.category_delete', $this, $name);

        $this->params = $params;

        return $this->save();
    }

    public function rename_category($old, $new) {
        if (!$new) return FALSE;

        if ($old == $new) return TRUE;

        $config_categories = array_keys(Config::get('extra.'.$this->object->name().'.'.$this->type, []));

        if (in_array($new, $config_categories) || in_array($old, $config_categories)) return FALSE; //系统配置无法rename

        $params = (array) $this->params;

        if (!array_key_exists($old, $params)) return FALSE; //不存在，无法rename

        $categories = $this->get_categories();

        foreach($categories as $c) {
            if ($c == $new) {
                return FALSE;
            }
        }

        $p = [];
        foreach($categories as $c) {
            if ($c == $old) {
                $p[$new] = $params[$old];
            }
            else {
                $p[$c] = $params[$c];
            }
        }

        $this->params = $p;
        Event::trigger('extra.category_rename', $this, $old, $new);

        return $this->save();
    }

    //设定category的fields
    public function set_category_fields($category, $fields) {

        $params = (array) $this->params;
        $params[$category] = $fields;

        $this->params = $params;

        $ret = $this->save();
        $this->init();

        return $ret;
    }

    //获得一个uniqid
    public function get_uniqid(){
        $max_uniqid = $this->autoinc ?: 0;
        $max_uniqid ++;
        $this->autoinc = $max_uniqid;

        if($this->save()) return $this->autoinc;
    }

    static function cmp($a, $b) {
        $a = (array)$a;
        $b = (array)$b;
        $a = $a['weight'];
        $b = $b['weight'];
        if ($a == $b) return 0;
        return $a < $b ? -1 : 1;
    }

    static function field_change_types($type){
        if(!$type) return;
        $charge_types = [];
        switch ($type) {
            case self::TYPE_TEXT:
                $charge_types = [
                    self::TYPE_TEXT => '单行文本',
                    self::TYPE_RADIO => '单选',
                    self::TYPE_CHECKBOX => '多选',
                    self::TYPE_TEXTAREA => '多行文本',
                    self::TYPE_SELECT => '下拉菜单'
                ];
                break;
            case self::TYPE_RADIO:
                $charge_types = [
                    self::TYPE_RADIO => '单选',
                    self::TYPE_CHECKBOX => '多选',
                    self::TYPE_SELECT => '下拉菜单'
                ];
                break;
            case self::TYPE_SELECT:
                $charge_types = [
                    self::TYPE_SELECT => '下拉菜单',
                    self::TYPE_RADIO => '单选',
                    self::TYPE_CHECKBOX => '多选',
                ];
                break;
            default:
                $charge_types = [
                    $type => I18N::T('extra', self::$type_names[$type]),
                ];
                break;
        }
        return $charge_types;
    }

    static function fetch($object, $type) {

        $extra = O('extra', ['object'=> $object, 'type'=> $type]);
        if (!$extra->id) {
            $extra = O('extra');
            $extra->object = $object;
            $extra->type = $type;
            $extra->save();
            Event::trigger('extra.equipment.common_setting', $extra);
            $extra = ORM_Model::refetch($extra);
        }

        return $extra;
    }

}
