<?php
class Sphinx_Search
{
    public static function orm_model_saved($e, $object, $old_data, $new_data)
    {
        $sphinx_confs = Config::get('sphinx', []);
        if (!array_key_exists($object->name(), $sphinx_confs)) {
            return;
        }

        Sphinx_Search::update_object_indexes($object);
    }

    public static function search($opt)
    {
        $sphinx = Database::factory('@sphinx');
        $SQL = 'SELECT * FROM '. '`' . self::get_index_name($opt['module_name']) . '`';

        $where = [];

        if ($opt['filter']) {
            foreach ($opt['filter'] as $k => $v) {
                $v = $sphinx->escape($v);
            }
            $arr1[] = "@({$k}) \"*{$v}*\"";
        }
        if (count($arr1)) {
            $pre_where = "(".implode('&', $arr1).")";
        }

        if ($pre_where) {
            $where[] = "MATCH('".$pre_where."')";
        }
        if (count($where)) {
            $SQL .= ' WHERE '. implode(' AND ', $where);
        }
        return $sphinx->query($SQL)->rows();
    }

    public static function update_object_indexes($object)
    {
        $config = Config::get('sphinx');
        if (!is_object($object)
            || !array_key_exists($object->name(), $config)
            || !isset($config[$object->name()]['source'])
        ) {
            return false;
        }

        $data = [];
        foreach ($config[$object->name()]['source'] as $k => $v) {
            switch ($v['type']) {
                case "self":
                    switch ($v['subtype']) {
                        case "extra_value":
                            $data[$k] = join(' ', self::_parse_extra_value($object->$k, $object));
                            break;
                        case "timestamp":
                            $data[$k] = Date::format($object->$k, "Y-m-d H:i:s");
                        break;
                            default:
                            $data[$k] = $object->$k;
                            break;
                    }
                    break;
                case "object":
                    $sub_obj_name = $v['object'];
                    $sub_obj_column = $v['subname'];
                    $data[$k] = $object->$sub_obj_name->$sub_obj_column;
                    break;
            }
        }
        return self::_update_index([
            'id' => $object->id,
            'search_text' => join(' ', $data),
        ], $object->name());
    }

    private static function _update_index($v, $module_name)
    {
        $k = [];
        $sphinx = Database::factory('@sphinx');
        foreach ($v as $kk => &$vv) {
            $k[$kk] = $kk;
            if (is_array($vv)) {
                $vv = $sphinx->quote($vv);
            } else {
                $vv = $sphinx->quote($vv);
            }
        }

        $SQL = 'REPLACE INTO ' . '`' . self::get_index_name($module_name) .
            '` ('.implode(', ', $k).') VALUES ('.implode(', ', $v).')';
        return $sphinx->query($SQL);
    }

    // TODO: friso分词
    // private static function _split_word($w) {
    // 	$splitWords = friso_split($w, array('mode' => FRISO_COMPLEX));
    // 	$participle = array_map(function($v){return $v['word'];}, $splitWords);
    // 	$result = implode(' ', $participle);
    // 	return $result;
    // }

    // private static function _split_application($w) {
    //     $splitWords = friso_split($w, array('mode' => FRISO_COMPLEX));
    //     $participle = array_map(function($v){return $v['word'];}, $splitWords);
    //     $result = implode(' ', $participle);
    //     $words = explode(',', $w);
    //     $rawWords = implode(' ', $words);
    //     $rawWords = $result ? ' ' . $rawWords : $rawWords;
    //     return $result . ' ' . $rawWords;
    // }

    public static function truncate_indexes($module_name)
    {
        $sphinx = Database::factory('@sphinx');
        $SQL = 'select * from `' . self::get_index_name($module_name) . '` limit 1000';
        do {
            $results = $sphinx->query($SQL);
            $ids = [];
            if ($results) {
                while ($row = $results->row()) {
                    $ids[] = $row->id;
                }
            }
            if (!count($ids)) {
                break;
            }
            $DEL_SQL = 'DELETE FROM ' . '`' . self::get_index_name($module_name) . '` WHERE id IN (' . join(',', $ids) . ')';
            $sphinx->query($DEL_SQL);
        } while (is_object($results) && $results->count());
    }

    public static function get_index_name($module_name)
    {
        $index_name = SITE_ID . '_' . LAB_ID . '_' . $module_name;
        $index_name = strtr($index_name, '-', '_');
        return $index_name;
    }

    private static function _parse_extra_value($v, $object)
    {
        $ret = [];
        $extra = Extra_Model::fetch($object->equipment, $object->name());

        foreach ($extra->get_categories() as $category) {
            $c_fields = $extra->get_fields($category);
            if (count($c_fields) == 0) {
                continue;
            }
            foreach ($c_fields as $uniqid=>$f) {
                $fields[$uniqid] = [
                    'title' => $f['title'],
                    'type' => $f['type']
                ];
            }
        }

        foreach($v as $key => $value) {
            switch($fields[$key]['type']) {
                case Extra_Model::TYPE_CHECKBOX: // 多选
                    $d = [];
                    foreach($value as $k => $v) {
                        if ($v == "null") continue;
                        $d[] = $k;
                    }
                    $ret[$fields[$key]['title']] = join(' ', $d);
                    break;
                case Extra_Model::TYPE_RANGE: // 数值范围
                    $ret[$fields[$key]['title']] = join(' ', array_values($value));
                    break;
                case Extra_Model::TYPE_DATETIME: // 日期时间
                    $ret[$fields[$key]['title']] = Date::format($value, 'Y-m-d H:i:s');
                    break;
                case Extra_Model::TYPE_RADIO: // 单选
                case Extra_Model::TYPE_TEXT: // 单行文本
                case Extra_Model::TYPE_TEXTAREA: // 多行文本
                case Extra_Model::TYPE_NUMBER: // 数值
                case Extra_Model::TYPE_SELECT: // 下拉菜单
                default:
                    $ret[$fields[$key]['title']] = $value;
                    break;
            }
        }
        return $ret;
    }
}
